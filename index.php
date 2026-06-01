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
    if ($_SESSION['role'] === 'admin') {
        $nb_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
        $nb_coiffeurs = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur'")->fetchColumn();
        
        $total_entrees = $pdo->query("SELECT SUM(montant_commission) FROM transactions_site WHERE type_transaction = 'entree'")->fetchColumn() ?? 0;
        $total_sorties = $pdo->query("SELECT SUM(montant_abonnement) FROM transactions_site WHERE type_transaction = 'sortie'")->fetchColumn() ?? 0;
        $solde_net = $total_entrees - $total_sorties;

        // Jointure pour récupérer le nom textuel de la ville au lieu de l'ID pour l'admin
        $users = $pdo->query("SELECT u.*, v.nom_ville FROM users u LEFT JOIN villes v ON u.ville = v.id ORDER BY u.id DESC")->fetchAll();
        $commentaires = $pdo->query("SELECT c.*, u.nom FROM commentaires c JOIN users u ON c.id_client = u.id ORDER BY c.date_creation DESC")->fetchAll();
        $motifs_suppression = $pdo->query("SELECT * FROM suppressions_comptes ORDER BY date_demande DESC")->fetchAll();
        
    } elseif ($_SESSION['role'] === 'coiffeur') {
        $chk = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $chk->execute([$coiffeur_id]);
        $db_coiffeur = $chk->fetch();

        // Récupération des prestations avec la ville et le quartier réels du coiffeur
        $stmt = $pdo->prepare("SELECT p.*, v.nom_ville as ville, q.nom_quartier as quartier FROM prestations p JOIN users u ON p.id_coiffeur = u.id LEFT JOIN villes v ON u.ville = v.id LEFT JOIN quartiers q ON u.id_quartier = q.id WHERE p.id_coiffeur = ? ORDER BY p.id_prestation DESC");
        $stmt->execute([$coiffeur_id]);
        $mes_coiffures = $stmt->fetchAll();
    }
}

