<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';

// Initialisation des variables pour éviter les erreurs "Undefined variable"
$total_entrees = 0;
$total_sorties = 0;
$solde_net = 0;
$nb_clients = 0;
$nb_coiffeurs = 0;
$users = [];
$commentaires = [];
$motifs_suppression = [];

// ÉLÉMENTS POUR LES COMPLÉMENTS DES CATALOGUES
$coiffeurs_matching = [];
$catalog_demo = [];
$role_actuel = $_SESSION['role'] ?? 'invite';
$coiffeur_id = $_SESSION['id_user'] ?? null;

// =================================================================
// COIFFURES PAR DÉFAUT (Si la base de données est vide)
// =================================================================
$coiffures_par_defaut = [
    [
        'id_prestation' => 1,
        'nom_style' => 'Gros Rastas Premium',
        'prix' => 7000,
        'nom_coiffeur' => 'Alliance Coiffure',
        'ville' => 'Cotonou',
        'quartier' => 'Fidjrossè'
    ],
    [
        'id_prestation' => 2,
        'nom_style' => 'Dégradé Américain / Wave',
        'prix' => 3000,
        'nom_coiffeur' => 'Barber Shop Pro',
        'ville' => 'Calavi',
        'quartier' => 'Zogbadjè'
    ],
    [
        'id_prestation' => 3,
        'nom_style' => 'Nattes Collées Graphiques',
        'prix' => 5000,
        'nom_coiffeur' => 'Divine Mains',
        'ville' => 'Porto-Novo',
        'quartier' => 'Ouando'
    ],
    [
        'id_prestation' => 4,
        'nom_style' => 'Faux Locs / Twist',
        'prix' => 10000,
        'nom_coiffeur' => 'Elegance Hair',
        'ville' => 'Cotonou',
        'quartier' => 'Haie Vive'
    ],
    [
        'id_prestation' => 5,
        'nom_style' => 'Tissage Fermé Pro',
        'prix' => 8000,
        'nom_coiffeur' => 'Mireille Beauty',
        'ville' => 'Calavi',
        'quartier' => 'Kpota'
    ],
    [
        'id_prestation' => 6,
        'nom_style' => 'Chignon Mariage Sublime',
        'prix' => 15000,
        'nom_coiffeur' => 'Grace Coiffure',
        'ville' => 'Parakou',
        'quartier' => 'Ladji Farani'
    ]
];

// =================================================================
// LOGIQUE DE RÉCUPÉRATION DES DONNÉES SELON LE RÔLE
// =================================================================
if ($coiffeur_id && isset($_SESSION['role'])) {
    //if ($_SESSION['role'] === 'admin') {
    //  $nb_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
    //$nb_coiffeurs = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur'")->fetchColumn();
    //
    //$total_entrees = $pdo->query("SELECT SUM(montant_commission) FROM transactions_site WHERE type_transaction = 'entree'")->fetchColumn() ?? 0;
    // $total_sorties = $pdo->query("SELECT SUM(montant_abonnement) FROM transactions_site WHERE type_transaction = 'sortie'")->fetchColumn() ?? 0;
    /// $solde_net = $total_entrees - $total_sorties;

    // Jointure pour récupérer le nom textuel de la ville au lieu de l'ID pour l'admin
    //$users = $pdo->query("SELECT u.*, v.nom_ville FROM users u LEFT JOIN villes v ON u.ville = v.id ORDER BY u.id DESC")->fetchAll();
    /// $commentaires = $pdo->query("SELECT c.*, u.nom FROM commentaires c JOIN users u ON c.id_client = u.id ORDER BY c.date_creation DESC")->fetchAll();
    ///$motifs_suppression = $pdo->query("SELECT * FROM suppressions_comptes ORDER BY date_demande DESC")->fetchAll();

    if ($_SESSION['role'] === 'coiffeur') {
        $chk = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $chk->execute([$coiffeur_id]);
        $db_coiffeur = $chk->fetch();

        // 🛠️ MODIFICATION : Correction 'LEFT JOIN quartier q' (sans s)
        $stmt = $pdo->prepare("SELECT p.*, v.nom_ville as ville, q.nom_quartier as quartiers FROM prestations p JOIN users u ON p.id_coiffeur = u.id LEFT JOIN villes v ON u.ville = v.id LEFT JOIN quartiers q ON u.id_quartier = q.id WHERE p.id_coiffeur = ? ORDER BY p.id_prestation DESC");
        $stmt->execute([$coiffeur_id]);
        $mes_coiffures = $stmt->fetchAll();
    }
}

