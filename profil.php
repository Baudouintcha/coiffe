<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';

// SÉCURITÉ : Si l'utilisateur n'est pas connecté, on le renvoie à la page de connexion
if (!isset($_SESSION['id_user'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['id_user'];

// Récupération des informations de l'utilisateur connecté (Client ou Coiffeur)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Si l'utilisateur est un admin, il n'a rien à faire ici, son dashboard est sur l'index
if ($user['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

// LOGIQUE DE TRAITEMENT : SUPPRESSION DU COMPTE (Inchangée, préservée à 100%)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    $raison = htmlspecialchars(trim($_POST['raison_depart']));
    
    if (!empty($raison)) {
        // 1. On sauvegarde la raison du départ pour l'admin avant de supprimer le compte
        $log = $pdo->prepare("INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) VALUES (?, ?, ?, ?)");
        $log->execute([$user['nom'], $user['email'], $user['role'], $raison]);
        
        // 2. Suppression définitive de l'utilisateur de la BDD
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?"); // Correction id_user -> id selon ta BDD
        $del->execute([$user_id]);
        
        // 3. On détruit la session
        session_destroy();
        
        // 4. Alerte et redirection
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
        
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="text-warning fw-bold" style="letter-spacing: 1px;">MON ESPACE PERSONNEL</h2>
                <p class="text-secondary small">Gérez vos informations, votre portefeuille et la sécurité de votre compte</p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card text-center p-4 h-100 shadow-lg" style="background-color: #111; border: 1px solid #222; border-radius: 20px;">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <img src="<?php echo !empty($user['photo_profil']) ? htmlspecialchars($user['photo_profil']) : 'uploads/profil/default.png'; ?>" 
                             class="rounded-circle border-gold shadow" 
                             style="width: 130px; height: 130px; object-fit: cover;" alt="Avatar">
                    </div>
                    
                    <h3 class="text-white fw-bold mb-1"><?php echo htmlspecialchars(strtoupper($user['nom'])) . ' ' . htmlspecialchars($user['prenom']); ?></h3>
                    
                    <div class="mb-4">
                        <span class="badge bg-black border border-warning text-warning px-3 py-1 text-uppercase small" style="border-radius: 30px;">
                            <i class="bi <?php echo $user['role'] === 'coiffeur' ? 'bi-scissors' : 'bi-person-fill'; ?> me-1"></i>
                            Espace <?php echo $user['role']; ?>
                        </span>
                    </div>

                    <hr class="border-secondary my-3">

                    <div class="d-grid gap-2 mt-2">
                        <a href="modifier_profil.php" class="btn btn-gold btn-sm py-2 fw-bold" style="border-radius: 8px;">
                            <i class="bi bi-pencil-square me-2"></i> MODIFIER MON PROFIL
                        </a>
                        <a href="deconnexion.php" class="btn btn-outline-secondary btn-sm py-2 fw-bold" style="border-radius: 8px;">
                            <i class="bi bi-box-arrow-left me-2"></i> SE DÉCONNECTER
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-4 h-100 shadow-lg" style="background-color: #111; border: 1px solid #222; border-radius: 20px;">
                    
                    <h5 class="text-warning fw-bold mb-3"><i class="bi bi-wallet2 me-2"></i> Mon Portefeuille</h5>
                    <div class="p-3 mb-4 rounded border border-warning" style="background: linear-gradient(135deg, #0a0a0a 0%, #151515 100%);">
                        <div class="row align-items-center">
                            <div class="col-md-7 mb-3 mb-md-0">
                                <span class="text-secondary small d-block">Solde disponible :</span>
                                <span class="text-white fw-bold fs-2" style="font-family: monospace;">
                                    <?php echo number_format($user['solde'] ?? 0, 0, ',', ' '); ?> <span class="text-warning fs-5">FCFA</span>
                                </span>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <?php if ($user['role'] === 'client'): ?>
                                    <a href="recharger_compte.php" class="btn btn-gold w-100 fw-bold py-2" style="border-radius: 8px;">
                                        <i class="bi bi-plus-circle me-2"></i> RECHARGER MON COMPTE
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-dark border border-secondary text-secondary py-2 px-3 small">
                                        Gains cumulés de vos prestations
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h5 class="text-warning fw-bold mb-3"><i class="bi bi-card-text me-2"></i> Informations Personnelles</h5>
                    <div class="bg-black p-3 rounded border border-secondary mb-4">
                        <div class="row g-3 small">
                            <div class="col-sm-6 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Adresse Email :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="col-sm-6 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Téléphone (WhatsApp) :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['telephone']); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Genre :</span>
                                <span class="text-white fw-bold text-uppercase"><?php echo htmlspecialchars($user['sexe']); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Ville :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['ville'] ?? 'Non renseignée'); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Quartier :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['quartier'] ?? 'Non renseigné'); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['role'] === 'coiffeur'): ?>
                        <h5 class="text-warning fw-bold mb-3"><i class="bi bi-patch-check me-2"></i> Statut Professionnel</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="bg-black p-3 rounded border border-secondary h-100 d-flex flex-column justify-content-center">
                                    <span class="text-secondary small d-block mb-1">Abonnement Application :</span>
                                    <div>
                                        <?php if (!empty($user['abonnement_actif']) && $user['abonnement_actif'] == 1): ?>
                                            <span class="badge bg-success px-3 py-2 fw-bold text-uppercase">ACTIF (Payé)</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-3 py-2 fw-bold text-uppercase mb-2 d-inline-block">INACTIF / EXPIRÉ</span>
                                            <a href="renouveler_abonnement.php" class="d-block text-warning small fw-bold text-decoration-none">Régulariser mon statut →</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-black p-3 rounded border border-secondary h-100">
                                    <span class="text-secondary small d-block mb-2">Diplôme / Certification :</span>
                                    <?php if (!empty($user['diplome'])): ?>
                                        <?php if (pathinfo($user['diplome'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                            <a href="<?php echo htmlspecialchars($user['diplome']); ?>" target="_blank" class="btn btn-sm btn-outline-warning w-100 py-2">
                                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Voir le PDF du Diplôme
                                            </a>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <img src="<?php echo htmlspecialchars($user['diplome']); ?>" class="img-thumbnail bg-dark border-secondary cursor-pointer" style="max-height: 70px; object-fit: contain;" data-bs-toggle="modal" data-bs-target="#diplomeModal" alt="Diplôme">
                                                <span class="d-block text-muted text-center" style="font-size: 10px; margin-top: 3px;">Cliquez pour agrandir</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-danger small fw-bold"><i class="bi bi-exclamation-circle me-1"></i> Aucun diplôme fourni</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card p-3 mt-auto" style="background-color: #0c0c0c; border: 1px solid #dc3545; border-radius: 12px;">
                        <h6 class="text-danger fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Zone de Danger</h6>
                        <p class="text-secondary mb-3" style="font-size: 11px; line-height: 1.3;">
                            La suppression de votre compte effacera instantanément et de manière irréversible toutes vos données de nos serveurs.
                        </p>

                        <button class="btn btn-sm btn-outline-danger w-100 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#formulaireSuppression" style="font-size: 12px;">
                            <i class="bi bi-trash3 me-1"></i> Supprimer mon compte définitivement
                        </button>

                        <div class="collapse mt-3" id="formulaireSuppression">
                            <form method="POST" class="p-3 rounded bg-black border border-secondary">
                                <div class="mb-3">
                                    <label class="text-warning small fw-bold mb-2" style="font-size: 12px;">Indiquez la raison de votre décision (Obligatoire) :</label>
                                    <textarea name="raison_depart" class="form-control bg-dark text-white border-secondary small" rows="2" placeholder="Ex: Je n'utilise plus l'application..." required></textarea>
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
    </div>
</section>

<?php if ($user['role'] === 'coiffeur' && !empty($user['diplome']) && pathinfo($user['diplome'], PATHINFO_EXTENSION) !== 'pdf'): ?>
<div class="modal fade" id="diplomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white border border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-warning">Justificatif de Diplôme / Certification</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-black">
                <img src="<?php echo htmlspecialchars($user['diplome']); ?>" class="img-fluid rounded" alt="Diplôme grand format">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
        transform: translateY(-2px);
    }
    .form-control:focus {
        background-color: #1a1a1a !important;
        border-color: var(--gold) !important;
        box-shadow: none !important;
        color: #fff !important;
    }
    .cursor-pointer {
        cursor: pointer;
        transition: 0.2s;
    }
    .cursor-pointer:hover {
        opacity: 0.8;
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>