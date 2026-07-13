<?php
/**
 * views/coiffeur/dashboard.php — Dashboard Coiffeur Premium
 *
 * Compatible BDD actuelle (cft) ET future (domizi)
 * Les requêtes utilisent des noms de tables universels
 * Pour migrer vers domizi : changer security/config.php uniquement
 *
 * Inclus par index.php via route ?page=dashboard_coiffeur
 * Variables disponibles : $pdo, $_SESSION
 */

// ── Sécurité : coiffeur connecté uniquement ──
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'coiffeur') {
    header("Location: /coiffons/index.php?page=login");
    exit();
}

$coiffeur_id  = $_SESSION['id_user'];
$prenom       = htmlspecialchars($_SESSION['prenom'] ?? 'Coiffeur');
$initiale     = strtoupper(substr($prenom, 0, 1));
$photo_profil = $_SESSION['photo_profil'] ?? null;

// ── Heure du jour pour le message de bienvenue ──
$heure = (int) date('H');
$salutation = $heure < 12 ? 'Bonjour' : ($heure < 18 ? 'Bon après-midi' : 'Bonsoir');

// ── Statuts abonnement et approbation ──
// Compatible cft (abonnement_status = 1/0) et domizi (actif/inactif)
$abonnement_actif = false;
$diplome_approuve = false;
$solde_disponible = 0;

try {
    $stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_user->execute([$coiffeur_id]);
    $coiffeur_data = $stmt_user->fetch();

    if ($coiffeur_data) {
        // Compatibilité cft (1/0) et domizi ('actif'/'inactif')
        $abo = $coiffeur_data['abonnement_status'] ?? 0;
        $abonnement_actif = ($abo == 1 || $abo === 'actif');
        $diplome_approuve = (bool)($coiffeur_data['is_approved'] ?? 0);
        $solde_disponible = floatval($coiffeur_data['solde'] ?? 0);
        $photo_profil     = $coiffeur_data['photo_profil'] ?? null;
    }
} catch (Exception $e) {
    $coiffeur_data = [];
}

// ── Stats du jour ──
$today              = date('Y-m-d');
$rdv_aujourd_hui    = 0;
$gains_aujourd_hui  = 0;
$demandes_attente   = 0;

// ── Prochain RDV ──
$prochain_rdv = null;

// ── Planning du jour (RDV aujourd'hui) ──
$planning_jour = [];

// ── Demandes en attente ──
$demandes = [];

// ── Mes services (catalogue) ──
$mes_services = [];

// ── Notifications non lues ──
$nb_notifs   = 0;
$notifs_list = [];

