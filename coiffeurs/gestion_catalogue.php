<?php
/**
 * coiffeurs/gestion_catalogue.php — Gestion CRUD du catalogue de prestations
 * Migration Design System v2.0 — Parcours Coiffeur — Page 1
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, upload, sécurité : inchangés.
 */

// 1. Sécurité et Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prestataire') {
    echo "<script>window.location.href='/coiffons/access/connexion.php';</script>";
    exit();
}

require_once __DIR__ . '/../security/config.php';

$prestataire_id = $_SESSION['user_id'];
$message_type = '';
$message_text = '';

// --- FONCTION POUR SÉPARER DESCRIPTION ET OPTIONS --- (inchangée)
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
    $nom_service = htmlspecialchars($_POST['nom_service']);
    $prix = floatval($_POST['prix']);
    $duree = intval($_POST['duree']);
    $raw_description = $_POST['description'];

    $description_complete = formaterDescriptionWithOptions($raw_description, $_POST['option_nom'] ?? [], $_POST['option_prix'] ?? []);
    $photo_path = NULL;
    $upload_ok = true;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowed)) {
            $message_type = 'danger';
            $message_text = 'Format non supporté. (Uniquement JPG, JPEG, PNG, WEBP)';
            $upload_ok = false;
        } elseif ($fileSize > (5 * 1024 * 1024)) {
            $message_type = 'danger';
            $message_text = 'La photo est trop lourde. Maximum 5 Mo autorisés.';
            $upload_ok = false;
        } else {
            $photo_path = 'uploads/prestations/' . uniqid('', true) . '.' . $fileExt;
            $target_dir = __DIR__ . '/../uploads/prestations';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../' . $photo_path);
        }
    }

    if ($upload_ok) {
        // Obtenir le premier id_metier disponible (ou créer un par défaut si absent)
        $metier_check = $pdo->query("SELECT id FROM metiers LIMIT 1");
        $metier = $metier_check->fetch();
        $id_metier = $metier['id'] ?? 1;
        
        $stmt = $pdo->prepare("INSERT INTO services (id_prestataire, id_metier, nom_service, prix, photo, description, duree) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$prestataire_id, $id_metier, $nom_service, $prix, $photo_path, $description_complete, $duree])) {
            $message_type = 'success';
            $message_text = 'Prestation ajoutée avec succès !';
        }
    }
}

// ==========================================
// B. ACTION : MODIFICATION D'UNE PRESTATION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modifier_prestation'])) {
    $id_service = intval($_POST['id_service']);
    $nom_service = htmlspecialchars($_POST['nom_service']);
    $prix = floatval($_POST['prix']);
    $duree = intval($_POST['duree']);
    $raw_description = $_POST['description'];
    $ancienne_photo = $_POST['ancienne_photo'];

    $check = $pdo->prepare("SELECT id_service FROM services WHERE id_service = ? AND id_prestataire = ?");
    $check->execute([$id_service, $prestataire_id]);

    if ($check->rowCount() > 0) {
        $description_complete = formaterDescriptionWithOptions($raw_description, $_POST['edit_option_nom'] ?? [], $_POST['edit_option_prix'] ?? []);
        $photo_path = $ancienne_photo;
        $upload_ok = true;

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $fileSize = $_FILES['photo']['size'];

            if (!in_array($fileExt, $allowed)) {
                $message_type = 'danger';
                $message_text = 'Format d\'image non supporté.';
                $upload_ok = false;
            } elseif ($fileSize > (5 * 1024 * 1024)) {
                $message_type = 'danger';
                $message_text = 'La nouvelle photo dépasse 5 Mo.';
                $upload_ok = false;
            } else {
                if (!empty($ancienne_photo) && file_exists(__DIR__ . '/../' . $ancienne_photo)) {
                    @unlink(__DIR__ . '/../' . $ancienne_photo);
                }
                $photo_path = 'uploads/prestations/' . uniqid('', true) . '.' . $fileExt;
                $target_dir = __DIR__ . '/../uploads/prestations';
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../' . $photo_path);
            }
        }

        if ($upload_ok) {
            $upd = $pdo->prepare("UPDATE services SET nom_service = ?, prix = ?, photo = ?, description = ?, duree = ? WHERE id_service = ?");
            if ($upd->execute([$nom_service, $prix, $photo_path, $description_complete, $duree, $id_service])) {
                $message_type = 'success';
                $message_text = 'Prestation mise à jour avec succès !';
            }
        }
    }
}

