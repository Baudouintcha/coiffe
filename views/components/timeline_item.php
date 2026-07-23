<?php
/**
 * views/components/timeline_item.php — Timeline Item (transaction)
 * Design System v2.0 | CL §26
 *
 * Variables attendues :
 *   $tl = [
 *     'type'    => 'gain'|'debit',  // type de transaction
 *     'label'   => string,           // libellé (motif)
 *     'date'    => string,           // date formatée
 *     'montant' => string,           // montant absolu formaté
 *     'unite'   => string,           // ex: 'FCFA'
 *   ]
 */

$tl = $tl ?? [];
$t = array_merge([
    'type'    => 'gain',
    'label'   => 'Transaction',
    'date'    => '',
    'montant' => '0',
    'unite'   => 'FCFA',
], $tl);

$is_gain   = $t['type'] === 'gain';
$sign      = $is_gain ? '+' : '−';
$icon      = $is_gain ? 'bi-arrow-down-circle' : 'bi-arrow-up-circle';
?>
<div class="timeline-item">
    <div class="timeline-icon timeline-icon--<?= $is_gain ? 'gain' : 'debit' ?>">
        <i class="bi <?= $icon ?>"
           style="color:<?= $is_gain ? 'var(--success-text)' : 'var(--danger-icon)' ?>;font-size:0.9rem;"
           aria-hidden="true"></i>
    </div>
    <div class="timeline-info">
        <div class="timeline-label"><?= htmlspecialchars($t['label']) ?></div>
        <?php if ($t['date']): ?>
            <div class="timeline-date"><?= htmlspecialchars($t['date']) ?></div>
        <?php endif; ?>
    </div>
    <div class="timeline-amount timeline-amount--<?= $is_gain ? 'gain' : 'debit' ?>">
        <?= $sign ?><?= htmlspecialchars($t['montant']) ?> <?= htmlspecialchars($t['unite']) ?>
    </div>
</div>
<?php unset($tl, $t, $is_gain, $sign, $icon); ?>
