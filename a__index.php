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

// Extraction dynamique du nom de l'utilisateur (évite le mot "Client" en brut)
$nom_affichage_client = $_SESSION['nom'] ?? $_SESSION['prenom'] ?? $_SESSION['username'] ?? null;

// =================================================================
// COIFFURES PAR DÉFAUT (Fallback si ta base de données est vide)
// =================================================================
$coiffures_par_defaut = [
    [
        'id_prestation' => 1,
        'id_coiffeur' => 1,
        'nom_style' => 'Gros Rastas Premium',
        'prix' => 7000,
        'nom_coiffeur' => 'Alliance Coiffure',
        'ville' => 'Cotonou',
        'quartier' => 'Fidjrossè',
        'photo_style' => null
    ],
    [
        'id_prestation' => 2,
        'id_coiffeur' => 2,
        'nom_style' => 'Dégradé Américain / Wave',
        'prix' => 3000,
        'nom_coiffeur' => 'Barber Shop Pro',
        'ville' => 'Calavi',
        'quartier' => 'Zogbadjè',
        'photo_style' => null
    ],
    [
        'id_prestation' => 3,
        'id_coiffeur' => 3,
        'nom_style' => 'Nattes Collées Graphiques',
        'prix' => 5000,
        'nom_coiffeur' => 'Divine Mains',
        'ville' => 'Porto-Novo',
        'quartier' => 'Ouando',
        'photo_style' => null
    ]
];

// =================================================================
// LOGIQUE DE RÉCUPÉRATION DES DONNÉES SELON LE RÔLE
// =================================================================
if ($coiffeur_id && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'coiffeur') {
        $chk = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $chk->execute([$coiffeur_id]);
        $db_coiffeur = $chk->fetch();

        $stmt = $pdo->prepare("SELECT p.*, v.nom_ville as ville, q.nom_quartier as quartiers FROM prestations p JOIN users u ON p.id_coiffeur = u.id LEFT JOIN villes v ON u.ville = v.id LEFT JOIN quartiers q ON u.id_quartier = q.id WHERE p.id_coiffeur = ? ORDER BY p.id_prestation DESC");
        $stmt->execute([$coiffeur_id]);
        $mes_coiffures = $stmt->fetchAll();
    }
}

