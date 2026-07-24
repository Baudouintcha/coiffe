<?php
/**
 * views/components/floating_section.php — FloatingSection
 * Design System v2.0 | CL §3
 *
 * Conteneur glassmorphisme générique.
 * Utilisé comme wrapper de sections de contenu.
 *
 * Variables attendues :
 *   $fs_title       — titre de la section (optionnel)
 *   $fs_subtitle    — sous-titre (optionnel)
 *   $fs_action_label— libellé du lien d'action (optionnel)
 *   $fs_action_href — href du lien d'action (optionnel)
 *   $fs_content     — HTML du contenu principal
 *   $fs_footer      — HTML du footer de la section (optionnel)
 *   $fs_variant     — 'default'|'highlighted'|'danger'
 *   $fs_padding     — padding CSS (défaut: 'p-4')
 */

$fs_title        = $fs_title        ?? '';
$fs_subtitle     = $fs_subtitle     ?? '';
$fs_action_label = $fs_action_label ?? '';
$fs_action_href  = $fs_action_href  ?? '#';
$fs_content      = $fs_content      ?? '';
$fs_footer       = $fs_footer       ?? '';
$fs_variant      = $fs_variant      ?? 'default';
$fs_padding      = $fs_padding      ?? 'p-4';

$border_style = match($fs_variant) {
    'highlighted' => 'border-color:rgba(212,175,55,0.25);',
    'danger'      => 'border-color:rgba(220,53,69,0.30);',
    default       => '',
};
?>

<div class="glass-cct <?= htmlspecialchars($fs_padding) ?>"
     style="<?= $border_style ?>">

    <?php if ($fs_title || $fs_action_label): ?>
    <div class="fs-header <?= ($fs_title || $fs_subtitle) && $fs_content ? 'mb-4 pb-2' : '' ?>"
         style="display:flex;justify-content:space-between;align-items:center;">
        <?php if ($fs_title || $fs_subtitle): ?>
        <div>
            <?php if ($fs_title): ?>
                <h4 style="font-family:var(--font-display);color:var(--gold);font-size:1.1rem;margin-bottom:2px;">
                    <?= htmlspecialchars($fs_title) ?>
                </h4>
            <?php endif; ?>
            <?php if ($fs_subtitle): ?>
                <p style="color:var(--text-muted);font-size:0.78rem;margin:0;">
                    <?= htmlspecialchars($fs_subtitle) ?>
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($fs_action_label): ?>
            <a href="<?= htmlspecialchars($fs_action_href) ?>"
               class="btn-ghost btn-sm"
               style="text-decoration:none;">
                <?= htmlspecialchars($fs_action_label) ?>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fs_content): ?>
        <div class="fs-content"><?= $fs_content ?></div>
    <?php endif; ?>

    <?php if ($fs_footer): ?>
        <div class="fs-footer" style="border-top:1px solid var(--glass-border);padding-top:12px;margin-top:16px;">
            <?= $fs_footer ?>
        </div>
    <?php endif; ?>

</div>
<?php unset($fs_title, $fs_subtitle, $fs_action_label, $fs_action_href, $fs_content, $fs_footer, $fs_variant, $fs_padding, $border_style); ?>
