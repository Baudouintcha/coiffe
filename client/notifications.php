<?php
/**
 * client/notifications.php — Centre de notifications client
 * Design System v2.0 — Coiffe Chez Toi V1.0
 *
 * Affiche les 50 dernières notifications, marque tout comme lu à l'ouverture.
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/notifications.php';

// ── Authentification ──────────────────────────────────────────────────────────
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'client') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

$id_user = (int) $_SESSION['id_user'];

// ── Marquer TOUTES comme lues à l'ouverture ───────────────────────────────────
try {
    $pdo->prepare(
        "UPDATE notifications SET statut_lecture = 'lu' WHERE id_user = ? AND statut_lecture = 'non_lu'"
    )->execute([$id_user]);
} catch (Exception $e) {
    // Non-bloquant
}

// ── Récupérer les 50 dernières notifications ──────────────────────────────────
$notifications = [];
try {
    $stmt = $pdo->prepare(
        "SELECT id_notif, message, type, statut_lecture, date_notification
         FROM notifications
         WHERE id_user = ?
         ORDER BY date_notification DESC
         LIMIT 50"
    );
    $stmt->execute([$id_user]);
    $notifications = $stmt->fetchAll();
} catch (Exception $e) {
    $notifications = [];
}

// ── Helper : date relative ────────────────────────────────────────────────────
function date_relative_notif(string $date_str): string {
    $ts   = strtotime($date_str);
    $diff = time() - $ts;
    if ($diff < 60)     return 'à l\'instant';
    if ($diff < 3600)   return 'il y a ' . floor($diff / 60) . ' min';
    if ($diff < 86400)  return 'il y a ' . floor($diff / 3600) . 'h';
    if ($diff < 172800) return 'hier';
    if ($diff < 604800) return 'il y a ' . floor($diff / 86400) . ' jours';
    return date('d/m/Y', $ts);
}

// ── Config visuelle par type ──────────────────────────────────────────────────
$type_config = [
    'info'    => ['icon' => 'bi-info-circle-fill',        'color' => '#3b9eff', 'bg' => 'rgba(59,158,255,0.08)',  'border' => 'rgba(59,158,255,0.30)'],
    'success' => ['icon' => 'bi-check-circle-fill',       'color' => '#28c76f', 'bg' => 'rgba(40,199,111,0.08)', 'border' => 'rgba(40,199,111,0.30)'],
    'danger'  => ['icon' => 'bi-exclamation-circle-fill', 'color' => '#ea5455', 'bg' => 'rgba(234,84,85,0.08)',  'border' => 'rgba(234,84,85,0.30)'],
    'warning' => ['icon' => 'bi-exclamation-triangle-fill','color'=> '#ff9f43', 'bg' => 'rgba(255,159,67,0.08)', 'border' => 'rgba(255,159,67,0.30)'],
];
$type_default = ['icon' => 'bi-bell-fill', 'color' => 'var(--gold)', 'bg' => 'var(--glass-bg)', 'border' => 'var(--glass-border)'];

$page_root     = '/coiffons';
$nb_notifs     = 0; // déjà marquées lues — badge = 0
$prenom_client = htmlspecialchars($_SESSION['prenom'] ?? 'Client');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes notifications — Coiffe Chez Toi</title>

    <!-- Design System v2.0 -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Page Notifications DS v2.0 ── */
    .notif-page-wrapper {
        padding-top: calc(var(--navbar-height) + 2rem);
        padding-bottom: 6rem;
        min-height: 100vh;
        background: var(--dark);
    }
    .notif-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .notif-page-title {
        font-family: var(--font-display);
        font-size: clamp(1.4rem, 3vw, 1.9rem);
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        letter-spacing: 1px;
    }
    /* Carte notification */
    .notif-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: var(--radius-md);
        border: 1px solid var(--glass-border);
        background: var(--glass-bg);
        margin-bottom: .75rem;
        transition: var(--transition-fast);
    }
    .notif-icon-wrap {
        width: 42px;
        height: 42px;
        min-width: 42px;
        border-radius: var(--radius-circle);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .notif-body   { flex: 1; min-width: 0; }
    .notif-msg    { font-size: .88rem; line-height: 1.55; margin-bottom: .3rem; color: var(--text-primary); }
    .notif-date   { font-size: .72rem; color: var(--text-muted); }
    </style>
</head>
<body>

<?php include __DIR__ . '/../views/components/navbar_client.php'; ?>

<div class="notif-page-wrapper page-transition">
    <div class="container" style="max-width:720px;">

        <!-- ── En-tête ── -->
        <div class="notif-page-header">
            <h1 class="notif-page-title">Mes notifications</h1>
            <a href="/coiffons/views/client/dashboard.php"
               class="btn-ghost btn-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Retour au dashboard
            </a>
        </div>

        <!-- ── Liste notifications ── -->
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5" style="color:var(--text-muted);">
                <i class="bi bi-bell-slash" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.4;" aria-hidden="true"></i>
                <p style="font-size:.95rem;">Aucune notification pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif):
                $cfg   = $type_config[$notif['type']] ?? $type_default;
                $date_r = date_relative_notif($notif['date_notification']);
            ?>
                <div class="notif-card"
                     style="background:<?= $cfg['bg'] ?>;border-color:<?= $cfg['border'] ?>;">
                    <div class="notif-icon-wrap"
                         style="background:<?= $cfg['bg'] ?>;border:1px solid <?= $cfg['border'] ?>;color:<?= $cfg['color'] ?>;">
                        <i class="bi <?= htmlspecialchars($cfg['icon']) ?>" aria-hidden="true"></i>
                    </div>
                    <div class="notif-body">
                        <p class="notif-msg"><?= htmlspecialchars($notif['message']) ?></p>
                        <span class="notif-date">
                            <i class="bi bi-clock me-1" aria-hidden="true"></i><?= $date_r ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ── CTA bas de page ── -->
        <div class="text-center mt-4">
            <a href="/coiffons/views/client/dashboard.php"
               class="btn-ghost btn-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-house" aria-hidden="true"></i>
                Retour au dashboard
            </a>
        </div>

    </div><!-- /container -->
</div>

<?php
include __DIR__ . '/../views/components/footer_global.php';
$current_page = 'notifications.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
