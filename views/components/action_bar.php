<?php
/**
 * views/components/action_bar.php — ActionBar (titre page + retour)
 * Design System v2.0 | CL §30
 *
 * Variables attendues :
 *   $ab = [
 *     'icon'     => string,  // classe bi-* ex: 'bi-wallet2'
 *     'title'    => string,  // titre de la page
 *     'subtitle' => string,  // sous-titre muted (optionnel)
 *     'back_href'=> string,  // lien retour (défaut: dashboard coiffeur)
 *     'back_label'=> string, // libellé du lien retour
 *   ]
 */

$ab = $ab ?? [];
$a = array_merge([
    'icon'       => 'bi-grid',
    'title'      => 'Page',
    'subtitle'   => '',
    'back_href'  => '/coiffons/index.php?page=dashboard_coiffeur',
    'back_label' => 'Dashboard',
], $ab);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="font-family:var(--font-display);font-size:1.4rem;color:var(--gold);margin-bottom:2px;">
            <i class="bi <?= htmlspecialchars($a['icon']) ?> me-2" aria-hidden="true"></i>
            <?= htmlspecialchars($a['title']) ?>
        </h2>
        <?php if ($a['subtitle']): ?>
            <p style="color:var(--text-muted);font-size:0.78rem;margin:0;">
                <?= htmlspecialchars($a['subtitle']) ?>
            </p>
        <?php endif; ?>
    </div>
    <a href="<?= htmlspecialchars($a['back_href']) ?>"
       class="btn-ghost btn-sm"
       style="display:flex;align-items:center;gap:5px;text-decoration:none;">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        <?= htmlspecialchars($a['back_label']) ?>
    </a>
</div>
<?php unset($ab, $a); ?>
