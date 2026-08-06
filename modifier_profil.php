<?php
/**
 * modifier_profil.php — Formulaire de modification du profil utilisateur
 * Migration Design System v2.0 — Profil Partagé
 *
 * ✅ BUG CORRIGÉ : --gold: #f39c12 (orange) → #D4AF37 (officiel DS)
 * ✅ layout/header.php → navbar_client.php
 * ✅ layout/footer.php → footer_global.php
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, upload photo, mot de passe, quartiers : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user'])) {
    header("Location: /coiffons/index.php?page=login");
    exit();
}

require_once __DIR__ . '/security/config.php';

$user_id = $_SESSION['id_user'];

// Récupération quartiers — SQL inchangé
try {
    $quartiers_stmt = $pdo->query("SELECT * FROM quartiers ORDER BY nom_quartier ASC");
    $liste_quartiers = $quartiers_stmt->fetchAll();
} catch (Exception $e) {
    $liste_quartiers = [];
}

// Récupération user — SQL inchangé
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

$erreurs = [];
$succes  = false;

// Traitement formulaire — SQL CORRIGÉ : utiliser id_ville (INT) au lieu de ville (TEXT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = htmlspecialchars(trim($_POST['nom']));
    $prenom    = htmlspecialchars(trim($_POST['prenom']));
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $id_ville  = (!empty($_POST['id_ville'])) ? intval($_POST['id_ville']) : null;
    $id_quartier = (!empty($_POST['id_quartier'])) ? intval($_POST['id_quartier']) : null;
    $nouveau_mdp = $_POST['nouveau_mdp'];

    if (empty($nom) || empty($prenom) || empty($telephone)) {
        $erreurs[] = "Le nom, le prénom et le téléphone sont obligatoires.";
    }

    $chemin_photo = $user['photo_profil'];
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['photo_profil']['tmp_name'];
        $file_name = $_FILES['photo_profil']['name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $extensions_autorisees = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $extensions_autorisees)) {
            $dossier_cible = __DIR__ . '/uploads/profil/';
            if (!is_dir($dossier_cible)) {
                mkdir($dossier_cible, 0777, true);
            }
            $nouveau_nom_fichier = 'profil_' . $user_id . '_' . time() . '.' . $file_ext;
            $destination = $dossier_cible . $nouveau_nom_fichier;
            if (move_uploaded_file($file_tmp, $destination)) {
                $chemin_photo = 'uploads/profil/' . $nouveau_nom_fichier;
            } else {
                $erreurs[] = "Erreur lors du déplacement de la photo.";
            }
        } else {
            $erreurs[] = "Format d'image invalide. Uniquement JPG, JPEG ou PNG.";
        }
    }

    if (empty($erreurs)) {
        try {
            $stmt_update = $pdo->prepare("UPDATE users SET nom = ?, prenom = ?, telephone = ?, id_ville = ?, id_quartier = ?, photo_profil = ? WHERE id = ?");
            $stmt_update->execute([$nom, $prenom, $telephone, $id_ville, $id_quartier, $chemin_photo, $user_id]);

            if (!empty($nouveau_mdp)) {
                if (strlen($nouveau_mdp) >= 6) {
                    $mdp_hache = password_hash($nouveau_mdp, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$mdp_hache, $user_id]);
                } else {
                    $erreurs[] = "Le nouveau mot de passe doit faire au moins 6 caractères.";
                }
            }

            if (empty($erreurs)) {
                echo "<script>alert('Vos informations ont été mises à jour avec succès !');window.location.href='/coiffons/profil.php';</script>";
                exit();
            }
        } catch (Exception $e) {
            $erreurs[] = "Une erreur est survenue lors de l'enregistrement : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil — Coiffe Chez Toi</title>
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    .modifier-page { padding-top:calc(var(--navbar-height) + 2rem); padding-bottom:calc(var(--bottomnav-height) + 2rem); min-height:100vh; background:var(--dark); }
    </style>
</head>
<body>
<?php
$page_root = '/coiffons';
include __DIR__ . '/views/components/navbar_client.php';
?>

<main class="modifier-page page-transition">
    <div class="container" style="max-width:680px;">

        <!-- Lien retour -->
        <div class="mb-4">
            <a href="/coiffons/profil.php" class="btn-ghost btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Mon profil
            </a>
        </div>

        <div class="text-center mb-4">
            <h1 style="font-family:var(--font-display);font-size:clamp(1.2rem,3vw,1.5rem);color:var(--gold);font-weight:700;letter-spacing:2px;margin-bottom:4px;">
                MODIFIER MES INFORMATIONS
            </h1>
            <p style="color:var(--text-muted);font-size:0.78rem;">Mettez à jour vos informations personnelles en toute sécurité</p>
        </div>

        <!-- Erreurs -->
        <?php if (!empty($erreurs)):
            $toast_type = 'danger';
            $toast_message = implode(' | ', $erreurs);
            include __DIR__ . '/views/components/toast.php';
        endif; ?>

        <div class="glass-cct p-4">
            <form method="POST" enctype="multipart/form-data" novalidate>

                <!-- Photo de profil -->
                <div class="text-center mb-4">
                    <?php if (!empty($user['photo_profil']) && file_exists(__DIR__ . '/' . $user['photo_profil'])): ?>
                        <img src="/coiffons/<?= htmlspecialchars($user['photo_profil']) ?>"
                             style="width:88px;height:88px;border-radius:50%;border:3px solid var(--gold);object-fit:cover;display:block;margin:0 auto 12px;"
                             alt="Photo actuelle">
                    <?php else: ?>
                        <div style="width:88px;height:88px;border-radius:50%;border:3px solid var(--gold);background:var(--gold-dim);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-family:var(--font-display);font-size:1.8rem;color:var(--gold);">
                            <?= strtoupper(substr($user['nom'] ?? 'U', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <?php
                    $ff = ['type'=>'file','name'=>'photo_profil','label'=>'Changer la photo de profil','attrs'=>'accept="image/jpeg,image/png"'];
                    include __DIR__ . '/views/components/form_field.php';
                    ?>
                </div>

                <hr style="border-color:var(--glass-border);margin:1.5rem 0;">

                <div class="row g-3">
                    <div class="col-md-6">
                        <?php $ff = ['type'=>'text','name'=>'nom','label'=>'Nom','value'=>$user['nom']??'','required'=>true]; include __DIR__ . '/views/components/form_field.php'; ?>
                    </div>
                    <div class="col-md-6">
                        <?php $ff = ['type'=>'text','name'=>'prenom','label'=>'Prénom','value'=>$user['prenom']??'','required'=>true]; include __DIR__ . '/views/components/form_field.php'; ?>
                    </div>
                    <div class="col-12">
                        <?php $ff = ['type'=>'email','name'=>'_email_readonly','label'=>'Adresse Email (non modifiable)','value'=>$user['email']??'','disabled'=>true,'icon_prefix'=>'bi-envelope']; include __DIR__ . '/views/components/form_field.php'; ?>
                    </div>
                    <div class="col-12">
                        <?php $ff = ['type'=>'tel','name'=>'telephone','label'=>'Téléphone (WhatsApp)','value'=>$user['telephone']??'','placeholder'=>'Ex: +22967000000','required'=>true,'icon_prefix'=>'bi-telephone']; include __DIR__ . '/views/components/form_field.php'; ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        // Récupérer les villes disponibles
                        try {
                            $villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
                            $liste_villes = $villes_stmt->fetchAll();
                        } catch (Exception $e) {
                            $liste_villes = [];
                        }
                        
                        $opts_v = [['value'=>'','label'=>'-- Sélectionnez une ville --']];
                        foreach ($liste_villes as $v) {
                            $opts_v[] = ['value'=>$v['id'], 'label'=>$v['nom_ville']??'', 'selected'=>(isset($user['id_ville']) && $user['id_ville'] == $v['id'])];
                        }
                        $ff = ['type'=>'select','name'=>'id_ville','label'=>'Ville','options'=>$opts_v,'attrs'=>'onchange="chargerQuartiers(this.value)"'];
                        include __DIR__ . '/views/components/form_field.php';
                        ?>
                    </div>
                    <div class="col-md-6">
                        <?php
                        $opts_q = [['value'=>'','label'=>'-- Sélectionnez votre quartier --']];
                        foreach ($liste_quartiers as $q) {
                            $opts_q[] = ['value'=>$q['id'], 'label'=>$q['nom_quartier']??'', 'selected'=>(isset($user['id_quartier']) && $user['id_quartier'] == $q['id'])];
                        }
                        $ff = ['type'=>'select','name'=>'id_quartier','label'=>'Quartier','options'=>$opts_q];
                        include __DIR__ . '/views/components/form_field.php';
                        ?>
                    </div>
                </div>

                <hr style="border-color:var(--glass-border);margin:1.5rem 0;">

                <?php
                $ff = ['type'=>'password','name'=>'nouveau_mdp','label'=>'Changer le mot de passe (laisser vide si aucun changement)','placeholder'=>'Entrez un nouveau mot de passe sécurisé','toggle_pw'=>true,'icon_prefix'=>'bi-shield-lock'];
                include __DIR__ . '/views/components/form_field.php';
                ?>

                <div class="d-flex gap-3 mt-4">
                    <a href="/coiffons/profil.php" class="btn-ghost w-50 text-center" style="padding:11px;">
                        ANNULER
                    </a>
                    <button type="submit" class="btn-gold w-50" style="padding:11px;">
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                        ENREGISTRER LES MODIFS
                    </button>
                </div>

            </form>
        </div>

    </div>
</main>

<?php
$page_root = '/coiffons';
include __DIR__ . '/views/components/footer_global.php';
$current_page = 'modifier_profil.php';
include __DIR__ . '/views/components/bottom_nav_client.php';
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Charger les quartiers en AJAX quand la ville change
function chargerQuartiers(idVille) {
    const qs = document.getElementById('id_quartier');
    if (!qs) return; // Si le select quartier n'existe pas
    
    if (!idVille || idVille === '0') {
        qs.innerHTML = '<option value="">-- Sélectionnez votre quartier --</option>';
        return;
    }
    
    fetch('/coiffons/access/get_quartiers.php?id_ville=' + idVille)
        .then(r => r.json())
        .then(data => {
            console.log('Quartiers chargés:', data);
            qs.innerHTML = '<option value="">-- Sélectionnez votre quartier --</option>';
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(q => {
                    const opt = document.createElement('option');
                    opt.value = q.id;
                    opt.textContent = q.nom_quartier;
                    qs.appendChild(opt);
                });
            }
        })
        .catch(e => {
            console.error('Erreur lors du chargement des quartiers:', e);
            qs.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}
</script>
</body>
</html>
