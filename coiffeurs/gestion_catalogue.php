<?php
// 1. TOUT EN HAUT : Sécurité et Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Seul un coiffeur connecté peut accéder à cette page
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    echo "<script>window.location.href='connexion.php';</script>";
    exit();
}

require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

$coiffeur_id = $_SESSION['id_user']; 
$message = "";

// --- FONCTION POUR SÉPARER DESCRIPTION ET OPTIONS ---
function formaterDescriptionWithOptions($texte_desc, $option_noms, $option_prix) {
    $options_array = [];
    if (!empty($option_noms) && is_array($option_noms)) {
        foreach ($option_noms as $index => $nom_opt) {
            $nom_opt = trim(htmlspecialchars($nom_opt));
            $prix_opt = floatval($option_prix[$index] ?? 0);
            if (!empty($nom_opt)) {
                $options_array[] = ['nom' => $nom_opt, 'prix' => $prix_opt];
            }
        }
    }
    $options_json = json_encode($options_array, JSON_UNESCAPED_UNICODE);
    return htmlspecialchars($texte_desc) . "|||" . $options_json;
}

// ==========================================
// A. ACTION : AJOUT D'UNE PRESTATION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajouter_prestation'])) {
    $nom_style = htmlspecialchars($_POST['nom_style']);
    $prix = floatval($_POST['prix']);
    $duree = intval($_POST['duree']);
    $raw_description = $_POST['description'];

    $description_complete = formaterDescriptionWithOptions($raw_description, $_POST['option_nom'] ?? [], $_POST['option_prix'] ?? []);
    $photo_style_path = NULL;
    $upload_ok = true;

    if (isset($_FILES['photo_style']) && $_FILES['photo_style']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $fileName = $_FILES['photo_style']['name'];
        $fileSize = $_FILES['photo_style']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowed)) {
            $message = "<div class='alert alert-danger text-center'>❌ Format non supporté. (Uniquement JPG, JPEG, PNG, WEBP)</div>";
            $upload_ok = false;
        } elseif ($fileSize > (5 * 1024 * 1024)) { // 5 Mo
            $message = "<div class='alert alert-danger text-center'>❌ La photo est trop lourde. Maximum 5 Mo autorisés.</div>";
            $upload_ok = false;
        } else {
            $photo_style_path = 'uploads/prestations/' . uniqid('', true) . '.' . $fileExt;
            $target_dir = __DIR__ . '/../uploads/prestations';
            
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            move_uploaded_file($_FILES['photo_style']['tmp_name'], __DIR__ . '/../' . $photo_style_path);
        }
    }

    if ($upload_ok) {
        $stmt = $pdo->prepare("INSERT INTO prestations (id_coiffeur, nom_style, prix, photo_style, description, duree) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$coiffeur_id, $nom_style, $prix, $photo_style_path, $description_complete, $duree])) {
            $message = "<div class='alert alert-success text-center fw-bold'>✨ Prestation ajoutée avec succès !</div>";
        }
    }
}

