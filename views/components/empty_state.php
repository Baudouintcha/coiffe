<?php
/**
 * views/components/empty_state.php — Empty State
 * Design System v2.0 | DS §17
 *
 * Variables attendues :
 *   $es = [
 *     'icon'      => string,  // classe bi-* ex: 'bi-calendar-x'
 *     'message'   => string,  // texte descriptif
 *     'cta_label' => string,  // texte du bouton (optionnel)
 *     'cta_href'  => string,  // lien du bouton (optionnel)
 *   ]
 */

$es = $es ?? [];
$e = array_merge([
    'icon'      => 'bi-inbox',
    'message'   => 'Aucun élément à afficher.',
    'cta_label' => '',
    'cta_href'  => '',
], $es);
?>
<div class="empty-state" role="status">
    <i class="bi <?= htmlspecialchars($e['icon']) ?>" aria-hidden="true"></i>
    <p><?= htmlspecialchars($e['message']) ?></p>
    <?php if ($e['cta_label'] && $e['cta_href']): ?>
        <a href="<?= htmlspecialchars($e['cta_href']) ?>"
           class="btn-gold btn-sm">
            <?= htmlspecialchars($e['cta_label']) ?>
        </a>
    <?php endif; ?>
</div>
<?php unset($es, $e); ?>
