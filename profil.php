<?php
/**
 * profil.php — Espace personnel (client & coiffeur) avec statistiques et Chart.js
 * Migration Design System v2.0 — Profil Partagé
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, graphiques, suppression compte : inchangés.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: /coiffons/index.php?page=login");
    exit();
}

$user_id = $_SESSION['id_user'];

// SQL inchangé — jointure villes + quartiers
$stmt = $pdo->prepare("
    SELECT u.*, v.nom_ville AS nom_ville_clean, q.nom_quartier AS nom_quartier_clean
    FROM users u
    LEFT JOIN villes v ON u.ville = v.id
    LEFT JOIN quartiers q ON u.id_quartier = q.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

// Variables blocs + graphiques — logique inchangée
$bloc1_val = 0; $bloc1_lbl = ""; $bloc1_icon = ""; $bloc1_link = ""; $bloc1_sub = "";
$bloc2_val = 0; $bloc2_lbl = ""; $bloc2_icon = ""; $bloc2_link = ""; $bloc2_sub = "";
$bloc3_val = 0; $bloc3_lbl = ""; $bloc3_icon = ""; $bloc3_link = ""; $bloc3_sub = "";
$bloc4_val = ""; $bloc4_lbl = ""; $bloc4_icon = ""; $bloc4_link = ""; $bloc4_sub = "";
$argent_gele = 0;
$stats_rdv = ['en_attente' => 0, 'accepte' => 0, 'termine' => 0, 'annule' => 0];
$stats_villes = [];
$stats_mensuelles = array_fill(1, 12, 0);
$commentaires = [];
$note_moyenne = 0;
$total_avis = 0;

if ($user['role'] === 'coiffeur') {
    try {
        $stmt_com = $pdo->prepare("SELECT r.date_creation, r.commentaire, r.note, u.nom, u.prenom FROM rendez_vous r JOIN users u ON r.client_id = u.id WHERE r.coiffeur_id = ? AND r.commentaire IS NOT NULL ORDER BY r.date_creation DESC LIMIT 10");
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

    $bloc1_lbl = "Demandes en attente"; $bloc1_icon = "bi-calendar-check"; $bloc1_link = "/coiffons/coiffeurs/valider_rendezvous.php"; $bloc1_sub = "Voir les demandes →";
    try { $s = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE coiffeur_id = ? AND statut_rdv = 'en_attente'"); $s->execute([$user_id]); $bloc1_val = intval($s->fetchColumn()); } catch (Exception $e) {}

    $bloc2_lbl = "Gains sécurisés"; $bloc2_icon = "bi-shield-lock"; $bloc2_link = "#solde-section"; $bloc2_sub = "Rendez-vous à venir";
    try { $s = $pdo->prepare("SELECT SUM(p.prix) FROM rendez_vous r JOIN prestations p ON r.coiffure_id = p.id_prestation WHERE r.coiffeur_id = ? AND r.statut_rdv IN ('en_attente', 'accepte')"); $s->execute([$user_id]); $bloc2_val = number_format($s->fetchColumn() ?? 0, 0, ',', ' ') . " <span style='font-size:0.75rem;'>FCFA</span>"; } catch (Exception $e) { $bloc2_val = "0 FCFA"; }

    $bloc3_lbl = "Quartiers couverts"; $bloc3_icon = "bi-geo-alt"; $bloc3_link = "/coiffons/coiffeurs/mes_zones.php"; $bloc3_sub = "Modifier mes zones →";
    try { $s = $pdo->prepare("SELECT COUNT(*) FROM zones_coiffeur WHERE id_coiffeur = ?"); $s->execute([$user_id]); $bloc3_val = intval($s->fetchColumn()) . " <span style='font-size:0.75rem;color:var(--text-muted)'>zones</span>"; } catch (Exception $e) { $bloc3_val = "0"; }

    $bloc4_lbl = "Clients fidélisés"; $bloc4_icon = "bi-people"; $bloc4_link = "#"; $bloc4_sub = "Total clients uniques";
    try { $s = $pdo->prepare("SELECT COUNT(DISTINCT client_id) FROM rendez_vous WHERE coiffeur_id = ?"); $s->execute([$user_id]); $bloc4_val = intval($s->fetchColumn()); } catch (Exception $e) { $bloc4_val = 0; }

    try { $s = $pdo->prepare("SELECT statut_rdv, COUNT(*) as total FROM rendez_vous WHERE coiffeur_id = ? GROUP BY statut_rdv"); $s->execute([$user_id]); while ($row = $s->fetch()) { if (array_key_exists($row['statut_rdv'], $stats_rdv)) $stats_rdv[$row['statut_rdv']] = intval($row['total']); } } catch (Exception $e) {}
    try { $s = $pdo->prepare("SELECT v.nom_ville, COUNT(zc.id_quartier) as total FROM zones_coiffeur zc JOIN quartiers q ON zc.id_quartier = q.id JOIN villes v ON q.id_ville = v.id WHERE zc.id_coiffeur = ? GROUP BY v.nom_ville"); $s->execute([$user_id]); $stats_villes = $s->fetchAll(PDO::FETCH_ASSOC) ?: []; } catch (Exception $e) {}

} else {
    try { $s = $pdo->prepare("SELECT r.date_creation, r.commentaire, r.note, u.nom, u.prenom FROM rendez_vous r JOIN users u ON r.coiffeur_id = u.id WHERE r.client_id = ? AND r.commentaire IS NOT NULL ORDER BY r.date_creation DESC LIMIT 10"); $s->execute([$user_id]); $commentaires = $s->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) {}

    $bloc1_lbl = "Rendez-vous prévus"; $bloc1_icon = "bi-calendar3"; $bloc1_link = "/coiffons/client/mes_rendezvous.php"; $bloc1_sub = "Consulter mon agenda →";
    try { $s = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE client_id = ? AND statut_rdv IN ('en_attente', 'accepte')"); $s->execute([$user_id]); $bloc1_val = intval($s->fetchColumn()); } catch (Exception $e) {}

    $bloc2_lbl = "Fonds en attente"; $bloc2_icon = "bi-lock-fill"; $bloc2_link = "#solde-section"; $bloc2_sub = "Paiements sécurisés";
    try { $s = $pdo->prepare("SELECT SUM(p.prix) as total_gele FROM rendez_vous r JOIN prestations p ON r.coiffure_id = p.id_prestation WHERE r.client_id = ? AND r.statut_rdv = 'en_attente'"); $s->execute([$user_id]); $r2 = $s->fetch(); $argent_gele = $r2['total_gele'] ?? 0; $bloc2_val = number_format($argent_gele, 0, ',', ' ') . " <span style='font-size:0.75rem;'>FCFA</span>"; } catch (Exception $e) { $bloc2_val = "0 FCFA"; }

    $bloc3_lbl = "Coiffeurs testés"; $bloc3_icon = "bi-scissors"; $bloc3_link = "/coiffons/index.php"; $bloc3_sub = "Prendre un nouveau RDV →";
    try { $s = $pdo->prepare("SELECT COUNT(DISTINCT coiffeur_id) FROM rendez_vous WHERE client_id = ? AND statut_rdv = 'termine'"); $s->execute([$user_id]); $bloc3_val = intval($s->fetchColumn()); } catch (Exception $e) {}

    $bloc4_val = "<span style='color:var(--success-text);font-weight:700;'><i class='bi bi-circle-fill me-1' style='font-size:7px;'></i> ACTIF</span>";
    $bloc4_lbl = "Statut du compte"; $bloc4_icon = "bi-shield-check"; $bloc4_link = "#"; $bloc4_sub = "Profil vérifié";

    try { $s = $pdo->prepare("SELECT statut_rdv, COUNT(*) as total FROM rendez_vous WHERE client_id = ? GROUP BY statut_rdv"); $s->execute([$user_id]); while ($row = $s->fetch()) { if (array_key_exists($row['statut_rdv'], $stats_rdv)) $stats_rdv[$row['statut_rdv']] = intval($row['total']); } } catch (Exception $e) {}
    try { $s = $pdo->prepare("SELECT MONTH(date_creation) as mois, COUNT(*) as total FROM rendez_vous WHERE client_id = ? AND YEAR(date_creation) = YEAR(CURDATE()) GROUP BY MONTH(date_creation)"); $s->execute([$user_id]); while ($row = $s->fetch()) { $stats_mensuelles[intval($row['mois'])] = intval($row['total']); } } catch (Exception $e) {}
}

$lien_rendezvous = ($user['role'] === 'client') ? "/coiffons/client/mes_rendezvous.php" : "/coiffons/coiffeurs/agenda_coiffeurs.php";

// Suppression compte — SQL inchangé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    $raison = htmlspecialchars(trim($_POST['raison_depart']));
    if (!empty($raison)) {
        $pdo->prepare("INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) VALUES (?, ?, ?, ?)")->execute([$user['nom'], $user['email'], $user['role'], $raison]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        session_destroy();
        echo "<script>alert('Votre compte et vos données ont été définitivement supprimés.');window.location.href='/coiffons/index.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace — Coiffe Chez Toi</title>
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    /* profil.php — styles spécifiques DS-conformes */
    .profil-page { padding-top:calc(var(--navbar-height) + 1.5rem); padding-bottom:calc(var(--bottomnav-height) + 2rem); min-height:100vh; background:var(--dark); }
    .profil-card { background:var(--dark-2); border:1px solid var(--glass-border); border-radius:var(--radius-lg); padding:1.5rem; }
    .metric-box { background:var(--dark-3); border:1px solid var(--glass-border); border-radius:var(--radius-md); padding:12px; height:100%; display:flex; flex-direction:column; justify-content:space-between; }
    .metric-highlight { border-color:rgba(212,175,55,0.18) !important; }
    .metric-label { font-size:0.65rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:700; margin-bottom:4px; }
    .metric-value { font-size:1.05rem; font-weight:700; color:var(--text-primary); margin:4px 0; }
    .metric-link { font-size:0.6rem; color:var(--gold); text-decoration:none; font-weight:600; }
    .metric-link:hover { color:var(--gold-hover); }
    .chart-box { background:var(--dark-3); border:1px solid var(--glass-border); border-radius:var(--radius-md); padding:12px; text-align:center; }
    .chart-label { font-size:0.65rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:10px; }
    .info-row { font-size:0.75rem; }
    .info-key { color:var(--text-muted); display:block; font-size:0.6rem; text-transform:uppercase; margin-bottom:2px; }
    .info-val { color:var(--text-primary); font-weight:700; }
    .zone-danger { background:rgba(220,53,69,0.06); border:1px dashed rgba(220,53,69,0.2); border-radius:var(--radius-md); padding:1rem; }
    .avatar-ring { width:96px; height:96px; border-radius:50%; border:3px solid var(--gold); object-fit:cover; display:block; margin:0 auto; }
    .avatar-ring-ph { width:96px; height:96px; border-radius:50%; border:3px solid var(--gold); background:var(--gold-dim); display:flex; align-items:center; justify-content:center; margin:0 auto; font-family:var(--font-display); font-size:2rem; font-weight:700; color:var(--gold); }
    .note-stars { font-size:.85rem; color:var(--gold); letter-spacing:1px; }
    .pulse-dot { width:5px; height:5px; background:var(--gold); border-radius:50%; display:inline-block; animation:pulseDot 1.6s infinite ease-in-out; margin-right:3px; vertical-align:middle; }
    @media (max-width:768px) { .profil-page { padding-top:calc(var(--navbar-height) + 1rem); } }
    </style>
