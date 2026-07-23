<?php
/**
 * views/components/information_block.php — InformationBlock
 * Design System v2.0 | CL §38
 *
 * Variables attendues :
 *   $ib = [
 *     'variant' => 'success'|'danger',
 *     'icon'    => string,    // classe bi-* ex: 'bi-check-circle-fill'
 *     'title'   => string,
 *     'text'    => string,
 *     'cta'     => [          // optionnel
 *       'label'  => string,
 *       'type'   => 'submit'|'link',
 *       'href'   => string,   // pour type='link'
 *       'form_fields' => string, // HTML champs cachés pour type='submit'
 *       'action' => string,   // action du formulaire
 *     ],
 *   ]
 */

$ib = $ib ?? [];
$b = array_merge([
    'variant' => 'success',
    'icon'    => 'bi-info-circle-fill',
    'title'   => '',
    'text'    => '',
    'cta'     => null,
], $ib);

$icon_color = $b['variant'] === 'danger' ? 'var(--danger-icon)' : 'var(--success-text)';
$title_color= $b['variant'] === 'danger' ? 'var(--danger-text)' : 'var(--success-text)';
?>
<div class="info-block info-block--<?= htmlspecialchars($b['variant']) ?>" role="alert">
    <i class="bi <?= htmlspecialchars($b['icon']) ?>"
       style="color:<?= $icon_color ?>;"
       class="info-block-icon"
       aria-hidden="true"></i>
    <div style="flex:1;">
        <?php if ($b['title']): ?>
            <div class="info-block-title" style="color:<?= $title_color ?>;">
                <?= htmlspecialchars($b['title']) ?>
            </div>
        <?php endif; ?>
        <?php if ($b['text']): ?>
            <div class="info-block-text"><?= htmlspecialchars($b['text']) ?></div>
        <?php endif; ?>
        <?php if ($b['cta']): ?>
            <?php if ($b['cta']['type'] === 'link'): ?>
                <a href="<?= htmlspecialchars($b['cta']['href'] ?? '#') ?>"
                   class="btn-ghost btn-sm">
                    <?= htmlspecialchars($b['cta']['label']) ?>
                </a>
            <?php else: ?>
                <form action="<?= htmlspecialchars($b['cta']['action'] ?? '') ?>" method="POST">
                    <?= $b['cta']['form_fields'] ?? '' ?>
                    <button type="submit" class="btn-gold btn-sm">
                        <?= htmlspecialchars($b['cta']['label']) ?>
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php unset($ib, $b, $icon_color, $title_color); ?>
