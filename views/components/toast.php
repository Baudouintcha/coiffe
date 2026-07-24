<?php
/**
 * views/components/toast.php — Toast
 * Design System v2.0 | CL §19
 *
 * Message flash temporaire positionné fixed.
 * S'affiche si $toast_message est défini, disparaît après $toast_duration ms.
 *
 * Variables attendues :
 *   $toast_message  — texte du message (peut contenir du HTML sécurisé)
 *   $toast_type     — 'success'|'danger'|'warning' (défaut: 'success')
 *   $toast_duration — durée en ms avant disparition (défaut: 4000)
 *   $toast_icon     — classe bi-* (auto-déterminé si non fourni)
 *
 * Utilisation depuis PHP :
 *   $toast_message = "Agenda enregistré avec succès !";
 *   $toast_type    = 'success';
 *   include 'views/components/toast.php';
 *
 * Utilisation depuis JS :
 *   showToast('Message', 'success');
 */

// Affichage PHP conditionnel
$toast_message  = $toast_message  ?? null;
$toast_type     = $toast_type     ?? 'success';
$toast_duration = $toast_duration ?? 4000;
$toast_icon     = $toast_icon     ?? match($toast_type) {
    'danger'  => 'bi-exclamation-triangle-fill',
    'warning' => 'bi-exclamation-circle-fill',
    default   => 'bi-check-circle-fill',
};

if ($toast_message):
?>
<div id="cct-toast-php"
     class="cct-toast msg-<?= htmlspecialchars($toast_type) ?>"
     role="alert"
     aria-live="assertive"
     aria-atomic="true">
    <i class="bi <?= htmlspecialchars($toast_icon) ?> me-2" aria-hidden="true"></i>
    <?= $toast_message /* HTML déjà sécurisé côté serveur si besoin */ ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const t = document.getElementById('cct-toast-php');
    if (!t) return;
    t.classList.add('cct-toast--visible');
    setTimeout(() => { t.classList.remove('cct-toast--visible'); }, <?= (int)$toast_duration ?>);
});
</script>
<?php endif; ?>

<!-- JS helper global — injecté une seule fois -->
<?php if (!defined('CCT_TOAST_JS_LOADED')): define('CCT_TOAST_JS_LOADED', true); ?>
<style>
/* Toast — CL §19, DS §15 */
.cct-toast {
    position: fixed;
    bottom: calc(var(--bottomnav-height) + 20px);
    right: 20px;
    z-index: 600;
    min-width: 260px;
    max-width: 380px;
    padding: 12px 18px;
    border-radius: var(--radius-md);
    font-size: 0.88rem;
    font-family: var(--font-body);
    display: flex;
    align-items: center;
    opacity: 0;
    transform: translateY(12px);
    transition: opacity 0.3s ease, transform 0.3s ease;
    pointer-events: none;
}
.cct-toast--visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}
/* Hérite des couleurs .msg-* de components.css */
</style>
<script>
/**
 * showToast(message, type, duration)
 * @param {string} message  — texte du toast (HTML autorisé)
 * @param {string} type     — 'success'|'danger'|'warning'
 * @param {number} duration — durée en ms (défaut: 4000)
 */
function showToast(message, type = 'success', duration = 4000) {
    // Supprimer toast précédent si existant
    const existing = document.getElementById('cct-toast-js');
    if (existing) existing.remove();

    const icons = { success:'bi-check-circle-fill', danger:'bi-exclamation-triangle-fill', warning:'bi-exclamation-circle-fill' };
    const icon  = icons[type] || icons.success;

    const el = document.createElement('div');
    el.id         = 'cct-toast-js';
    el.className  = `cct-toast msg-${type}`;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.innerHTML  = `<i class="bi ${icon} me-2" aria-hidden="true"></i>${message}`;
    document.body.appendChild(el);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => el.classList.add('cct-toast--visible'));
    });
    setTimeout(() => {
        el.classList.remove('cct-toast--visible');
        setTimeout(() => el.remove(), 400);
    }, duration);
}
</script>
<?php endif;
unset($toast_message, $toast_type, $toast_duration, $toast_icon); ?>