// ==========================================
// B. ACTION : MODIFICATION D'UNE PRESTATION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier_prestation'])) {
    $id_prestation = intval($_POST['id_prestation']);
    $nom_style = htmlspecialchars($_POST['nom_style']);
    $prix = floatval($_POST['prix']);
    $duree = intval($_POST['duree']);
    $raw_description = $_POST['description'];
    $ancienne_photo = $_POST['ancienne_photo'];

    // Vérification de sécurité propriétaire
    $check = $pdo->prepare("SELECT id_prestation FROM prestations WHERE id_prestation = ? AND id_coiffeur = ?");
    $check->execute([$id_prestation, $coiffeur_id]);

    if ($check->rowCount() > 0) {
        $description_complete = formaterDescriptionWithOptions($raw_description, $_POST['edit_option_nom'] ?? [], $_POST['edit_option_prix'] ?? []);
        $photo_style_path = $ancienne_photo;
        $upload_ok = true;

        if (isset($_FILES['photo_style']) && $_FILES['photo_style']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExt = strtolower(pathinfo($_FILES['photo_style']['name'], PATHINFO_EXTENSION));
            $fileSize = $_FILES['photo_style']['size'];

            if (!in_array($fileExt, $allowed)) {
                $message = "<div class='alert alert-danger text-center'>❌ Format d'image non supporté.</div>";
                $upload_ok = false;
            } elseif ($fileSize > (5 * 1024 * 1024)) {
                $message = "<div class='alert alert-danger text-center'>❌ La nouvelle photo dépasse 5 Mo.</div>";
                $upload_ok = false;
            } else {
                if (!empty($ancienne_photo) && file_exists(__DIR__ . '/../' . $ancienne_photo)) {
                    @unlink(__DIR__ . '/../' . $ancienne_photo);
                }
                
                $photo_style_path = 'uploads/prestations/' . uniqid('', true) . '.' . $fileExt;
                $target_dir = __DIR__ . '/../uploads/prestations';
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                move_uploaded_file($_FILES['photo_style']['tmp_name'], __DIR__ . '/../' . $photo_style_path);
            }
        }

        if ($upload_ok) {
            $upd = $pdo->prepare("UPDATE prestations SET nom_style = ?, prix = ?, photo_style = ?, description = ?, duree = ? WHERE id_prestation = ?");
            if ($upd->execute([$nom_style, $prix, $photo_style_path, $description_complete, $duree, $id_prestation])) {
                $message = "<div class='alert alert-success text-center fw-bold'>🔄 Prestation mise à jour avec succès !</div>";
            }
        }
    }
}

// ==========================================
// C. ACTION : SUPPRESSION D'UNE PRESTATION
// ==========================================
if (isset($_GET['supprimer'])) {
    $id_prestation = intval($_GET['supprimer']);
    
    $check = $pdo->prepare("SELECT photo_style FROM prestations WHERE id_prestation = ? AND id_coiffeur = ?");
    $check->execute([$id_prestation, $coiffeur_id]);
    $prest = $check->fetch();

    if ($prest) {
        if (!empty($prest['photo_style']) && file_exists(__DIR__ . '/../' . $prest['photo_style'])) {
            @unlink(__DIR__ . '/../' . $prest['photo_style']);
        }
        
        $del = $pdo->prepare("DELETE FROM prestations WHERE id_prestation = ?");
        $del->execute([$id_prestation]);
        $message = "<div class='alert alert-warning text-center fw-bold'>🗑️ Prestation supprimée du catalogue.</div>";
    }
}