</head>
<body>
<?php
$page_root = '/coiffons';
include __DIR__ . '/views/components/navbar_client.php';
?>
<main class="profil-page page-transition">
<div class="container" style="max-width:1200px;">

    <div class="text-center mb-4">
        <h1 style="font-family:var(--font-display);font-size:clamp(1.2rem,3vw,1.6rem);color:var(--gold);font-weight:700;letter-spacing:2px;margin-bottom:4px;">
            MON ESPACE PERSONNEL
        </h1>
        <p style="color:var(--text-muted);font-size:0.65rem;letter-spacing:1px;text-transform:uppercase;">
            Suivi de vos activités et de vos réservations
        </p>
    </div>

    <div class="row g-4">

        <!-- ── Colonne gauche : profil card ── -->
        <div class="col-lg-4">
            <div class="profil-card text-center">
                <!-- Avatar -->
                <?php if (!empty($user['photo_profil']) && file_exists(__DIR__ . '/' . $user['photo_profil'])): ?>
                    <img src="/coiffons/<?= htmlspecialchars($user['photo_profil']) ?>" class="avatar-ring mb-3" alt="Photo de profil">
                <?php else: ?>
                    <div class="avatar-ring-ph mb-3"><?= strtoupper(substr($user['nom'] ?? 'U', 0, 1)) ?></div>
                <?php endif; ?>

                <h3 style="font-weight:700;font-size:1rem;color:var(--text-primary);margin-bottom:4px;">
                    <?= htmlspecialchars(strtoupper($user['nom'])) . ' ' . htmlspecialchars($user['prenom']) ?>
                </h3>
                <span class="badge-muted" style="font-size:0.65rem;text-transform:uppercase;letter-spacing:.5px;">
                    <i class="bi <?= $user['role'] === 'coiffeur' ? 'bi-scissors' : 'bi-person-fill' ?> me-1" style="color:var(--gold);" aria-hidden="true"></i>
                    COMPTE // <?= strtoupper($user['role']) ?>
                </span>

                <?php if ($user['role'] === 'coiffeur'): ?>
                    <div class="metric-box metric-highlight text-center mt-3 mb-3">
                        <span class="metric-label">Réputation globale</span>
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <span style="font-size:1.5rem;font-weight:800;color:var(--gold);"><?= number_format($note_moyenne, 1) ?></span>
                            <div>
                                <div class="note-stars"><?php for($i=1;$i<=5;$i++) echo $i<=round($note_moyenne)?'★':'☆'; ?></div>
                                <span style="font-size:0.6rem;color:var(--text-muted);"><?= $total_avis ?> avis</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <hr style="border-color:var(--glass-border);margin:1rem 0;">

                <div class="d-grid gap-2">
                    <a href="/coiffons/modifier_profil.php" class="btn-gold" style="padding:10px;font-size:0.78rem;">
                        <i class="bi bi-pencil-square me-2" aria-hidden="true"></i> MODIFIER MON PROFIL
                    </a>
                    <button type="button" class="btn-outline-gold" style="padding:8px;font-size:0.72rem;" onclick="openModal('modal-avis')">
                        <span class="pulse-dot"></span>
                        <?= $user['role'] === 'coiffeur' ? 'HISTORIQUE DES AVIS REÇUS' : 'MES DERNIERS AVIS LAISSÉS' ?>
                    </button>
                    <a href="<?= $lien_rendezvous ?>" class="btn-outline-gold" style="padding:8px;font-size:0.72rem;">
                        <i class="bi bi-calendar3 me-2" aria-hidden="true"></i> MES RENDEZ-VOUS
                    </a>
                    <a href="/coiffons/deconnexion.php" class="btn-ghost" style="padding:8px;font-size:0.72rem;color:var(--danger-text);">
                        <i class="bi bi-box-arrow-left me-2" aria-hidden="true"></i> SE DÉCONNECTER
                    </a>
                </div>
            </div>
        </div>

        <!-- ── Colonne droite : stats + graphiques ── -->
        <div class="col-lg-8">
            <div class="profil-card">

                <!-- Blocs métriques -->
                <h5 style="font-size:0.72rem;font-weight:700;color:var(--gold);letter-spacing:1px;text-transform:uppercase;margin-bottom:1rem;">
                    <i class="bi bi-cpu-fill me-2" aria-hidden="true"></i>VOS STATISTIQUES EN TEMPS RÉEL
                </h5>
                <div class="row g-2 mb-4">
                    <?php foreach ([
                        [$bloc1_icon,$bloc1_lbl,$bloc1_val,$bloc1_link,$bloc1_sub,false],
                        [$bloc2_icon,$bloc2_lbl,$bloc2_val,$bloc2_link,$bloc2_sub,true],
                        [$bloc3_icon,$bloc3_lbl,$bloc3_val,$bloc3_link,$bloc3_sub,false],
                        [$bloc4_icon,$bloc4_lbl,$bloc4_val,$bloc4_link,$bloc4_sub,false],
                    ] as [$bicon,$blbl,$bval,$blink,$bsub,$highlight]): ?>
                    <div class="col-6 col-md-3">
                        <div class="metric-box <?= $highlight ? 'metric-highlight' : '' ?>">
                            <div>
                                <i class="bi <?= $bicon ?>" style="color:var(--gold);font-size:1.1rem;display:block;margin-bottom:4px;" aria-hidden="true"></i>
                                <span class="metric-label"><?= htmlspecialchars($blbl) ?></span>
                            </div>
                            <div class="metric-value"><?= $bval ?></div>
                            <?php if ($blink && $blink !== '#'): ?>
                                <a href="<?= htmlspecialchars($blink) ?>" class="metric-link"><?= htmlspecialchars($bsub) ?></a>
                            <?php else: ?>
                                <span style="font-size:0.55rem;color:var(--text-muted);"><?= htmlspecialchars($bsub) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Graphiques Chart.js — logique inchangée -->
                <h5 style="font-size:0.72rem;font-weight:700;color:var(--gold);letter-spacing:1px;text-transform:uppercase;margin-bottom:1rem;">
                    <i class="bi bi-bar-chart-line-fill me-2" aria-hidden="true"></i>ANALYSE ET COUVERTURE VISUELLE
                </h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="chart-box">
                            <span class="chart-label">
                                <?= $user['role'] === 'coiffeur' ? 'SITUATION DES DEMANDES' : 'STATUTS DE MES RÉSERVATIONS' ?>
                            </span>
                            <div style="max-height:160px;display:flex;justify-content:center;">
                                <canvas id="chartRdv"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-box">
                            <span class="chart-label">
                                <?= $user['role'] === 'coiffeur' ? 'QUARTIERS COUVERTS PAR VILLE' : 'FRÉQUENCE DE MES SÉANCES (2026)' ?>
                            </span>
                            <div style="max-height:160px;display:flex;justify-content:center;">
                                <canvas id="chartDynamiqueRole"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Portefeuille -->
                <h5 id="solde-section" style="font-size:0.72rem;font-weight:700;color:var(--gold);letter-spacing:1px;text-transform:uppercase;margin-bottom:1rem;">
                    <i class="bi bi-wallet2 me-2" aria-hidden="true"></i>MON PORTEFEUILLE NUMÉRIQUE
                </h5>
                <div class="glass-cct p-3 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-7 mb-3 mb-md-0">
                            <span style="font-size:0.6rem;color:var(--text-muted);text-transform:uppercase;display:block;">Solde disponible</span>
                            <span style="font-size:1.4rem;font-weight:800;color:var(--text-primary);">
                                <?= number_format($user['solde'] ?? 0, 0, ',', ' ') ?>
                                <span style="color:var(--gold);font-size:0.85rem;">FCFA</span>
                            </span>
                        </div>
                        <div class="col-md-5 text-md-end">
                            <?php if ($user['role'] === 'client'): ?>
                                <button type="button" class="btn-gold btn-sm w-100" style="padding:8px;" onclick="openModal('modal-recharge')">
                                    <i class="bi bi-cash-coin me-1" aria-hidden="true"></i> RECHARGER MON COMPTE
                                </button>
                            <?php else: ?>
                                <a href="/coiffons/coiffeurs/portefeuille.php" class="btn-outline-gold btn-sm w-100" style="padding:8px;">
                                    <i class="bi bi-graph-up-arrow me-1" aria-hidden="true"></i> SUIVRE MES GAINS
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Infos personnelles -->
                <h5 style="font-size:0.72rem;font-weight:700;color:var(--gold);letter-spacing:1px;text-transform:uppercase;margin-bottom:1rem;">
                    <i class="bi bi-card-text me-2" aria-hidden="true"></i>MES INFORMATIONS PERSONNELLES
                </h5>
                <div class="glass-cct p-3 mb-4">
                    <div class="row g-3 info-row">
                        <div class="col-sm-6"><span class="info-key">Adresse Email</span><span class="info-val"><?= htmlspecialchars($user['email']) ?></span></div>
                        <div class="col-sm-6"><span class="info-key">Numéro WhatsApp</span><span class="info-val" style="color:var(--gold);"><?= htmlspecialchars($user['telephone']) ?></span></div>
                        <div class="col-sm-4"><span class="info-key">Genre</span><span class="info-val"><?= htmlspecialchars(ucfirst($user['sexe'] ?? '—')) ?></span></div>
                        <div class="col-sm-4"><span class="info-key">Ville</span><span class="info-val"><?= htmlspecialchars($user['nom_ville_clean'] ?? 'Non spécifiée') ?></span></div>
                        <div class="col-sm-4"><span class="info-key">Quartier</span><span class="info-val"><?= htmlspecialchars($user['nom_quartier_clean'] ?? 'Général') ?></span></div>
                    </div>
                </div>

                <?php if ($user['role'] === 'coiffeur'): ?>
                <!-- Statut professionnel coiffeur -->
                <h5 style="font-size:0.72rem;font-weight:700;color:var(--gold);letter-spacing:1px;text-transform:uppercase;margin-bottom:1rem;">
                    <i class="bi bi-patch-check me-2" aria-hidden="true"></i>STATUT PROFESSIONNEL
                </h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="glass-cct p-3 h-100">
                            <span style="font-size:0.6rem;color:var(--text-muted);text-transform:uppercase;display:block;margin-bottom:8px;">Autorisation sur la plateforme</span>
                            <?php if (isset($user['abonnement_actif']) && $user['abonnement_actif'] == 1): ?>
                                <span class="badge-verified">COMPTE ACTIF // AUTORISÉ</span>
                            <?php else: ?>
                                <span class="badge-danger" style="display:inline-block;margin-bottom:8px;">ABONNEMENT EXPIRÉ</span><br>
                                <a href="/coiffons/coiffeurs/portefeuille.php" style="color:var(--gold);font-size:0.65rem;font-weight:700;">Régulariser ma situation →</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-cct p-3 h-100">
                            <span style="font-size:0.6rem;color:var(--text-muted);text-transform:uppercase;display:block;margin-bottom:8px;">Diplôme ou Attestation</span>
                            <?php if (!empty($user['diplome'])): ?>
                                <?php if (pathinfo($user['diplome'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                    <a href="<?= htmlspecialchars($user['diplome']) ?>" target="_blank" class="btn-outline-gold btn-sm" style="font-size:0.65rem;">
                                        <i class="bi bi-file-earmark-pdf-fill me-1" aria-hidden="true"></i> VOIR LE DOCUMENT PDF
                                    </a>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($user['diplome']) ?>" style="max-height:55px;object-fit:contain;border-radius:6px;cursor:pointer;" onclick="openModal('modal-diplome')" alt="Diplôme">
                                    <span style="display:block;color:var(--text-muted);font-size:9px;margin-top:3px;">Cliquer pour agrandir</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--danger-text);font-size:0.65rem;font-weight:700;"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i> AUCUN DOCUMENT ENREGISTRÉ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Zone danger suppression -->
                <div class="zone-danger">
                    <h6 style="color:var(--danger-text);font-weight:700;font-size:0.78rem;letter-spacing:.5px;margin-bottom:6px;">
                        <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>ZONE DE DANGER // SUPPRESSION DU COMPTE
                    </h6>
                    <p style="color:var(--text-muted);font-size:0.72rem;line-height:1.5;margin-bottom:12px;">
                        Cette action supprimera définitivement votre compte, l'historique de vos rendez-vous et vos soldes. Opération irréversible.
                    </p>
                    <button class="btn-ghost btn-sm" type="button"
                            onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none';"
                            style="font-size:0.72rem;color:var(--danger-text);">
                        <i class="bi bi-trash3 me-1" aria-hidden="true"></i> DEMANDER LA SUPPRESSION DE MON COMPTE
                    </button>
                    <div style="display:none;margin-top:1rem;">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label-cct" style="color:var(--gold);font-size:0.72rem;">POURQUOI SOUHAITEZ-VOUS NOUS QUITTER ?</label>
                                <textarea name="raison_depart" class="fc-dark" rows="2" placeholder="Dites-nous en quelques mots pourquoi..." required style="font-size:0.75rem;"></textarea>
                            </div>
                            <button type="submit" name="confirmer_suppression" class="btn-danger-cct btn-sm w-100"
                                    onclick="return confirm('Êtes-vous absolument sûr(e) de vouloir supprimer votre compte ? Cette action est irréversible.')">
                                CONFIRMER LA SUPPRESSION DÉFINITIVE
                            </button>
                        </form>
                    </div>
                </div>

            </div><!-- /.profil-card -->
        </div><!-- /.col-lg-8 -->
    </div><!-- /.row -->
</div><!-- /.container -->
</main>

<!-- Modal Recharge client -->
<?php
ob_start();
?>
<form action="/coiffons/client/traiter_recharge.php" method="POST">
    <p style="color:var(--text-muted);font-size:0.78rem;margin-bottom:1rem;">
        Créditez votre compte instantanément pour payer vos prochaines coiffures en un clic.
    </p>
    <?php
    $ff = ['type'=>'number','name'=>'montant','label'=>'Montant à recharger (FCFA)','placeholder'=>'Ex: 5000','required'=>true,'attrs'=>'min="500"'];
    include __DIR__ . '/views/components/form_field.php';
    ?>
    <div class="glass-cct p-2 mb-3" style="font-size:0.65rem;color:var(--text-muted);">
        <i class="bi bi-shield-check" style="color:var(--success-text);" aria-hidden="true"></i>
        Paiement 100% sécurisé via Mobile Money et carte bancaire.
    </div>
    <div class="d-flex gap-2 justify-content-end">
        <button type="button" class="btn-ghost btn-sm" onclick="closeModal('modal-recharge')">Annuler</button>
        <button type="submit" class="btn-gold btn-sm">Passer au paiement</button>
    </div>
</form>
<?php
$modal_content = ob_get_clean();
$modal_id = 'modal-recharge'; $modal_title = 'Recharger mon compte'; $modal_width = '480px';
include __DIR__ . '/views/components/modal.php';
unset($modal_content, $modal_id, $modal_title, $modal_width);
?>

<!-- Modal Avis -->
<?php
ob_start();
?>
<div class="table-responsive">
    <table class="table-dark-cct">
        <thead>
            <tr>
                <th><?= $user['role'] === 'coiffeur' ? 'Client' : 'Coiffeur' ?></th>
                <th>Note</th>
                <th>Commentaire</th>
                <th class="text-end">Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($commentaires)): ?>
                <?php foreach ($commentaires as $com): ?>
                    <tr>
                        <td style="font-weight:700;"><?= htmlspecialchars(($com['prenom'] ?? '') . ' ' . ($com['nom'] ?? '')) ?></td>
                        <td style="color:var(--gold);"><?php for($i=1;$i<=5;$i++) echo $i<=($com['note']??0)?'★':'☆'; ?></td>
                        <td style="color:var(--text-muted);font-size:0.78rem;">"<?= htmlspecialchars($com['commentaire'] ?? '') ?>"</td>
                        <td class="text-end" style="font-size:0.7rem;font-family:monospace;color:var(--text-muted);"><?= htmlspecialchars($com['date_creation'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-muted);">Aucun historique d'avis disponible.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
$modal_content = ob_get_clean();
$modal_id = 'modal-avis';
$modal_title = $user['role'] === 'coiffeur' ? 'Avis reçus (max 10)' : 'Mes derniers avis laissés (max 10)';
$modal_width = '700px';
include __DIR__ . '/views/components/modal.php';
unset($modal_content, $modal_id, $modal_title, $modal_width);
?>

<?php if ($user['role'] === 'coiffeur' && !empty($user['diplome']) && pathinfo($user['diplome'], PATHINFO_EXTENSION) !== 'pdf'): ?>
<?php
ob_start();
echo '<img src="' . htmlspecialchars($user['diplome']) . '" class="img-fluid" style="border-radius:8px;" alt="Diplôme grand format">';
$modal_content = ob_get_clean();
$modal_id = 'modal-diplome'; $modal_title = 'Document de Certification'; $modal_width = '600px';
include __DIR__ . '/views/components/modal.php';
unset($modal_content, $modal_id, $modal_title, $modal_width);
?>
<?php endif; ?>

<?php
$page_root = '/coiffons';
include __DIR__ . '/views/components/footer_global.php';
$current_page = 'profil.php';
include __DIR__ . '/views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* Chart.js — logique inchangée, couleurs via DS tokens */
const ctxRdv = document.getElementById('chartRdv').getContext('2d');
new Chart(ctxRdv, {
    type: 'doughnut',
    data: {
        labels: ['En attente', 'Acceptés', 'Terminés', 'Annulés'],
        datasets: [{
            data: [<?= $stats_rdv['en_attente'] ?>,<?= $stats_rdv['accepte'] ?>,<?= $stats_rdv['termine'] ?>,<?= $stats_rdv['annule'] ?>],
            backgroundColor: ['#D4AF37','#6EE7B7','#60A5FA','#FF6B6B'],
            borderWidth: 0
        }]
    },
    options: {
        plugins: { legend: { display: true, position: 'right', labels: { color: 'rgba(255,255,255,0.4)', font: { size: 9 } } } },
        responsive: true, maintainAspectRatio: false
    }
});

const ctxDyna = document.getElementById('chartDynamiqueRole').getContext('2d');
<?php if ($user['role'] === 'coiffeur'): ?>
new Chart(ctxDyna, {
    type: 'bar',
    data: {
        labels: [<?= !empty($stats_villes) ? '"' . implode('","', array_column($stats_villes, 'nom_ville')) . '"' : '"Aucune donnée"' ?>],
        datasets: [{ label: 'Quartiers', data: [<?= !empty($stats_villes) ? implode(',', array_column($stats_villes, 'total')) : '0' ?>], backgroundColor: '#D4AF37', borderRadius: 4 }]
    },
    options: { scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 9 } } }, x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 9 } } } }, plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
});
<?php else: ?>
new Chart(ctxDyna, {
    type: 'line',
    data: {
        labels: ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aoû','Sep','Oct','Nov','Déc'],
        datasets: [{ label: 'Séances', data: [<?= implode(',', $stats_mensuelles) ?>], borderColor: '#D4AF37', backgroundColor: 'rgba(212,175,55,0.05)', borderWidth: 2, tension: 0.3, fill: true, pointBackgroundColor: '#D4AF37' }]
    },
    options: { scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { stepSize: 1, color: 'rgba(255,255,255,0.3)', font: { size: 9 } } }, x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.3)', font: { size: 9 } } } }, plugins: { legend: { display: false } }, responsive: true, maintainAspectRatio: false }
});
<?php endif; ?>
</script>
</body>
</html>
