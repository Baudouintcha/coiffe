<?php
/**
 * views/components/hero_capsule.php — HeroCapsule
 * Design System v2.0 | CL §2 — Dashboard client connecté
 *
 * Variables attendues :
 *   $hc_images        — array URLs images carousel
 *   $hc_title         — titre principal dans la capsule
 *   $hc_subtitle      — sous-titre
 *   $hc_search_action — URL cible du formulaire de recherche
 *   $hc_search_placeholder — texte placeholder
 *   $hc_profile       — array|null ['nom'=>str, 'initiale'=>str, 'photo_url'=>str|null, 'stat'=>str]
 *   $hc_navbar_height — hauteur navbar pour padding-top (défaut: 64px)
 */

$hc_images              = $hc_images              ?? [];
$hc_title               = $hc_title               ?? 'Votre coiffeur, directement chez vous';
$hc_subtitle            = $hc_subtitle            ?? 'Des professionnels certifiés dans votre quartier';
$hc_search_action       = $hc_search_action       ?? '/coiffons/filter/annuaire_coiffeurs.php';
$hc_search_placeholder  = $hc_search_placeholder  ?? 'Rechercher un coiffeur, un style...';
$hc_profile             = $hc_profile             ?? null;
$hc_navbar_height       = $hc_navbar_height       ?? 64;
?>

<div class="hc-wrapper" style="padding-top:calc(<?= (int)$hc_navbar_height ?>px + 1.5rem);">
    <section class="hc-capsule" aria-label="Hero dashboard">

        <!-- Carousel confiné dans la capsule -->
        <div class="hc-carousel" aria-hidden="true">
            <?php foreach ($hc_images as $idx => $img): ?>
                <div class="hero-slide <?= $idx === 0 ? 'active' : '' ?>"
                     style="background-image:url('<?= htmlspecialchars($img) ?>');"
                     role="img"></div>
            <?php endforeach; ?>
        </div>

        <!-- Overlay gradient -->
        <div class="hc-overlay" aria-hidden="true"></div>

        <!-- Floating Card profil — top right -->
        <?php if ($hc_profile): ?>
        <div class="hc-profile-card" aria-label="Profil <?= htmlspecialchars($hc_profile['nom'] ?? '') ?>">
            <?php if (!empty($hc_profile['photo_url'])): ?>
                <img src="<?= htmlspecialchars($hc_profile['photo_url']) ?>"
                     alt="Photo de profil"
                     class="avatar-img"
                     style="width:44px;height:44px;">
            <?php else: ?>
                <div class="avatar-placeholder avatar-sm">
                    <?= htmlspecialchars(strtoupper(substr($hc_profile['initiale'] ?? '?', 0, 1))) ?>
                </div>
            <?php endif; ?>
            <div class="hc-profile-info">
                <span class="hc-profile-greeting">Bonjour </span>
                <span class="hc-profile-name"><?= htmlspecialchars($hc_profile['nom'] ?? '') ?></span>
                <?php if (!empty($hc_profile['stat'])): ?>
                    <span class="hc-profile-stat"><?= htmlspecialchars($hc_profile['stat']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Contenu bas de capsule -->
        <div class="hc-content">
            <h1 class="hc-title"><?= htmlspecialchars($hc_title) ?></h1>
            <p class="hc-subtitle"><?= htmlspecialchars($hc_subtitle) ?></p>

            <!-- Search Capsule intégrée -->
            <div class="hc-search-wrap">
                <form action="<?= htmlspecialchars($hc_search_action) ?>" method="GET"
                      class="search-bar" role="search">
                    <i class="bi bi-search" style="color:rgba(255,255,255,0.5);margin-right:6px;font-size:0.9rem;" aria-hidden="true"></i>
                    <input type="text"
                           name="q"
                           placeholder="<?= htmlspecialchars($hc_search_placeholder) ?>"
                           autocomplete="off"
                           aria-label="Rechercher un coiffeur">
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Rechercher
                    </button>
                </form>
            </div>
        </div>

    </section>
</div>

<style>
/* HeroCapsule — CL §2, DS §19 */
.hc-wrapper    { display:flex; justify-content:center; background: var(--dark); padding-bottom: 20px; margin-bottom: 0; }
.hc-capsule    { position:relative; width:92%; max-width:var(--max-width); height:420px; min-height:380px; border-radius:52px; overflow:hidden; box-shadow:0 24px 60px rgba(0,0,0,0.55),0 4px 16px rgba(0,0,0,0.3),0 0 0 1px rgba(255,255,255,0.06); background:#0a0a0a; }
.hc-carousel   { position:absolute; inset:0; z-index:0; background: #0a0a0a; }

/* ── SLIDES ── */
.hc-carousel .hero-slide {
    position: absolute;
    inset: 0;
    background-color: #0a0a0a;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0;
    transform: scale(1.06);
    transition: opacity 1.4s ease, transform 8s ease;
    will-change: opacity, transform;
}
.hc-carousel .hero-slide.active {
    opacity: 1;
    transform: scale(1);
}

.hc-overlay    { position:absolute; inset:0; z-index:1; background:linear-gradient(180deg,rgba(10,10,10,0.35) 0%,rgba(10,10,10,0.15) 40%,rgba(10,10,10,0.75) 100%); }
.hc-profile-card { position:absolute; top:84px; right:24px; z-index:3; background:rgba(255,255,255,0.08); backdrop-filter:blur(20px); border:1px solid var(--glass-border-md); border-radius:var(--radius-lg); padding:14px 18px; display:flex; align-items:center; gap:12px; min-width:220px; box-shadow:0 4px 20px rgba(0,0,0,0.4); }
.hc-profile-info  { display:flex; flex-direction:column; min-width:0; }
.hc-profile-greeting { font-size:0.72rem; color:var(--text-muted); }
.hc-profile-name    { font-weight:700; font-size:0.9rem; color:#fff; }
.hc-profile-stat    { font-size:0.7rem; color:var(--gold); margin-top:2px; }
.hc-content    { position:absolute; inset:0; z-index:2; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; padding:0 2rem 2rem; text-align:center; }
.hc-title      { font-family:var(--font-display); font-size:clamp(1.6rem,3.5vw,2.6rem); font-weight:900; margin-bottom:0.4rem; line-height:1.15; text-shadow:0 2px 12px rgba(0,0,0,0.5); }
.hc-subtitle   { color:rgba(255,255,255,0.65); font-size:0.9rem; margin-bottom:1.2rem; font-weight:300; }
.hc-search-wrap{ width:100%; max-width:560px; margin:0 auto; }
@media (max-width:768px) {
    .hc-profile-card { display:none; }
    .hc-capsule { height:340px; border-radius:28px; }
    .hc-wrapper { padding:0.75rem 0.75rem 0; }
}
@media (max-width:480px) {
    .hc-title   { font-size:1.4rem; }
    .hc-capsule { height:300px; border-radius:20px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.hc-carousel .hero-slide');
    console.log('[HeroCapsule] slides détectées :', slides.length);
    if (slides.length < 2) return;
    let cur = 0;
    setInterval(function () {
        slides[cur].classList.remove('active');
        cur = (cur + 1) % slides.length;
        slides[cur].classList.add('active');
    }, 5000);
});
</script>
<?php unset($hc_images, $hc_title, $hc_subtitle, $hc_search_action, $hc_search_placeholder, $hc_profile, $hc_navbar_height); ?>
