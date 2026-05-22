<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// SÉCURITÉ : Si l'utilisateur n'est pas connecté, on le renvoie à la page de connexion
if (!isset($_SESSION['id_user'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['id_user'];

// Récupération des informations de l'utilisateur connecté (Client ou Coiffeur)
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Si l'utilisateur est un admin, il n'a rien à faire ici, son dashboard est sur l'index
if ($user['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

// LOGIQUE DE TRAITEMENT : SUPPRESSION DU COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    $raison = htmlspecialchars(trim($_POST['raison_depart']));
    
    if (!empty($raison)) {
        // 1. On sauvegarde la raison du départ pour l'admin avant de supprimer le compte
        $log = $pdo->prepare("INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) VALUES (?, ?, ?, ?)");
        $log->execute([$user['nom'], $user['email'], $user['role'], $raison]);
        
        // 2. Suppression définitive de l'utilisateur de la BDD
        $del = $pdo->prepare("DELETE FROM utilisateurs WHERE id_user = ?");
        $del->execute([$user_id]);
        
        // 3. On détruit la session (déconnexion forcée puisque le compte n'existe plus)
        session_destroy();
        
        // 4. Alerte et redirection vers l'accueil invité
        echo "<script>
                alert('Votre demande a bien été prise en compte. Votre compte et vos données ont été définitivement supprimés.');
                window.location.href='index.php';
              </script>";
        exit();
    }
}
?>

<section class="py-5" style="background-color: #000; min-height: 90vh; color: #fff;">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                
                <div class="card p-4 mb-4" style="background-color: #111; border: 1px solid #222; border-radius: 16px;">
                    <div class="text-center mb-4">
                        <img src="<?php echo !empty($user['photo_profil']) ? htmlspecialchars($user['photo_profil']) : 'uploads/default.png'; ?>" 
                             class="rounded-circle border border-warning mb-3 shadow" 
                             style="width: 110px; height: 110px; object-fit: cover;" alt="Avatar">
                        
                        <h3 class="text-warning fw-bold mb-1"><?php echo htmlspecialchars(strtoupper($user['nom'])); ?></h3>
                        <span class="badge bg-black border border-secondary text-secondary px-3 py-1 text-uppercase mb-2">
                            Espace <?php echo $user['role']; ?>
                        </span>
                    </div>

                    <div class="bg-black p-3 rounded border border-secondary mb-3">
                        <div class="d-flex justify-content-between border-bottom border-dark pb-2 mb-2 small">
                            <span class="text-secondary">Adresse Email :</span>
                            <span class="text-white fw-bold"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom border-dark pb-2 mb-2 small">
                            <span class="text-secondary">Ville / Localité :</span>
                            <span class="text-white fw-bold"><?php echo htmlspecialchars($user['ville'] ?? 'Non renseignée'); ?></span>
                        </div>
                        <?php if ($user['role'] === 'coiffeur'): ?>
                            <div class="d-flex justify-content-between small">
                                <span class="text-secondary">Statut Abonnement :</span>
                                <span class="fw-bold <?php echo $user['abonnement_actif'] ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $user['abonnement_actif'] ? 'Actif (Payé)' : 'Inactif'; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-2">
                        <a href="deconnexion.php" class="btn btn-outline-secondary w-100 fw-bold py-2" style="border-radius: 8px;">
                            <i class="bi bi-box-arrow-left me-2"></i> SE DÉCONNECTER
                        </a>
                    </div>
                </div>

                <div class="card p-4" style="background-color: #111; border: 1px solid #dc3545; border-radius: 16px;">
                    <h5 class="text-danger fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i> Zone de Danger</h5>
                    <p class="text-secondary small mb-3">
                        Si vous décidez de supprimer votre compte, cette action sera instantanée et irréversible. Toutes vos données (prestations, rendez-vous, historique) seront effacées de nos serveurs.
                    </p>

                    <button class="btn btn-sm btn-outline-danger w-100 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#formulaireSuppression">
                        <i class="bi bi-trash3 me-1"></i> Supprimer mon compte définitivement
                    </button>

                    <div class="collapse mt-3" id="formulaireSuppression">
                        <form method="POST" class="p-3 rounded bg-black border border-secondary">
                            <div class="mb-3">
                                <label class="text-warning small fw-bold mb-2">Veuillez indiquer la raison de votre décision (Obligatoire pour l'administration) :</label>
                                <textarea name="raison_depart" class="form-control bg-dark text-white border-secondary small" rows="3" placeholder="Ex: Je n'utilise plus l'application, je change de secteur, problème d'abonnement..." required></textarea>
                            </div>
                            
                            <div class="p-2 bg-dark rounded small text-secondary mb-3 border border-secondary">
                                <i class="bi bi-info-circle text-warning me-1"></i> En cliquant sur le bouton ci-dessous, votre compte sera immédiatement détruit et vous serez redirigé.
                            </div>

                            <button type="submit" name="confirmer_suppression" class="btn btn-danger btn-sm w-100 fw-bold" onclick="return confirm('Êtes-vous sûr à 100% de vouloir détruire votre compte maintenant ?');">
                                CONFIRMER L'EFFACEMENT IMMÉDIAT
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<style>
    .form-control:focus {
        background-color: #1a1a1a !important;
        border-color: var(--gold) !important;
        box-shadow: none;
        color: #fff;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>