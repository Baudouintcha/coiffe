<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. SÉCURITÉ STRICTE : Seul l'admin entre ici
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php");
    exit();
}

// 2. INCLUSION CONFIG ET DÉBOGAGE COMPATIBILITÉ BDD
require_once __DIR__ . '/../security/config.php';

// Si config.php définit $pdo, on s'assure d'avoir la variable $bdd pour compatibilité
if (isset($pdo) && !isset($bdd)) {
    $bdd = $pdo;
}

// 3. FONCTION DE FORMATAGE WHATSAPP STRICT BÉNIN (10 CHIFFRES AVEC LE NOUVEAU PRÉFIXE 01)
function formaterBeninWhatsApp($telephone) {
    $num = preg_replace('/[^0-9]/', '', $telephone);
    if (strpos($num, '00229') === 0) {
        $num = substr($num, 2);
    }
    if (strlen($num) === 10 && strpos($num, '0') === 0) {
        $num = '229' . substr($num, 1);
    } elseif (strlen($num) === 9 && strpos($num, '1') === 0) {
        $num = '229' . $num;
    } elseif (strlen($num) === 8) {
        $num = '22901' . $num;
    }
    return $num;
}

// 4. RÉCUPÉRATION DES STATISTIQUES — tables réelles BDD
try {
    $stat_commission = (float)($bdd->query("SELECT COALESCE(SUM(ABS(montant)), 0) FROM transactions_portefeuille WHERE type_transaction = 'gain_prestation'")->fetchColumn() ?? 0);
    $stat_commission = $stat_commission * 0.05; // 5% de commission plateforme
} catch (Exception $e) { $stat_commission = 0; }

try {
    $stat_abonnements = (float)($bdd->query("SELECT COUNT(*) * 1500 FROM users WHERE role = 'coiffeur' AND abonnement_status = 1")->fetchColumn() ?? 0);
} catch (Exception $e) { $stat_abonnements = 0; }