// ==========================================
// C. ACTION : SUPPRESSION D'UNE PRESTATION
// ==========================================
if (isset($_GET['supprimer'])) {
    $id_service = intval($_GET['supprimer']);

    $check = $pdo->prepare("SELECT photo FROM services WHERE id_service = ? AND id_prestataire = ?");
    $check->execute([$id_service, $prestataire_id]);
    $prest = $check->fetch();

    if ($prest) {
        if (!empty($prest['photo']) && file_exists(__DIR__ . '/../' . $prest['photo'])) {
            @unlink(__DIR__ . '/../' . $prest['photo']);
        }
        $del = $pdo->prepare("DELETE FROM services WHERE id_service = ?");
        $del->execute([$id_service]);
        $redirect = $_SERVER['HTTP_REFERER'] ?? 'gestion_catalogue.php';
        echo "<script>alert('Prestation supprimée du catalogue.'); window.location.href='" . htmlspecialchars($redirect, ENT_QUOTES) . "';</script>";
        exit();
    }
}

// Récupération des prestations à jour
$stmt = $pdo->prepare("SELECT * FROM services WHERE id_prestataire = ? ORDER BY id_service DESC");
$stmt->execute([$prestataire_id]);
$prestations = $stmt->fetchAll();

// Header coiffeur (ouverture HTML + topbar)
include __DIR__ . '/../layout/header_coiffeur.php';
?>


<!-- ActionBar -->
<?php
$ab = [
    'icon'     => 'bi-scissors',
    'title'    => 'Mon Catalogue',
    'subtitle' => 'Gérez vos prestations et tarifs',
];
include __DIR__ . '/../views/components/action_bar.php';
?>

