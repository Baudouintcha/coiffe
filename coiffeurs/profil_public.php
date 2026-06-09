<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php'; // On remonte d'un dossier pour atteindre security
include __DIR__ . '/../layout/header.php';

// 1. SÉCURITÉ ET INTERCEPTION : Si c'est un invité, interdiction d'être ici, redirection connexion
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'invite') {
    header("Location: /coiffons/access/connexion.php?redirect_reason=booking");
    exit();
}

// 2. RÉCUPÉRATION ET VÉRIFICATION DE L'ID DU COIFFEUR
$id_coiffeur = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_coiffeur) {
    echo "<div class='container py-5 text-center text-white'><h3>Coiffeur introuvable ou ID invalide.</h3><a href='/coiffons/index.php' class='btn btn-warning mt-3'>Retour à l'accueil</a></div>";
    include __DIR__ . '/../layout/footer.php';
    exit();
}

try {
    // 3. REQUÊTE POUR LES INFOS DU COIFFEUR
    $stmt = $pdo->prepare("
        SELECT u.*, v.nom_ville as nom_ville, q.nom_quartier as nom_quartier 
        FROM users u 
        LEFT JOIN villes v ON u.ville = v.id 
        LEFT JOIN quartiers q ON u.id_quartier = q.id 
        WHERE u.id = ? AND u.role = 'coiffeur' AND u.abonnement_status = 1
    ");
    $stmt->execute([$id_coiffeur]);
    $coiffeur = $stmt->fetch();

    if (!$coiffeur) {
        echo "<div class='container py-5 text-center text-white'><h3>Ce coiffeur n'est pas disponible ou son abonnement a expiré.</h3><a href='/coiffons/index.php' class='btn btn-warning mt-3'>Retour à l'accueil</a></div>";
        include __DIR__ . '/../layout/footer.php';
        exit();
    }

    // 4. REQUÊTE POUR LE CATALOGUE DE SES PRESTATIONS
    $stmt_presta = $pdo->prepare("SELECT * FROM prestations WHERE id_coiffeur = ? ORDER BY id_prestation DESC");
    $stmt_presta->execute([$id_coiffeur]);
    $prestations = $stmt_presta->fetchAll();

    // 5. REQUÊTE POUR CALCULER LA NOTE MOYENNE D'APRES LES AVIS (optionnel mais fait pro devant le jury)
    $stmt_note = $pdo->prepare("SELECT AVG(note) as moyenne, COUNT(*) as total FROM commentaires WHERE id_coiffeur = ?");
    $stmt_note->execute([$id_coiffeur]);
    $stats_avis = $stmt_note->fetch();
    $note_moyenne = $stats_avis['moyenne'] ? round($stats_avis['moyenne'], 1) : null;

} catch (Exception $e) {
    echo "<div class='container py-5 text-white'>Erreur technique : " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/../layout/footer.php';
    exit();
}
?>

<div class="luxury-profile-wrapper" style="background: linear-gradient(135deg, #0a0a0a 0%, #121212 100%); color: #fff; min-height: 100vh;">
    
    <section class="py-5 border-bottom border-secondary" style="background: rgba(255,255,255,0.02);">
        <div class="container text-center">
            <div class="mb-3">
                <div class="avatar-placeholder d-inline-flex justify-content-center align-items-center bg-warning text-dark rounded-circle fw-bold shadow-lg" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?php echo strtoupper(substr($coiffeur['nom'], 0, 1)); ?>
                </div>
            </div>
            
            <h1 class="text-warning fw-bold h2 mb-1"><?php echo strtoupper(htmlspecialchars($coiffeur['nom'])); ?></h1>
            <p class="text-secondary small mb-3">
                <i class="bi bi-geo-alt-fill text-warning"></i> 
                <?php echo htmlspecialchars($coiffeur['nom_ville'] ?? 'Non spécifiée'); ?> — <?php echo htmlspecialchars($coiffeur['nom_quartier'] ?? 'Général'); ?>
            </p>

            <div class="mb-3">
                <?php if ($note_moyenne): ?>
                    <span class="badge bg-dark border border-warning text-warning px-3 py-2">
                        <i class="bi bi-star-fill text-warning me-1"></i> <?php echo $note_moyenne; ?> / 5 (<?php echo $stats_avis['total']; ?> avis)
                    </span>
                <?php else: ?>
                    <span class="badge bg-dark border border-secondary text-secondary px-3 py-2">
                        <i class="bi bi-star me-1"></i> Aucun avis pour le moment
                    </span>
                <?php endif; ?>
            </div>

            <p class="text-light opacity-75 small mx-auto mb-4" style="max-width: 600px;">
                <?php echo !empty($coiffeur['bio']) ? htmlspecialchars($coiffeur['bio']) : "Artisan coiffeur professionnel sélectionné par notre plateforme, expert en créations capillaires haut de gamme à domicile."; ?>
            </p>

            <a href="/coiffons/client/prendre_rendezvous.php?id_coiffeur=<?php echo $coiffeur['id']; ?>" class="btn btn-warning text-black fw-bold px-5 py-2.5 shadow-lg" style="border-radius: 30px;">
                <i class="bi bi-calendar-check-fill me-2"></i> VOIR LES DISPONIBILITÉS & RÉSERVER
            </a>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <h3 class="text-white mb-4 h4 text-center text-md-start">
                <i class="bi bi-scissors text-warning me-2"></i> LE PORTFOLIO DE SES CRÉATIONS
            </h3>

            <?php if (empty($prestations)): ?>
                <div class="text-center text-muted py-5 border border-secondary border-dashed rounded" style="border-style: dashed !important;">
                    <i class="bi bi-camera-video display-4 text-secondary mb-3 d-block"></i>
                    Ce coiffeur n'a pas encore publié d'images ou de styles dans son catalogue privé.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($prestations as $p): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="card luxury-profile-card h-100 border-0 text-white shadow-sm">
                                <div class="luxury-img-container">
                                    <?php if (!empty($p['photo_style']) && file_exists(__DIR__ . '/../uploads/' . $p['photo_style'])): ?>
                                        <img src="<?php echo '/coiffons/uploads/' . $p['photo_style']; ?>" class="card-img-top" alt="Style">
                                    <?php else: ?>
                                        <div class="profile-placeholder-img d-flex flex-column justify-content-center align-items-center">
                                            <i class="bi bi-stars text-warning opacity-50 display-6 mb-2"></i>
                                            <span class="text-muted text-uppercase font-monospace" style="font-size: 0.6rem; letter-spacing: 1px;">Création Studio</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body d-flex flex-column p-4 text-center">
                                    <h5 class="fw-bold text-white mb-2"><?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                    <h4 class="text-warning fw-bold font-monospace mb-3 h5">
                                        <?php echo number_format($p['prix'], 0, ',', ' '); ?> <span style="font-size: 0.75rem;">FCFA</span>
                                    </h4>
                                    <div class="mt-auto">
                                        <a href="/coiffons/client/prendre_rendezvous.php?id_coiffeur=<?php echo $coiffeur['id']; ?>&id_prestation=<?php echo $p['id_prestation']; ?>" class="btn btn-outline-warning btn-sm w-100 py-2 fw-bold">
                                            CHOISIR CE STYLE
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</div>

<style>
    .luxury-profile-card {
        background-color: #121212 !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 12px !important;
        overflow: hidden;
    }

    .luxury-img-container {
        width: 100%;
        height: 240px;
        overflow: hidden;
        background-color: #1a1a1a;
    }

    .luxury-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-placeholder-img {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #151515 0%, #252525 100%);
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>