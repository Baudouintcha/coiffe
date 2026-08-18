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
require_once __DIR__ . '/security/csrf.php';
require_once __DIR__ . '/src/Services/OtpService.php';
require_once __DIR__ . '/src/Services/EmailService.php';

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

// Variables blocs stats
$bloc1_val = 0; $bloc1_lbl = ""; $bloc1_icon = ""; $bloc1_link = ""; $bloc1_sub = "";
$bloc2_val = 0; $bloc2_lbl = ""; $bloc2_icon = ""; $bloc2_link = ""; $bloc2_sub = "";
$bloc3_val = 0; $bloc3_lbl = ""; $bloc3_icon = ""; $bloc3_link = ""; $bloc3_sub = "";
$bloc4_val = ""; $bloc4_lbl = ""; $bloc4_icon = ""; $bloc4_link = ""; $bloc4_sub = "";
$argent_gele = 0;
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
    try { $s = $pdo->prepare("SELECT SUM(s.prix) FROM rendez_vous r JOIN services s ON r.service_id = s.id_service WHERE r.prestataire_id = ? AND r.statut_rdv IN ('en_attente', 'confirme')"); $s->execute([$user_id]); $bloc2_val = number_format($s->fetchColumn() ?? 0, 0, ',', ' ') . " <span style='font-size:0.75rem;'>FCFA</span>"; } catch (Exception $e) { $bloc2_val = "0 FCFA"; }

    $bloc3_lbl = "Quartiers couverts"; $bloc3_icon = "bi-geo-alt"; $bloc3_link = "/coiffons/coiffeurs/mes_zones.php"; $bloc3_sub = "Modifier mes zones →";
    try { $s = $pdo->prepare("SELECT COUNT(*) FROM zones_prestataire WHERE id_prestataire = ?"); $s->execute([$user_id]); $bloc3_val = intval($s->fetchColumn()) . " <span style='font-size:0.75rem;color:var(--text-muted)'>zones</span>"; } catch (Exception $e) { $bloc3_val = "0"; }

    $bloc4_lbl = "Clients fidélisés"; $bloc4_icon = "bi-people"; $bloc4_link = "#"; $bloc4_sub = "Total clients uniques";
    try { $s = $pdo->prepare("SELECT COUNT(DISTINCT client_id) FROM rendez_vous WHERE coiffeur_id = ?"); $s->execute([$user_id]); $bloc4_val = intval($s->fetchColumn()); } catch (Exception $e) { $bloc4_val = 0; }

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
}

$lien_rendezvous = ($user['role'] === 'client') ? "/coiffons/client/mes_rendezvous.php" : "/coiffons/coiffeurs/agenda_coiffeurs.php";

// ═══ ACCOUNT DELETION WITH OTP ═══
$account_deletion_step = 1; // Step 1: Confirmation, Step 2: OTP, Step 3: Final confirmation
if (isset($_SESSION['account_deletion_step'])) {
    $account_deletion_step = $_SESSION['account_deletion_step'];
}

// STEP 1: Request account deletion (generate OTP)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_deletion') {
    csrf_verify();
    
    $otp_service = new OtpService($pdo);
    $otp_result = $otp_service->generate($user_id, 'account_deletion');
    
    if ($otp_result['success']) {
        $email_service = new EmailService();
        $email_send_result = $email_service->sendOtpCode($user['email'], $otp_result['code'], 5, 'account_deletion');
        
        if ($email_send_result['success']) {
            $_SESSION['account_deletion_step'] = 2;
            header("Location: /coiffons/profil.php");
            exit();
        }
    }
}

// STEP 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_deletion') {
    csrf_verify();
    
    $code = $_POST['otp_code'] ?? '';
    
    if (preg_match('/^\d{6}$/', $code)) {
        $otp_service = new OtpService($pdo);
        $result = $otp_service->verify($user_id, 'account_deletion', $code);
        
        if ($result['success']) {
            $_SESSION['account_deletion_step'] = 3;
            header("Location: /coiffons/profil.php");
            exit();
        }
    }
}

// STEP 3: Final confirmation and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_final_deletion') {
    csrf_verify();
    
    if (isset($_SESSION['account_deletion_step']) && $_SESSION['account_deletion_step'] === 3) {
        // Log deletion
        $raison = htmlspecialchars(trim($_POST['raison_depart'] ?? 'Suppression via formulaire'));
        $pdo->prepare("INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) VALUES (?, ?, ?, ?)")
            ->execute([$user['nom'], $user['email'], $user['role'], $raison]);
        
        // Delete user
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
        
        // Clear session and redirect
        session_destroy();
        header("Location: /coiffons/index.php?account_deleted=1");
        exit();
    }
}

