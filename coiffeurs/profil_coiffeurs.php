<?php
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

$id_coiffeur  = $_SESSION['id_user'];
$message_type = '';
$message_text = '';

// Mise à jour du profil — SQL inchangé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['maj_profil'])) {
    $nom       = htmlspecialchars($_POST['nom']);
    $prenom    = htmlspecialchars($_POST['prenom']);
    $email     = htmlspecialchars($_POST['email']);
    $telephone = htmlspecialchars($_POST['telephone']);
    $ville     = htmlspecialchars($_POST['ville']);
    $quartier  = htmlspecialchars($_POST['quartier']);

    $stmt = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, ville = ?, quartier = ? WHERE id = ?");
    if ($stmt->execute([$nom, $prenom, $email, $telephone, $ville, $quartier, $id_coiffeur])) {
        $_SESSION['nom'] = $nom;
        $message_type = 'success';
        $message_text = 'Profil mis à jour avec succès.';
    } else {
        $message_type = 'danger';
        $message_text = 'Une erreur est survenue lors de la mise à jour.';
    }
}

// Récupération infos coiffeur — SQL inchangé
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id_coiffeur]);
$coiffeur = $stmt->fetch();

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
                    <?php
                    $ff = ['type'=>'text','name'=>'ville','label'=>'Ville','value'=>$coiffeur['ville']??'','required'=>true,'icon_prefix'=>'bi-building'];
                    include __DIR__ . '/../views/components/form_field.php';
                    ?>
                </div>
                <div class="col-md-6">
                    <?php
                    $ff = ['type'=>'text','name'=>'quartier','label'=>'Quartier','value'=>$coiffeur['quartier']??'','required'=>true,'icon_prefix'=>'bi-geo-alt'];
                    include __DIR__ . '/../views/components/form_field.php';
                    ?>
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

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
