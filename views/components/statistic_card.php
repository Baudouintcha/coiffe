<?php
/**
 * views/components/statistic_card.php — StatisticCard
 * Design System v2.0 | CL §9
 *
 * Variables attendues :
 *   $stat = [
 *     'icon'    => string,  // classe Bootstrap Icon ex: 'bi-graph-up'
 *     'label'   => string,  // libellé uppercase
 *     'value'   => string,  // valeur formatée ex: '12 500'
 *     'unit'    => string,  // unité ex: 'FCFA'
 *     'variant' => 'gold'|'danger'|'success', // couleur de l'icône
 *   ]
 */

$stat = $stat ?? [];
$s = array_merge([
    'icon'    => 'bi-bar-chart',
    'label'   => 'Statistique',
    'value'   => '0',
    'unit'    => '',
    'variant' => 'gold',
], $stat);

$icon_color = match($s['variant']) {
    'danger'  => 'var(--danger-icon)',
    'success' => 'var(--success-text)',
    default   => 'var(--gold)',
};
?>
<div class="glass-cct p-3 text-center h-100">
    <i class="bi <?= htmlspecialchars($s['icon']) ?>"
       style="color:<?= $icon_color ?>;font-size:1.3rem;display:block;margin-bottom:6px;"
       aria-hidden="true"></i>
    <div class="stat-card-label"><?= htmlspecialchars($s['label']) ?></div>
    <div class="stat-card-value">
        <?= htmlspecialchars($s['value']) ?>
        <?php if ($s['unit']): ?>
            <span class="stat-card-unit"><?= htmlspecialchars($s['unit']) ?></span>
        <?php endif; ?>
    </div>
</div>
<?php unset($stat, $s, $icon_color); ?>
