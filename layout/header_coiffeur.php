<?php
/**
 * layout/header_coiffeur.php — Navbar premium partagée pour toutes les pages coiffeur
 * Remplace layout/header.php pour le parcours coiffeur
 * Design Dark/Gold cohérent avec le dashboard client
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Sécurité : coiffeur uniquement
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'prestataire') {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

require_once __DIR__ . '/../security/config.php';

$prestataire_id   = $_SESSION['user_id'];
$prenom_nav    = htmlspecialchars($_SESSION['prenom'] ?? 'Coiffeur');
$initiale_nav  = strtoupper(substr($prenom_nav, 0, 1));
$photo_nav     = $_SESSION['photo_profil'] ?? null;
$current_page  = basename($_SERVER['PHP_SELF']);

// Notifications non lues
$nb_notifs_nav = 0;
try {
    $q = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE id = ? AND lu = 'non_lu'");
    $q->execute([$prestataire_id]);
    $nb_notifs_nav = intval($q->fetchColumn());
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coiffe Chez Toi — Espace Coiffeur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Design System v2.0 — Ordre officiel -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <style>
        :root {
            --gold: #D4AF37;
            --gold-dim: rgba(212,175,55,0.12);
            --dark: #0a0a0a;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: var(--dark);
            color: #fff;
            font-family: 'Inter', sans-serif;
        }

        /* ── PAGE TRANSITION ── */
        .page-transition {
            animation: fadeSlideIn 0.3s ease forwards;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── TOPBAR COIFFEUR ── */
        .coiffeur-topbar {
            position: sticky; top: 0; z-index: 200;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 2rem; height: 60px;
            background: rgba(0,0,0,0.92);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(212,175,55,0.12);
        }
        .topbar-left { display:flex; align-items:center; gap:14px; }
        .topbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1rem; font-weight: 700;
            color: var(--gold); text-decoration: none;
            letter-spacing: 1px;
            /* LOGO : remplace ce texte par <img> quand prêt */
        }
        .topbar-page {
            font-size: 0.8rem; color: rgba(255,255,255,0.3);
            padding-left: 14px;
            border-left: 1px solid rgba(255,255,255,0.08);
        }
        .topbar-right { display:flex; align-items:center; gap:10px; }
        .t-btn {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            display: flex; align-items:center; justify-content:center;
            color: rgba(255,255,255,0.5); cursor:pointer;
            transition: all 0.2s; text-decoration: none;
            position: relative;
        }
        .t-btn:hover { background: var(--gold-dim); color: var(--gold); border-color: var(--gold); }
        .t-notif-dot {
            position: absolute; top: 5px; right: 5px;
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--gold); border: 2px solid #000;
        }
        .t-avatar-ph {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--gold-dim); border: 2px solid var(--gold);
            display: flex; align-items:center; justify-content:center;
            font-size: 0.78rem; font-weight: 700; color: var(--gold);
            text-decoration: none;
        }
        .t-avatar-img {
            width: 36px; height: 36px; border-radius: 50%;
            border: 2px solid var(--gold); object-fit: cover;
        }

        /* ── BREADCRUMB BOTTOM NAV (mobile) ── */
        .coiffeur-bottom-nav {
            display: none; position: fixed; bottom: 0; left: 0; right: 0;
            background: #000; border-top: 1px solid rgba(255,255,255,0.06);
            z-index: 200; padding: 6px 0 2px;
        }
        .cbn-items { display:flex; justify-content:space-around; }
        .cbn-item {
            display:flex; flex-direction:column; align-items:center; gap:2px;
            color: rgba(255,255,255,0.3); text-decoration:none;
            font-size: 0.58rem; padding: 4px 10px;
            border-radius: 8px; transition: color 0.2s;
        }
        .cbn-item i { font-size: 1.15rem; }
        .cbn-item.active, .cbn-item:hover { color: var(--gold); }

        /* ── PAGE BODY ── */
        .coiffeur-page-body {
            padding: 1.5rem 2rem 5rem;
            background: var(--dark);
            min-height: calc(100vh - 60px);
        }

        /* ── FORM CONTROLS DARK ── */
        .fc-dark {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.12) !important;
            color: #fff !important;
            border-radius: 10px !important;
        }
        .fc-dark:focus {
            border-color: var(--gold) !important;
            background: rgba(255,255,255,0.1) !important;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.12) !important;
            color: #fff !important;
        }
        .fc-dark::placeholder { color: rgba(255,255,255,0.25) !important; }
        .fc-dark option { background: #111; color: #fff; }

        /* ── BTN GOLD : utiliser .btn-gold du DS v2.0 (components.css) ── */

        /* ── GLASS CARD ── */
        .glass-cct {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
        }
        .glass-cct:hover { border-color: rgba(212,175,55,0.25); }

        /* ── ALERT MESSAGES ── */
        .msg-success {
            background: rgba(25,135,84,0.15); border: 1px solid rgba(25,135,84,0.3);
            color: #6ee7b7; border-radius: 10px; padding: 10px 16px; font-size:0.85rem;
        }
        .msg-danger {
            background: rgba(220,53,69,0.15); border: 1px solid rgba(220,53,69,0.3);
            color: #ffb3b3; border-radius: 10px; padding: 10px 16px; font-size:0.85rem;
        }

        @media (max-width: 768px) {
            .coiffeur-topbar { padding: 0 1rem; }
            .coiffeur-page-body { padding: 1rem 1rem 5rem; }
            .coiffeur-bottom-nav { display: block; }
            .topbar-page { display: none; }
        }
    </style>
</head>
<body>

<!-- ── TOPBAR ── -->
<nav class="coiffeur-topbar">
    <div class="topbar-left">
        <a href="/coiffons/index.php?page=dashboard_coiffeur" class="topbar-brand">
            <!-- LOGO ICI -->
            CCT
        </a>
        <span class="topbar-page">Espace Coiffeur</span>
    </div>
    <div class="topbar-right">
        <!-- Retour dashboard -->
        <a href="/coiffons/index.php?page=dashboard_coiffeur" class="t-btn" title="Dashboard">
            <i class="bi bi-grid-1x2"></i>
        </a>
        <!-- Notifications -->
        <a href="#" class="t-btn" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($nb_notifs_nav > 0): ?>
                <span class="t-notif-dot"></span>
            <?php endif; ?>
        </a>
        <!-- Avatar profil -->
        <a href="/coiffons/coiffeurs/profil_coiffeurs.php">
            <?php if ($photo_nav && file_exists(__DIR__ . '/../' . $photo_nav)): ?>
                <img src="/coiffons/<?= htmlspecialchars($photo_nav) ?>" class="t-avatar-img" alt="">
            <?php else: ?>
                <div class="t-avatar-ph"><?= $initiale_nav ?></div>
            <?php endif; ?>
        </a>
    </div>
</nav>

<!-- ── BOTTOM NAV MOBILE ── -->
<nav class="coiffeur-bottom-nav">
    <div class="cbn-items">
        <a href="/coiffons/index.php?page=dashboard_coiffeur"
           class="cbn-item <?= $current_page === 'index.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2"></i>Dashboard
        </a>
        <a href="/coiffons/coiffeurs/gestion_catalogue.php"
           class="cbn-item <?= $current_page === 'gestion_catalogue.php' ? 'active' : '' ?>">
            <i class="bi bi-scissors"></i>Catalogue
        </a>
        <a href="/coiffons/coiffeurs/agenda_coiffeurs.php"
           class="cbn-item <?= $current_page === 'agenda_coiffeurs.php' ? 'active' : '' ?>">
            <i class="bi bi-calendar3"></i>Agenda
        </a>
        <a href="/coiffons/coiffeurs/valider_rendezvous.php"
           class="cbn-item <?= $current_page === 'valider_rendezvous.php' ? 'active' : '' ?>">
            <i class="bi bi-check2-circle"></i>RDV
        </a>
        <a href="/coiffons/coiffeurs/mes_avis.php"
           class="cbn-item <?= $current_page === 'mes_avis.php' ? 'active' : '' ?>">
            <i class="bi bi-star-half"></i>Avis
        </a>
        <a href="/coiffons/deconnexion.php" class="cbn-item">
            <i class="bi bi-box-arrow-right"></i>Quitter
        </a>
    </div>
</nav>

<div class="coiffeur-page-body page-transition">
