<?php
/**
 * views/components/modal.php — Modal (vanilla JS, sans Bootstrap)
 * Design System v2.0 | CL §18
 *
 * Variables attendues :
 *   $modal_id      — ID unique du modal (ex: 'modal-historique')
 *   $modal_title   — Titre affiché dans le header
 *   $modal_content — HTML du contenu (déjà préparé dans la page appelante)
 *   $modal_width   — max-width CSS (défaut: '700px')
 *
 * Le déclencheur dans la page :
 *   <button onclick="openModal('modal-historique')">Voir</button>
 *
 * Injection du JS helper une seule fois (utiliser ob_start si besoin de conditionnement).
 */

$modal_id      = $modal_id      ?? 'modal-' . uniqid();
$modal_title   = $modal_title   ?? '';
$modal_content = $modal_content ?? '';
$modal_width   = $modal_width   ?? '700px';
?>
<div id="<?= htmlspecialchars($modal_id) ?>"
     class="modal-overlay"
     style="display:none;"
     role="dialog"
     aria-modal="true"
     aria-label="<?= htmlspecialchars($modal_title) ?>"
     onclick="if(event.target===this)closeModal('<?= htmlspecialchars($modal_id) ?>')">
    <div class="modal-box" style="max-width:<?= htmlspecialchars($modal_width) ?>;">
        <?php if ($modal_title): ?>
        <div class="modal-header-cct">
            <span class="modal-title-cct"><?= htmlspecialchars($modal_title) ?></span>
            <button class="modal-close-cct"
                    onclick="closeModal('<?= htmlspecialchars($modal_id) ?>')"
                    aria-label="Fermer">
                &times;
            </button>
        </div>
        <?php endif; ?>
        <div class="modal-body-cct">
            <?= $modal_content ?>
        </div>
    </div>
</div>

<?php if (!defined('CCT_MODAL_JS_LOADED')): define('CCT_MODAL_JS_LOADED', true); ?>
<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'flex';
    el.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'none';
    el.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay[style*="flex"]').forEach(m => closeModal(m.id));
    }
});
</script>
<?php endif;

unset($modal_id, $modal_title, $modal_content, $modal_width); ?>