// =================================================================
// 🎯 LOGIQUE DYNAMIQUE (CLIENTS & INVITÉS) AVEC TES VRAIS COIFFEURS
// =================================================================
if ($role_actuel === 'client') {
    $id_ville_client = $_SESSION['ville'] ?? 0;
    $id_quartier_client = $_SESSION['id_quartier'] ?? 0;

    try {
        // NIVEAU 1 : Recherche stricte (Quartier du client via zones_coiffeur)
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

        // NIVEAU 2 : Plan de secours (Même ville globale)
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

        // NIVEAU 3 : Plan de secours ultime (Catalogue global aléatoire de vrais coiffeurs actifs)
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

    if (empty($coiffeurs_matching)) {
        $coiffeurs_matching = $coiffures_par_defaut;
    }
} elseif ($role_actuel === 'invite') {
    try {
        $catalog_demo = $pdo->query("
            SELECT p.*, u.nom as nom_coiffeur, v.nom_ville as ville, q.nom_quartier as quartier 
            FROM prestations p 
            JOIN users u ON p.id_coiffeur = u.id 
            JOIN villes v ON u.ville = v.id 
            LEFT JOIN quartier q ON u.id_quartier = q.id 
            WHERE u.abonnement_status = 1 
            ORDER BY RAND() LIMIT 6
        ")->fetchAll();
    } catch (Exception $e) {
        $catalog_demo = [];
    }

    if (empty($catalog_demo)) {
        $catalog_demo = $coiffures_par_defaut;
    }
}

// Récupération des avis pour l'interface publique
try {
    $query_coms = $pdo->query("SELECT c.*, u.nom FROM commentaires c JOIN users u ON c.id_client = u.id ORDER BY c.date_creation DESC LIMIT 6");
    $all_comments = $query_coms->fetchAll();
} catch (Exception $e) {
    $all_comments = [];
}
?>

<div class="luxury-main-wrapper" style="background: linear-gradient(135deg, #0a0a0a 0%, #121212 100%); color: #fff; min-height: 100vh;">

    <?php if ($role_actuel === 'coiffeur'): ?>
        <section class="py-5">
            <div class="container">
                <div class="row mb-4 text-center">
                    <div class="col-12">
                        <h1 class="text-warning fw-bold h3 mb-1" style="letter-spacing: 0.5px;">
                            SALON DE : <?php echo strtoupper(htmlspecialchars($db_coiffeur['nom'] ?? '')); ?>
                        </h1>
                        <p class="text-success mb-0 small">
                            <i class="bi bi-check-circle-fill"></i> Votre abonnement est actif
                        </p>
                    </div>
                </div>

                <div class="row g-3">
                    <?php if (empty($mes_coiffures)): ?>
                        <div class="col-12 text-center text-muted small py-5">
                            Vous n'avez pas encore ajouté de style à votre catalogue.
                        </div>
                    <?php else: ?>
                        <?php foreach ($mes_coiffures as $c): ?>
                            <div class="col-6 col-md-4">
                                <a href="/coiffons/coiffeurs/gestion_catalogue.php?ouvrir_modifier=<?php echo $c['id_prestation']; ?>" class="text-decoration-none card-clickable-wrapper d-block h-100">
                                    <div class="card luxury-card h-100 border-0">
                                        <div class="card-body d-flex flex-column p-3">
                                            <h4 class="fw-bold text-white mb-1 text-truncate h6"><?php echo htmlspecialchars($c['nom_style'] ?? ''); ?></h4>
                                            <p class="text-secondary small mb-2 text-truncate" style="font-size: 0.7rem;">
                                                <i class="bi bi-geo-alt text-warning"></i> <?php echo htmlspecialchars($c['ville'] ?? 'Non spécifiée'); ?>
                                            </p>
                                            <h3 class="text-warning fw-bold font-monospace my-2 h5" style="font-size: 0.95rem;">
                                                <?php echo number_format($c['prix'], 0, ',', ' '); ?> <span style="font-size: 0.65rem;">FCFA</span>
                                            </h3>
                                            <div class="mt-auto pt-2 d-flex gap-1">
                                                <span class="btn btn-success btn-sm flex-grow-1 py-1" style="font-size: 0.7rem;">
                                                    <i class="bi bi-pencil-square"></i> Éditer
                                                </span>
                                                <a href="/coiffons/coiffeurs/gestion_catalogue.php?supprimer=<?php echo $c['id_prestation']; ?>" class="btn btn-danger btn-sm px-2 py-1 fw-bold" style="font-size: 0.7rem;" onclick="event.stopPropagation(); return confirm('Supprimer ?');">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="row my-4 justify-content-center">
                    <div class="col-12 col-sm-8 col-md-6 text-center">
                        <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="btn btn-warning text-black fw-bold w-100 py-2 shadow" style="border-radius: 30px;">
                            <i class="bi bi-plus-circle-fill me-1"></i> AJOUTER UN STYLE
                        </a>
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="card luxury-card p-3 text-decoration-none h-100 d-flex flex-column align-items-center text-center justify-content-center">
                            <h5 class="text-warning mb-1 h6" style="font-size: 0.85rem;"><i class="bi bi-calendar3 me-1"></i> Mon Agenda</h5>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="card luxury-card p-3 text-decoration-none h-100 d-flex flex-column align-items-center text-center justify-content-center">
                            <h5 class="text-success mb-1 h6" style="font-size: 0.85rem;"><i class="bi bi-check2-circle me-1"></i> Mes Demandes</h5>
                        </a>
                    </div>
                </div>
            </div>
        </section>

    <?php elseif ($role_actuel === 'client'): ?>
        <section class="py-5 text-center">
            <div class="container">
                <h1 class="text-warning fw-bold h2 mb-3">COIFFE_CHEZ_TOI — VOTRE SALON DE LUXE À DOMICILE</h1>
                <p class="text-light opacity-75 small mb-4 mx-auto" style="max-width: 750px;">
                    Découvrez, réservez et planifiez vos prestations avec les meilleurs coiffeurs professionnels de votre région.
                </p>
                <p class="text-secondary small mb-4">
                    ✨ Ravi de vous revoir, <strong><?php echo htmlspecialchars($nom_affichage_client ?? 'Client'); ?></strong> !
                </p>
                <a href="/coiffons/filter/annuaire_coiffeurs.php" class="btn btn-warning text-black fw-bold px-4 py-2.5 shadow-lg small">EXPLORER L'ANNUAIRE DES COIFFEURS</a>
            </div>
        </section>

        <section class="py-4">
            <div class="container">
                <h3 class="text-white mb-4 h5 text-center text-md-start"><i class="bi bi-stars text-warning me-2"></i> DÉCOUVREZ NOS CRÉATIONS EXCLUSIVES</h3>
                <div class="row g-4">
                    <?php foreach ($coiffeurs_matching as $coif): ?>
                        <div class="col-md-4">
                            <a href="/coiffons/coiffeurs/profil_public.php?id=<?php echo $coif['id_coiffeur']; ?>" class="text-decoration-none card-clickable-wrapper d-block h-100">
                                <div class="card luxury-card h-100 border-0 text-white shadow-sm">
                                    <div class="luxury-card-img-container">
                                        <?php if (!empty($coif['photo_style']) && file_exists(__DIR__ . '/uploads/' . $coif['photo_style'])): ?>
                                            <img src="<?php echo '/coiffons/uploads/' . $coif['photo_style']; ?>" class="card-img-top" alt="Style">
                                        <?php else: ?>
                                            <div class="luxury-placeholder-img d-flex flex-column justify-content-center align-items-center">
                                                <i class="bi bi-scissors text-warning opacity-50 display-6 mb-2"></i>
                                                <span class="text-muted text-uppercase font-monospace" style="font-size: 0.6rem; letter-spacing: 1px;">Création Privée</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body d-flex flex-column p-4 text-center align-items-center">
                                        <span class="badge bg-warning text-dark mb-3"><i class="bi bi-person-fill"></i> Par <?php echo htmlspecialchars($coif['nom_coiffeur']); ?></span>
                                        <h4 class="fw-bold text-white mb-1 h5"><?php echo htmlspecialchars($coif['nom_style']); ?></h4>
                                        <p class="text-secondary small mb-3"><i class="bi bi-pin-map text-warning"></i> <?php echo htmlspecialchars($coif['ville']); ?> - <?php echo htmlspecialchars($coif['quartier'] ?? 'Général'); ?></p>
                                        <h3 class="text-warning fw-bold fs-4 my-3 font-monospace"><?php echo number_format($coif['prix'], 0, ',', ' '); ?> <span class="small" style="font-size: 0.8rem;">FCFA</span></h3>
                                        <div class="mt-auto w-100">
                                            <span class="btn btn-outline-warning btn-sm w-100 fw-bold py-2">PRENDRE RDV AVEC <?php echo strtoupper(htmlspecialchars($coif['nom_coiffeur'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    <?php else: ?>
        <section class="py-5 text-center">
            <div class="container">
                <h1 class="text-warning fw-bold h2 mb-3">COIFFE_CHEZ_TOI — VOTRE SALON DE LUXE À DOMICILE</h1>
                <p class="text-light opacity-75 small mb-4 mx-auto" style="max-width: 750px;">
                    Découvrez, réservez et planifiez vos prestations avec les meilleurs coiffeurs professionnels de votre région. Un système de portefeuille sécurisé, des professionnels certifiés et un service sur-mesure directement chez vous.
                </p>
                <p class="text-secondary small mb-4">Découvrez les meilleurs talents de la coiffure à domicile près de chez vous avant de planifier vos bails.</p>
                <a href="/coiffons/access/connexion.php" class="btn btn-warning text-black fw-bold px-4 py-2.5 shadow-lg small">REJOINDRE LA PLATEFORME / SE CONNECTER</a>
                <div class="d-flex gap-3 justify-content-center mt-3">
                    <a href="/coiffons/access/inscription.php?role=client" class="btn btn-outline-warning text-warning fw-bold px-4 py-2 small">JE SUIS CLIENT</a>
                    <a href="/coiffons/access/inscription.php?role=prestataire" class="btn btn-outline-light text-white fw-bold px-4 py-2 small">JE SUIS COIFFEUR</a>
                </div>
            </div>
        </section>

        <section class="py-4">
            <div class="container">
                <h3 class="text-white mb-4 h5 text-center text-md-start"><i class="bi bi-stars text-warning me-2"></i> DÉCOUVREZ NOS CRÉATIONS EXCLUSIVES</h3>
                <div class="row g-4">
                    <?php foreach ($catalog_demo as $cd): ?>
                        <div class="col-md-4">
                            <a href="/coiffons/access/connexion.php" class="text-decoration-none card-clickable-wrapper d-block h-100">
                                <div class="card luxury-card h-100 border-0 text-white shadow-sm">
                                    <div class="luxury-card-img-container">
                                        <?php if (!empty($cd['photo_style']) && file_exists(__DIR__ . '/uploads/' . $cd['photo_style'])): ?>
                                            <img src="<?php echo '/coiffons/uploads/' . $cd['photo_style']; ?>" class="card-img-top" alt="Style">
                                        <?php else: ?>
                                            <div class="luxury-placeholder-img d-flex flex-column justify-content-center align-items-center">
                                                <i class="bi bi-scissors text-warning opacity-50 display-6 mb-2"></i>
                                                <span class="text-muted text-uppercase font-monospace" style="font-size: 0.6rem; letter-spacing: 1px;">Création Privée</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body d-flex flex-column p-4 text-center align-items-center">
                                        <span class="badge bg-warning text-dark mb-3"><i class="bi bi-person-fill"></i> Par <?php echo htmlspecialchars($cd['nom_coiffeur']); ?></span>
                                        <h4 class="fw-bold text-white mb-1 h5"><?php echo htmlspecialchars($cd['nom_style']); ?></h4>
                                        <p class="text-secondary small mb-3"><i class="bi bi-pin-map text-warning"></i> <?php echo htmlspecialchars($cd['ville']); ?> - <?php echo htmlspecialchars($cd['quartier'] ?? 'Général'); ?></p>
                                        <h3 class="text-warning fw-bold fs-4 my-3 font-monospace"><?php echo number_format($cd['prix'], 0, ',', ' '); ?> <span class="small" style="font-size: 0.8rem;">FCFA</span></h3>
                                        <div class="mt-auto w-100">
                                            <span class="btn btn-outline-warning btn-sm w-100 fw-bold py-2">REJOINDRE POUR PRENDRE RDV</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="py-5 mt-4">
        <div class="container text-center">
            <h2 class="text-warning mb-4 fw-bold h4">L'AVIS DE NOS CLIENTS</h2>
            
            <div class="row g-4 justify-content-center">
                <?php if (empty($all_comments)): ?>
                    <div class="col-md-4">
                        <div class="card luxury-card p-4 h-100 text-start">
                            <p class="text-light small italic mb-3">"Service impeccable ! La coiffeuse est venue à l'heure et le tressage est super propre. Je recommande !"</p>
                            <h6 class="text-warning mb-0 small">- Mariam B. (Cotonou)</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card luxury-card p-4 h-100 text-start">
                            <p class="text-light small italic mb-3">"Le dégradé américain à domicile est parfait. Plus besoin de faire la queue pendant des heures au salon."</p>
                            <h6 class="text-warning mb-0 small">- Arnaud K. (Calavi)</h6>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card luxury-card p-4 h-100 text-start">
                            <p class="text-light small italic mb-3">"Simple, rapide et ultra professionnel. Le paiement via portefeuille sécurisé donne vraiment confiance."</p>
                            <h6 class="text-warning mb-0 small">- Carine T. (Porto-Novo)</h6>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_comments as $com): ?>
                        <div class="col-md-4">
                            <div class="card luxury-card p-4 h-100 text-start">
                                <p class="text-light small italic mb-3">"<?php echo htmlspecialchars($com['message']); ?>"</p>
                                <h6 class="text-warning mb-0 small">- <?php echo htmlspecialchars($com['nom']); ?></h6>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

</div>

<style>
    .luxury-main-wrapper section {
        border: none !important;
    }

    .luxury-card {
        background-color: #121212 !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 12px !important;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), border-color 0.3s ease;
    }

    .card-clickable-wrapper:hover .luxury-card {
        transform: translateY(-5px);
        border-color: #D4AF37 !important; 
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5) !important;
    }

    .luxury-card-img-container {
        width: 100%;
        height: 200px;
        overflow: hidden;
        background-color: #1a1a1a;
    }

    .luxury-card-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .luxury-placeholder-img {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #151515 0%, #222222 100%);
    }

    .italic {
        font-style: italic;
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>