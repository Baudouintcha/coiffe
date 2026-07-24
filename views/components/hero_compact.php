<?php
/**
 * views/components/hero_compact.php — HeroCompact
 * Design System v2.0 | CL §37 — Hero 35vh avec fond flou et filtres
 *
 * Variables attendues :
 *   $hcmp_bg_image  — URL image de fond (floutée automatiquement)
 *   $hcmp_title     — titre principal H1
 *   $hcmp_subtitle  — sous-titre
 *   $hcmp_content   — HTML du contenu interne (formulaire de filtres ou autre)
 */

$hcmp_bg_image  = $hcmp_bg_image  ?? '';
$hcmp_title     = $hcmp_title     ?? 'Trouvez votre artiste capillaire';
$hcmp_subtitle  = $hcmp_subtitle  ?? 'Le raffinement de la coiffure privée, directement chez vous.';
$hcmp_content   = $hcmp_content   ?? '';
?>

<section class="hero-compact"
         <?= $hcmp_bg_image ? 'style="--hcmp-bg:url(' . htmlspecialchars($hcmp_bg_image) . ');"' : '' ?>
         aria-label="<?= htmlspecialchars($hcmp_title) ?>">
    <div class="container">
        <div class="glass-md hcmp-card">
            <h1 class="hcmp-title"><?= htmlspecialchars($hcmp_title) ?></h1>
            <?php if ($hcmp_subtitle): ?>
                <p class="hcmp-subtitle"><?= htmlspecialchars($hcmp_subtitle) ?></p>
            <?php endif; ?>
            <?php if ($hcmp_content): ?>
                <div class="hcmp-body"><?= $hcmp_content ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* HeroCompact — CL §37, DS §19 */
.hero-compact {
    width:100%;
    min-height:35vh;
    background:linear-gradient(135deg,var(--dark) 0%,var(--dark-3) 100%);
    display:flex;
    align-items:flex-end;
    padding:0 0 2rem;
    position:relative;
    overflow:hidden;
    border-bottom:1px solid rgba(212,175,55,0.10);
}
.hero-compact::before {
    content:'';
    position:absolute; inset:0;
    background:var(--hcmp-bg,none) center/cover no-repeat;
    filter:blur(4px) brightness(0.18);
    transform:scale(1.05);
    z-index:0;
}
.hero-compact .container { position:relative; z-index:1; width:100%; }
.hcmp-card     { padding:24px; width:100%; }
.hcmp-title    { font-family:var(--font-display); font-size:clamp(1.4rem,3vw,2rem); font-weight:700; color:#fff; margin-bottom:4px; }
.hcmp-subtitle { color:var(--text-muted); font-size:0.82rem; margin-bottom:1.2rem; }
.hcmp-body     { }
@media (max-width:768px) {
    .hero-compact { padding: 0 0 1.5rem; }
    .hcmp-card    { padding: 16px; }
}
</style>
<?php unset($hcmp_bg_image, $hcmp_title, $hcmp_subtitle, $hcmp_content); ?>
