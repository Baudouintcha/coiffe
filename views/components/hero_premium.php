<?php
/**
 * views/components/hero_premium.php — HeroPremium
 * Design System v2.0 | CL §1 — Variante: plein écran visiteur
 *
 * Variables attendues :
 *   $hp_images      — array de chemins URL d'images (ex: ['/coiffons/imgid/...'])
 *   $hp_title       — titre principal (défaut: 'COIFFE CHEZ TOI')
 *   $hp_subtitle    — sous-titre (défaut: prédéfini)
 *   $hp_role_client_href  — lien inscription client
 *   $hp_role_coiffeur_href— lien inscription coiffeur
 *   $hp_login_href  — lien connexion
 *   $hp_show_roles  — bool, afficher les boutons de rôle (défaut: true)
 */

$hp_images              = $hp_images              ?? [];
$hp_title               = $hp_title               ?? 'COIFFE CHEZ TOI';
$hp_subtitle            = $hp_subtitle            ?? 'Des coupes modernes, un style unique et une expérience premium.';
$hp_role_client_href    = $hp_role_client_href    ?? '/coiffons/index.php?page=register&role=client';
$hp_role_coiffeur_href  = $hp_role_coiffeur_href  ?? '/coiffons/index.php?page=register&role=coiffeur';
$hp_login_href          = $hp_login_href          ?? '/coiffons/index.php?page=login';
$hp_show_roles          = $hp_show_roles          ?? true;
?>

<div class="hero-slider" role="banner" aria-label="<?= htmlspecialchars($hp_title) ?>">

    <!-- Slides -->
    <?php foreach ($hp_images as $idx => $img): ?>
        <div class="hp-slide <?= $idx === 0 ? 'active' : '' ?>"
             style="background-image:url('<?= htmlspecialchars($img) ?>');"
             aria-hidden="true"
             role="img"
             aria-label="Image de fond <?= $idx + 1 ?>">
        </div>
    <?php endforeach; ?>

    <!-- Overlay -->
    <div class="hp-overlay" aria-hidden="true"></div>

    <!-- Contenu centré -->
    <div class="hp-content page-transition">
        <h1 class="hp-title"><?= htmlspecialchars($hp_title) ?></h1>
        <p class="hp-subtitle"><?= htmlspecialchars($hp_subtitle) ?></p>

        <?php if ($hp_show_roles): ?>
        <div class="hp-roles" role="navigation" aria-label="Choisir votre rôle">
            <p class="hp-roles-label">Qui êtes-vous ?</p>
            <div class="hp-cta-row">
                <a href="<?= htmlspecialchars($hp_role_client_href) ?>"
                   class="btn-gold btn-lg">Trouver un coiffeur</a>
                <a href="<?= htmlspecialchars($hp_role_coiffeur_href) ?>"
                   class="btn-outline-gold btn-lg">Je suis Coiffeur</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="hp-login">
            <p class="hp-login-label">Vous avez déjà un compte ?</p>
            <a href="<?= htmlspecialchars($hp_login_href) ?>"
               class="btn-ghost btn-lg">Se connecter</a>
        </div>
    </div>

</div>

<style>
/* HeroPremium — CL §1, DS §19 */
.hero-slider        { position:relative; width:100vw; height:100vh; overflow:hidden; }
.hp-slide           { position:absolute; inset:0; background-size:cover; background-position:center; background-repeat:no-repeat; opacity:0; transition:opacity 1.2s ease; }
.hp-slide.active    { opacity:1; }
.hp-overlay         { position:absolute; inset:0; background:rgba(0,0,0,0.50); z-index:1; }
.hp-content         { position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); z-index:2; text-align:center; color:#fff; width:90%; max-width:800px; }
.hp-title           { font-family:var(--font-display); font-size:clamp(3rem,10vw,6rem); font-weight:900; text-transform:uppercase; letter-spacing:5px; margin-bottom:16px; text-shadow:2px 2px 10px rgba(0,0,0,0.5); }
.hp-subtitle        { font-family:var(--font-body); font-size:1.2rem; font-weight:300; letter-spacing:1px; opacity:0.9; margin-bottom:2rem; }
.hp-roles           { margin-bottom:1.5rem; }
.hp-roles-label     { font-size:1rem; margin-bottom:12px; opacity:0.8; }
.hp-cta-row         { display:flex; justify-content:center; gap:12px; flex-wrap:wrap; }
.hp-login           { margin-top:1.5rem; }
.hp-login-label     { font-size:0.9rem; margin-bottom:8px; opacity:0.7; }
</style>

<script>
(function() {
    const slides = document.querySelectorAll('.hp-slide');
    if (slides.length < 2) return;
    let current = 0;
    setInterval(() => {
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }, 4000);
})();
</script>
<?php unset($hp_images, $hp_title, $hp_subtitle, $hp_role_client_href, $hp_role_coiffeur_href, $hp_login_href, $hp_show_roles); ?>