try {
    // Stats du jour
    $q = $pdo->prepare("
        SELECT COUNT(*) as nb, COALESCE(SUM(p.prix), 0) as gains
        FROM rendez_vous r
        JOIN prestations p ON r.coiffure_id = p.id_prestation
        WHERE r.coiffeur_id = ? AND r.date_rdv = ?
        AND r.statut_rdv IN ('confirme', 'accepte', 'termine')
    ");
    $q->execute([$coiffeur_id, $today]);
    $stats = $q->fetch();
    $rdv_aujourd_hui   = intval($stats['nb'] ?? 0);
    $gains_aujourd_hui = floatval($stats['gains'] ?? 0);

    // Demandes en attente
    $q2 = $pdo->prepare("
        SELECT COUNT(*) FROM rendez_vous
        WHERE coiffeur_id = ? AND statut_rdv = 'en_attente'
    ");
    $q2->execute([$coiffeur_id]);
    $demandes_attente = intval($q2->fetchColumn());

    // Prochain RDV (le plus proche dans le futur)
    $q3 = $pdo->prepare("
        SELECT r.*, p.nom_style, u.nom as client_nom, u.prenom as client_prenom,
               u.photo_profil as client_photo, u.telephone as client_tel,
               r.client_adresse_texte, r.client_gps_lat, r.client_gps_lng
        FROM rendez_vous r
        JOIN prestations p ON r.coiffure_id = p.id_prestation
        JOIN users u ON r.client_id = u.id
        WHERE r.coiffeur_id = ?
        AND r.statut_rdv IN ('confirme', 'accepte', 'en_attente')
        AND CONCAT(r.date_rdv, ' ', r.heure_debut) >= NOW()
        ORDER BY r.date_rdv ASC, r.heure_debut ASC
        LIMIT 1
    ");
    $q3->execute([$coiffeur_id]);
    $prochain_rdv = $q3->fetch();

    // Planning du jour
    $q4 = $pdo->prepare("
        SELECT r.*, p.nom_style, p.duree, u.nom as client_nom, u.prenom as client_prenom,
               u.photo_profil as client_photo, r.client_adresse_texte
        FROM rendez_vous r
        JOIN prestations p ON r.coiffure_id = p.id_prestation
        JOIN users u ON r.client_id = u.id
        WHERE r.coiffeur_id = ? AND r.date_rdv = ?
        ORDER BY r.heure_debut ASC
    ");
    $q4->execute([$coiffeur_id, $today]);
    $planning_jour = $q4->fetchAll();

    // Demandes en attente (liste)
    $q5 = $pdo->prepare("
        SELECT r.*, p.nom_style, p.prix, u.nom as client_nom, u.prenom as client_prenom,
               u.photo_profil as client_photo
        FROM rendez_vous r
        JOIN prestations p ON r.coiffure_id = p.id_prestation
        JOIN users u ON r.client_id = u.id
        WHERE r.coiffeur_id = ? AND r.statut_rdv = 'en_attente'
        ORDER BY r.date_demande DESC
        LIMIT 5
    ");
    $q5->execute([$coiffeur_id]);
    $demandes = $q5->fetchAll();

    // Mes services (catalogue)
    $q6 = $pdo->prepare("
        SELECT * FROM prestations WHERE id_coiffeur = ?
        ORDER BY id_prestation DESC LIMIT 6
    ");
    $q6->execute([$coiffeur_id]);
    $mes_services = $q6->fetchAll();

    // Notifications
    $q7 = $pdo->prepare("
        SELECT COUNT(*) FROM notifications
        WHERE id_user = ? AND statut_lecture = 'non_lu'
    ");
    $q7->execute([$coiffeur_id]);
    $nb_notifs = intval($q7->fetchColumn());

    $q8 = $pdo->prepare("
        SELECT * FROM notifications WHERE id_user = ?
        ORDER BY date_notification DESC LIMIT 5
    ");
    $q8->execute([$coiffeur_id]);
    $notifs_list = $q8->fetchAll();

} catch (Exception $e) {
    // Silencieux en prod — erreurs logguées
}

// ── Calcul complétude du profil ──
$completude = 0;
$checks = [
    'photo'        => !empty($coiffeur_data['photo_profil']),
    'services'     => count($mes_services) > 0,
    'disponibilites'=> false, // vérifié ci-dessous
    'zones'        => false,  // vérifié ci-dessous
    'diplome'      => !empty($coiffeur_data['diplome']),
    'approbation'  => $diplome_approuve,
];

try {
    $dispo_count = $pdo->prepare("SELECT COUNT(*) FROM disponibilites WHERE prestataire_id = ?");
    $dispo_count->execute([$coiffeur_id]);
    $checks['disponibilites'] = intval($dispo_count->fetchColumn()) > 0;

    $zones_count = $pdo->prepare("SELECT COUNT(*) FROM zones_coiffeur WHERE id_coiffeur = ?");
    $zones_count->execute([$coiffeur_id]);
    $checks['zones'] = intval($zones_count->fetchColumn()) > 0;
} catch (Exception $e) {}

$total_checks  = count($checks);
$done_checks   = count(array_filter($checks));
$completude_pct = round(($done_checks / $total_checks) * 100);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon Dashboard — Coiffe Chez Toi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --gold: #D4AF37;
    --gold-dim: rgba(212,175,55,0.12);
    --gold-border: rgba(212,175,55,0.25);
    --dark: #0a0a0a;
    --dark-2: #111;
    --dark-3: #161616;
    --sidebar-w: 240px;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { background:var(--dark); color:#fff; font-family:'Inter',sans-serif; min-height:100vh; }

/* ── SIDEBAR DESKTOP ── */
.sidebar {
    position: fixed; top:0; left:0; bottom:0;
    width: var(--sidebar-w);
    background: #000;
    border-right: 1px solid rgba(255,255,255,0.06);
    display: flex; flex-direction: column;
    z-index: 200;
    transition: transform 0.3s;
}
.sidebar-brand {
    padding: 24px 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.sidebar-brand-text {
    font-family:'Playfair Display',serif;
    font-size:1rem; font-weight:700; color:var(--gold);
    text-decoration:none; letter-spacing:1px;
    /* LOGO : remplace ce texte par <img src="..."> quand le logo est prêt */
}
.sidebar-avatar {
    display:flex; align-items:center; gap:10px;
    padding:16px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.s-avatar-img { width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--gold); }
.s-avatar-ph {
    width:36px;height:36px;border-radius:50%;
    background:var(--gold-dim);border:2px solid var(--gold);
    display:flex;align-items:center;justify-content:center;
    font-weight:700;color:var(--gold);font-size:0.82rem;
}
.s-name { font-size:0.82rem;font-weight:600;color:#fff; }
.s-role { font-size:0.68rem;color:rgba(255,255,255,0.35); }
.sidebar-nav { flex:1; padding:12px 0; overflow-y:auto; }
.nav-item {
    display:flex; align-items:center; gap:10px;
    padding:11px 20px;
    color:rgba(255,255,255,0.45);
    text-decoration:none; font-size:0.85rem;
    transition:all 0.2s; border-left:3px solid transparent;
}
.nav-item:hover { color:#fff; background:rgba(255,255,255,0.04); }
.nav-item.active { color:var(--gold); border-left-color:var(--gold); background:var(--gold-dim); }
.nav-item i { font-size:1rem; width:18px; text-align:center; }
.nav-badge {
    margin-left:auto; background:var(--gold); color:#000;
    font-size:0.65rem; font-weight:700;
    padding:2px 7px; border-radius:20px;
}
.sidebar-footer {
    padding:12px 0;
    border-top:1px solid rgba(255,255,255,0.06);
}
.sidebar-footer a {
    display:flex;align-items:center;gap:10px;
    padding:11px 20px;
    color:rgba(255,255,255,0.35);
    text-decoration:none;font-size:0.85rem;
    transition:color 0.2s;
}
.sidebar-footer a:hover { color:#ff6b6b; }

/* ── MAIN CONTENT ── */
.main-content {
    margin-left: var(--sidebar-w);
    min-height:100vh;
    background: var(--dark);
}
.topbar {
    position:sticky; top:0; z-index:100;
    display:flex; align-items:center; justify-content:space-between;
    padding:0 2rem; height:60px;
    background:rgba(10,10,10,0.9);
    backdrop-filter:blur(16px);
    border-bottom:1px solid rgba(255,255,255,0.06);
}
.topbar-title { font-size:0.95rem; font-weight:600; color:#fff; }
.topbar-actions { display:flex; align-items:center; gap:12px; }
.topbar-btn {
    width:36px;height:36px;border-radius:50%;
    background:rgba(255,255,255,0.05);
    border:1px solid rgba(255,255,255,0.08);
    display:flex;align-items:center;justify-content:center;
    color:rgba(255,255,255,0.55);cursor:pointer;
    transition:all 0.2s; text-decoration:none; position:relative;
}
.topbar-btn:hover { background:var(--gold-dim);color:var(--gold);border-color:var(--gold); }
.notif-dot {
    position:absolute;top:6px;right:6px;
    width:8px;height:8px;border-radius:50%;
    background:var(--gold);border:2px solid #000;
}
.page-body { padding:1.5rem 2rem 4rem; }

/* ── BANNIÈRES ── */
.banner {
    padding:12px 16px; border-radius:12px;
    display:flex;align-items:center;gap:10px;
    margin-bottom:12px; font-size:0.85rem;
}
.banner-danger { background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ffb3b3; }
.banner-warning { background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.25);color:#ffd60a; }
.banner-success { background:rgba(25,135,84,0.12);border:1px solid rgba(25,135,84,0.25);color:#6ee7b7; }
.banner a { color:inherit;font-weight:700;text-underline-offset:3px; }
.banner i { font-size:1rem;flex-shrink:0; }

/* ── HERO STATS ── */
.hero-stats {
    background:linear-gradient(135deg, rgba(212,175,55,0.08) 0%, rgba(255,255,255,0.03) 100%);
    border:1px solid var(--gold-border);
    border-radius:20px; padding:24px 28px; margin-bottom:1.5rem;
    display:flex;align-items:center;justify-content:space-between;
    flex-wrap:wrap;gap:20px;
}
.hero-greet h2 { font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;margin-bottom:3px; }
.hero-greet p { color:rgba(255,255,255,0.4);font-size:0.82rem; }
.stats-row { display:flex;gap:24px;flex-wrap:wrap; }
.stat-box {
    text-align:center; min-width:90px;
    padding:12px 16px; border-radius:12px;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.06);
}
.stat-val { font-size:1.4rem;font-weight:700;color:var(--gold);display:block; }
.stat-lbl { font-size:0.68rem;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.5px; }

/* ── GLASS CARDS ── */
.g-card {
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.07);
    border-radius:16px; overflow:hidden;
    transition:border-color 0.2s;
}
.g-card:hover { border-color:var(--gold-border); }
.g-card-header {
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 20px 12px;
    border-bottom:1px solid rgba(255,255,255,0.05);
}
.g-card-title { font-size:0.88rem;font-weight:700;color:#fff; }
.g-card-link { font-size:0.75rem;color:var(--gold);text-decoration:none; }
.g-card-link:hover { text-decoration:underline; }
.g-card-body { padding:16px 20px; }

/* ── PROCHAIN RDV ── */
.next-rdv-card {
    display:flex;align-items:center;gap:16px;
    padding:16px 20px;
}
.rdv-time-block {
    text-align:center;min-width:60px;
    background:var(--gold-dim);border-radius:12px;
    padding:10px 8px;
}
.rdv-time { font-size:1.1rem;font-weight:700;color:var(--gold);display:block; }
.rdv-date-s { font-size:0.65rem;color:rgba(255,255,255,0.4); }
.rdv-info { flex:1;min-width:0; }
.rdv-client { font-weight:600;font-size:0.9rem; }
.rdv-service { font-size:0.78rem;color:rgba(255,255,255,0.45);margin:2px 0; }
.rdv-addr { font-size:0.75rem;color:rgba(212,175,55,0.7); }
.rdv-actions { display:flex;gap:8px; }
.btn-gold-sm {
    background:var(--gold);color:#000;font-weight:700;
    font-size:0.72rem;padding:6px 14px;border-radius:20px;
    border:none;cursor:pointer;transition:background 0.2s;
    text-decoration:none;
}
.btn-gold-sm:hover { background:#c9a227;color:#000; }
.btn-outline-sm {
    background:transparent;color:rgba(255,255,255,0.5);
    font-size:0.72rem;padding:6px 14px;border-radius:20px;
    border:1px solid rgba(255,255,255,0.15);cursor:pointer;
    transition:all 0.2s;text-decoration:none;
}
.btn-outline-sm:hover { border-color:var(--gold);color:var(--gold); }

/* ── PLANNING DU JOUR ── */
.planning-item {
    display:flex;align-items:center;gap:14px;
    padding:12px 0;border-bottom:1px solid rgba(255,255,255,0.04);
}
.planning-item:last-child { border-bottom:none; }
.pl-time { font-size:0.8rem;font-weight:600;color:var(--gold);min-width:48px; }
.pl-info { flex:1;min-width:0; }
.pl-name { font-size:0.85rem;font-weight:600; }
.pl-service { font-size:0.72rem;color:rgba(255,255,255,0.4); }
.status-badge {
    font-size:0.65rem;font-weight:700;padding:3px 10px;
    border-radius:20px;white-space:nowrap;
}
.badge-upcoming { background:rgba(59,130,246,0.2);color:#93c5fd; }
.badge-done { background:rgba(25,135,84,0.2);color:#6ee7b7; }
.badge-cancelled { background:rgba(220,53,69,0.2);color:#ffb3b3; }
.badge-waiting { background:rgba(255,193,7,0.15);color:#ffd60a; }

/* ── DEMANDES EN ATTENTE ── */
.demand-card {
    background:rgba(255,255,255,0.03);
    border:1px solid rgba(255,255,255,0.06);
    border-radius:12px;padding:14px 16px;margin-bottom:10px;
}
.demand-card:last-child { margin-bottom:0; }
.demand-client { display:flex;align-items:center;gap:10px;margin-bottom:8px; }
.d-avatar-ph {
    width:32px;height:32px;border-radius:50%;
    background:var(--gold-dim);border:1px solid var(--gold-border);
    display:flex;align-items:center;justify-content:center;
    font-size:0.75rem;font-weight:700;color:var(--gold);
}
.d-name { font-size:0.85rem;font-weight:600; }
.d-date { font-size:0.72rem;color:rgba(255,255,255,0.35); }
.d-service { font-size:0.78rem;color:rgba(255,255,255,0.5);margin-bottom:10px; }
.d-price { font-size:0.82rem;font-weight:700;color:var(--gold);margin-bottom:10px; }
.d-actions { display:flex;gap:8px; }
.btn-accept {
    background:rgba(25,135,84,0.2);color:#6ee7b7;
    border:1px solid rgba(25,135,84,0.3);
    font-size:0.72rem;font-weight:700;
    padding:5px 14px;border-radius:20px;cursor:pointer;
    transition:all 0.2s;text-decoration:none;
}
.btn-accept:hover { background:rgba(25,135,84,0.35);color:#6ee7b7; }
.btn-decline {
    background:rgba(220,53,69,0.15);color:#ffb3b3;
    border:1px solid rgba(220,53,69,0.25);
    font-size:0.72rem;font-weight:700;
    padding:5px 14px;border-radius:20px;cursor:pointer;
    transition:all 0.2s;text-decoration:none;
}
.btn-decline:hover { background:rgba(220,53,69,0.3);color:#ffb3b3; }

/* ── PROFIL COMPLÉTUDE ── */
.completude-bar {
    height:6px;background:rgba(255,255,255,0.08);
    border-radius:10px;overflow:hidden;margin:10px 0;
}
.completude-fill {
    height:100%;background:linear-gradient(90deg,var(--gold),#c9a227);
    border-radius:10px;transition:width 0.6s ease;
}
.check-item {
    display:flex;align-items:center;gap:8px;
    font-size:0.78rem;padding:4px 0;
    color:rgba(255,255,255,0.5);
}
.check-item.ok { color:rgba(255,255,255,0.8); }
.check-item i { font-size:0.75rem;width:14px;text-align:center; }

/* ── SERVICES ── */
.service-card {
    background:rgba(255,255,255,0.03);
    border:1px solid rgba(255,255,255,0.06);
    border-radius:12px;overflow:hidden;
}
.service-img { width:100%;height:100px;object-fit:cover;background:var(--dark-3); }
.service-img-ph {
    width:100%;height:100px;
    background:linear-gradient(135deg,#151515,#1f1f1f);
    display:flex;align-items:center;justify-content:center;
    color:rgba(255,255,255,0.1);font-size:2rem;
}
.service-info { padding:10px 12px; }
.service-name { font-size:0.82rem;font-weight:600;margin-bottom:3px; }
.service-price { font-size:0.75rem;color:var(--gold);font-weight:700; }
.service-actions { display:flex;gap:6px;margin-top:8px; }
.sa-btn {
    flex:1;font-size:0.65rem;font-weight:700;
    padding:5px;border-radius:8px;border:none;cursor:pointer;
    transition:background 0.2s;text-decoration:none;text-align:center;
}
.sa-edit { background:var(--gold-dim);color:var(--gold); }
.sa-edit:hover { background:rgba(212,175,55,0.2);color:var(--gold); }

/* ── ACTIONS RAPIDES ── */
.quick-action {
    background:rgba(255,255,255,0.03);
    border:1px solid rgba(255,255,255,0.07);
    border-radius:14px;padding:16px;
    display:flex;flex-direction:column;align-items:center;
    gap:8px;text-align:center;
    text-decoration:none;color:#fff;
    transition:all 0.2s;
}
.quick-action:hover { border-color:var(--gold-border);background:var(--gold-dim);color:var(--gold); }
.qa-icon {
    width:44px;height:44px;border-radius:12px;
    background:var(--gold-dim);
    display:flex;align-items:center;justify-content:center;
    font-size:1.1rem;color:var(--gold);
}
.qa-label { font-size:0.72rem;font-weight:600; }

/* ── BOTTOM NAV MOBILE ── */
.bottom-nav {
    display:none;position:fixed;bottom:0;left:0;right:0;
    background:#000;border-top:1px solid rgba(255,255,255,0.08);
    z-index:200;padding:8px 0 4px;
}
.bn-items { display:flex;justify-content:space-around; }
.bn-item {
    display:flex;flex-direction:column;align-items:center;
    gap:3px;color:rgba(255,255,255,0.35);
    text-decoration:none;font-size:0.6rem;
    padding:4px 8px;border-radius:8px;transition:color 0.2s;
    min-width:50px;text-align:center;
}
.bn-item i { font-size:1.2rem; }
.bn-item.active { color:var(--gold); }
.bn-item:hover { color:var(--gold); }

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
    .sidebar { transform:translateX(-100%); }
    .sidebar.open { transform:translateX(0); }
    .main-content { margin-left:0; }
    .bottom-nav { display:block; }
    .page-body { padding:1rem 1rem 5rem; }
    .topbar { padding:0 1rem; }
}
@media (min-width: 901px) {
    .bottom-nav { display:none !important; }
}
</style>
</head>
<body>

<!-- ── SIDEBAR ── -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <a href="/coiffons/index.php" class="sidebar-brand-text">Coiffe Chez Toi</a>
    </div>
    <div class="sidebar-avatar">
        <?php if ($photo_profil && file_exists(__DIR__ . '/../../' . $photo_profil)): ?>
            <img src="/coiffons/<?= htmlspecialchars($photo_profil) ?>" class="s-avatar-img" alt="">
        <?php else: ?>
            <div class="s-avatar-ph"><?= $initiale ?></div>
        <?php endif; ?>
        <div>
            <div class="s-name"><?= $prenom ?></div>
            <div class="s-role">Coiffeur · <?= $abonnement_actif ? '<span style="color:#6ee7b7">Actif</span>' : '<span style="color:#ffb3b3">Inactif</span>' ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="?page=dashboard_coiffeur" class="nav-item active"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
        <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="nav-item">
            <i class="bi bi-calendar-check"></i> Rendez-vous
            <?php if ($demandes_attente > 0): ?><span class="nav-badge"><?= $demandes_attente ?></span><?php endif; ?>
        </a>
        <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="nav-item"><i class="bi bi-scissors"></i> Mes services</a>
        <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="nav-item"><i class="bi bi-calendar3"></i> Mon agenda</a>
        <a href="/coiffons/coiffeurs/mes_zones.php" class="nav-item"><i class="bi bi-geo-alt"></i> Mes zones</a>
        <a href="/coiffons/coiffeurs/portefeuille.php" class="nav-item"><i class="bi bi-wallet2"></i> Portefeuille</a>
        <a href="/coiffons/coiffeurs/profil_public.php?id=<?= $coiffeur_id ?>" class="nav-item"><i class="bi bi-person-badge"></i> Mon profil public</a>
    </nav>
    <div class="sidebar-footer">
        <a href="/coiffons/profil.php"><i class="bi bi-gear"></i> Paramètres</a>
        <a href="/coiffons/deconnexion.php"><i class="bi bi-box-arrow-left"></i> Déconnexion</a>
    </div>
</aside>

<!-- ── MAIN CONTENT ── -->
<div class="main-content">
    <!-- Topbar -->
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="topbar-btn d-block d-md-none" onclick="document.getElementById('sidebar').classList.toggle('open')">
                <i class="bi bi-list"></i>
            </button>
            <span class="topbar-title"><?= $salutation ?>, <?= $prenom ?> 👋</span>
        </div>
        <div class="topbar-actions">
            <a href="#" class="topbar-btn" title="Notifications">
                <i class="bi bi-bell"></i>
                <?php if ($nb_notifs > 0): ?><span class="notif-dot"></span><?php endif; ?>
            </a>
            <a href="/coiffons/profil.php" class="topbar-btn">
                <?php if ($photo_profil && file_exists(__DIR__ . '/../../' . $photo_profil)): ?>
                    <img src="/coiffons/<?= htmlspecialchars($photo_profil) ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="">
                <?php else: ?>
                    <span style="font-size:0.82rem;font-weight:700;color:var(--gold)"><?= $initiale ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <div class="page-body">

        <!-- BANNIÈRES -->
        <?php if (!$abonnement_actif): ?>
        <div class="banner banner-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>Votre abonnement est inactif — vous n'apparaissez pas dans l'annuaire.
                <a href="/coiffons/coiffeurs/portefeuille.php">Souscrire maintenant →</a>
            </span>
        </div>
        <?php endif; ?>
        <?php if (!$diplome_approuve): ?>
        <div class="banner banner-warning">
            <i class="bi bi-shield-exclamation"></i>
            <span>Votre diplôme est en cours de vérification — votre profil est moins visible jusqu'à l'approbation.</span>
        </div>
        <?php endif; ?>

        <!-- HERO STATS -->
        <div class="hero-stats">
            <div class="hero-greet">
                <h2><?= $salutation ?>, <?= $prenom ?> 👋</h2>
                <p><?= date('l d F Y') ?> · Voici votre activité du jour</p>
            </div>
            <div class="stats-row">
                <div class="stat-box">
                    <span class="stat-val"><?= $rdv_aujourd_hui ?></span>
                    <span class="stat-lbl">RDV aujourd'hui</span>
                </div>
                <div class="stat-box">
                    <span class="stat-val"><?= number_format($gains_aujourd_hui, 0, ',', ' ') ?></span>
                    <span class="stat-lbl">Gains (FCFA)</span>
                </div>
                <div class="stat-box">
                    <span class="stat-val"><?= $demandes_attente ?></span>
                    <span class="stat-lbl">En attente</span>
                </div>
                <div class="stat-box">
                    <span class="stat-val"><?= number_format($solde_disponible, 0, ',', ' ') ?></span>
                    <span class="stat-lbl">Solde (FCFA)</span>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Colonne gauche : prochain RDV + planning + demandes -->
            <div class="col-12 col-xl-8">

                <!-- PROCHAIN RDV -->
                <div class="g-card mb-3">
                    <div class="g-card-header">
                        <span class="g-card-title"><i class="bi bi-clock text-warning me-2"></i>Prochain rendez-vous</span>
                        <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="g-card-link">Voir tout →</a>
                    </div>
                    <?php if ($prochain_rdv): ?>
                        <div class="next-rdv-card">
                            <div class="rdv-time-block">
                                <span class="rdv-time"><?= substr($prochain_rdv['heure_debut'], 0, 5) ?></span>
                                <span class="rdv-date-s"><?= date('d/m', strtotime($prochain_rdv['date_rdv'])) ?></span>
                            </div>
                            <div class="rdv-info">
                                <div class="rdv-client"><?= htmlspecialchars($prochain_rdv['client_nom'] . ' ' . $prochain_rdv['client_prenom']) ?></div>
                                <div class="rdv-service"><?= htmlspecialchars($prochain_rdv['nom_style'] ?? '') ?></div>
                                <?php if (!empty($prochain_rdv['client_adresse_texte'])): ?>
                                    <div class="rdv-addr">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <?= htmlspecialchars($prochain_rdv['client_adresse_texte']) ?>
                                        <?php if ($prochain_rdv['client_gps_lat'] && $prochain_rdv['client_gps_lng']): ?>
                                            <a href="https://www.google.com/maps?q=<?= $prochain_rdv['client_gps_lat'] ?>,<?= $prochain_rdv['client_gps_lng'] ?>"
                                               target="_blank" style="color:var(--gold);font-size:0.7rem;margin-left:4px;">
                                                Ouvrir Maps →
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="rdv-actions">
                                <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="btn-gold-sm">Détails</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="g-card-body text-center py-4">
                            <i class="bi bi-calendar-x" style="font-size:2rem;color:rgba(255,255,255,0.1)"></i>
                            <p style="color:rgba(255,255,255,0.3);font-size:0.82rem;margin-top:8px">Aucun prochain rendez-vous</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PLANNING DU JOUR -->
                <div class="g-card mb-3">
                    <div class="g-card-header">
                        <span class="g-card-title"><i class="bi bi-list-check text-warning me-2"></i>Planning du jour</span>
                        <span style="font-size:0.72rem;color:rgba(255,255,255,0.3)"><?= date('d/m/Y') ?></span>
                    </div>
                    <div class="g-card-body">
                        <?php if (empty($planning_jour)): ?>
                            <p style="color:rgba(255,255,255,0.3);font-size:0.82rem;text-align:center;padding:16px 0">Aucun RDV aujourd'hui</p>
                        <?php else: ?>
                            <?php foreach ($planning_jour as $rdv): ?>
                                <div class="planning-item">
                                    <div class="pl-time"><?= substr($rdv['heure_debut'], 0, 5) ?></div>
                                    <div class="pl-info">
                                        <div class="pl-name"><?= htmlspecialchars($rdv['client_nom'] . ' ' . $rdv['client_prenom']) ?></div>
                                        <div class="pl-service"><?= htmlspecialchars($rdv['nom_style'] ?? '') ?> · <?= $rdv['duree'] ?? 60 ?> min</div>
                                    </div>
                                    <?php
                                    $st = $rdv['statut_rdv'] ?? 'en_attente';
                                    $badge_class = match($st) {
                                        'confirme','accepte' => 'badge-upcoming',
                                        'termine'            => 'badge-done',
                                        'annule'             => 'badge-cancelled',
                                        default              => 'badge-waiting',
                                    };
                                    $badge_label = match($st) {
                                        'confirme','accepte' => 'Confirmé',
                                        'termine'            => 'Terminé',
                                        'annule'             => 'Annulé',
                                        default              => 'En attente',
                                    };
                                    ?>
                                    <span class="status-badge <?= $badge_class ?>"><?= $badge_label ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- DEMANDES EN ATTENTE -->
                <?php if (!empty($demandes)): ?>
                <div class="g-card mb-3">
                    <div class="g-card-header">
                        <span class="g-card-title"><i class="bi bi-hourglass-split text-warning me-2"></i>Demandes en attente</span>
                        <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="g-card-link">Gérer tout →</a>
                    </div>
                    <div class="g-card-body">
                        <?php foreach ($demandes as $d): ?>
                            <div class="demand-card">
                                <div class="demand-client">
                                    <div class="d-avatar-ph"><?= strtoupper(substr($d['client_prenom'] ?? 'C', 0, 1)) ?></div>
                                    <div>
                                        <div class="d-name"><?= htmlspecialchars($d['client_nom'] . ' ' . $d['client_prenom']) ?></div>
                                        <div class="d-date"><?= date('d/m/Y', strtotime($d['date_rdv'])) ?> à <?= substr($d['heure_debut'], 0, 5) ?></div>
                                    </div>
                                </div>
                                <div class="d-service"><?= htmlspecialchars($d['nom_style'] ?? '') ?></div>
                                <div class="d-price"><?= number_format($d['prix'] ?? 0, 0, ',', ' ') ?> FCFA</div>
                                <div class="d-actions">
                                    <a href="/coiffons/coiffeurs/valider_rendezvous.php?accepter=<?= $d['id'] ?>&csrf=<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>" class="btn-accept">
                                        <i class="bi bi-check-lg"></i> Accepter
                                    </a>
                                    <a href="/coiffons/coiffeurs/valider_rendezvous.php?refuser=<?= $d['id'] ?>&csrf=<?= htmlspecialchars($_SESSION['_csrf_token'] ?? '') ?>" class="btn-decline">
                                        <i class="bi bi-x-lg"></i> Refuser
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Colonne droite : profil + actions rapides + services -->
            <div class="col-12 col-xl-4">

                <!-- COMPLÉTUDE DU PROFIL -->
                <div class="g-card mb-3">
                    <div class="g-card-header">
                        <span class="g-card-title"><i class="bi bi-person-check text-warning me-2"></i>Mon profil public</span>
                        <span style="font-size:0.82rem;font-weight:700;color:var(--gold)"><?= $completude_pct ?>%</span>
                    </div>
                    <div class="g-card-body">
                        <div class="completude-bar">
                            <div class="completude-fill" style="width:<?= $completude_pct ?>%"></div>
                        </div>
                        <div class="mt-2">
                            <?php foreach ([
                                'photo'         => ['Photo de profil', $checks['photo']],
                                'services'      => ['Catalogue de services', $checks['services']],
                                'disponibilites'=> ['Agenda configuré', $checks['disponibilites']],
                                'zones'         => ['Zones d\'intervention', $checks['zones']],
                                'diplome'       => ['Diplôme uploadé', $checks['diplome']],
                                'approbation'   => ['Approuvé par l\'admin', $checks['approbation']],
                            ] as $key => [$label, $done]): ?>
                                <div class="check-item <?= $done ? 'ok' : '' ?>">
                                    <i class="bi <?= $done ? 'bi-check-circle-fill text-success' : 'bi-circle text-secondary' ?>"></i>
                                    <?= $label ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS RAPIDES -->
                <div class="g-card mb-3">
                    <div class="g-card-header">
                        <span class="g-card-title"><i class="bi bi-lightning text-warning me-2"></i>Actions rapides</span>
                    </div>
                    <div class="g-card-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="quick-action">
                                    <div class="qa-icon"><i class="bi bi-plus-circle"></i></div>
                                    <span class="qa-label">Ajouter service</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="quick-action">
                                    <div class="qa-icon"><i class="bi bi-calendar-plus"></i></div>
                                    <span class="qa-label">Mon agenda</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/coiffons/coiffeurs/mes_zones.php" class="quick-action">
                                    <div class="qa-icon"><i class="bi bi-geo-alt"></i></div>
                                    <span class="qa-label">Mes zones</span>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/coiffons/coiffeurs/portefeuille.php" class="quick-action">
                                    <div class="qa-icon"><i class="bi bi-wallet2"></i></div>
                                    <span class="qa-label">Portefeuille</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MES SERVICES (résumé) -->
                <div class="g-card">
                    <div class="g-card-header">
                        <span class="g-card-title"><i class="bi bi-scissors text-warning me-2"></i>Mes services</span>
                        <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="g-card-link">Gérer →</a>
                    </div>
                    <div class="g-card-body">
                        <?php if (empty($mes_services)): ?>
                            <p style="color:rgba(255,255,255,0.3);font-size:0.8rem;text-align:center;padding:12px 0">
                                Aucun service. <a href="/coiffons/coiffeurs/gestion_catalogue.php" style="color:var(--gold)">Ajouter →</a>
                            </p>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach (array_slice($mes_services, 0, 4) as $svc): ?>
                                    <div class="col-6">
                                        <div class="service-card">
                                            <?php
                                            $photo_svc = $svc['photo_style'] ?? null;
                                            $photo_path_svc = $photo_svc ? __DIR__ . '/../../uploads/' . $photo_svc : null;
                                            ?>
                                            <?php if ($photo_path_svc && file_exists($photo_path_svc)): ?>
                                                <img src="/coiffons/uploads/<?= htmlspecialchars($photo_svc) ?>" class="service-img" loading="lazy" alt="">
                                            <?php else: ?>
                                                <div class="service-img-ph"><i class="bi bi-image"></i></div>
                                            <?php endif; ?>
                                            <div class="service-info">
                                                <div class="service-name"><?= htmlspecialchars(mb_strimwidth($svc['nom_style'] ?? '', 0, 20, '…')) ?></div>
                                                <div class="service-price"><?= number_format($svc['prix'] ?? 0, 0, ',', ' ') ?> F</div>
                                                <div class="service-actions">
                                                    <a href="/coiffons/coiffeurs/gestion_catalogue.php?ouvrir_modifier=<?= $svc['id_prestation'] ?>" class="sa-btn sa-edit">
                                                        <i class="bi bi-pencil"></i> Éditer
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- BOTTOM NAV MOBILE -->
<nav class="bottom-nav">
    <div class="bn-items">
        <a href="?page=dashboard_coiffeur" class="bn-item active"><i class="bi bi-grid-1x2"></i><span>Accueil</span></a>
        <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="bn-item"><i class="bi bi-calendar-check"></i><span>RDV</span></a>
        <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="bn-item"><i class="bi bi-scissors"></i><span>Services</span></a>
        <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="bn-item"><i class="bi bi-calendar3"></i><span>Agenda</span></a>
        <a href="/coiffons/profil.php" class="bn-item"><i class="bi bi-person"></i><span>Profil</span></a>
    </div>
</nav>

<script>
// Ferme la sidebar si clic en dehors (mobile)
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});
</script>
</body>
</html>