// Récupération des prestations à jour
$stmt = $pdo->prepare("SELECT * FROM prestations WHERE id_coiffeur = ? ORDER BY id_prestation DESC");
$stmt->execute([$coiffeur_id]);
$prestations = $stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">GÉRER MON CATALOGUE</h2>

        <?php echo $message; ?>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 shadow-lg sticky-top" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px; top: 20px; z-index: 1;">
                    <h4 class="text-warning mb-3">Ajouter un style</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="text-white small">Nom de la coiffure</label>
                            <input type="text" name="nom_style" class="form-control" placeholder="Ex: Nattes Collées Homme" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Prix de base (FCFA)</label>
                            <input type="number" name="prix" class="form-control" placeholder="Ex: 5000" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Durée estimée</label>
                            <select name="duree" class="form-select" required>
                                <option value="30">30 minutes</option>
                                <option value="60" selected>1 heure</option>
                                <option value="90">1h 30min</option>
                                <option value="120">2 heures</option>
                                <option value="180">3 heures</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Image de la coiffure (Max 5 Mo)</label>
                            <input type="file" name="photo_style" class="form-control" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Description de base</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Ex: Lavage inclus..."></textarea>
                        </div>

                        <div class="p-2 mb-3 rounded" style="background-color: #1a1a1a; border: 1px dashed #444;">
                            <label class="text-warning small d-block mb-2 fw-bold">Variations & Options Tarifs (Facultatif)</label>
                            <div id="wrapper-options-ajout"></div>
                            <button type="button" class="btn btn-outline-warning btn-sm w-100 mt-1" onclick="ajouterOptionAjout()">
                                ➕ Ajouter une option (Teinte, Grosseur...)
                            </button>
                        </div>

                        <button type="submit" name="ajouter_prestation" class="btn btn-gold w-100 py-2 fw-bold mt-2">AJOUTER AU CATALOGUE</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <h4 class="text-warning mb-3">Mes prestations actuelles</h4>
                <div class="row g-3">
                    <?php if (empty($prestations)): ?>
                        <div class="col-12 text-center text-secondary py-5">Aucune prestation enregistrée pour le moment.</div>
                    <?php else: ?>
                        <?php foreach ($prestations as $p): 
                            $explode = explode("|||", $p['description']);
                            $un_description = $explode[0] ?? '';
                            $json_options = $explode[1] ?? '[]';
                            $options_array = json_decode($json_options, true) ?: [];
                        ?>
                            <div class="col-md-6">
                                <div class="card h-100 shadow-sm" style="background-color: #111; border: 1px solid #333; border-radius: 15px; overflow: hidden;">
                                    
                                    <?php if (!empty($p['photo_style']) && file_exists(__DIR__ . '/../' . $p['photo_style'])): ?>
                                        <img src="/coiffons/<?php echo $p['photo_style']; ?>" class="card-img-top" style="height: 180px; object-fit: cover;" alt="Style">
                                    <?php else: ?>
                                        <div class="bg-secondary text-center py-5"><i class="bi bi-image" style="font-size: 2rem;"></i></div>
                                    <?php endif; ?>

                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <div>
                                            <h5 class="text-warning mb-1"><?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-success fw-bold"><?php echo number_format($p['prix'], 0, ',', ' '); ?> FCFA</span>
                                                <span class="badge bg-dark border border-warning text-warning">⏱️ <?php echo $p['duree']; ?> min</span>
                                            </div>
                                            <p class="text-light small mb-2"><?php echo htmlspecialchars($un_description); ?></p>
                                            
                                            <?php if(!empty($options_array)): ?>
                                                <div class="pt-1 border-top border-secondary">
                                                    <span class="text-secondary" style="font-size:0.75rem;">Options :</span><br>
                                                    <?php foreach($options_array as $o): ?>
                                                        <span class="badge me-1 text-black" style="font-size: 0.7rem; background-color:#e0a96d !important;">
                                                            <?php echo htmlspecialchars($o['nom']); ?> (+<?php echo $o['prix']; ?>F)
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-2 mt-3">
                                            <button type="button" class="btn btn-outline-warning btn-sm flex-grow-1" data-bs-toggle="modal" data-bs-target="#modalModifier-<?php echo $p['id_prestation']; ?>">
                                                ⚙️ Modifier
                                            </button>
                                            <a href="?supprimer=<?php echo $p['id_prestation']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Supprimer définitivement ce style ?');">
                                                ✕ Supprimer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal fade" id="modalModifier-<?php echo $p['id_prestation']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content text-white" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                                        <div class="modal-header border-secondary">
                                            <h5 class="modal-title text-warning">Modifier : <?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" enctype="multipart/form-data">
                                            <div class="modal-body">
                                                <input type="hidden" name="id_prestation" value="<?php echo $p['id_prestation']; ?>">
                                                <input type="hidden" name="ancienne_photo" value="<?php echo $p['photo_style']; ?>">

                                                <div class="mb-2">
                                                    <label class="small text-secondary">Nom de la coiffure</label>
                                                    <input type="text" name="nom_style" class="form-control" value="<?php echo htmlspecialchars($p['nom_style']); ?>" required>
                                                </div>
                                                <div class="row g-2 mb-2">
                                                    <div class="col-6">
                                                        <label class="small text-secondary">Prix de base (FCFA)</label>
                                                        <input type="number" name="prix" class="form-control" value="<?php echo $p['prix']; ?>" required>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="small text-secondary">Durée</label>
                                                        <select name="duree" class="form-select">
                                                            <option value="30" <?php echo $p['duree'] == 30 ? 'selected' : ''; ?>>30 min</option>
                                                            <option value="60" <?php echo $p['duree'] == 60 ? 'selected' : ''; ?>>1 heure</option>
                                                            <option value="90" <?php echo $p['duree'] == 90 ? 'selected' : ''; ?>>1h 30</option>
                                                            <option value="120" <?php echo $p['duree'] == 120 ? 'selected' : ''; ?>>2 heures</option>
                                                            <option value="180" <?php echo $p['duree'] == 180 ? 'selected' : ''; ?>>3 heures</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="small text-secondary">Changer la photo (Optionnel - Max 5 Mo)</label>
                                                    <input type="file" name="photo_style" class="form-control" accept="image/*">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="small text-secondary">Description</label>
                                                    <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($un_description); ?></textarea>
                                                </div>

                                                <div class="p-2 rounded" style="background-color: #1a1a1a; border: 1px dashed #444;">
                                                    <label class="text-warning small d-block mb-2 fw-bold">Modifier les Options / Tarifs</label>
                                                    <div id="wrapper-options-edit-<?php echo $p['id_prestation']; ?>">
                                                        <?php foreach ($options_array as $index => $o): ?>
                                                            <div class="row g-1 mb-2 align-items-center option-item">
                                                                <div class="col-7">
                                                                    <input type="text" name="edit_option_nom[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($o['nom']); ?>">
                                                                </div>
                                                                <div class="col-4">
                                                                    <input type="number" name="edit_option_prix[]" class="form-control form-control-sm" value="<?php echo $o['prix']; ?>">
                                                                </div>
                                                                <div class="col-1 text-end">
                                                                    <button type="button" class="btn btn-danger btn-sm p-1 py-0" onclick="this.closest('.option-item').remove()">✕</button>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-warning btn-sm w-100 mt-2" onclick="ajouterOptionEdit(<?php echo $p['id_prestation']; ?>)">
                                                        ➕ Rajouter une variation
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-secondary">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal">Annuler</button>
                                                <button type="submit" name="modifier_prestation" class="btn btn-gold btn-sm fw-bold">SAUVEGARDER LES MODIFICATIONS</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function ajouterOptionAjout() {
    var wrapper = document.getElementById('wrapper-options-ajout');
    var div = document.createElement('div');
    div.className = "row g-1 mb-2 align-items-center option-item";
    div.innerHTML = `
        <div class="col-7">
            <input type="text" name="option_nom[]" class="form-control form-control-sm" placeholder="Ex: Teinte Blanche" required>
        </div>
        <div class="col-4">
            <input type="number" name="option_prix[]" class="form-control form-control-sm" placeholder="Prix" required>
        </div>
        <div class="col-1 text-end">
            <button type="button" class="btn btn-danger btn-sm p-1 py-0" onclick="this.closest('.option-item').remove()">✕</button>
        </div>
    `;
    wrapper.appendChild(div);
}

function ajouterOptionEdit(idPrestation) {
    var wrapper = document.getElementById('wrapper-options-edit-' + idPrestation);
    var div = document.createElement('div');
    div.className = "row g-1 mb-2 align-items-center option-item";
    div.innerHTML = `
        <div class="col-7">
            <input type="text" name="edit_option_nom[]" class="form-control form-control-sm" placeholder="Ex: Mèches Longues" required>
        </div>
        <div class="col-4">
            <input type="number" name="edit_option_prix[]" class="form-control form-control-sm" placeholder="Prix" required>
        </div>
        <div class="col-1 text-end">
            <button type="button" class="btn btn-danger btn-sm p-1 py-0" onclick="this.closest('.option-item').remove()">✕</button>
        </div>
    `;
    wrapper.appendChild(div);
}
</script>

<style>
    .form-control, .form-select { background-color: #fff !important; color: #000 !important; border-radius: 8px; }
    .btn-gold { background-color: var(--gold); color: black; border: none; border-radius: 8px; }
    .btn-gold:hover { background-color: #c99b2c; color: black; }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>