r <?php
/**
 * coiffeurs/profil_coiffeurs.php — Profil et paramètres du coiffeur
 * Migration Design System v2.0 — Parcours Coiffeur — Page 6
 *
 * ✅ BUG CORRIGÉ : $_SESSION['user_id'] → $_SESSION['id_user']
 * ✅ layout/header.php → layout/header_coiffeur.php
 * ✅ layout/footer.php → layout/footer_coiffeur.php
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, sessions, mise à jour profil : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

// Sécurité — BUG CORRIGÉ : $_SESSION['user_id'] → $_SESSION['id_user']
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

$prestataire_id  = $_SESSION['id_user'];
$message_type = '';
$message_text = '';

// Mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['maj_profil'])) {
    $nom        = htmlspecialchars(trim($_POST['nom']));
    $prenom     = htmlspecialchars(trim($_POST['prenom']));
    $email      = htmlspecialchars(trim($_POST['email']));
    $telephone  = htmlspecialchars(trim($_POST['telephone']));
    $id_ville   = !empty($_POST['ville']) ? intval($_POST['ville']) : null;
    $id_quartier= !empty($_POST['id_quartier']) ? intval($_POST['id_quartier']) : null;

    $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, id_ville = ?, id_quartier = ? WHERE id = ?");
    if ($stmt->execute([$nom, $prenom, $email, $telephone, $id_ville, $id_quartier, $prestataire_id])) {
        $_SESSION['nom'] = $nom;
        $message_type = 'success';
        $message_text = 'Profil mis à jour avec succès.';
    } else {
        $message_type = 'danger';
        $message_text = 'Une erreur est survenue lors de la mise à jour.';
    }
}

// Récupération infos coiffeur avec ville et quartier actuels
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$prestataire_id]);
$coiffeur = $stmt->fetch();

// Récupération des villes
try {
    $stmtV = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville");
    $villes = $stmtV->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $villes = [];
}

// Récupération des quartiers de la ville actuelle du coiffeur
$id_ville_actuelle = $coiffeur['ville'] ?? null; // Note: 'ville' stocke l'ID de la ville
$quartiers = [];
if ($id_ville_actuelle) {
    try {
        $stmtQ = $pdo->prepare("SELECT id, nom_quartier FROM quartiers WHERE id_ville = ? ORDER BY nom_quartier");
        $stmtQ->execute([$id_ville_actuelle]);
        $quartiers = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $quartiers = [];
    }
}

include __DIR__ . '/../layout/header_coiffeur.php';
?>

<!-- ActionBar -->
<?php
$ab = [
    'icon'     => 'bi-person-circle',
    'title'    => 'Mon Profil',
    'subtitle' => 'Gérez vos informations personnelles',
];
include __DIR__ . '/../views/components/action_bar.php';
?>

<!-- Toast message -->
<?php if (!empty($message_text)):
    $toast_type    = $message_type;
    $toast_message = $message_text;
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<div style="max-width:600px;margin:0 auto;">
    <div class="glass-cct p-4">
        <form method="POST" novalidate>

            <div class="row g-3">
                <div class="col-md-6">
                    <?php
                    $ff = ['type'=>'text','name'=>'nom','label'=>'Nom','value'=>$coiffeur['nom']??'','required'=>true];
                    include __DIR__ . '/../views/components/form_field.php';
                    ?>
                </div>
                <div class="col-md-6">
                    <?php
                    $ff = ['type'=>'text','name'=>'prenom','label'=>'Prénom','value'=>$coiffeur['prenom']??'','required'=>true];
                    include __DIR__ . '/../views/components/form_field.php';
                    ?>
                </div>
                <div class="col-12">
                    <?php
                    $ff = ['type'=>'email','name'=>'email','label'=>'Adresse e-mail','value'=>$coiffeur['email']??'','required'=>true,'icon_prefix'=>'bi-envelope'];
                    include __DIR__ . '/../views/components/form_field.php';
                    ?>
                </div>
                <div class="col-12">
                    <?php
                    $ff = ['type'=>'tel','name'=>'telephone','label'=>'Téléphone / WhatsApp','value'=>$coiffeur['telephone']??'','placeholder'=>'Ex: +22967000000','required'=>true,'icon_prefix'=>'bi-telephone'];
                    include __DIR__ . '/../views/components/form_field.php';
                    ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label-cct">Ville <span style="color:var(--danger-text)">*</span></label>
                    <select name="ville" id="villeSelect" class="fc-dark" required onchange="chargerQuartiers(this.value)">
                        <option value="">— Choisir une ville —</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= ($coiffeur['ville'] ?? '') == $v['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nom_ville']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label-cct">Quartier <span style="color:var(--danger-text)">*</span></label>
                    <select name="id_quartier" id="quartierSelect" class="fc-dark" required>
                        <option value="">— Choisir un quartier —</option>
                        <?php foreach ($quartiers as $q): ?>
                            <option value="<?= $q['id'] ?>" <?= ($coiffeur['id_quartier'] ?? '') == $q['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($q['nom_quartier']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex flex-column gap-2 mt-4">
                <button type="submit"
                        name="maj_profil"
                        class="btn-gold w-100"
                        style="padding:13px;">
                    <i class="bi bi-check2-circle me-2" aria-hidden="true"></i>
                    ENREGISTRER LES MODIFICATIONS
                </button>
                <a href="/coiffons/deconnexion.php"
                   class="btn-ghost w-100 text-center"
                   style="padding:10px;color:var(--danger-text);">
                    <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i>
                    Déconnexion
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Charger les quartiers en AJAX quand la ville change
function chargerQuartiers(idVille) {
    const qs = document.getElementById('quartierSelect');
    if (!idVille || idVille === '0') {
        qs.innerHTML = '<option value="">— Choisir un quartier —</option>';
        return;
    }
    fetch('/coiffons/access/get_quartiers.php?id_ville=' + idVille)
        .then(r => r.json())
        .then(data => {
            console.log('Quartiers chargés:', data);  // Debug
            qs.innerHTML = '<option value="">— Choisir un quartier —</option>';
            if (data.length === 0) {
                console.warn('Aucun quartier trouvé pour cette ville');
            }
            data.forEach(q => {
                const opt = document.createElement('option');
                opt.value = q.id;  // q.id est l'alias de id_quartier
                opt.textContent = q.nom_quartier;
                qs.appendChild(opt);
            });
        })
        .catch(e => {
            console.error('Erreur lors du chargement des quartiers:', e);
            qs.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}
</script>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
