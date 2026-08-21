<?php
/**
 * views/components/hero_premium.php — Floating Hero Capsule
 * Design System v2.0 | CL §1 — Refonte finale
 *
 * Variables attendues :
 *   $hp_images             — array URLs images slider (ex: ['/coiffons/imgid/...'])
 *   $hp_title              — titre H1 principal
 *   $hp_subtitle           — sous-titre court
 *   $hp_role_client_href   — lien bouton client
 *   $hp_role_coiffeur_href — lien bouton coiffeur
 *   $hp_login_href         — lien connexion
 *   $hp_show_roles         — bool afficher les deux cartes rôle
 */

$hp_images             = $hp_images             ?? [];
$hp_title              = $hp_title              ?? 'COIFFE CHEZ TOI';
$hp_subtitle           = $hp_subtitle           ?? 'Réservez un coiffeur professionnel qui se déplace directement chez vous.';
$hp_role_client_href   = $hp_role_client_href   ?? '/coiffons/filter/annuaire_coiffeurs.php';
$hp_role_coiffeur_href = $hp_role_coiffeur_href ?? '/coiffons/index.php?metier=coiffure&page=register&role=coiffeur';
$hp_login_href         = $hp_login_href         ?? '/coiffons/index.php?metier=coiffure&page=login';
$hp_show_roles         = $hp_show_roles         ?? true;
?>

<!-- ══════════════════════════════════════════
     FLOATING HERO CAPSULE
     Conteneur centré, max 1280px, coins arrondis
     ══════════════════════════════════════════ -->
<section class="hp-section" aria-label="<?= htmlspecialchars($hp_title) ?>">
    <div class="hp-outer">

        <!-- Capsule principale -->
        <div class="hp-capsule" role="banner">

            <!-- Slider d'images en arrière-plan -->
            <div class="hp-slides" aria-hidden="true">
                <?php if (!empty($hp_images)): ?>
                    <?php foreach ($hp_images as $idx => $img): ?>
                        <div class="hp-slide <?= $idx === 0 ? 'hp-slide--active' : '' ?>"
                             style="background-image:url('<?= htmlspecialchars($img) ?>')">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback gradient si aucune image -->
                    <div class="hp-slide hp-slide--active hp-slide--fallback"></div>
                <?php endif; ?>
            </div>

            <!-- Overlay dégradé premium (transparent → sombre) -->
            <div class="hp-overlay" aria-hidden="true"></div>

            <!-- Contenu de la capsule -->
            <div class="hp-inner page-transition">

                <!-- Titre -->
                <h1 class="hp-title">
                    <?= htmlspecialchars($hp_title) ?>
                </h1>

                <!-- Sous-titre orienté métier -->
                <p class="hp-subtitle">
                    <?= htmlspecialchars($hp_subtitle) ?>
                </p>
                <p class="hp-tagline">Simple.&nbsp;&nbsp;Rapide.&nbsp;&nbsp;Sécurisé.</p>

                <!-- Search Capsule -->
                <div class="hp-search-wrap">
                    <form action="/coiffons/filter/annuaire_coiffeurs.php"
                          method="GET"
                          class="hp-search"
                          role="search"
                          aria-label="Rechercher un coiffeur">
                        <i class="bi bi-search hp-search-icon" aria-hidden="true"></i>
                        <input type="search"
                               name="q"
                               class="hp-search-input"
                               placeholder="Nom, style ou quartier..."
                               autocomplete="off"
                               aria-label="Rechercher un coiffeur par nom, style ou quartier">
                        <button type="submit" class="hp-search-btn" aria-label="Lancer la recherche">
                            Rechercher
                        </button>
                    </form>
                </div>

                <?php if ($hp_show_roles): ?>
                <!-- Cartes de rôle -->
                <div class="hp-roles" role="navigation" aria-label="Choisir votre rôle">
                    <a href="<?= htmlspecialchars($hp_role_client_href) ?>"
                       class="hp-role-card"
                       aria-label="Je cherche un coiffeur à domicile">
                        <span class="hp-role-icon" aria-hidden="true"> </span>
                        <span class="hp-role-title">Je cherche un coiffeur</span>
                        <span class="hp-role-sub">Trouver et réserver une prestation à domicile</span>
                    </a>
                    <a href="<?= htmlspecialchars($hp_role_coiffeur_href) ?>"
                       class="hp-role-card"
                       aria-label="Je suis coiffeur et je propose mes services">
                        <span class="hp-role-icon" aria-hidden="true"> </span>
                        <span class="hp-role-title">Je suis coiffeur</span>
                        <span class="hp-role-sub">Créer mon profil et recevoir des clients</span>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Connexion discrète -->
                <p class="hp-login-line">
                    Déjà inscrit ?&nbsp;
                    <a href="<?= htmlspecialchars($hp_login_href) ?>"
                       class="hp-login-link">Se connecter</a>
                </p>

            </div><!-- /.hp-inner -->
        </div><!-- /.hp-capsule -->
    </div><!-- /.hp-outer -->