<!-- Toast message -->
<?php if (!empty($message_text)):
    $toast_type    = $message_type;
    $toast_message = $message_text;
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<div class="row g-4">

    <!-- ── Colonne gauche : Formulaire ajout ── -->
    <div class="col-lg-4">
        <div class="glass-cct p-4" style="position:sticky;top:calc(var(--navbar-height-coiffeur) + 1rem);">
            <h4 style="font-family:var(--font-display);font-size:1rem;color:var(--gold);margin-bottom:1.25rem;">
                <i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Ajouter un style
            </h4>
            <form method="POST" enctype="multipart/form-data" novalidate>

                <?php
                $ff = ['type'=>'text','name'=>'nom_service','label'=>'Nom de la coiffure','placeholder'=>'Ex: Nattes Collées Homme','required'=>true];
                include __DIR__ . '/../views/components/form_field.php';

                $ff = ['type'=>'number','name'=>'prix','label'=>'Prix de base (FCFA)','placeholder'=>'Ex: 5000','required'=>true,'attrs'=>'min="0"'];
                include __DIR__ . '/../views/components/form_field.php';

                $ff = [
                    'type'    => 'select',
                    'name'    => 'duree',
                    'label'   => 'Durée estimée',
                    'required'=> true,
                    'options' => [
                        ['value'=>'30', 'label'=>'30 minutes'],
                        ['value'=>'60', 'label'=>'1 heure', 'selected'=>true],
                        ['value'=>'90', 'label'=>'1h 30min'],
                        ['value'=>'120','label'=>'2 heures'],
                        ['value'=>'180','label'=>'3 heures'],
                    ],
                ];
                include __DIR__ . '/../views/components/form_field.php';

                $ff = ['type'=>'file','name'=>'photo','label'=>'Image (Max 5 Mo)','required'=>true,'attrs'=>'accept="image/*"'];
                include __DIR__ . '/../views/components/form_field.php';

                $ff = ['type'=>'textarea','name'=>'description','label'=>'Description de base','placeholder'=>'Ex: Lavage inclus...','rows'=>2];
                include __DIR__ . '/../views/components/form_field.php';
                ?>

                <!-- Options tarifs -->
                <div class="glass-cct p-3 mb-3" style="border:1px dashed var(--glass-border-md);">
                    <label class="form-label-cct" style="color:var(--gold);">
                        <i class="bi bi-tags me-1" aria-hidden="true"></i>
                        Variations &amp; Options Tarifs
                        <span style="color:var(--text-muted);font-weight:400;"> (Facultatif)</span>
                    </label>
                    <div id="wrapper-options-ajout"></div>
                    <button type="button"
                            class="btn-outline-gold btn-sm w-100 mt-2"
                            onclick="ajouterOptionAjout()">
                        <i class="bi bi-plus" aria-hidden="true"></i> Ajouter une option
                    </button>
                </div>

                <button type="submit"
                        name="ajouter_prestation"
                        class="btn-gold w-100"
                        style="padding:12px;">
                    <i class="bi bi-plus-circle me-2" aria-hidden="true"></i>
                    AJOUTER AU CATALOGUE
                </button>
            </form>
        </div>
    </div>

    <!-- ── Colonne droite : Grille prestations ── -->
    <div class="col-lg-8">
        <h4 style="font-family:var(--font-display);font-size:1rem;color:var(--text-secondary);margin-bottom:1.25rem;">
            Mes prestations actuelles
            <span style="color:var(--text-muted);font-weight:400;font-size:0.82rem;margin-left:8px;">
                (<?= count($prestations) ?> style<?= count($prestations) > 1 ? 's' : '' ?>)
            </span>
        </h4>

        <?php if (empty($prestations)): ?>
            <?php
            $es = [
                'icon'      => 'bi-scissors',
                'message'   => 'Aucune prestation enregistrée. Ajoutez votre premier style !',
                'cta_label' => '',
                'cta_href'  => '',
            ];
            include __DIR__ . '/../views/components/empty_state.php';
            ?>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($prestations as $p):
                    $explode     = explode("|||", $p['description']);
                    $un_description = $explode[0] ?? '';
                    $json_options   = $explode[1] ?? '[]';
                    $options_array  = json_decode($json_options, true) ?: [];

                    // URL photo
                    $photo_url_p = null;
                    if (!empty($p['photo']) && file_exists(__DIR__ . '/../' . $p['photo'])) {
                        $photo_url_p = '/coiffons/' . htmlspecialchars($p['photo']);
                    }

                    $modal_id_p = 'modal-modifier-' . $p['id_service'];
                ?>
                    <div class="col-sm-6">
                        <!-- GalleryCard étendue avec options + actions modal -->
                        <div class="gallery-card h-100">
                            <!-- Image -->
                            <div class="gallery-card-img">
                                <?php if ($photo_url_p): ?>
                                    <img src="<?= $photo_url_p ?>"
                                         alt="<?= htmlspecialchars($p['nom_service']) ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="gallery-card-img-placeholder">
                                        <i class="bi bi-scissors" style="font-size:2rem;color:var(--gold);opacity:0.5;" aria-hidden="true"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- Body -->
                            <div class="p-4 d-flex flex-column" style="flex:1;">
                                <h5 style="font-weight:700;color:#fff;margin-bottom:6px;">
                                    <?= htmlspecialchars($p['nom_service']) ?>
                                </h5>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span style="color:var(--gold);font-weight:700;font-size:1rem;">
                                        <?= number_format($p['prix'], 0, ',', ' ') ?>
                                        <span style="font-size:0.72rem;">FCFA</span>
                                    </span>
                                    <span class="badge-muted">
                                        <i class="bi bi-clock me-1" aria-hidden="true"></i>
                                        <?= $p['duree'] ?> min
                                    </span>
                                </div>
                                <?php if (!empty($un_description)): ?>
                                    <p style="color:var(--text-secondary);font-size:0.8rem;margin-bottom:10px;">
                                        <?= htmlspecialchars($un_description) ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($options_array)): ?>
                                    <div style="margin-bottom:12px;">
                                        <span style="font-size:0.72rem;color:var(--text-muted);display:block;margin-bottom:4px;">Options :</span>
                                        <?php foreach ($options_array as $o): ?>
                                            <span class="badge-gold" style="font-size:0.68rem;margin-right:4px;margin-bottom:4px;display:inline-block;">
                                                <?= htmlspecialchars($o['nom']) ?> (+<?= number_format($o['prix'], 0, ',', ' ') ?> FCFA)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Actions -->
                                <div class="d-flex gap-2 mt-auto">
                                    <button type="button"
                                            class="btn-outline-gold btn-sm flex-grow-1"
                                            onclick="openModal('<?= $modal_id_p ?>')"
                                            aria-label="Modifier <?= htmlspecialchars($p['nom_service']) ?>">
                                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> Modifier
                                    </button>
                                    <a href="?supprimer=<?= $p['id_service'] ?>"
                                       class="btn-danger-cct btn-sm"
                                       onclick="return confirm('Supprimer définitivement ce style ?')"
                                       aria-label="Supprimer <?= htmlspecialchars($p['nom_service']) ?>">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Modification -->
                    <?php
                    // Construction du contenu modal
                    ob_start();
                    ?>
                    <form method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="id_service" value="<?= $p['id_service'] ?>">
                        <input type="hidden" name="ancienne_photo" value="<?= htmlspecialchars($p['photo']) ?>">

                        <?php
                        $ff = ['type'=>'text','name'=>'nom_service','label'=>'Nom de la coiffure','value'=>$p['nom_service'],'required'=>true];
                        include __DIR__ . '/../views/components/form_field.php';
                        ?>

                        <div class="row g-2">
                            <div class="col-6">
                                <?php
                                $ff = ['type'=>'number','name'=>'prix','label'=>'Prix (FCFA)','value'=>$p['prix'],'required'=>true,'attrs'=>'min="0"'];
                                include __DIR__ . '/../views/components/form_field.php';
                                ?>
                            </div>
                            <div class="col-6">
                                <?php
                                $ff = [
                                    'type'    => 'select',
                                    'name'    => 'duree',
                                    'label'   => 'Durée',
                                    'options' => [
                                        ['value'=>'30', 'label'=>'30 min',   'selected'=>$p['duree']==30],
                                        ['value'=>'60', 'label'=>'1 heure',  'selected'=>$p['duree']==60],
                                        ['value'=>'90', 'label'=>'1h 30',    'selected'=>$p['duree']==90],
                                        ['value'=>'120','label'=>'2 heures', 'selected'=>$p['duree']==120],
                                        ['value'=>'180','label'=>'3 heures', 'selected'=>$p['duree']==180],
                                    ],
                                ];
                                include __DIR__ . '/../views/components/form_field.php';
                                ?>
                            </div>
                        </div>

                        <?php
                        $ff = ['type'=>'file','name'=>'photo','label'=>'Changer la photo (optionnel — Max 5 Mo)','attrs'=>'accept="image/*"'];
                        include __DIR__ . '/../views/components/form_field.php';

                        $ff = ['type'=>'textarea','name'=>'description','label'=>'Description','value'=>$un_description,'rows'=>2];
                        include __DIR__ . '/../views/components/form_field.php';
                        ?>

                        <!-- Options modales -->
                        <div class="glass-cct p-3 mb-3" style="border:1px dashed var(--glass-border-md);">
                            <label class="form-label-cct" style="color:var(--gold);">
                                <i class="bi bi-tags me-1" aria-hidden="true"></i>
                                Options / Variations
                            </label>
                            <div id="wrapper-options-edit-<?= $p['id_service'] ?>">
                                <?php foreach ($options_array as $o): ?>
                                    <div class="row g-2 mb-2 align-items-center option-item">
                                        <div class="col-7">
                                            <input type="text"
                                                   name="edit_option_nom[]"
                                                   class="fc-dark"
                                                   value="<?= htmlspecialchars($o['nom']) ?>">
                                        </div>
                                        <div class="col-4">
                                            <input type="number"
                                                   name="edit_option_prix[]"
                                                   class="fc-dark"
                                                   value="<?= $o['prix'] ?>">
                                        </div>
                                        <div class="col-1">
                                            <button type="button"
                                                    class="btn-danger-cct btn-sm"
                                                    onclick="this.closest('.option-item').remove()"
                                                    aria-label="Supprimer cette option">
                                                <i class="bi bi-x" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button"
                                    class="btn-outline-gold btn-sm w-100 mt-2"
                                    onclick="ajouterOptionEdit(<?= $p['id_service'] ?>)">
                                <i class="bi bi-plus" aria-hidden="true"></i> Ajouter une variation
                            </button>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button"
                                    class="btn-ghost btn-sm"
                                    onclick="closeModal('<?= $modal_id_p ?>')">
                                Annuler
                            </button>
                            <button type="submit"
                                    name="modifier_prestation"
                                    class="btn-gold btn-sm">
                                <i class="bi bi-check2 me-1" aria-hidden="true"></i>
                                SAUVEGARDER
                            </button>
                        </div>
                    </form>
                    <?php
                    $modal_content = ob_get_clean();
                    $modal_id      = $modal_id_p;
                    $modal_title   = 'Modifier : ' . htmlspecialchars($p['nom_service']);
                    $modal_width   = '580px';
                    include __DIR__ . '/../views/components/modal.php';
                    unset($modal_content, $modal_id, $modal_title, $modal_width);
                    ?>

                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div><!-- /.col-lg-8 -->