try {
    $nb_coiffeurs = (int)($bdd->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur'")->fetchColumn());
} catch (Exception $e) { $nb_coiffeurs = 0; }

try {
    $tresorerie_sequestre = (float)($bdd->query("SELECT COALESCE(SUM(solde), 0) FROM users")->fetchColumn() ?? 0);
} catch (Exception $e) { $tresorerie_sequestre = 0; }

// 4b. DIPLÔMES EN ATTENTE (section critique admin)
try {
    $diplomes_attente = $bdd->query("
        SELECT id, nom, prenom, telephone, diplome, created_at
        FROM users
        WHERE role = 'coiffeur'
          AND is_approved = 0
          AND diplome IS NOT NULL
          AND diplome != ''
        ORDER BY created_at ASC
        LIMIT 20
    ")->fetchAll();
} catch (Exception $e) { $diplomes_attente = []; }

// 4c. NOTIFICATIONS ADMIN non lues
try {
    $admin_id = $_SESSION['id_user'] ?? 0;
    $nb_notifs_admin = 0;
    if ($admin_id > 0) {
        $nb_notifs_admin = (int)$bdd->query("SELECT COUNT(*) FROM notifications WHERE id_user = $admin_id AND statut_lecture = 'non_lu'")->fetchColumn();
    }
} catch (Exception $e) { $nb_notifs_admin = 0; }

// 5. DATA POUR LES GRAPHIQUES (CHART.JS)
try {
    $data_sexe = $bdd->query("SELECT sexe, COUNT(*) as total FROM users WHERE role IN ('client', 'coiffeur') GROUP BY sexe")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $data_sexe = []; }

try {
    $top_coiffeurs = $bdd->query("SELECT u.nom, u.prenom, COUNT(r.id) as total_rdv FROM rendez_vous r JOIN users u ON r.coiffeur_id = u.id GROUP BY r.coiffeur_id ORDER BY total_rdv DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $top_coiffeurs = []; }

try {
    $top_prestations = $bdd->query("SELECT p.nom_style, COUNT(r.id) as nb_demandes FROM rendez_vous r JOIN prestations p ON r.coiffure_id = p.id_prestation GROUP BY r.coiffure_id ORDER BY nb_demandes DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $top_prestations = []; }

// 6. MODULES WHATSAPP & DIAGNOSTICS RÉELS
try {
    $relances_coiffeurs = $bdd->query("
        SELECT r.*, uc.nom as nom_coiffeur, uc.prenom as prenom_coiffeur, uc.telephone as tel_coiffeur, ucl.nom as nom_client, ucl.prenom as prenom_client
        FROM rendez_vous r
        JOIN users uc ON r.coiffeur_id = uc.id
        JOIN users ucl ON r.client_id = ucl.id
        WHERE r.statut_rdv = 'en_attente'
        ORDER BY r.date_demande ASC
        LIMIT 20
    ")->fetchAll();
} catch (Exception $e) { $relances_coiffeurs = []; }

// Paniers abandonnés — RDV en attente depuis +30 min
try {
    $paniers_abandonnes = $bdd->query("
        SELECT r.*, ucl.nom as nom_client, ucl.prenom as prenom_client, ucl.telephone as tel_client,
               uc.nom as nom_coiffeur, uc.prenom as prenom_coiffeur
        FROM rendez_vous r
        JOIN users ucl ON r.client_id = ucl.id
        JOIN users uc ON r.coiffeur_id = uc.id
        WHERE r.statut_rdv = 'en_attente'
          AND r.date_demande < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ORDER BY r.date_demande DESC
        LIMIT 20
    ")->fetchAll();
} catch (Exception $e) { $paniers_abandonnes = []; }

// Diagnostics d'activité — tables réelles
try {
    $coiffeurs_inactifs = $bdd->query("SELECT nom, prenom, telephone FROM users WHERE role = 'coiffeur' AND id NOT IN (SELECT DISTINCT coiffeur_id FROM rendez_vous)")->fetchAll();
} catch (Exception $e) { $coiffeurs_inactifs = []; }

try {
    $bugs_transactions = (int)$bdd->query("SELECT COUNT(*) FROM transactions_portefeuille WHERE type_transaction IN ('erreur', 'annule')")->fetchColumn();
} catch (Exception $e) { $bugs_transactions = 0; }

try {
    $clients_sans_rdv = (int)$bdd->query("SELECT COUNT(*) FROM users WHERE role = 'client' AND id NOT IN (SELECT DISTINCT client_id FROM rendez_vous)")->fetchColumn();
} catch (Exception $e) { $clients_sans_rdv = 0; }

// 7. COMPTES & AVIS SIGNALÉS
try {
    $users_list = $bdd->query("SELECT id, nom, prenom, role, email, telephone, statut, created_at FROM users ORDER BY id DESC LIMIT 50")->fetchAll();
} catch (Exception $e) { $users_list = []; }

try {
    $commentaires = $bdd->query("
        SELECT c.*, u.nom, u.prenom
        FROM commentaires c
        JOIN users u ON c.id_client = u.id
        WHERE c.type_commentaire IN ('plainte', 'public')
        ORDER BY c.date_creation DESC
        LIMIT 30
    ")->fetchAll();
} catch (Exception $e) { $commentaires = []; }

// 8. COIFFEURS EN ATTENTE D'ABONNEMENT + RETRAITS
try {
    $abonnements_attente = $bdd->query("
        SELECT id, nom, prenom, telephone, date_expiration_abo
        FROM users
        WHERE role = 'coiffeur'
          AND (abonnement_status = 0 OR date_expiration_abo < CURDATE() OR date_expiration_abo IS NULL)
        ORDER BY created_at DESC
        LIMIT 20
    ")->fetchAll();
} catch (Exception $e) { $abonnements_attente = []; }

try {
    $retraits_attente = $bdd->query("
        SELECT t.*, u.nom, u.prenom, u.telephone
        FROM transactions_portefeuille t
        JOIN users u ON t.user_id = u.id
        WHERE t.type_transaction = 'retrait'
        ORDER BY t.date_creation DESC
        LIMIT 20
    ")->fetchAll();
} catch (Exception $e) { $retraits_attente = []; }
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>S.G. Back-Office - Coiffe Chez Toi</title>
    <!-- CSS Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --gold: #D4AF37;
            --gold-hover: #C9A227;
            --dark-bg: #000000;
            --card-bg: #0c0c0c;
            --border-color: #222222;
        }

        body {
            background-color: var(--dark-bg);
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .text-gold { color: var(--gold); }
        .bg-gold { background-color: var(--gold); color: #000; }
        .border-gold { border-color: var(--gold) !important; }

        .navbar {
            background-color: #000000 !important;
            border-bottom: 2px solid var(--gold);
        }
        .navbar-brand { font-family: 'Playfair Display', serif; letter-spacing: 1px; }

        /* Cartes Premium */
        .card-custom {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-top: 3px solid var(--gold);
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.05);
        }

        /* Navigation Onglets */
        .nav-tabs { border-bottom: 1px solid var(--border-color); }
        .nav-tabs .nav-link {
            color: #888888;
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            padding: 12px 20px;
            transition: all 0.3s;
        }
        .nav-tabs .nav-link:hover { color: var(--gold); }
        .nav-tabs .nav-link.active {
            background-color: transparent;
            color: var(--gold) !important;
            border-bottom: 3px solid var(--gold);
        }

        /* Tableaux */
        .table-premium {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }
        .table-premium th {
            background-color: #050505;
            color: #888888;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--border-color);
            padding: 15px;
        }
        .table-premium td {
            background-color: var(--card-bg);
            color: #ffffff;
            border-bottom: 1px solid #151515;
            padding: 15px;
            vertical-align: middle;
        }

        /* Bouton WhatsApp Vert */
        .btn-whatsapp {
            background-color: #25D366;
            color: #fff;
            border: none;
            transition: all 0.3s;
        }
        .btn-whatsapp:hover {
            background-color: #1ebd54;
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }

        /* Bannières Prévention */
        .prevention-banner {
            background: linear-gradient(90deg, #110000 0%, #330000 100%);
            border: 1px solid #ff3b30;
            border-radius: 8px;
            color: #ff9f0a;
        }
    </style>
</head>
<body>

    <!-- NAVBAR PREMIUM -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-lg">
        <div class="container">
            <span class="navbar-brand fw-bold text-gold fs-4">
                <i class="bi bi-shield-lock-fill me-2"></i>COIFFE CHEZ TOI <span class="fs-6 fw-light text-white-50">| S.G. ADMIN</span>
            </span>
            <div class="ms-auto">
                <a href="../deconnexion.php" class="btn btn-outline-danger btn-sm fw-bold"><i class="bi bi-power"></i> Quitter</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">

        <!-- BANDEAU ANTI-CONTOURNEMENT -->
        <div class="prevention-banner p-3 mb-4 d-flex align-items-center justify-content-between shadow-sm">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-octagon-fill text-danger fs-3 me-3"></i>
                <div>
                    <h6 class="m-0 fw-bold text-white">CHARTE DE VIGILANCE FRAUDE & CONTOURNEMENT</h6>
                    <small class="text-white-50">Tout contournement avéré entraîne le gel immédiat des fonds. Les dénonciations avérées créditent 2 000 FCFA au client.</small>
                </div>
            </div>
            <span class="badge bg-danger text-uppercase px-2 py-1 small">Sécurité active</span>
        </div>

        <!-- EN-TÊTE -->
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h1 class="fw-bold mb-1">TABLEAU DE BORD GLOBAL</h1>
                <p class="text-muted m-0">Gestion unifiée de la conformité, de la finance et de la rétention au Bénin</p>
            </div>
        </div>

        <!-- GESTION DES ALERTES RETOUR COMPOSANT ACTIONS -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show bg-success text-white border-0 shadow-lg mb-4" role="alert">
                <strong><i class="bi bi-check2-circle"></i> Système mis à jour :</strong>
                <?php
                    if ($_GET['success'] === 'depense_ajoutee') echo "La dépense a été enregistrée avec succès.";
                    if ($_GET['success'] === 'user_supprime') echo "L'utilisateur a été exclu définitivement.";
                    if ($_GET['success'] === 'com_supprime') echo "Le commentaire inapproprié a été supprimé.";
                    if ($_GET['success'] === 'log_nettoye') echo "L'historique de suppression a été mis à jour.";
                    if ($_GET['success'] === 'statut_visibilite_maj') echo "L'approbation publique a été modifiée.";
                    if ($_GET['success'] === 'bannissement_maj') echo "Le statut de bannissement a été mis à jour.";
                    if ($_GET['success'] === 'fraude_traitee') echo "La fraude a été résolue, le tricheur banni et le client récompensé (+2 000 FCFA).";
                    if ($_GET['success'] === 'notifications_envoyees') echo "Notifications push de rétention diffusées avec succès.";
                ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- KPI D'ACTIVITÉ -->
        <div class="row g-3 mb-5">
            <div class="col-6 col-lg-3">
                <div class="card-custom p-4 text-center">
                    <h6 class="text-secondary text-uppercase small fw-bold mb-2">Commissions (5%)</h6>
                    <h3 class="text-gold fw-bold m-0"><?php echo number_format($stat_commission, 0, ',', ' '); ?> F</h3>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card-custom p-4 text-center">
                    <h6 class="text-secondary text-uppercase small fw-bold mb-2">Revenus Abonnements</h6>
                    <h3 class="text-gold fw-bold m-0"><?php echo number_format($stat_abonnements, 0, ',', ' '); ?> F</h3>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card-custom p-4 text-center" style="border-top-color: #2ecc71;">
                    <h6 class="text-secondary text-uppercase small fw-bold mb-2">Fonds Séquestres (Site)</h6>
                    <h3 class="text-success fw-bold m-0"><?php echo number_format($tresorerie_sequestre, 0, ',', ' '); ?> F</h3>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card-custom p-4 text-center" style="border-top-color: #0dcaf0;">
                    <h6 class="text-secondary text-uppercase small fw-bold mb-2">Coiffeurs Connectés</h6>
                    <h3 class="text-info fw-bold m-0"><?php echo $nb_coiffeurs; ?></h3>
                </div>
            </div>
        </div>

        <!-- ONGLETS S.G. BOARD -->
        <ul class="nav nav-tabs mb-4 gap-2" id="adminTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="bi-tab" data-bs-toggle="tab" data-bs-target="#bi" type="button"><i class="bi bi-graph-up-arrow me-1"></i> 1. Analyses & BI</button>
            </li>
            <li class="nav-item">
                <button class="nav-link position-relative" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#whatsapp" type="button">
                    <i class="bi bi-whatsapp me-1"></i> 2. Relances WhatsApp
                    <?php if (count($relances_coiffeurs) > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo count($relances_coiffeurs); ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="validations-tab" data-bs-toggle="tab" data-bs-target="#validations" type="button"><i class="bi bi-cash-stack me-1"></i> 3. Validations & Flux</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="moderation-tab" data-bs-toggle="tab" data-bs-target="#moderation" type="button"><i class="bi bi-shield-exclamation me-1"></i> 4. Modération & Comptes</button>
            </li>
        </ul>

        <!-- CONTENU -->
        <div class="tab-content" id="adminTabsContent">

            <!-- 1. ONGLET ANALYSES & BI -->
            <div class="tab-pane fade show active" id="bi">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card-custom p-4">
                            <h6 class="text-secondary fw-bold mb-4 text-center">Répartition de la Communauté</h6>
                            <div class="d-flex justify-content-center" style="height: 250px;">
                                <canvas id="chartSexe"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-custom p-4">
                            <h6 class="text-secondary fw-bold mb-4 text-center">Top 4 Coiffeurs (Volume RDV)</h6>
                            <div style="height: 250px;">
                                <canvas id="chartCoiffeurs"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-custom p-4">
                            <h6 class="text-secondary fw-bold mb-4 text-center">Prestations les plus Demandées</h6>
                            <div style="height: 250px;">
                                <canvas id="chartPrestations"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DIAGNOSTICS INTÉGRÉS -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="card bg-dark border-secondary p-3 h-100">
                            <h6 class="text-danger fw-bold mb-3"><i class="bi bi-person-x-fill"></i> Alerte Rendement (Coiffeurs Inactifs)</h6>
                            <?php if(empty($coiffeurs_inactifs)): ?>
                                <p class="text-muted small mt-2 mb-0">Tous vos coiffeurs ont une activité enregistrée. Excellent !</p>
                            <?php else: ?>
                                <ul class="list-unstyled small mb-0 text-white-50" style="max-height: 140px; overflow-y: auto;">
                                    <?php foreach($coiffeurs_inactifs as $ci): ?>
                                        <li class="border-bottom border-dark py-2">⚠️ <?php echo htmlspecialchars($ci['nom'].' '.$ci['prenom']); ?> (<span class="text-warning"><?php echo htmlspecialchars($ci['telephone']); ?></span>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark border-secondary p-3 h-100 text-center d-flex flex-column justify-content-center">
                            <h6 class="text-warning fw-bold mb-2"><i class="bi bi-bug"></i> Échecs de Transactions Kkiapay</h6>
                            <h2 class="text-white fw-bold my-2"><?php echo $bugs_transactions; ?></h2>
                            <span class="text-secondary small">Échecs / Annulations enregistrés</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark border-secondary p-3 h-100 text-center d-flex flex-column justify-content-center">
                            <h6 class="text-info fw-bold mb-2"><i class="bi bi-cart-x"></i> Taux d'Abandon à l'Inscription</h6>
                            <h2 class="text-white fw-bold my-2"><?php echo $clients_sans_rdv; ?></h2>
                            <span class="text-secondary small">Clients inscrits sans aucun rendez-vous</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. ONGLET RELANCES WHATSAPP (BÉNIN OPTIMISÉ) -->
            <div class="tab-pane fade" id="whatsapp">
                <div class="row g-4">
                    
                    <!-- Coiffeurs en retard (Abonnement payé / RDV en attente) -->
                    <div class="col-lg-6">
                        <div class="card bg-dark border-secondary p-4 h-100">
                            <h5 class="text-white mb-3"><i class="bi bi-scissors text-warning"></i> Coiffeurs en retard de Validation</h5>
                            <p class="text-muted small font-italic">En un clic, l'admin ouvre WhatsApp avec l'API formatée pour le Bénin et marque l'action comme effectuée.</p>
                            <div class="table-responsive">
                                <table class="table table-premium text-center align-middle">
                                    <thead>
                                        <tr>
                                            <th>Coiffeur</th><th>Date Prévue</th><th>Relancer</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($relances_coiffeurs)): ?>
                                            <tr><td colspan="3" class="text-muted">Aucune relance coiffeur en attente.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($relances_coiffeurs as $rc): 
                                                $whatsapp_num = formaterBeninWhatsApp($rc['tel_coiffeur']);
                                                $message_text = "Bonjour " . htmlspecialchars($rc['nom_coiffeur']) . ", vous avez une demande de rendez-vous en attente pour le " . date('d/m/Y', strtotime($rc['date_rdv'])) . " à " . $rc['heure_debut'] . " sur COIFFONS. Merci de vous connecter pour la valider au plus vite.";
                                                $whatsapp_link = "https://wa.me/" . $whatsapp_num . "?text=" . urlencode($message_text);
                                            ?>
                                                <tr>
                                                    <td class="fw-bold text-gold"><?php echo htmlspecialchars($rc['nom_coiffeur'] . ' ' . $rc['prenom_coiffeur']); ?><br><small class="text-muted"><?php echo htmlspecialchars($rc['tel_coiffeur']); ?></small></td>
                                                    <td><?php echo date('d/m', strtotime($rc['date_rdv'])) . ' à ' . htmlspecialchars($rc['heure_debut']); ?></td>
                                                    <td>
                                                        <a href="admin_actions.php?action=marquer_wa_envoye&id=<?php echo $rc['id']; ?>&redirect=<?php echo urlencode($whatsapp_link); ?>" target="_blank" class="btn btn-whatsapp btn-sm fw-bold px-3">
                                                            <i class="bi bi-whatsapp"></i> Relancer
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Paniers Abandonnés (Désintéressement client / Problème technique) -->
                    <div class="col-lg-6">
                        <div class="card bg-dark border-secondary p-4 h-100">
                            <h5 class="text-white mb-3"><i class="bi bi-cart-x text-danger"></i> Paniers Abandonnés (Clients)</h5>
                            <p class="text-muted small">Clients n'ayant pas finalisé leur réservation. Permet d'offrir une assistance rapide via WhatsApp.</p>
                            <div class="table-responsive">
                                <table class="table table-premium text-center align-middle">
                                    <thead>
                                        <tr>
                                            <th>Client</th><th>Style Souhaité</th><th>Support</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($paniers_abandonnes)): ?>
                                            <tr><td colspan="3" class="text-muted">Aucun abandon de panier enregistré.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($paniers_abandonnes as $pa): 
                                                $whatsapp_num = formaterBeninWhatsApp($pa['tel_client']);
                                                $message_text = "Bonjour " . htmlspecialchars($pa['nom_client']) . ", nous avons remarqué que vous n'avez pas pu finaliser votre réservation de coiffure sur COIFFONS. Rencontrez-vous un problème technique avec le paiement Kkiapay ou avez-vous besoin d'assistance ? Répondez-nous directement ici, un agent est à votre écoute !";
                                                $whatsapp_link = "https://wa.me/" . $whatsapp_num . "?text=" . urlencode($message_text);
                                            ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($pa['nom_client'] . ' ' . $pa['prenom_client']); ?><br><small class="text-muted"><?php echo htmlspecialchars($pa['tel_client']); ?></small></td>
                                                    <td class="text-warning">Salon: <?php echo htmlspecialchars($pa['nom_coiffeur']); ?></td>
                                                    <td>
                                                        <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="btn btn-outline-success btn-sm fw-bold px-3">
                                                            <i class="bi bi-whatsapp"></i> Discuter
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 3. ONGLET VALIDATIONS DE FLUX -->
            <div class="tab-pane fade" id="validations">
                <div class="row g-4">
                    
                    <!-- Validations Abonnements -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary p-4 h-100">
                            <h5 class="text-success mb-3 fw-bold small text-uppercase"><i class="bi bi-card-checklist"></i> Abonnements en attente de Validation (1 500 F)</h5>
                            <div class="table-responsive">
                                <table class="table table-premium text-center align-middle">
                                    <thead>
                                        <tr>
                                            <th>Coiffeur</th><th>Téléphone</th><th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($abonnements_attente)): ?>
                                            <tr><td colspan="3" class="text-muted">Aucune attente.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($abonnements_attente as $ab): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($ab['nom'] . ' ' . $ab['prenom']); ?></td>
                                                    <td class="text-warning"><?php echo htmlspecialchars($ab['telephone']); ?></td>
                                                    <td>
                                                        <a href="admin_actions.php?action=valider_abonnement&id=<?php echo $ab['id_abonnement']; ?>" class="btn btn-success btn-sm fw-bold">
                                                            <i class="bi bi-check-circle"></i> Confirmer
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Retraits Mobile Money -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary p-4 h-100">
                            <h5 class="text-warning mb-3 fw-bold small text-uppercase"><i class="bi bi-cash-coin"></i> Retraits Mobile Money</h5>
                            <div class="table-responsive">
                                <table class="table table-premium text-center align-middle">
                                    <thead>
                                        <tr>
                                            <th>Coiffeur</th><th>Net à envoyer</th><th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($retraits_attente)): ?>
                                            <tr><td colspan="3" class="text-muted">Aucune demande de retrait active.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($retraits_attente as $ret): ?>
                                                <tr>
                                                    <td class="fw-bold"><?php echo htmlspecialchars($ret['nom'] . ' ' . $ret['prenom']); ?><br><small class="text-muted">MM: <?php echo htmlspecialchars($ret['telephone']); ?></small></td>
                                                    <td class="text-gold fw-bold"><?php echo number_format(abs($ret['montant_net']), 0, ',', ' '); ?> F</td>
                                                    <td>
                                                        <a href="admin_actions.php?action=valider_retrait&id=<?php echo $ret['id_transaction']; ?>" class="btn btn-warning btn-sm text-dark fw-bold">
                                                            <i class="bi bi-send-check-fill"></i> Effectué
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- 4. ONGLET MODÉRATION ET COMPTES -->
            <div class="tab-pane fade" id="moderation">
                
                <!-- Arbitrage Fraude & Signalement -->
                <div class="card bg-dark border-danger p-4 mb-4">
                    <h5 class="text-danger fw-bold mb-3"><i class="bi bi-shield-alert"></i> Traitement d'Évitement & Dénonciation</h5>
                    <p class="text-muted small">Sanctionnez un coiffeur pour évitement de commission, et attribuez automatiquement les 2 000 FCFA de récompense au dénonciateur.</p>
                    
                    <form action="admin_actions.php?action=valider_fraude_contournement" method="POST" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small text-secondary">ID Plainte / Ticket</label>
                            <input type="number" name="plainte_id" class="form-control bg-black text-white border-secondary" required placeholder="Ex: 1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-secondary">Coiffeur à Bannir (ID)</label>
                            <input type="number" name="coiffeur_id" class="form-control bg-black text-white border-secondary" required placeholder="Ex: 14">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-secondary">Client à Créditer +2000F (ID)</label>
                            <input type="number" name="client_id" class="form-control bg-black text-white border-secondary" required placeholder="Ex: 8">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-danger w-100 fw-bold text-uppercase">Bannir & Récompenser</button>
                        </div>
                    </form>
                </div>

                <!-- Gestion globale des comptes -->
                <div class="card bg-dark border-secondary p-4 mb-4">
                    <h5 class="text-white fw-bold mb-3"><i class="bi bi-people-fill text-gold"></i> Gestion de la Communauté</h5>
                    <div class="table-responsive">
                        <table class="table table-premium text-center align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th>Visibilité</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users_list as $u): ?>
                                    <tr>
                                        <td>#<?php echo $u['id']; ?></td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($u['nom'] . ' ' . $u['prenom']); ?></td>
                                        <td><span class="badge <?php echo $u['role'] === 'coiffeur' ? 'bg-warning text-dark' : 'bg-secondary'; ?>"><?php echo strtoupper($u['role']); ?></span></td>
                                        <td>
                                            <span class="badge <?php echo $u['statut'] === 'actif' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo strtoupper($u['statut']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'coiffeur'): ?>
                                                <a href="admin_actions.php?action=toggle_approbation&id=<?php echo $u['id']; ?>" class="btn btn-sm <?php echo $u['is_approved'] ? 'btn-success' : 'btn-outline-info'; ?>">
                                                    <?php echo $u['is_approved'] ? '✓ Approuvé (En ligne)' : 'En attente'; ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form action="admin_actions.php?action=toggle_bannissement" method="POST" class="d-inline">
                                                <input type="hidden" name="id_user" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="raison_ban" value="Manquement aux règles">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <?php echo $u['statut'] === 'actif' ? 'Bannir' : 'Réactiver'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Envoi Notification de Rétention -->
                <div class="card bg-dark border-secondary p-4">
                    <h5 class="text-white fw-bold mb-3"><i class="bi bi-send-fill text-warning"></i> Diffuser une Notification de Rétention (In-App)</h5>
                    <form action="admin_actions.php?action=pousser_notification" method="POST" class="row g-3">
                        <div class="col-md-3">
                            <select class="form-select bg-black text-white border-secondary" name="cible_role">
                                <option value="tous">Tous les utilisateurs</option>
                                <option value="coiffeur">Coiffeurs uniquement</option>
                                <option value="client">Clients uniquement</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <input type="text" name="message" class="form-control bg-black text-white border-secondary" required placeholder="Votre message flash (ex: Profitez d'une coiffure à domicile ce week-end !)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning w-100 fw-bold text-dark text-uppercase"><i class="bi bi-send-fill"></i> Diffuser</button>
                        </div>
                    </form>
                </div>

            </div>

        </div>

    </div>

    <!-- SCRIPT CHARTS DÉPORTÉ EN JSON SANS BRUTALITÉ -->
    <script>
        window.onload = function() {
            // Chart 1: Sexe des utilisateurs
            const ctxSexe = document.getElementById('chartSexe').getContext('2d');
            new Chart(ctxSexe, {
                type: 'doughnut',
                data: {
                    labels: [<?php foreach($data_sexe as $d) { echo '"' . strtoupper($d['sexe']) . '",'; } ?>],
                    datasets: [{
                        data: [<?php foreach($data_sexe as $d) { echo $d['total'] . ','; } ?>],
                        backgroundColor: ['#D4AF37', '#ffffff', '#444444'],
                        borderWidth: 0
                    }]
                },
                options: { 
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { color: '#888' } } } 
                }
            });

            // Chart 2: Performance Coiffeurs
            const ctxCoif = document.getElementById('chartCoiffeurs').getContext('2d');
            new Chart(ctxCoif, {
                type: 'bar',
                data: {
                    labels: [<?php foreach($top_coiffeurs as $tc) { echo '"' . htmlspecialchars($tc['prenom']) . '",'; } ?>],
                    datasets: [{
                        data: [<?php foreach($top_coiffeurs as $tc) { echo $tc['total_rdv'] . ','; } ?>],
                        backgroundColor: '#D4AF37'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { grid: { color: '#151515' }, ticks: { color: '#888' } },
                        x: { grid: { display: false }, ticks: { color: '#888' } }
                    },
                    plugins: { legend: { display: false } }
                }
            });

            // Chart 3: Prestations demandées
            const ctxPrest = document.getElementById('chartPrestations').getContext('2d');
            new Chart(ctxPrest, {
                type: 'line',
                data: {
                    labels: [<?php foreach($top_prestations as $tp) { echo '"' . htmlspecialchars($tp['nom_style']) . '",'; } ?>],
                    datasets: [{
                        data: [<?php foreach($top_prestations as $tp) { echo $tp['nb_demandes'] . ','; } ?>],
                        borderColor: '#2ecc71',
                        backgroundColor: '#2ecc71',
                        tension: 0.3,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { grid: { color: '#151515' }, ticks: { color: '#888' } },
                        x: { grid: { display: false }, ticks: { color: '#888' } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>