</section>

<style>
/* ═══════════════════════════════════════════════════════
   FLOATING HERO CAPSULE — DS v2.0
   ═══════════════════════════════════════════════════════ */

/* Section wrapper avec fond visible autour */
.hp-section {
    background: var(--dark);
    padding: 2.5rem 1.5rem 0;
}

/* Conteneur centré max-1280 */
.hp-outer {
    max-width: 1280px;
    margin: 0 auto;
}

/* La capsule elle-même */
.hp-capsule {
    position: relative;
    width: 100%;
    min-height: 52vh;
    border-radius: 28px;
    overflow: hidden;
    box-shadow:
        0 32px 80px rgba(0,0,0,0.70),
        0 0 0 1px rgba(255,255,255,0.06),
        inset 0 1px 0 rgba(255,255,255,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ── SLIDER ── */
.hp-slides {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.hp-slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center top;
    background-repeat: no-repeat;
    opacity: 0;
    transform: scale(1.04);
    transition: opacity 1.4s ease, transform 6s ease;
}
.hp-slide--active {
    opacity: 1;
    transform: scale(1);
}
.hp-slide--fallback {
    background: linear-gradient(135deg, #0f0f0f 0%, #1a1410 50%, #0a0a0a 100%);
    opacity: 1;
}

/* ── OVERLAY dégradé premium ── */
.hp-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background: linear-gradient(
        to bottom,
        rgba(0,0,0,0.10) 0%,
        rgba(0,0,0,0.30) 30%,
        rgba(0,0,0,0.70) 75%,
        rgba(0,0,0,0.88) 100%
    );
}

/* ── CONTENU ── */
.hp-inner {
    position: relative;
    z-index: 2;
    text-align: center;
    color: #fff;
    padding: 3.5rem 2.5rem 3rem;
    width: 100%;
    max-width: 720px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
}

/* Titre principal */
.hp-title {
    font-family: var(--font-display);
    font-size: clamp(2.4rem, 7vw, 5rem);
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 6px;
    line-height: 1.05;
    margin: 0 0 1rem;
    text-shadow: 0 2px 20px rgba(0,0,0,0.6);
}

/* Sous-titre */
.hp-subtitle {
    font-family: var(--font-body);
    font-size: clamp(.9rem, 2vw, 1.15rem);
    font-weight: 300;
    letter-spacing: .5px;
    color: rgba(255,255,255,0.88);
    margin: 0 0 .4rem;
    max-width: 540px;
    line-height: 1.6;
}

.hp-tagline {
    font-size: .78rem;
    font-weight: 600;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--gold);
    margin: 0 0 2rem;
}

/* ── SEARCH CAPSULE ── */
.hp-search-wrap {
    width: 100%;
    max-width: 560px;
    margin-bottom: 2rem;
}
.hp-search {
    display: flex;
    align-items: center;
    background: rgba(255,255,255,0.14);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1.5px solid rgba(255,255,255,0.22);
    border-radius: 50px;
    padding: 6px 6px 6px 20px;
    box-shadow:
        0 8px 32px rgba(0,0,0,0.40),
        inset 0 1px 0 rgba(255,255,255,0.12);
    transition: border-color .2s, box-shadow .2s;
    gap: 8px;
}
.hp-search:focus-within {
    border-color: var(--gold);
    box-shadow:
        0 8px 32px rgba(0,0,0,0.45),
        0 0 0 3px rgba(212,175,55,0.18);
}
.hp-search-icon {
    color: rgba(255,255,255,0.55);
    font-size: .95rem;
    flex-shrink: 0;
}
.hp-search-input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: #fff;
    font-size: .92rem;
    font-family: var(--font-body);
    padding: 9px 0;
    min-width: 0;
}
.hp-search-input::placeholder {
    color: rgba(255,255,255,0.45);
}
.hp-search-btn {
    background: var(--gold);
    color: #000;
    border: none;
    border-radius: 50px;
    padding: 10px 22px;
    font-weight: 700;
    font-size: .82rem;
    font-family: var(--font-body);
    cursor: pointer;
    transition: background .2s, transform .15s;
    white-space: nowrap;
    flex-shrink: 0;
}
.hp-search-btn:hover {
    background: var(--gold-hover);
    transform: translateY(-1px);
}