// Cancel deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_deletion') {
    unset($_SESSION['account_deletion_step']);
    header("Location: /coiffons/profil.php");
    exit();
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
                        <div class="col-sm-4"><span class="info-key">Quartier</span><span class="info-val"><?= htmlspecialchars($user['nom_quartier_clean'] ?? 'Non spécifié') ?></span></div>
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
                            <?php if (isset($user['abonnement_status']) && ($user['abonnement_status'] == 1 || strtolower($user['abonnement_status']) === 'actif')): ?>
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

                    <!-- STEP 1: Initial request -->
                    <?php if ($account_deletion_step === 1): ?>
                        <button class="btn-ghost btn-sm" type="button"
                                onclick="document.getElementById('deletion-form-1').style.display=document.getElementById('deletion-form-1').style.display==='none'?'block':'none';"
                                style="font-size:0.72rem;color:var(--danger-text);">
                            <i class="bi bi-trash3 me-1" aria-hidden="true"></i> DEMANDER LA SUPPRESSION DE MON COMPTE
                        </button>
                        <div id="deletion-form-1" style="display:none;margin-top:1rem;">
                            <form method="POST" novalidate>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="request_deletion">
                                <p style="color:var(--warning-text);font-size:0.65rem;margin-bottom:8px;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Un code de vérification sera envoyé à votre email. Vous devrez le confirmer pour supprimer votre compte.
                                </p>
                                <button type="submit" class="btn-danger-cct btn-sm w-100">
                                    ENVOYER UN CODE DE VÉRIFICATION
                                </button>
                            </form>
                        </div>

                    <!-- STEP 2: OTP verification -->
                    <?php elseif ($account_deletion_step === 2): ?>
                        <div style="background:rgba(220,53,69,0.1);border:1px solid rgba(220,53,69,0.3);border-radius:8px;padding:12px;margin:8px 0;">
                            <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:10px;">
                                Un code de vérification a été envoyé à <strong><?= htmlspecialchars($user['email']) ?></strong>
                            </p>
                            <form method="POST" novalidate>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="verify_deletion">
                                
                                <div class="otp-inputs" style="display:flex;gap:6px;justify-content:center;margin:12px 0;flex-wrap:wrap;">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <input type="text" class="deletion-otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" 
                                               placeholder="•" name="deletion_otp_digit_<?= $i ?>" id="deletion_otp_digit_<?= $i ?>"
                                               style="width:40px;height:40px;text-align:center;font-size:1.2rem;border:1px solid rgba(220,53,69,0.3);border-radius:6px;background:rgba(220,53,69,0.05);color:var(--danger-text);font-weight:700;">
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="otp_code" id="deletion_otp_code" value="">
                                
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn-danger-cct btn-sm w-50">Vérifier</button>
                                    <form method="POST" style="flex:1;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel_deletion">
                                        <button type="submit" class="btn-ghost btn-sm w-100">Annuler</button>
                                    </form>
                                </div>
                            </form>
                        </div>

                    <!-- STEP 3: Final confirmation -->
                    <?php elseif ($account_deletion_step === 3): ?>
                        <div style="background:rgba(220,53,69,0.15);border:2px solid rgba(220,53,69,0.4);border-radius:8px;padding:12px;margin:8px 0;">
                            <p style="font-size:0.75rem;color:var(--danger-text);font-weight:700;margin-bottom:8px;">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                DERNIER AVERTISSEMENT
                            </p>
                            <p style="font-size:0.7rem;color:var(--text-muted);line-height:1.5;margin-bottom:10px;">
                                Vous êtes sur le point de supprimer votre compte. Cette action est définitive et irréversible. Tous vos données, historique et soldes seront perdus.
                            </p>
                            <form method="POST" novalidate>
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="confirm_final_deletion">
                                <div class="mb-3">
                                    <label class="form-label-cct" style="color:var(--danger-text);font-size:0.72rem;">RAISON DE VOTRE DÉPART (OPTIONNEL)</label>
                                    <textarea name="raison_depart" class="fc-dark" rows="2" placeholder="Aidez-nous à améliorer..." style="font-size:0.75rem;"></textarea>
                                </div>
                                <div style="display:flex;gap:6px;">
                                    <button type="submit" class="btn-danger-cct btn-sm w-50" onclick="return confirm('Êtes-vous absolument sûr(e) ? Cette action est irréversible.')">
                                        SUPPRIMER MON COMPTE
                                    </button>
                                    <form method="POST" style="flex:1;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="cancel_deletion">
                                        <button type="submit" class="btn-ghost btn-sm w-100">Annuler</button>
                                    </form>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
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
</body>
</html>

new Chart(ctxRdv, {
    type: 'doughnut',
    data: {
        labels: ['En attente', 'Acceptés', 'Terminés', 'Annulés'],
        datasets: [{
            data: [<?= ($stats_rdv['en_attente'] ?? 0) ?>,<?= ($stats_rdv['accepte'] ?? 0) ?>,<?= ($stats_rdv['termine'] ?? 0) ?>,<?= ($stats_rdv['annule'] ?? 0) ?>],
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

// ═══ OTP Deletion Input Handler ═══
document.addEventListener('DOMContentLoaded', function() {
    const deletionOtpInputs = document.querySelectorAll('.deletion-otp-input');
    
    deletionOtpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            if (!/^\d*$/.test(this.value)) {
                this.value = '';
                return;
            }
            if (this.value.length > 1) {
                this.value = this.value.slice(-1);
            }
            
            // Update hidden field
            const code = Array.from(deletionOtpInputs).map(i => i.value).join('');
            document.getElementById('deletion_otp_code').value = code;
            
            // Auto focus next
            if (this.value && index < deletionOtpInputs.length - 1) {
                deletionOtpInputs[index + 1].focus();
            }
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                deletionOtpInputs[index - 1].focus();
                e.preventDefault();
            } else if (e.key === 'ArrowLeft' && index > 0) {
                deletionOtpInputs[index - 1].focus();
                e.preventDefault();
            } else if (e.key === 'ArrowRight' && index < deletionOtpInputs.length - 1) {
                deletionOtpInputs[index + 1].focus();
                e.preventDefault();
            }
        });
    });
});
</script>
</body>
</html>
