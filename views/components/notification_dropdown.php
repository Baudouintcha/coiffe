<?php
/**
 * views/components/notification_dropdown.php — NotificationDropdown
 * Design System v2.0 | CL §17
 *
 * Dropdown de notifications pour navbar.
 * Rendu sous forme de dropdown Bootstrap dark.
 *
 * Variables attendues :
 *   $nd_count         — nombre de notifications non lues
 *   $nd_notifications — array de notifications :
 *     [['type'=>'danger|info|standard', 'message'=>str, 'icone'=>'bi-*', 'lien'=>str, 'date'=>str], ...]
 *   $nd_mark_read_url — URL pour marquer tout comme lu
 *   $nd_trigger_id    — ID de l'élément déclencheur (défaut: 'notifDropdown')
 */

$nd_count         = $nd_count         ?? 0;
$nd_notifications = $nd_notifications ?? [];
$nd_mark_read_url = $nd_mark_read_url ?? '?action_notif=marquer_lu';
$nd_trigger_id    = $nd_trigger_id    ?? 'notifDropdown';
?>

<!-- Trigger -->
<div class="dropdown d-flex align-items-center">
    <a class="t-btn position-relative dropdown-toggle nd-toggle"
       href="#"
       id="<?= htmlspecialchars($nd_trigger_id) ?>"
       role="button"
       data-bs-toggle="dropdown"
       aria-expanded="false"
       aria-label="Notifications (<?= (int)$nd_count ?>)">
        <i class="bi bi-bell" aria-hidden="true"></i>
        <?php if ($nd_count > 0): ?>
            <span class="t-notif-dot animate-pulse" aria-hidden="true"></span>
        <?php endif; ?>
    </a>

    <!-- Panel -->
    <ul class="dropdown-menu dropdown-menu-end nd-panel p-2"
        aria-labelledby="<?= htmlspecialchars($nd_trigger_id) ?>"
        style="width:280px;">

        <!-- Header -->
        <li class="nd-panel-header d-flex justify-content-between border-bottom border-secondary pb-2 mb-2 px-2 small">
            <span class="nd-count-label">
                Alertes (<?= (int)$nd_count ?>)
            </span>
            <?php if ($nd_count > 0 && !empty($nd_notifications)): ?>
                <a href="<?= htmlspecialchars($nd_mark_read_url) ?>"
                   class="text-decoration-none"
                   style="color:var(--text-muted);font-size:0.75rem;">
                    Tout lire
                </a>
            <?php endif; ?>
        </li>

        <!-- Liste -->
        <?php if (empty($nd_notifications)): ?>
            <li class="text-center py-2 small" style="color:var(--text-muted);">
                Rien à signaler
            </li>
        <?php else: ?>
            <?php foreach ($nd_notifications as $notif):
                $bg = '';
                $tc = 'text-light';
                if ($notif['type'] === 'danger') {
                    $bg = 'background:#1a0505;border:1px solid #4a1414;';
                    $tc = 'text-danger fw-bold';
                } elseif ($notif['type'] === 'info') {
                    $bg = 'background:#0b131f;';
                    $tc = 'text-info';
                }
            ?>
            <li class="p-2 rounded mb-1" style="<?= $bg ?>">
                <a href="<?= htmlspecialchars($notif['lien'] ?? '#') ?>"
                   class="text-decoration-none d-block">
                    <p class="mb-0 <?= $tc ?> small" style="white-space:normal;font-size:0.82rem;">
                        <i class="bi <?= htmlspecialchars($notif['icone'] ?? 'bi-info-circle') ?> me-1"
                           aria-hidden="true"></i>
                        <?= htmlspecialchars($notif['message'] ?? '') ?>
                    </p>
                    <?php if (!empty($notif['date'])): ?>
                        <small style="color:var(--text-muted);font-size:0.60rem;display:block;text-align:right;">
                            <?= htmlspecialchars($notif['date']) ?>
                        </small>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        <?php endif; ?>

    </ul>
</div>

<style>
/* NotificationDropdown — CL §17 */
.nd-panel       { background-color:#0c0c0c !important; border:1px solid var(--glass-border) !important; border-radius:var(--radius-md) !important; margin-top:8px !important; }
.nd-count-label { color:var(--gold); font-weight:700; font-size:0.82rem; }
.nd-toggle      { font-size:1.1rem; }
</style>
<?php unset($nd_count, $nd_notifications, $nd_mark_read_url, $nd_trigger_id, $notif, $bg, $tc); ?>