// =================================================================
// 🎯 MODIFICATION CONSTRUCTIVE : MATCHING CLIENT OPTIMISÉ (QUARTIER PUIS VILLE)
// =================================================================
if ($role_actuel === 'client') {
    $id_ville_client = $_SESSION['ville'] ?? 0;
    $id_quartier_client = $_SESSION['id_quartier'] ?? 0; // Capture de la zone géographique du client
    $nom_ville_affichage = $_SESSION['nom_ville'] ?? 'votre ville';

    try {
        // NIVEAU 1 : Recherche stricte des coiffeurs couvrant le quartier spécifique du client
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville 
            FROM prestations p 
            JOIN users u ON p.id_coiffeur = u.id 
            JOIN villes v ON u.ville = v.id 
            INNER JOIN zones_coiffeur z ON u.id = z.id_coiffeur
            LEFT JOIN quartier q ON u.id_quartier = q.id 
            WHERE u.role = 'coiffeur' 
            AND u.abonnement_status = 1 
            AND u.ville = ?
            AND z.id_quartier = ? 
            ORDER BY RAND() LIMIT 6
        ");
        $stmt->execute([$id_ville_client, $id_quartier_client]);
        $coiffeurs_matching = $stmt->fetchAll();

        // NIVEAU 2 (PLAN DE SECOURS) : Si aucun coiffeur ne dessert ce quartier, on élargit à toute la Ville
        if (empty($coiffeurs_matching)) {
            $stmt_secours = $pdo->prepare("
                SELECT p.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville 
                FROM prestations p 
                JOIN users u ON p.id_coiffeur = u.id 
                JOIN villes v ON u.ville = v.id 
                LEFT JOIN quartier q ON u.id_quartier = q.id 
                WHERE u.role = 'coiffeur'
                AND u.abonnement_status = 1 
                AND u.ville = ? 
                ORDER BY RAND() LIMIT 6
            ");
            $stmt_secours->execute([$id_ville_client]);
            $coiffeurs_matching = $stmt_secours->fetchAll();
        }

        // NIVEAU 3 (PLAN DE SECOURS ULTIME) : Si la ville entière n'a pas de professionnels actifs, catalogue global
        if (empty($coiffeurs_matching)) {
            $coiffeurs_matching = $pdo->query("
                SELECT p.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville 
                FROM prestations p 
                JOIN users u ON p.id_coiffeur = u.id 
                JOIN villes v ON u.ville = v.id 
                LEFT JOIN quartier q ON u.id_quartier = q.id 
                WHERE u.abonnement_status = 1 
                ORDER BY RAND() LIMIT 6
            ")->fetchAll();
        }
    } catch (Exception $e) {
        $coiffeurs_matching = [];
    }

    // Si la base de données est complètement vide, chargement des cartes de démonstration
    if (empty($coiffeurs_matching)) {
        $coiffeurs_matching = $coiffures_par_defaut;
        usort($coiffeurs_matching, function ($a, $b) use ($nom_ville_affichage) {
            if ($a['ville'] === $nom_ville_affichage && $b['ville'] !== $nom_ville_affichage) return -1;
            if ($a['ville'] !== $nom_ville_affichage && $b['ville'] === $nom_ville_affichage) return 1;
            return 0;
        });
    }
} elseif ($role_actuel === 'invite') {
    try {
        // 🛠️ MODIFICATION : Correction 'LEFT JOIN quartier q'
        $catalog_demo = $pdo->query("SELECT p.*, u.nom as nom_coiffeur, v.nom_ville as ville, q.nom_quartier as quartier FROM prestations p JOIN users u ON p.id_coiffeur = u.id JOIN villes v ON u.ville = v.id LEFT JOIN quartier q ON u.id_quartier = q.id WHERE u.abonnement_status = 1 ORDER BY RAND() LIMIT 6")->fetchAll();
    } catch (Exception $e) {
        $catalog_demo = [];
    }

    if (empty($catalog_demo)) {
        $catalog_demo = $coiffures_par_defaut;
    }
}

// Pour l'interface publique (toujours chargée pour les commentaires)
try {
    $query_coms = $pdo->query("SELECT c.*, u.nom FROM commentaires c JOIN users u ON c.id_client = u.id ORDER BY c.date_creation DESC LIMIT 6");
    $all_comments = $query_coms->fetchAll();
} catch (Exception $e) {
    $all_comments = [];
}
?>







<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'coiffeur'): ?>
    <section class="py-4 bg-black style-smartwatch-container" style="min-height: 100vh; color: #fff;">
        <div class="container mt-2">

            <div class="row mb-4">
                <div class="col-12 text-center">
                    <h1 class="text-warning fw-bold h3 mb-1" style="letter-spacing: 0.5px;">
                        SALON DE : <?php echo strtoupper(htmlspecialchars($db_coiffeur['nom'])); ?>
                    </h1>
                    <p class="text-success mb-0 small" style="font-size: 0.75rem;">
                        <i class="bi bi-check-circle-fill"></i> Votre abonnement est actif
                    </p>
                </div>
            </div>

            <div class="row g-2 g-sm-4">
                <?php if (empty($mes_coiffures)): ?>
                    <div class="col-12 text-center text-muted small py-4">
                        Vous n'avez pas encore ajouté de style à votre catalogue.
                    </div>
                <?php else: ?>
                    <?php foreach ($mes_coiffures as $c): ?>
                        <div class="col-6 col-md-4">
                            <div class="card bg-dark text-white h-100 border-secondary shadow-sm">
                                <div class="card-body d-flex flex-column p-2 p-sm-3">
                                    <h4 class="fw-bold text-white mb-1 text-truncate style-max-width-name h6">
                                        <?php echo htmlspecialchars($c['nom_style'] ?? ''); ?>
                                    </h4>
                                    <p class="text-secondary small mb-2 text-truncate style-max-width-style" style="font-size: 0.7rem;">
                                        <i class="bi bi-geo-alt text-warning"></i> <?php echo htmlspecialchars($c['ville'] ?? 'Non spécifiée'); ?>
                                    </p>
                                    <h3 class="text-warning fw-bold font-monospace my-2 h5" style="font-size: 0.95rem;">
                                        <?php echo number_format($c['prix'], 0, ',', ' '); ?> <span style="font-size: 0.65rem;">F</span>
                                    </h3>

                                    <div class="mt-auto pt-2 border-top border-secondary d-flex gap-1">
                                        <a href="/coiffons/coiffeurs/gestion_catalogue.php?ouvrir_modifier=<?php echo $c['id_prestation']; ?>" class="btn btn-success btn-sm flex-grow-1 py-1 style-btn-responsive" style="font-size: 0.7rem;">
                                            <i class="bi bi-pencil-square"></i> <span class="d-none d-xs-inline">Éditer</span>
                                        </a>
                                        <a href="/coiffons/coiffeurs/gestion_catalogue.php?supprimer=<?php echo $c['id_prestation']; ?>" class="btn btn-danger btn-sm px-2 py-1 fw-bold style-btn-responsive" style="font-size: 0.7rem;" onclick="return confirm('Supprimer ?');">
                                            <i class="bi bi-trash3-fill"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="row my-4 justify-content-center">
                <div class="col-12 col-sm-8 col-md-6 text-center">
                    <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="btn btn-warning text-black fw-bold w-100 py-2 shadow" style="border-radius: 30px; letter-spacing: 0.5px;">
                        <i class="bi bi-plus-circle-fill me-1"></i> AJOUTER UN STYLE
                    </a>
                </div>
            </div>

            <div class="row g-2 style-flex-smartwatch">
                <div class="col-6">
                    <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="card bg-dark text-white p-2 p-sm-3 text-decoration-none border-secondary h-100 d-flex flex-column align-items-center text-center justify-content-center" style="min-height: 80px; border-radius: 8px;">
                        <h5 class="text-warning mb-1 h6" style="font-size: 0.85rem;"><i class="bi bi-calendar3 me-1"></i> Mon Agenda</h5>
                    </a>
                </div>
                <div class="col-6">
                    <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="card bg-dark text-white p-2 p-sm-3 text-decoration-none border-secondary h-100 d-flex flex-column align-items-center text-center justify-content-center" style="min-height: 80px; border-radius: 8px;">
                        <h5 class="text-success mb-1 h6" style="font-size: 0.85rem;"><i class="bi bi-check2-circle me-1"></i> Mes Demandes</h5>
                    </a>
                </div>
            </div>

        </div>
    </section>

<?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
    <section class="py-5 text-center bg-pure-dark border-bottom border-secondary-dim">
        <div class="container py-3">
            <h1 class="text-warning fw-bold display-4 mb-3">COIFFE_CHEZ_TOI — VOTRE SALON DE LUXE À DOMICILE</h1>
            <p class="text-light fs-5 mb-4 mx-auto" style="max-width: 750px;">
                Découvrez, réservez et planifiez vos prestations avec les meilleurs coiffeurs professionnels de votre région. Un système de portefeuille sécurisé, des coiffeurs certifiés et un service sur-mesure directement chez vous.
            </p>
            <p class="text-secondary fs-6 mb-4">
                Ravi de vous revoir, <strong><?php echo htmlspecialchars($_SESSION['nom'] ?? 'Client'); ?></strong> ! Prêt à réserver une coiffure à <?php echo htmlspecialchars($_SESSION['nom_ville'] ?? 'votre ville'); ?> ?
            </p>
            <a href="/coiffons/filter/annuaire_coiffeurs.php" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow-lg">EXPLORER L'ANNUAIRE DES COIFFEURS</a>
        </div>
    </section>

    <section class="py-5 bg-soft-dark">
        <div class="container">
            <h3 class="text-white mb-4"><i class="bi bi-geo-alt-fill text-warning me-2"></i> Les styles disponibles à <?php echo htmlspecialchars($_SESSION['nom_ville'] ?? 'votre ville'); ?></h3>
            <div class="row g-4">
                <?php foreach ($coiffeurs_matching as $coif): ?>
                    <div class="col-md-4">
                        <div class="card bg-pure-dark border-secondary-dim text-white h-100 shadow-sm card-hover">
                            <div class="card-body d-flex flex-column p-4">
                                <span class="badge bg-warning text-dark align-self-start mb-2"><i class="bi bi-person-fill"></i> Par <?php echo htmlspecialchars($coif['nom_coiffeur']); ?></span>
                                <h4 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($coif['nom_style']); ?></h4>
                                <p class="text-secondary small mb-3"><i class="bi bi-pin-map text-warning"></i> Zone : <?php echo htmlspecialchars($coif['ville']); ?> - <?php echo htmlspecialchars($coif['quartier'] ?? 'Général'); ?></p>

                                <h3 class="text-warning fw-bold fs-4 my-3 font-monospace"><?php echo number_format($coif['prix'], 0, ',', ' '); ?> <span class="small">FCFA</span></h3>

                                <div class="mt-auto">
                                    <a href="planifier_rendezvous.php?prestation=<?php echo $coif['id_prestation']; ?>" class="btn btn-gold btn-sm w-100 fw-bold py-2">PRENDRE RENDEZ-VOUS</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-pure-dark border-top border-secondary-dim">
        <div class="container text-center">
            <h2 class="text-warning mb-5 fw-bold">L'AVIS DE NOS CLIENTS</h2>
            <div id="carouselExampleClient" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                <div class="carousel-inner">
                    <?php if (empty($all_comments)): ?>
                        <div class="carousel-item active py-3">
                            <p class="fs-4 italic text-light">"Service impeccable ! La coiffeuse est venue à l'heure et le tressage est super propre. Je recommande !"</p>
                            <h5 class="text-warning">- Mariam B. (Cotonou)</h5>
                        </div>
                        <div class="carousel-item py-3">
                            <p class="fs-4 italic text-light">"Le dégradé américain à domicile est parfait. Plus besoin de faire la queue pendant des heures au salon."</p>
                            <h5 class="text-warning">- Arnaud K. (Calavi)</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_comments as $index => $com): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?> py-3">
                                <p class="fs-4 italic text-light">"<?php echo htmlspecialchars($com['message']); ?>"</p>
                                <h5 class="text-warning">- <?php echo htmlspecialchars($com['nom']); ?></h5>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

