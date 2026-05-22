<?php
// 1. TOUT EN HAUT : Gestion de la session et de la sécurité
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Si pas connecté, redirection immédiate
if (!isset($_SESSION['id_user'])) {
    header("Location: connexion.php");
    exit();
}

require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';

$user_id = $_SESSION['id_user'];

// 2. RÉCUPÉRATION DES INFOS ACTUELLES POUR PRÉ-REMPLIR LE FORMULAIRE
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Sécurité Admin : L'admin n'a pas sa place sur cette page grand public
if ($user['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

$erreurs = [];
$succes = false;

// 3. TRAITEMENT DU FORMULAIRE QUAND L'UTILISATEUR SOUMET SES MODIFS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(trim($_POST['nom']));
    $prenom = htmlspecialchars(trim($_POST['prenom']));
    $telephone = htmlspecialchars(trim($_POST['telephone']));
    $ville = htmlspecialchars(trim($_POST['ville']));
    $quartier = htmlspecialchars(trim($_POST['quartier']));
    $nouveau_mdp = $_POST['nouveau_mdp'];

    // Validations de base
    if (empty($nom) || empty($prenom) || empty($telephone)) {
        $erreurs[] = "Le nom, le prénom et le téléphone sont obligatoires.";
    }

    // Gestion de la Photo de Profil (si un fichier est envoyé)
    $chemin_photo = $user['photo_profil']; // Par défaut, on garde l'ancienne
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['photo_profil']['tmp_name'];
        $file_name = $_FILES['photo_profil']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $extensions_autorisees = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $extensions_autorisees)) {
            // Création du dossier s'il n'existe pas
            $dossier_cible = __DIR__ . '/uploads/profil/';
            if (!is_dir($dossier_cible)) {
                mkdir($dossier_cible, 0777, true);
            }
            
            // On renomme l'image de manière unique pour éviter les doublons
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

    // Si aucune erreur, on prépare la mise à jour
    if (empty($erreurs)) {
        try {
            // Étape A : Mise à jour des infos de base (communes aux clients et coiffeurs)
            $sql = "UPDATE users SET nom = ?, prenom = ?, telephone = ?, ville = ?, quartier = ?, photo_profil = ? WHERE id = ?";
            $params = [$nom, $prenom, $telephone, $ville, $quartier, $chemin_photo, $user_id];
            
            $stmt_update = $pdo->prepare($sql);
            $stmt_update->execute($params);

            // Étape B : Gestion du Mot de passe (Uniquement s'il a rempli la case)
            if (!empty($nouveau_mdp)) {
                if (strlen($nouveau_mdp) >= 6) {
                    $mdp_hache = password_hash($nouveau_mdp, PASSWORD_BCRYPT);
                    $stmt_mdp = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt_mdp->execute([$mdp_hache, $user_id]);
                } else {
                    $erreurs[] = "Le nouveau mot de passe doit faire au moins 6 caractères.";
                }
            }

            // Si tout s'est bien passé
            if (empty($erreurs)) {
                $succes = true;
                // On rafraîchit les données locales pour l'affichage immédiat
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                // Redirection magique après 2 secondes vers le profil mis à jour
                echo "<script>
                        alert('Vos informations ont été mises à jour avec succès !');
                        window.location.href='profil.php';
                      </script>";
                exit();
            }

        } catch (Exception $e) {
            $erreurs[] = "Une erreur est survenue lors de l'enregistrement : " . $e->getMessage();
        }
    }
}
?>

<section class="py-5" style="background-color: #000; min-height: 90vh; color: #fff;">
    <div class="container mt-4">
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid #222; border-radius: 20px;">
                    
                    <div class="text-center mb-4">
                        <h2 class="text-warning fw-bold">MODIFIER MES INFORMATIONS</h2>
                        <p class="text-secondary small">Mettez à jour vos bails personnels en toute sécurité</p>
                    </div>

                    <?php if (!empty($erreurs)): ?>
                        <div class="alert alert-danger bg-danger text-white border-0 small">
                            <ul class="mb-0">
                                <?php foreach ($erreurs as $erreur): ?>
                                    <li><?php echo $erreur; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="row g-3">
                        
                        <div class="col-12 text-center mb-3">
                            <div class="position-relative d-inline-block mx-auto mb-2">
                                <img src="<?php echo !empty($user['photo_profil']) ? htmlspecialchars($user['photo_profil']) : 'uploads/profil/default.png'; ?>" 
                                     class="rounded-circle border-gold shadow" 
                                     style="width: 110px; height: 110px; object-fit: cover;" alt="Avatar">
                            </div>
                            <div class="mx-auto" style="max-width: 250px;">
                                <label class="form-label text-secondary small">Changer la photo de profil</label>
                                <input type="file" name="photo_profil" class="form-control form-control-sm bg-dark text-white border-secondary">
                            </div>
                        </div>

                        <hr class="border-secondary my-2">

                        <div class="col-md-6">
                            <label class="form-label text-warning small fw-bold">Nom :</label>
                            <input type="text" name="nom" class="form-control bg-dark text-white border-secondary" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-warning small fw-bold">Prénom :</label>
                            <input type="text" name="prenom" class="form-control bg-dark text-white border-secondary" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Adresse Email (Non modifiable) :</label>
                            <input type="email" class="form-control bg-black text-secondary border-secondary cursor-not-allowed" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-warning small fw-bold">Téléphone (WhatsApp) :</label>
                            <input type="text" name="telephone" class="form-control bg-dark text-white border-secondary" value="<?php echo htmlspecialchars($user['telephone']); ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-warning small fw-bold">Ville :</label>
                            <input type="text" name="ville" class="form-control bg-dark text-white border-secondary" value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>" placeholder="Ex: Cotonou">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-warning small fw-bold">Quartier :</label>
                            <input type="text" name="quartier" class="form-control bg-dark text-white border-secondary" value="<?php echo htmlspecialchars($user['quartier'] ?? ''); ?>" placeholder="Ex: Fidjrossè">
                        </div>

                        <hr class="border-secondary my-3">

                        <div class="col-12">
                            <label class="form-label text-warning small fw-bold"><i class="bi bi-shield-lock-fill"></i> Changer le mot de passe (Laissez vide si aucun changement) :</label>
                            <input type="password" name="nouveau_mdp" class="form-control bg-dark text-white border-secondary" placeholder="Entrez un nouveau mot de passe sécurisé">
                        </div>

                        <div class="col-12 d-flex gap-3 mt-4">
                            <a href="profil.php" class="btn btn-outline-secondary w-50 fw-bold py-2" style="border-radius: 8px;">
                                ANNULER
                            </a>
                            <button type="submit" class="btn btn-gold w-50 fw-bold py-2" style="border-radius: 8px;">
                                <i class="bi bi-check-circle-fill me-1"></i> ENREGISTRER LES MODIFS
                            </button>
                        </div>

                    </form>

                </div>

            </div>
        </div>

    </div>
</section>

<style>
    :root {
        --gold: #f39c12;
    }
    .border-gold {
        border: 3px solid var(--gold) !important;
    }
    .btn-gold {
        background-color: var(--gold) !important;
        color: #000 !important;
        border: none !important;
        transition: 0.3s ease-in-out;
    }
    .btn-gold:hover {
        background-color: #d35400 !important;
    }
    .form-control:focus {
        background-color: #1a1a1a !important;
        border-color: var(--gold) !important;
        box-shadow: none !important;
        color: #fff !important;
    }
    .cursor-not-allowed {
        cursor: not-allowed;
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>