// Extraction géolocalisée pour le Client
if ($role_actuel === 'client') {
    $id_ville_client = $_SESSION['ville'] ?? 0; 
    $nom_ville_affichage = $_SESSION['nom_ville'] ?? 'votre ville'; 

    try {
        $stmt = $pdo->prepare("SELECT p.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville FROM prestations p JOIN users u ON p.id_coiffeur = u.id JOIN villes v ON u.ville = v.id LEFT JOIN quartiers q ON u.id_quartier = q.id WHERE u.role = 'coiffeur' AND u.abonnement_status = 1 AND u.ville = ? ORDER BY RAND() LIMIT 6");
        $stmt->execute([$id_ville_client]);
        $coiffeurs_matching = $stmt->fetchAll();
        
        if (empty($coiffeurs_matching)) {
            $coiffeurs_matching = $pdo->query("SELECT p.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville FROM prestations p JOIN users u ON p.id_coiffeur = u.id JOIN villes v ON u.ville = v.id LEFT JOIN quartiers q ON u.id_quartier = q.id WHERE u.abonnement_status = 1 ORDER BY RAND() LIMIT 6")->fetchAll();
        }
    } catch (Exception $e) {
        $coiffeurs_matching = [];
    }

    if (empty($coiffeurs_matching)) {
        $coiffeurs_matching = $coiffures_par_defaut;
        usort($coiffeurs_matching, function($a, $b) use ($nom_ville_affichage) {
            if ($a['ville'] === $nom_ville_affichage && $b['ville'] !== $nom_ville_affichage) return -1;
            if ($a['ville'] !== $nom_ville_affichage && $b['ville'] === $nom_ville_affichage) return 1;
            return 0;
        });
    }

} elseif ($role_actuel === 'invite') {
    try {
        $catalog_demo = $pdo->query("SELECT p.*, u.nom as nom_coiffeur, v.nom_ville as ville, q.nom_quartier as quartier FROM prestations p JOIN users u ON p.id_coiffeur = u.id JOIN villes v ON u.ville = v.id LEFT JOIN quartiers q ON u.id_quartier = q.id WHERE u.abonnement_status = 1 ORDER BY RAND() LIMIT 6")->fetchAll();
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

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <section class="py-5 bg-pure-dark" style="min-height: 100vh;">
        <div class="container mt-4">
            <h1 class="text-danger fw-bold mb-4"><i class="bi bi-shield-lock-fill"></i> BACK-OFFICE DE SUPÉRIEURE GÉNÉRALE</h1>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="p-4 rounded bg-soft-dark border-start border-success border-4 shadow-sm">
                        <h6 class="text-secondary uppercase small mb-1">Argent Entré (Abonnements Kkiapay)</h6>
                        <span class="fs-3 fw-bold text-success">+ <?php echo number_format($total_entrees, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded bg-soft-dark border-start border-danger border-4 shadow-sm">
                        <h6 class="text-secondary uppercase small mb-1">Argent Sorti (Charges enregistrées)</h6>
                        <span class="fs-3 fw-bold text-danger">- <?php echo number_format($total_sorties, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded bg-soft-dark border-start border-info border-4 shadow-sm">
                        <h6 class="text-secondary uppercase small mb-1">Bénéfice Net Actuel</h6>
                        <span class="fs-3 fw-bold text-info"><?php echo number_format($solde_net, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>
            </div>

            <div class="card bg-soft-dark border-secondary-dim p-4 mb-5">
                <h5 class="text-white mb-3"><i class="bi bi-plus-circle text-danger me-2"></i> Enregistrer un flux sortant (Dépense)</h5>
                <form action="admin_action.php?action=ajouter_depense&id=1" method="POST" class="row g-3">
                    <div class="col-md-4">
                        <input type="number" name="montant" class="form-control bg-pure-dark text-white border-secondary-dim" placeholder="Montant en FCFA" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="motif" class="form-control bg-pure-dark text-white border-secondary-dim" placeholder="Motif de la dépense (ex: Hébergement serveur, Publicité)" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-danger w-100 fw-bold">Valider</button>
                    </div>
                </form>
            </div>

            <div class="card bg-soft-dark border-secondary-dim p-4 mb-5">
                <h4 class="text-white mb-4"><i class="bi bi-people me-2"></i> Comptes users (Total : <?php echo ($nb_clients + $nb_coiffeurs); ?>)</h4>
                <div class="table-responsive">
                    <table class="table table-dark-custom align-middle text-center">
                        <thead>
                            <tr class="text-secondary small">
                                <th>Profil</th><th>Nom</th><th>Email</th><th>Ville</th><th>Rôle</th><th>Abonnement (Coiffeur)</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><img src="<?php echo htmlspecialchars($u['photo_profil'] ?? 'uploads/default.png'); ?>" class="rounded-circle" style="width:35px; height:35px; object-fit:cover;"></td>
                                    <td class="fw-bold text-white"><?php echo htmlspecialchars($u['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['nom_ville'] ?? 'Non spécifiée'); ?></td>
                                    <td><span class="badge <?php echo $u['role'] === 'coiffeur' ? 'bg-warning text-dark' : ($u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'); ?>"><?php echo strtoupper($u['role']); ?></span></td>
                                    <td>
                                        <?php if ($u['role'] === 'coiffeur'): ?>
                                            <span class="badge <?php echo $u['abonnement_status'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $u['abonnement_status'] ? 'Actif jusqu\'au '.$u['date_expiration_abo'] : 'Inactif'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <a href="/coiffons/first/admin_action.php?action=supprimer_user&id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Exclure définitivement ce membre ?');"><i class="bi bi-trash"></i></a>
                                        <?php else: ?>
                                            <span class="text-muted small">Protégé</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card bg-soft-dark border-secondary-dim p-4 mb-5">
                <h4 class="text-white mb-4"><i class="bi bi-archive me-2"></i> Historique des Comptes Supprimés (Raisons de départ)</h4>
                <div class="table-responsive">
                    <table class="table table-dark-custom align-middle">
                        <thead>
                            <tr class="text-secondary small text-center">
                                <th>Utilisateur</th><th>Email</th><th>Ancien Rôle</th><th>Raison / Motif de sa décision</th><th>Date</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($motifs_suppression)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">Aucun compte supprimé enregistré pour le moment.</td></tr>
                            <?php else: ?>
                                <?php foreach ($motifs_suppression as $ms): ?>
                                    <tr>
                                        <td class="text-white fw-bold"><?php echo htmlspecialchars($ms['nom_utilisateur']); ?></td>
                                        <td><?php echo htmlspecialchars($ms['email_utilisateur']); ?></td>
                                        <td class="text-center"><span class="badge bg-secondary"><?php echo strtoupper($ms['role_utilisateur']); ?></span></td>
                                        <td class="text-secondary small"><?php echo nl2br(htmlspecialchars($ms['raison'])); ?></td>
                                        <td class="text-center text-muted small"><?php echo date('d/m/Y H:i', strtotime($ms['date_demande'])); ?></td>
                                        <td class="text-center">
                                            <a href="/coiffons/first/admin_action.php?action=supprimer_log_suppression&id=<?php echo $ms['id_suppression']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card bg-soft-dark border-secondary-dim p-4">
                <h4 class="text-white mb-4"><i class="bi bi-chat-dots me-2"></i> Modération des Commentaires en ligne</h4>
                <div class="table-responsive">
                    <table class="table table-dark-custom align-middle">
                        <thead>
                            <tr class="text-secondary small text-center">
                                <th>Client</th><th>Message / Avis</th><th>Note</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commentaires as $com): ?>
                                <tr>
                                    <td class="fw-bold text-warning"><?php echo htmlspecialchars($com['nom']); ?></td>
                                    <td class="text-light small"><?php echo htmlspecialchars($com['message']); ?></td>
                                    <td class="text-center text-warning"><?php echo str_repeat('⭐', $com['note']); ?></td>
                                    <td class="text-center">
                                        <a href="/coiffons/first/admin_action.php?action=supprimer_commentaire&id=<?php echo $com['id_commentaire']; ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i> Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

<?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'coiffeur'): ?>
    <section class="py-5 bg-pure-dark" style="min-height: 100vh;">
        <div class="container mt-4">
            
            <?php if (!$db_coiffeur['abonnement_status']): ?>
                <div class="row justify-content-center py-5">
                    <div class="col-md-6 text-center bg-soft-dark p-5 rounded border border-warning-dim shadow-lg">
                        <i class="bi bi-credit-card-2-back text-warning display-1 mb-4"></i>
                        <h2 class="text-white fw-bold mb-3">Activation de votre Vitrine Pro</h2>
                        <p class="text-secondary mb-4">Pour publier votre catalogue de coiffures et recevoir des réservations de clients de votre ville, veuillez vous acquitter de votre abonnement mensuel.</p>
                        <div class="bg-pure-dark p-3 rounded mb-4 border border-secondary-dim">
                            <span class="text-muted d-block small">TARIF UNIQUE MENSUEL</span>
                            <span class="fs-2 fw-bold text-success">1500 FCFA / mois</span>
                        </div>
                        
                        <script src="https://cdn.kkiapay.me/k.js"></script>
                        <kkiapay-widget 
                            amount="1500" 
                            key="VOTRE_CLE_PUBLIQUE_KKIAPAY_ICI" 
                            position="center" 
                            sandbox="true" 
                            data="<?php echo $coiffeur_id; ?>"
                            callback="http://localhost/coiffe_chez_toi/kkiapay_webhook.php">
                        </kkiapay-widget>
                    </div>
                </div>
            <?php else: ?>
                <div class="row mb-5 align-items-center">
                    <div class="col-md-8">
                        <h1 class="text-warning fw-bold">SALON DE : <?php echo strtoupper(htmlspecialchars($db_coiffeur['nom'])); ?></h1>
                        <p class="text-success mb-0 small"><i class="bi bi-check-circle-fill"></i> Votre abonnement est actif (Expire le : <?php echo $_SESSION['date_expiration_abo'] ?? 'Aucune';?>)</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="btn btn-warning fw-bold px-4"><i class="bi bi-plus-circle-fill me-2"></i> AJOUTER UN STYLE</a>
                    </div>
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="card bg-soft-dark text-white p-3 text-decoration-none border-secondary-dim h-100 card-hover">
                            <h5 class="text-warning"><i class="bi bi-calendar3 me-2"></i> Configurer mon agenda</h5>
                            <span class="text-secondary small">Définissez vos horaires d'intervention chez les clients.</span>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="card bg-soft-dark text-white p-3 text-decoration-none border-secondary-dim h-100 card-hover">
                            <h5 class="text-success"><i class="bi bi-check2-circle me-2"></i> Gérer mes Rendez-vous</h5>
                            <span class="text-secondary small">Consultez et validez les demandes clients.</span>
                        </a>
                    </div>
                </div>

                <h3 class="text-white mt-5 mb-3 border-bottom border-secondary-dim pb-2">Mon Catalogue Actuel</h3>
                <div class="row g-4">
                    <?php if (empty($mes_coiffures)): ?>
                        <div class="col-12 text-muted small">Vous n'avez pas encore ajouté de style à votre catalogue.</div>
                    <?php else: ?>
                        <?php foreach ($mes_coiffures as $c): ?>
                            <div class="col-md-4">
                                <div class="card bg-soft-dark text-white h-100 border-secondary-dim shadow-sm card-hover">
                                    <div class="card-body d-flex flex-column p-4">
                                        <h4 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($c['nom_style']); ?></h4>
                                        <p class="text-secondary small mb-3">
                                            <i class="bi bi-geo-alt text-warning"></i> Localisation : <?php echo htmlspecialchars($c['ville'] ?? 'Non spécifiée'); ?> - <?php echo htmlspecialchars($c['quartier'] ?? 'Général'); ?>
                                        </p>
                                        
                                        <h3 class="text-warning fw-bold fs-4 my-3 font-monospace"><?php echo number_format($c['prix'], 0, ',', ' '); ?> <span class="small">FCFA</span></h3>
                                        
                                        <div class="mt-auto pt-3 border-top border-secondary-dim d-flex gap-2">
                                            <a href="/coiffons/coiffeurs/gestion_catalogue.php?ouvrir_modifier=<?php echo $c['id_prestation']; ?>" class="btn btn-success btn-sm flex-grow-1 fw-bold text-white py-2" title="Modifier ce style">
                                                <i class="bi bi-pencil-square me-1"></i> Éditer
                                            </a>
                                            <a href="/coiffons/coiffeurs/gestion_catalogue.php?supprimer=<?php echo $c['id_prestation']; ?>" class="btn btn-danger btn-sm px-3 py-2 fw-bold" title="Supprimer ce style" onclick="return confirm('Voulez-vous vraiment supprimer définitivement cette coiffure de votre catalogue ?');">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

<?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
    <section class="py-5 text-center bg-pure-dark border-bottom border-secondary-dim">
        <div class="container py-3">
            <h1 class="text-warning fw-bold display-4 mb-3">COIFFE_CHEZ_TOI — VOTRE SALON DE LUXE À DOMICILE</h1>
            <p class="text-light fs-5 mb-4 mx-auto" style="max-width: 750px;">
                Découvrez, réservez et planifiez vos prestations avec les meilleurs coiffeurs professionnels de votre région. Un système de portefeuille sécurisé, des coiffeurs certifiés et un service sur-mesure directement chez vous.
            </p>
            <p class="text-secondary fs-6 mb-4">Ravi de vous revoir, <strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong> ! Prêt à réserver une coiffure à <?php echo htmlspecialchars($_SESSION['nom_ville'] ?? 'votre ville'); ?> ?</p>
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