<?php else: ?>
    <section class="py-5 text-center bg-pure-dark border-bottom border-secondary-dim" style="min-height: 45vh; display: flex; align-items: center;">
        <div class="container">
            <h1 class="text-warning fw-bold display-4 mb-3">COIFFONS — VOTRE SALON DE LUXE À DOMICILE</h1>
            <p class="text-light fs-5 mb-4 mx-auto" style="max-width: 750px;">
                Découvrez, réservez et planifiez vos prestations avec les meilleurs coiffeurs professionnels de votre région. Un système de portefeuille sécurisé, des coiffeurs certifiés et un service sur-mesure directement là où vous le souhaitez.
            </p>
            <p class="text-secondary small mb-4">Découvrez les meilleurs talents de la coiffure à domicile près de chez vous avant de planifier vos bails.</p>
            <a href="/coiffons/access/connexion.php" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow">REJOINDRE LA PLATEFORME / SE CONNECTER</a>
        </div>
    </section>

    <section class="py-5 bg-soft-dark text-white">
        <div class="container">
            <h2 class="text-center text-warning fw-bold mb-2">NOS COIFFURES LES PLUS DEMANDÉES</h2>
            <p class="text-center text-secondary mb-5">Un aperçu des créations de nos professionnels (Tri aléatoire)</p>

            <div class="row g-4">
                <?php foreach ($catalog_demo as $cd): ?>
                    <div class="col-md-4">
                        <div class="card bg-pure-dark border-secondary-dim text-white h-100 shadow-sm card-hover">
                            <div class="card-body d-flex flex-column p-4">
                                <span class="badge bg-warning text-dark align-self-start mb-2"><i class="bi bi-scissors"></i> Style par <?php echo htmlspecialchars($cd['nom_coiffeur']); ?></span>
                                <h4 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($cd['nom_style']); ?></h4>
                                <p class="text-secondary small mb-3"><i class="bi bi-geo-alt text-warning"></i> Localisation : <?php echo htmlspecialchars($cd['ville']); ?> - <?php echo htmlspecialchars($cd['quartier'] ?? 'Général'); ?></p>

                                <h3 class="text-warning fw-bold fs-4 my-3 font-monospace"><?php echo number_format($cd['prix'], 0, ',', ' '); ?> <span class="small">FCFA</span></h3>

                                <div class="mt-auto">
                                    <a href="/coiffons/access/connexion.php?redirect_reason=booking" class="btn btn-outline-warning w-100 fw-bold py-2">PRENDRE RENDEZ-VOUS</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-pure-dark text-white border-top border-secondary-dim">
        <div class="container text-center">
            <h2 class="text-warning mb-5 fw-bold">L'AVIS DE NOS CLIENTS</h2>
            <div id="carouselExampleInvite" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
                <div class="carousel-inner">
                    <?php if (empty($all_comments)): ?>
                        <div class="carousel-item active py-3">
                            <p class="fs-4 italic text-light">"Service impeccable ! La coiffeuse est venue à l'heure et le tressage est super propre. Je recommande !"</p>
                            <h5 class="text-warning">- Mariam B. (Cotonou)</h5>
                        </div>
                        <div class="carousel-item py-3">
                            <p class="fs-4 italic text-light">"Le dégradé américain à domicile est parfait. Plus besoin de faire la queue pendant des heures au salon."</p>
                            <h5 class="text-warning">- Arnaud K. (Calavi)</h5>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_comments as $index => $com): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?> py-3">
                                <p class="fs-4 italic text-light">"<?php echo htmlspecialchars($com['message']); ?>"</p>
                                <h5 class="text-warning">- <?php echo htmlspecialchars($com['nom']); ?></h5>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<style>
    /* Teintes fines pour effet de profondeur haut de gamme */
    .bg-pure-dark {
        background-color: #000000 !important;
    }

    .btn-success {
        background-color: #198754 !important;
        border-color: #198754 !important;
    }

    .btn-success:hover {
        background-color: #157347 !important;
    }

    .btn-danger {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
    }

    .btn-danger:hover {
        background-color: #bb2d3b !important;
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>