/* ── CARTES RÔLE ── */
.hp-roles {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
    width: 100%;
    max-width: 520px;
}
.hp-role-card {
    flex: 1;
    min-width: 180px;
    max-width: 230px;
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.14);
    border-radius: 18px;
    padding: 1.4rem 1rem;
    text-align: center;
    color: #fff;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    cursor: pointer;
}
.hp-role-card:hover {
    background: rgba(212,175,55,0.15);
    border-color: var(--gold);
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.45), 0 0 0 1px rgba(212,175,55,0.25);
    color: #fff;
}
.hp-role-icon {
    font-size: 1.8rem;
    display: block;
    margin-bottom: 2px;
}
.hp-role-title {
    font-weight: 700;
    font-size: .88rem;
    letter-spacing: .3px;
}
.hp-role-sub {
    font-size: .72rem;
    color: rgba(255,255,255,0.55);
    line-height: 1.4;
}

/* ── CONNEXION ── */
.hp-login-line {
    font-size: .82rem;
    color: rgba(255,255,255,0.45);
    margin: 0;
}
.hp-login-link {
    color: var(--gold);
    text-decoration: none;
    font-weight: 600;
    transition: color .15s;
}
.hp-login-link:hover {
    color: var(--gold-hover);
    text-decoration: underline;
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .hp-section  { padding: 1.5rem 1rem 0; }
    .hp-capsule  { min-height: 58vh; border-radius: 20px; }
    .hp-inner    { padding: 2.5rem 1.25rem 2.5rem; }
    .hp-roles    { gap: 10px; }
    .hp-role-card{ min-width: 140px; padding: 1.1rem .75rem; }
    .hp-role-sub { display: none; } /* masqué sur mobile pour ne pas surcharger */
    .hp-search   { flex-direction: column; border-radius: 18px; padding: 12px; gap: 10px; }
    .hp-search-btn { width: 100%; border-radius: 12px; }
    .hp-search-input { padding: 4px 0; }
}
@media (max-width: 400px) {
    .hp-capsule  { border-radius: 14px; }
    .hp-roles    { flex-direction: column; align-items: center; }
    .hp-role-card{ width: 100%; max-width: 100%; flex-direction: row; text-align: left; gap: 12px; padding: 1rem; }
    .hp-role-icon{ font-size: 1.4rem; margin: 0; }
}
</style>

<script>
/* Slider auto avec zoom subtil */
(function () {
    const slides = document.querySelectorAll('.hp-slide');
    if (slides.length < 2) return;
    let cur = 0;
    setInterval(function () {
        slides[cur].classList.remove('hp-slide--active');
        cur = (cur + 1) % slides.length;
        slides[cur].classList.add('hp-slide--active');
    }, 4500);
})();
</script>

<?php unset($hp_images, $hp_title, $hp_subtitle, $hp_role_client_href, $hp_role_coiffeur_href, $hp_login_href, $hp_show_roles); ?>
