<?php
// 1. TOUT EN HAUT : Sécurité et Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si l'utilisateur n'est pas connecté, redirection immédiate en JS
if (!isset($_SESSION['id_user'])) {
    echo "<script>window.location.href='connexion.php';</script>";
    exit();
}

// Inclusion du header (la sidebar s'adaptera automatiquement grâce à nos modifs d'avant)
include 'header.php';
require 'config.php';

// Récupération des informations fraîches de l'utilisateur depuis la BDD
$id_user = $_SESSION['id_user'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();

// Si l'utilisateur n'existe pas en BDD (sécurité)
if (!$user) {
    echo "<script>window.location.href='deconnexion.php';</script>";
    exit();
}

// Initialisation de variables de stats pour le tableau de bord selon le rôle
$stats_rdv = 0;
$solde_portefeuille = 0;

if ($user['role'] === 'client') {
    // Compter ses rendez-vous passés ou en attente
    $stmt_stats = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE client_id = ?");
    $stmt_stats->execute([$id_user]);
    $stats_rdv = $stmt_stats->fetchColumn();
} elseif ($user['role'] === 'coiffeur') {
    // Compter ses rendez-vous à gérer
    $stmt_stats = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE coiffeur_id = ?");
    $stmt_stats->execute([$id_user]);
    $stats_rdv = $stmt_stats->fetchColumn();
    
    // Simulation ou récupération du portefeuille si le champ existe (sinon valeur par défaut)
    $solde_portefeuille = $user['portefeuille'] ?? 0; 
}
?>

<div class="container py-5" style="min-height: 90vh;">
    <div class="row mb-5">
        <div class="col-12 text-center text-md-start">
            <h2 class="text-warning border-bottom border-warning pb-3 d-inline-block" style="letter-spacing: 2px;">
                ESPACE PROFIL &bull; <?php echo strtoupper($user['role']); ?>
            </h2>
            <p class="text-secondary mt-2">Bienvenue sur votre tableau de bord personnel, <?php echo htmlspecialchars($user['prenom']); ?>.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5 col-md-12">
            <div class="id-card-container">
                <div class="id-card">
                    <div class="id-card-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold tracking-wider text-dark small">COIFFE CHEZ TOI</span>
                        <span class="badge bg-dark text-warning border border-warning px-2 py-1 uppercase small">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </span>
                    </div>
                    
                    <div class="id-card-body row mt-4 align-items-center">
                        <div class="col-4 text-center">
                            <div class="avatar-box border border-dark rounded d-flex align-items-center justify-content-center bg-light text-dark">
                                <i class="bi bi-person-vcard fs-1 text-secondary"></i>
                            </div>
                        </div>
                        <div class="col-8 id-fields">
                            <div class="mb-2">
                                <small class="text-muted d-block text-uppercase">Nom & Prénom</small>
                                <span class="fw-bold text-dark fs-5"><?php echo htmlspecialchars(strtoupper($user['nom']) . ' ' . $user['prenom']); ?></span>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted d-block text-uppercase">Identifiant unique</small>
                                <span class="font-monospace text-dark">#CCT-00<?php echo htmlspecialchars($user['id']); ?></span>
                            </div>
                            <div>
                                <small class="text-muted d-block text-uppercase">Téléphone</small>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($user['telephone']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="id-card-footer mt-4 pt-3 border-top border-secondary-subtle d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Statut du compte</small>
                            <span class="text-success fw-bold"><i class="bi bi-patch-check-fill"></i> Actif</span>
                        </div>
                        <img src="images/logo.png" alt="Logo" style="height: 30px; opacity: 0.7; filter: grayscale(100%);" onerror="this.style.display='none'">
                    </div>
                </div>
            </div>
            
            <div class="d-grid mt-4">
                <a href="deconnexion.php" class="btn btn-outline-danger fw-bold py-2">
                    <i class="bi bi-box-arrow-left"></i> Se déconnecter de la session
                </a>
            </div>
        </div>

        <div class="col-lg-7 col-md-12">
            
            <?php if ($user['role'] === 'client'): ?>
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <div class="card bg-card-custom border border-secondary text-white p-4 h-100">
                            <h5 class="text-warning"><i class="bi bi-calendar3"></i> Mes Rendez-vous</h5>
                            <p class="fs-3 fw-bold my-2"><?php echo $stats_rdv; ?></p>
                            <p class="text-secondary small">Total des réservations enregistrées sur votre compte.</p>
                            <a href="mes_rendezvous.php" class="btn btn-warning btn-sm mt-auto fw-bold text-dark">Voir l'historique</a>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-12">
                        <div class="card bg-card-custom border border-secondary text-white p-4 h-100">
                            <h5 class="text-warning"><i class="bi bi-scissors"></i> Prendre RDV</h5>
                            <p class="text-secondary small mt-3">Envie d'une nouvelle coupe fraîche à domicile ? Parcourez le catalogue complet.</p>
                            <a href="catalogue.php" class="btn btn-outline-warning btn-sm mt-auto fw-bold">Ouvrir le catalogue</a>
                        </div>
                    </div>
                </div>

            <?php elseif ($user['role'] === 'coiffeur'): ?>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-12">
                        <div class="card bg-card-custom border border-secondary text-white p-4">
                            <h6 class="text-secondary text-uppercase small">Rendez-vous à gérer</h6>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fs-2 fw-bold text-warning"><?php echo $stats_rdv; ?></span>
                                <i class="bi bi-calendar-check fs-2 text-secondary"></i>
                            </div>
                            <a href="valider_rendezvous.php" class="btn btn-link text-warning text-decoration-none p-0 mt-3 small text-start">
                                Ouvrir mon agenda <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-12">
                        <div class="card bg-card-custom border border-secondary text-white p-4">
                            <h6 class="text-secondary text-uppercase small">Mon Portefeuille (Gains)</h6>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fs-2 fw-bold text-success"><?php echo number_format($solde_portefeuille, 0, ',', ' '); ?> <small class="fs-6">FCFA</small></span>
                                <i class="bi bi-wallet2 fs-2 text-secondary"></i>
                            </div>
                            <a href="portefeuille.php" class="btn btn-link text-success text-decoration-none p-0 mt-3 small text-start">
                                Retirer mes fonds <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card bg-card-custom border border-warning text-white p-4">
                    <h5 class="text-warning mb-3"><i class="bi bi-award"></i> Certification & Diplôme Professionnel</h5>
                    <p class="text-secondary small">Voici le document justificatif enregistré lors de votre inscription pour valider vos compétences de coiffeur sur la plateforme.</p>
                    
                    <div class="text-center mt-3 bg-dark p-3 border border-secondary rounded overflow-hidden">
                        <img src="<?php echo !empty($user['diplome']) ? htmlspecialchars($user['diplome']) : 'images/diplome_default.jpg'; ?>" 
                             alt="Diplôme de Coiffure Professionnel" 
                             class="img-fluid rounded diplome-preview"
                             style="max-height: 220px; object-fit: contain; width: 100%;">
                    </div>
                    <small class="text-center text-muted mt-2 d-block">
                        <i class="bi bi-info-circle"></i> Dimensions certifiées conformes par l'administration CCT.
                    </small>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
    /* Style de la boîte Dashboard de droite */
    .bg-card-custom {
        background-color: #1a1a1a !important;
        border-radius: 12px;
        transition: transform 0.3s ease;
    }
    .bg-card-custom:hover {
        transform: translateY(-3px);
    }

    /* --- EFFET CARTE D'IDENTITÉ GRAPHIQUE --- */
    .id-card-container {
        perspective: 1000px;
    }
    .id-card {
        background: linear-gradient(135deg, #e5c060 0%, #ffffff 60%, #f4f4f4 100%);
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 15px 35px rgba(212, 175, 55, 0.15), 0 5px 15px rgba(0, 0, 0, 0.5);
        color: #000;
        border: 1px solid rgba(255, 255, 255, 0.4);
        position: relative;
    }
    .avatar-box {
        width: 85px;
        height: 100px;
        border-style: dashed !important;
        border-color: #6c757d !important;
    }
    .id-fields small {
        font-size: 0.65rem;
        letter-spacing: 1px;
        color: #555 !important;
    }

    /* Effet Zoom sur le diplôme */
    .diplome-preview {
        transition: transform 0.4s ease;
        cursor: pointer;
    }
    .diplome-preview:hover {
        transform: scale(1.03);
    }
</style>

<?php include 'footer.php'; ?>