</div><!-- /.row -->

<!-- JS — Ouverture modale depuis URL (ergonomie préservée) + options dynamiques -->
<script>
function ajouterOptionAjout() {
    const wrapper = document.getElementById('wrapper-options-ajout');
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 align-items-center option-item';
    div.innerHTML = `
        <div class="col-7">
            <input type="text" name="option_nom[]" class="fc-dark" placeholder="Ex: Teinte Blanche" required>
        </div>
        <div class="col-4">
            <input type="number" name="option_prix[]" class="fc-dark" placeholder="Prix" required>
        </div>
        <div class="col-1">
            <button type="button" class="btn-danger-cct btn-sm" onclick="this.closest('.option-item').remove()" aria-label="Supprimer">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    wrapper.appendChild(div);
}

function ajouterOptionEdit(idPrestation) {
    const wrapper = document.getElementById('wrapper-options-edit-' + idPrestation);
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 align-items-center option-item';
    div.innerHTML = `
        <div class="col-7">
            <input type="text" name="edit_option_nom[]" class="fc-dark" placeholder="Ex: Mèches Longues" required>
        </div>
        <div class="col-4">
            <input type="number" name="edit_option_prix[]" class="fc-dark" placeholder="Prix" required>
        </div>
        <div class="col-1">
            <button type="button" class="btn-danger-cct btn-sm" onclick="this.closest('.option-item').remove()" aria-label="Supprimer">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    wrapper.appendChild(div);
}

// Ouverture automatique depuis l'extérieur via ?ouvrir_modifier=ID
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('ouvrir_modifier')) {
        const id = urlParams.get('ouvrir_modifier');
        openModal('modal-modifier-' + id);
    }
});
</script>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
