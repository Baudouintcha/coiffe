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

// JOINTURE SQL POUR RÉCUPÉRER LES NOMS DE VILLES ET QUARTIERS À LA PLACE DES IDs
$stmt = $pdo->prepare("
    SELECT u.*, v.nom_ville AS nom_ville_clean, q.nom_quartier AS nom_quartier_clean 
    FROM users u
    LEFT JOIN villes v ON u.ville = v.id
    LEFT JOIN quartiers q ON u.id_quartier = q.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Si l'utilisateur est un admin, son dashboard est sur l'index
if ($user['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

// HARMONISATION DES COMPTEURS EN TEMPS RÉEL
$bloc1_val = 0; $bloc1_lbl = ""; $bloc1_icon = ""; $bloc1_link = ""; $bloc1_sub = "";
$bloc2_val = 0; $bloc2_lbl = ""; $bloc2_icon = ""; $bloc2_link = ""; $bloc2_sub = "";
$bloc3_val = 0; $bloc3_lbl = ""; $bloc3_icon = ""; $bloc3_link = ""; $bloc3_sub = "";
$bloc4_val = ""; $bloc4_lbl = ""; $bloc4_icon = ""; $bloc4_link = ""; $bloc4_sub = "";

$argent_gele = 0;

// Initialisation des variables partagées (Graphiques & Avis)
$stats_rdv = ['en_attente' => 0, 'accepte' => 0, 'termine' => 0, 'annule' => 0];
$stats_villes = []; 
$stats_mensuelles = array_fill(1, 12, 0); 
$commentaires = [];
$note_moyenne = 0;
$total_avis = 0;

if ($user['role'] === 'coiffeur') {
    
    // REQUÊTES DES AVIS (COIFFEUR)
    try {
        $stmt_com = $pdo->prepare("
            SELECT r.date_creation, r.commentaire, r.note, u.nom, u.prenom 
            FROM rendez_vous r
            JOIN users u ON r.client_id = u.id
            WHERE r.coiffeur_id = ? AND r.commentaire IS NOT NULL
            ORDER BY r.date_creation DESC LIMIT 10
        ");
        $stmt_com->execute([$user_id]);
        $commentaires = $stmt_com->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $stmt_note = $pdo->prepare("SELECT AVG(note) as moyenne, COUNT(*) as total FROM rendez_vous WHERE coiffeur_id = ? AND note IS NOT NULL");
        $stmt_note->execute([$user_id]);
        $note_data = $stmt_note->fetch();
        $note_moyenne = $note_data['moyenne'] ?? 0;
        $total_avis = $note_data['total'] ?? 0;
    } catch (Exception $e) {}

    // BLOC 1 : Demandes en attente
    $bloc1_lbl = "Demandes en attente";
    $bloc1_icon = "bi-calendar-check";
    $bloc1_link = "/coiffons/coiffeurs/valider_rendezvous.php";
    $bloc1_sub = "Voir les demandes →";
    try {
        $stmt_rdv = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE coiffeur_id = ? AND statut_rdv = 'en_attente'");
        $stmt_rdv->execute([$user_id]);
        $bloc1_val = intval($stmt_rdv->fetchColumn());
    } catch (Exception $e) { $bloc1_val = 0; }

    // BLOC 2 : Gains sécurisés
    $bloc2_lbl = "Gains sécurisés";
    $bloc2_icon = "bi-shield-lock";
    $bloc2_link = "#solde-section";
    $bloc2_sub = "Rendez-vous à venir";
    try {
        $stmt_gains_encours = $pdo->prepare("
            SELECT SUM(p.prix) FROM rendez_vous r 
            JOIN prestations p ON r.coiffure_id = p.id_prestation 
            WHERE r.coiffeur_id = ? AND r.statut_rdv IN ('en_attente', 'accepte')
        ");
        $stmt_gains_encours->execute([$user_id]);
        $gains_futurs = $stmt_gains_encours->fetchColumn() ?? 0;
        $bloc2_val = number_format($gains_futurs, 0, ',', ' ') . " <span style='font-size:0.75rem;'>FCFA</span>";
    } catch (Exception $e) { $bloc2_val = "0 <span style='font-size:0.75rem;'>FCFA</span>"; }

    // BLOC 3 : Zones d'intervention
    $bloc3_lbl = "Quartiers couverts";
    $bloc3_icon = "bi-geo-alt";
    $bloc3_link = "/coiffons/coiffeurs/mes_zones.php";
    $bloc3_sub = "Modifier mes zones →";
    try {
        $stmt_zones = $pdo->prepare("SELECT COUNT(*) FROM zones_coiffeur WHERE id_coiffeur = ?");
        $stmt_zones->execute([$user_id]);
        $bloc3_val = intval($stmt_zones->fetchColumn()) . " <span style='font-size:0.75rem; color:#888;'>zones</span>";
    } catch (Exception $e) { $bloc3_val = "0 <span style='font-size:0.75rem; color:#888;'>zones</span>"; }

    // BLOC 4 : Clients uniques
    $bloc4_lbl = "Clients fidélisés";
    $bloc4_icon = "bi-people";
    $bloc4_link = "#";
    $bloc4_sub = "Total clients uniques";
    try {
        $stmt_clients = $pdo->prepare("SELECT COUNT(DISTINCT client_id) FROM rendez_vous WHERE coiffeur_id = ?");
        $stmt_clients->execute([$user_id]);
        $bloc4_val = intval($stmt_clients->fetchColumn());
    } catch (Exception $e) { $bloc4_val = 0; }

    // 📊 GRAPH 1 (COIFFEUR)
    try {
        $stmt_chart_rdv = $pdo->prepare("SELECT statut_rdv, COUNT(*) as total FROM rendez_vous WHERE coiffeur_id = ? GROUP BY statut_rdv");
        $stmt_chart_rdv->execute([$user_id]);
        while ($row = $stmt_chart_rdv->fetch()) {
            if (array_key_exists($row['statut_rdv'], $stats_rdv)) {
                $stats_rdv[$row['statut_rdv']] = intval($row['total']);
            }
        }
    } catch (Exception $e) {}

    // 📊 GRAPH 2 (COIFFEUR)
    try {
        $stmt_chart_zones = $pdo->prepare("
            SELECT v.nom_ville, COUNT(zc.id_quartier) as total 
            FROM zones_coiffeur zc
            JOIN quartiers q ON zc.id_quartier = q.id
            JOIN villes v ON q.id_ville = v.id
            WHERE zc.id_coiffeur = ?
            GROUP BY v.nom_ville
        ");
        $stmt_chart_zones->execute([$user_id]);
        $stats_villes = $stmt_chart_zones->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {}

} else {
    // REQUÊTES DES AVIS LAISSÉS (CLIENT)
    try {
        $stmt_com = $pdo->prepare("
            SELECT r.date_creation, r.commentaire, r.note, u.nom, u.prenom 
            FROM rendez_vous r
            JOIN users u ON r.coiffeur_id = u.id
            WHERE r.client_id = ? AND r.commentaire IS NOT NULL
            ORDER BY r.date_creation DESC LIMIT 10
        ");
        $stmt_com->execute([$user_id]);
        $commentaires = $stmt_com->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // BLOC 1 : Rendez-vous prévus
    $bloc1_lbl = "Rendez-vous prévus";
    $bloc1_icon = "bi-calendar3";
    $bloc1_link = "/coiffons/client/mes_rendezvous.php";
    $bloc1_sub = "Consulter mon agenda →";
    try {
        $stmt_rdv_cl = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE client_id = ? AND statut_rdv IN ('en_attente', 'accepte')");
        $stmt_rdv_cl->execute([$user_id]);
        $bloc1_val = intval($stmt_rdv_cl->fetchColumn());
    } catch (Exception $e) { $bloc1_val = 0; }

    // BLOC 2 : Fonds en attente
    $bloc2_lbl = "Fonds en attente";
    $bloc2_icon = "bi-lock-fill";
    $bloc2_link = "#solde-section";
    $bloc2_sub = "Paiements sécurisés";
    try {
        $stmt_gele = $pdo->prepare("
            SELECT SUM(p.prix) as total_gele 
            FROM rendez_vous r
            JOIN prestations p ON r.coiffure_id = p.id_prestation
            WHERE r.client_id = ? AND r.statut_rdv = 'en_attente'
        ");
        $stmt_gele->execute([$user_id]);
        $result_gele = $stmt_gele->fetch();
        $argent_gele = $result_gele['total_gele'] ?? 0;
        $bloc2_val = number_format($argent_gele, 0, ',', ' ') . " <span style='font-size:0.75rem;'>FCFA</span>";
    } catch (Exception $e) { $bloc2_val = "0 <span style='font-size:0.75rem;'>FCFA</span>"; }

    // BLOC 3 : Coiffeurs visités
    $bloc3_lbl = "Coiffeurs testés";
    $bloc3_icon = "bi-scissors";
    $bloc3_link = "/coiffons/index.php";
    $bloc3_sub = "Prendre un nouveau RDV →";
    try {
        $stmt_coiff_visite = $pdo->prepare("SELECT COUNT(DISTINCT coiffeur_id) FROM rendez_vous WHERE client_id = ? AND statut_rdv = 'termine'");
        $stmt_coiff_visite->execute([$user_id]);
        $bloc3_val = intval($stmt_coiff_visite->fetchColumn());
    } catch (Exception $e) { $bloc3_val = 0; }

    // BLOC 4 : Statut
    $bloc4_val = "<span class='text-success' style='font-size:0.85rem; font-weight:700;'><i class='bi bi-circle-fill me-1' style='font-size:7px;'></i> ACTIF</span>";
    $bloc4_lbl = "Statut du compte";
    $bloc4_icon = "bi-shield-check";
    $bloc4_link = "#";
    $bloc4_sub = "Profil vérifié";

    // 📊 GRAPH 1 (CLIENT)
    try {
        $stmt_chart_rdv_cl = $pdo->prepare("SELECT statut_rdv, COUNT(*) as total FROM rendez_vous WHERE client_id = ? GROUP BY statut_rdv");
        $stmt_chart_rdv_cl->execute([$user_id]);
        while ($row = $stmt_chart_rdv_cl->fetch()) {
            if (array_key_exists($row['statut_rdv'], $stats_rdv)) {
                $stats_rdv[$row['statut_rdv']] = intval($row['total']);
            }
        }
    } catch (Exception $e) {}

    // 📊 GRAPH 2 (CLIENT)
    try {
        $stmt_chart_mois = $pdo->prepare("
            SELECT MONTH(date_creation) as mois, COUNT(*) as total 
            FROM rendez_vous 
            WHERE client_id = ? AND YEAR(date_creation) = YEAR(CURDATE()) 
            GROUP BY MONTH(date_creation)
        ");
        $stmt_chart_mois->execute([$user_id]);
        while ($row = $stmt_chart_mois->fetch()) {
            $stats_mensuelles[intval($row['mois'])] = intval($row['total']);
        }
    } catch (Exception $e) {}
}

$lien_rendezvous = ($user['role'] === 'client') ? "/coiffons/client/mes_rendezvous.php" : "/coiffons/coiffeurs/agenda_coiffeurs.php";

// LOGIQUE DE TRAITEMENT : SUPPRESSION DU COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    $raison = htmlspecialchars(trim($_POST['raison_depart']));
    
    if (!empty($raison)) {
        $log = $pdo->prepare("INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) VALUES (?, ?, ?, ?)");
        $log->execute([$user['nom'], $user['email'], $user['role'], $raison]);
        
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?"); 
        $del->execute([$user_id]);
        
        session_destroy();
        
        echo "<script>
                alert('Votre compte et vos données ont été définitivement supprimés.');
                window.location.href='index.php';
              </script>";
        exit();
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<section class="py-5" style="background: linear-gradient(135deg, #050505 0%, #0d0d0d 100%); min-height: 95vh; color: #fff;">
    <div class="container mt-4">
        
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="text-warning fw-bold h4 mb-1" style="letter-spacing: 2px;">MON ESPACE PERSONNEL</h2>
                <p class="text-secondary small text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Suivi de vos activités et de vos réservations</p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card p-4 h-100 text-center custom-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <img src="<?php echo !empty($user['photo_profil']) ? htmlspecialchars($user['photo_profil']) : 'uploads/profil/default.png'; ?>" 
                             class="rounded-circle border-gold-premium shadow" 
                             style="width: 120px; height: 120px; object-fit: cover;" alt="Avatar">
                        <span class="position-absolute bottom-0 end-0 bg-success border border-dark rounded-circle p-1.5" style="transform: translate(-10%, -10%);"></span>
                    </div>
                    
                    <h3 class="text-white fw-bold h5 mb-1 text-truncate"><?php echo htmlspecialchars(strtoupper($user['nom'])) . ' ' . htmlspecialchars($user['prenom']); ?></h3>
                    
                    <div class="mb-4">
                        <span class="badge-role-luxury">
                            <i class="bi <?php echo $user['role'] === 'coiffeur' ? 'bi-scissors' : 'bi-person-fill'; ?> me-1 text-warning"></i>
                            COMPTE // <?php echo strtoupper($user['role']); ?>
                        </span>
                    </div>

                    <?php if ($user['role'] === 'coiffeur'): ?>
                        <div class="metric-box highlight-metric p-3 mb-4 text-center">
                            <span class="text-secondary small text-uppercase fw-bold d-block mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Réputation globale</span>
                            <div class="d-flex align-items-center justify-content-center">
                                <span class="h3 fw-bold text-warning mb-0 me-2"><?php echo number_format($note_moyenne, 1); ?></span>
                                <div class="text-start">
                                    <div class="text-warning small" style="line-height: 1;">
                                        <?php for($i=1; $i<=5; $i++) echo $i <= round($note_moyenne) ? '★' : '☆'; ?>
                                    </div>
                                    <span class="text-muted extra-small" style="font-size: 0.65rem; font-weight: 600;"><?php echo $total_avis; ?> avis</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr style="border-color: rgba(255,255,255,0.06);" class="my-3">

                    <div class="d-grid gap-2 mt-2">
                        <a href="modifier_profil.php" class="btn btn-gold-action fw-bold py-2">
                            <i class="bi bi-pencil-square me-2"></i> MODIFIER MON PROFIL
                        </a>
                        
                        <button type="button" class="btn btn-outline-luxury fw-bold py-2" data-bs-toggle="modal" data-bs-target="#commentsModal">
                            <span class="pulse-dot"></span> 
                            <?php echo $user['role'] === 'coiffeur' ? 'HISTORIQUE DES AVIS RECUS' : 'MES DERNIERS AVIS LAISSÉS'; ?>
                        </button>

                        <a href="<?php echo $lien_rendezvous; ?>" class="btn btn-outline-luxury fw-bold py-2">
                            <i class="bi bi-calendar3 me-2"></i> MES RENDEZ-VOUS
                        </a>
                        <a href="/coiffons/deconnexion.php" class="btn btn-outline-danger-silent fw-bold py-2">
                            <i class="bi bi-box-arrow-left me-2"></i> SE DÉCONNECTER
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-4 h-100 custom-profile-card">
                    
                    <h5 class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-cpu-fill me-2"></i> VOS STATISTIQUES EN TEMPS RÉEL</h5>
                    
                    <div class="row g-2 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="metric-box">
                                <div>
                                    <i class="bi <?php echo $bloc1_icon; ?> text-warning fs-5 d-block mb-1"></i>
                                    <span class="text-secondary d-block text-truncate" style="font-size: 0.65rem; font-weight: bold;"><?php echo $bloc1_lbl; ?></span>
                                </div>
                                <h4 class="fw-bold my-1 text-white h5"><?php echo $bloc1_val; ?></h4>
                                <a href="<?php echo $bloc1_link; ?>" class="text-warning text-decoration-none d-block small mt-1" style="font-size: 0.6rem; font-weight: 600;"><?php echo $bloc1_sub; ?></a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="metric-box highlight-metric">
                                <div>
                                    <i class="bi <?php echo $bloc2_icon; ?> text-warning fs-5 d-block mb-1"></i>
                                    <span class="text-secondary d-block text-truncate" style="font-size: 0.65rem; font-weight: bold;"><?php echo $bloc2_lbl; ?></span>
                                </div>
                                <h4 class="fw-bold my-1 text-white h6"><?php echo $bloc2_val; ?></h4>
                                <span class="text-muted d-block small mt-1" style="font-size: 0.55rem; font-weight: 600;"><span class="pulse-dot"></span> <?php echo $bloc2_sub; ?></span>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="metric-box">
                                <div>
                                    <i class="bi <?php echo $bloc3_icon; ?> text-warning fs-5 d-block mb-1"></i>
                                    <span class="text-secondary d-block text-truncate" style="font-size: 0.65rem; font-weight: bold;"><?php echo $bloc3_lbl; ?></span>
                                </div>
                                <h4 class="fw-bold my-1 text-white h5"><?php echo $bloc3_val; ?></h4>
                                <a href="<?php echo $bloc3_link; ?>" class="text-warning text-decoration-none d-block small mt-1" style="font-size: 0.6rem; font-weight: 600;"><?php echo $bloc3_sub; ?></a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="metric-box">
                                <div>
                                    <i class="bi <?php echo $bloc4_icon; ?> text-warning fs-5 d-block mb-1"></i>
                                    <span class="text-secondary d-block text-truncate" style="font-size: 0.65rem; font-weight: bold;"><?php echo $bloc4_lbl; ?></span>
                                </div>
                                <div class="my-1"><?php echo $bloc4_val; ?></div>
                                <span class="text-muted d-block small mt-1" style="font-size: 0.6rem;"><?php echo $bloc4_sub; ?></span>
                            </div>
                        </div>
                    </div>

                    <h5 class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-bar-chart-line-fill me-2"></i> ANALYSE ET COUVERTURE VISUELLE</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-black rounded border border-secondary text-center">
                                <span class="text-secondary d-block mb-2" style="font-size: 11px; font-weight:600;">
                                    <?php echo $user['role'] === 'coiffeur' ? 'SITUATION DES DEMANDES' : 'STATUTS DE MES RÉSERVATIONS'; ?>
                                </span>
                                <div style="max-height: 160px; display: flex; justify-content: center;">
                                    <canvas id="chartRdv"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-black rounded border border-secondary text-center">
                                <span class="text-secondary d-block mb-2" style="font-size: 11px; font-weight:600;">
                                    <?php echo $user['role'] === 'coiffeur' ? 'QUARTIERS COUVERTS PAR VILLE' : 'FRÉQUENCE DE MES SÉANCES (2026)'; ?>
                                </span>
                                <div style="max-height: 160px; display: flex; justify-content: center;">
                                    <canvas id="chartDynamiqueRole"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 id="solde-section" class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-wallet2 me-2"></i> MON PORTEFEUILLE NUMÉRIQUE</h5>
                    <div class="p-3 mb-4 rounded border-luxury-gold" style="background: rgba(255,255,255,0.01);">
                        <div class="row align-items-center">
                            <div class="col-md-7 mb-3 mb-md-0">
                                <div class="mb-2">
                                    <span class="text-secondary small d-block text-uppercase" style="font-size:0.6rem;">Votre solde actuel disponible :</span>
                                    <span class="text-white fw-bold fs-4">
                                        <?php echo number_format($user['solde'] ?? 0, 0, ',', ' '); ?> <span class="text-warning small fs-6">FCFA</span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <?php if ($user['role'] === 'client'): ?>
                                    <button type="button" class="btn btn-gold-action btn-sm px-3 fw-bold w-100 py-2" data-bs-toggle="modal" data-bs-target="#modalRechargeClient">
                                        <i class="bi bi-cash-coin me-1"></i> RECHARGER MON COMPTE
                                    </button>
                                <?php else: ?>
                                    <a href="/coiffons/coiffeurs/portefeuille.php" class="btn btn-outline-luxury btn-sm px-3 fw-bold w-100 py-2">
                                        <i class="bi bi-graph-up-arrow me-1"></i> SUIVRE MES GAINS
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h5 class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-card-text me-2"></i> MES INFORMATIONS PERSONNELLES</h5>
                    <div class="bg-black p-3 rounded border border-secondary mb-4">
                        <div class="row g-3" style="font-size: 0.75rem;">
                            <div class="col-sm-6 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">ADRESSE EMAIL :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="col-sm-6 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">NUMÉRO WHATSAPP :</span>
                                <span class="text-white fw-bold text-warning"><?php echo htmlspecialchars($user['telephone']); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">GENRE :</span>
                                <span class="text-white fw-bold text-uppercase"><?php echo htmlspecialchars($user['sexe'] === 'femme' ? 'Femme' : 'Homme'); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">VILLE :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['nom_ville_clean'] ?? 'Non spécifiée'); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">QUARTIER actuel :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['nom_quartier_clean'] ?? 'Général'); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['role'] === 'coiffeur'): ?>
                        <h5 class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-patch-check me-2"></i> STATUT PROFESSIONNEL</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="bg-black p-3 rounded border border-secondary h-100 d-flex flex-column justify-content-center">
                                    <span class="text-secondary small d-block mb-1 text-uppercase" style="font-size: 0.6rem;">Autorisation sur la plateforme :</span>
                                    <div>
                                        <?php if (isset($user['abonnement_actif']) && $user['abonnement_actif'] == 1): ?>
                                            <span class="badge bg-success px-3 py-1.5 small fw-bold">COMPTE ACTIF // AUTORISÉ</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-3 py-1.5 small fw-bold mb-2 d-inline-block">ABONNEMENT EXPIRÉ</span>
                                            <a href="renouveler_abonnement.php" class="d-block text-warning small fw-bold text-decoration-none" style="font-size:0.65rem;">Régulariser ma situation →</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-black p-3 rounded border border-secondary h-100">
                                    <span class="text-secondary small d-block mb-2" style="font-size: 0.6rem;">Diplôme ou Attestation :</span>
                                    <?php if (!empty($user['diplome'])): ?>
                                        <?php if (pathinfo($user['diplome'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                            <a href="<?php echo htmlspecialchars($user['diplome']); ?>" target="_blank" class="btn btn-sm btn-outline-warning w-100 py-2" style="font-size:0.65rem;">
                                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> VOIR LE DOCUMENT PDF
                                            </a>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <img src="<?php echo htmlspecialchars($user['diplome']); ?>" class="img-thumbnail bg-dark border-secondary cursor-pointer" style="max-height: 55px; object-fit: contain;" data-bs-toggle="modal" data-bs-target="#diplomeModal" alt="Diplôme">
                                                <span class="d-block text-muted text-center" style="font-size: 9px; margin-top: 3px;">Cliquez pour agrandir</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-danger small fw-bold" style="font-size:0.65rem;"><i class="bi bi-exclamation-circle me-1"></i> AUCUN DOCUMENT ENREGISTRÉ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card p-3 mt-auto border-danger-silent" style="background-color: #080808;">
                        <h6 class="text-danger fw-bold mb-1 small" style="letter-spacing: 0.5px;"><i class="bi bi-exclamation-triangle-fill me-2"></i> ZONE DE DANGER // SUPPRESSION DU COMPTE</h6>
                        <p class="text-secondary mb-3" style="font-size: 11px; line-height: 1.4;">
                            Cette action supprimera définitivement votre compte, l'historique de vos rendez-vous et vos soldes. Cette opération est irréversible.
                        </p>

                        <button class="btn btn-sm btn-outline-danger w-100 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#formulaireSuppression" style="font-size: 11px; border-radius: 6px;">
                            <i class="bi bi-trash3 me-1"></i> DEMANDER LA SUPPRESSION DE MON COMPTE
                        </button>

                        <div class="collapse mt-3" id="formulaireSuppression">
                            <div class="p-3 rounded bg-black border border-secondary">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="text-warning small fw-bold mb-2" style="font-size: 11px;">POURQUOI SOUHAITEZ-VOUS NOUS QUITTER ?</label>
                                        <textarea name="raison_depart" class="form-control bg-dark text-white border-secondary small" rows="2" placeholder="Dites-nous en quelques mots pourquoi..." required style="font-size:0.75rem; border-radius:5px;"></textarea>
                                    </div>
                                    <button type="submit" name="confirmer_suppression" class="btn btn-danger btn-sm w-100 fw-bold" onclick="return confirm('Êtes-vous absolument sûr de vouloir supprimer définitivement votre compte ?');" style="font-size: 11px; border-radius: 5px;">
                                        CONFIRMER LA SUPPRESSION DÉFINITIVE
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<div class="modal fade" id="modalRechargeClient" tabindex="-1" aria-labelledby="rechargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-black border border-warning" style="border-radius: 15px;">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title text-warning fw-bold shadow-sm" id="rechargeModalLabel" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                    <i class="bi bi-wallet2 me-2"></i>RECHARGER MON COMPTE
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/coiffons/client/traiter_recharge.php" method="POST">
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3" style="font-size: 0.75rem; line-height: 1.4;">Créditez votre compte instantanément pour payer vos prochaines coiffures en un clic.</p>
                    <div class="mb-4">
                        <label for="montant_recharge" class="form-label text-warning small fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 0.3px;">MONTANT À RECHARGER (FCFA)</label>
                        <div class="input-group">
                            <input type="number" name="montant" id="montant_recharge" class="form-control bg-dark text-white border-secondary small" placeholder="Ex: 5000" min="500" required style="font-size: 0.85rem;">
                            <span class="input-group-text bg-secondary text-white border-secondary small" style="font-size: 0.8rem; font-weight: 600;">FCFA</span>
                        </div>
                    </div>
                    <div class="p-2 rounded bg-dark border border-secondary text-muted" style="font-size: 0.65rem;">
                        <i class="bi bi-shield-check text-success me-1"></i> Paiement 100% sécurisé via Mobile Money et carte bancaire.
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-secondary btn-sm small px-3" data-bs-dismiss="modal" style="font-size: 0.7rem; font-weight: 600;">Annuler</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark px-3" style="font-size: 0.7rem;">Passer au paiement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="commentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white border border-secondary" style="border-radius:12px;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-warning h6 fw-bold text-uppercase tracking-wider">
                    <?php echo $user['role'] === 'coiffeur' ? 'Avis Récents Reçus (Max 10)' : 'Mes Derniers Avis Laissés (Max 10)'; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-black p-0">
                <div class="table-responsive">
                    <table class="table table-dark table-striped align-middle mb-0 small">
                        <thead>
                            <tr class="text-muted" style="border-bottom: 2px solid #222;">
                                <th class="ps-3 py-3"><?php echo $user['role'] === 'coiffeur' ? 'Client' : 'Coiffeur'; ?></th>
                                <th class="py-3">Note</th>
                                <th class="py-3">Commentaire</th>
                                <th class="pe-3 py-3 text-end">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($commentaires)): ?>
                                <?php foreach($commentaires as $com): ?>
                                    <tr style="border-bottom: 1px solid #111;">
                                        <td class="ps-3 py-3 fw-bold text-white"><?php echo htmlspecialchars($com['prenom'] ?? '') . ' ' . htmlspecialchars($com['nom'] ?? ''); ?></td>
                                        <td class="text-warning">
                                            <?php for($i=1; $i<=5; $i++) echo $i <= ($com['note'] ?? 0) ? '★' : '☆'; ?>
                                        </td>
                                        <td class="text-muted italic">"<?php echo htmlspecialchars($com['commentaire'] ?? ''); ?>"</td>
                                        <td class="pe-3 py-3 text-end text-muted font-monospace" style="font-size:0.75rem;"><?php echo htmlspecialchars($com['date_creation'] ?? ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Aucun historique d'avis disponible.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($user['role'] === 'coiffeur' && !empty($user['diplome']) && pathinfo($user['diplome'], PATHINFO_EXTENSION) !== 'pdf'): ?>
<div class="modal fade" id="diplomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content bg-dark text-white border border-secondary" style="border-radius:12px;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-warning h6 fw-bold">DOCUMENT DE CERTIFICATION</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-black p-3">
                <img src="<?php echo htmlspecialchars($user['diplome']); ?>" class="img-fluid rounded border border-dark" alt="Diplôme grand format">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    const ctxRdv = document.getElementById('chartRdv').getContext('2d');
    new Chart(ctxRdv, {
        type: 'doughnut',
        data: {
            labels: ['En attente', 'Acceptés', 'Terminés', 'Annulés'],
            datasets: [{
                data: [
                    <?php echo $stats_rdv['en_attente']; ?>,
                    <?php echo $stats_rdv['accepte']; ?>,
                    <?php echo $stats_rdv['termine']; ?>,
                    <?php echo $stats_rdv['annule']; ?>
                ],
                backgroundColor: ['#f1c40f', '#2ecc71', '#3498db', '#e74c3c'],
                borderWidth: 0
            }]
        },
        options: {
            plugins: { legend: { display: true, position: 'right', labels: { color: '#888', font: { size: 9 } } } },
            responsive: true,
            maintainAspectRatio: false
        }
    });

    const ctxDyna = document.getElementById('chartDynamiqueRole').getContext('2d');
    
    <?php if ($user['role'] === 'coiffeur'): ?>
        new Chart(ctxDyna, {
            type: 'bar',
            data: {
                labels: [<?php echo !empty($stats_villes) ? '"' . implode('","', array_column($stats_villes, 'nom_ville')) . '"' : '"Aucune donnée"'; ?>],
                datasets: [{
                    label: 'Quartiers',
                    data: [<?php echo !empty($stats_villes) ? implode(',', array_column($stats_villes, 'total')) : '0'; ?>],
                    backgroundColor: '#f39c12',
                    borderRadius: 4
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, grid: { color: '#222' }, ticks: { color: '#888', font: { size: 9 } } },
                    x: { grid: { display: false }, ticks: { color: '#888', font: { size: 9 } } }
                },
                plugins: { legend: { display: false } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    <?php else: ?>
        new Chart(ctxDyna, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
                datasets: [{
                    label: 'Séances',
                    data: [<?php echo implode(',', $stats_mensuelles); ?>],
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.05)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#f39c12'
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, grid: { color: '#222' }, ticks: { stepSize: 1, color: '#888', font: { size: 9 } } },
                    x: { grid: { display: false }, ticks: { color: '#888', font: { size: 9 } } }
                },
                plugins: { legend: { display: false } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    <?php endif; ?>
</script>

<style>
    .custom-profile-card { background-color: #0f0f0f !important; border: 1px solid rgba(255, 255, 255, 0.04) !important; border-radius: 14px !important; }
    .border-gold-premium { border: 2px solid #f39c12 !important; padding: 3px; background: linear-gradient(to bottom, #f39c12, #d35400); }
    .border-luxury-gold { border: 1px dashed rgba(243, 156, 18, 0.25) !important; }
    .badge-role-luxury { background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); padding: 5px 12px; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.5px; border-radius: 4px; color: #b3b3b3; }
    .btn-gold-action { background-color: #f39c12 !important; color: #000 !important; border: none !important; font-size: 0.7rem !important; letter-spacing: 0.5px; border-radius: 6px !important; transition: all 0.2s ease-in-out; }
    .btn-gold-action:hover { background-color: #e67e22 !important; transform: translateY(-1px); }
    .btn-outline-luxury { background-color: transparent !important; border: 1px solid rgba(255, 255, 255, 0.08) !important; color: #fff !important; font-size: 0.7rem !important; border-radius: 6px !important; transition: 0.2s; }
    .btn-outline-luxury:hover { border-color: #f39c12 !important; color: #f39c12 !important; }
    .btn-outline-danger-silent { background-color: rgba(220, 53, 69, 0.02) !important; border: 1px solid rgba(220, 53, 69, 0.15) !important; color: #dc3545 !important; font-size: 0.7rem !important; border-radius: 6px !important; }
    .btn-outline-danger-silent:hover { background-color: #dc3545 !important; color: #fff !important; }
    .border-danger-silent { border: 1px dashed rgba(220, 53, 69, 0.2) !important; }
    .metric-box { background-color: #080808; border: 1px solid rgba(255, 255, 255, 0.02); padding: 12px; border-radius: 8px; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
    .highlight-metric { background: linear-gradient(135deg, #090909 0%, #111111 100%); border: 1px solid rgba(243, 156, 18, 0.1) !important; }
    .pulse-dot { width: 5px; height: 5px; background-color: #f39c12; border-radius: 50%; display: inline-block; animation: active-pulse 1.6s infinite ease-in-out; margin-right: 3px; vertical-align: middle; }
    @keyframes active-pulse { 0% { transform: scale(0.8); opacity: 0.4; } 50% { transform: scale(1.2); opacity: 1; } 100% { transform: scale(0.8); opacity: 0.4; } }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>