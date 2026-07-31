<?php
/**
 * views/home.php — Page d'accueil visiteur
 * Migration Design System v2.0 — Parcours Visiteur — Page 1/6
 *
 * RÈGLES RESPECTÉES :
 * - Logique PHP intacte (glob images, routes)
 * - Aucune variable PHP modifiée
 * - Uniquement HTML/CSS/composants refactorisés
 */

// Collecte des images pour le slider — logique PHP inchangée
$images     = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$hp_images  = [];
foreach ($images as $img) {
    $hp_images[] = '/coiffons/imgid/' . basename($img);
}

// Variables du composant HeroPremium
$hp_title              = 'COIFFE CHEZ TOI';
$hp_subtitle           = 'Des coupes modernes, un style unique et une expérience premium.';
$hp_role_client_href   = '/coiffons/filter/annuaire_coiffeurs.php';
$hp_role_coiffeur_href = '/coiffons/index.php?page=register&role=coiffeur';
$hp_login_href         = '/coiffons/index.php?page=login';
$hp_show_roles         = true;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coiffe Chez Toi — Coiffure à domicile au Bénin</title>

    <!-- Design System v2.0 — Ordre de chargement officiel -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">

    <!-- Bootstrap 5.3.3 (grille uniquement) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts — Polices officielles DS -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Section "Comment ça marche" ── */
    .how-section          { padding:5rem 0; background:var(--dark-2); }
    .how-step             { text-align:center; padding:2rem 1.5rem; }
    .how-step-number      { width:56px; height:56px; border-radius:var(--radius-circle); background:var(--gold-dim); border:2px solid var(--gold); display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-size:1.3rem; font-weight:900; color:var(--gold); margin:0 auto 1.2rem; }
    .how-step-title       { font-family:var(--font-body); font-weight:700; font-size:1rem; color:var(--text-primary); margin-bottom:.5rem; }
    .how-step-desc        { font-size:.85rem; color:var(--text-muted); line-height:1.6; }
    .how-connector        { display:none; }

    /* ── Section "Pourquoi CCT" ── */
    .why-section          { padding:5rem 0; background:var(--dark); }
    .why-card             { background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:var(--radius-lg); padding:2rem 1.5rem; text-align:center; transition:var(--transition-spring), border-color .3s; height:100%; }
    .why-card:hover       { border-color:rgba(212,175,55,.30); transform:translateY(-4px); }
    .why-icon             { width:56px; height:56px; border-radius:var(--radius-md); background:var(--gold-dim); display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:var(--gold); margin:0 auto 1.2rem; }
    .why-title            { font-weight:700; font-size:.95rem; margin-bottom:.5rem; color:var(--text-primary); }
    .why-desc             { color:var(--text-muted); font-size:.82rem; line-height:1.6; }

    /* ── Section CTA bas de page ── */
    .cta-bottom-section   { padding:5rem 0; background:var(--dark-3); }
    .cta-bottom-inner     { background:var(--glass-bg); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border:1px solid var(--glass-border-md); border-radius:var(--radius-xl); padding:3rem 2rem; text-align:center; }
    .cta-bottom-title     { font-family:var(--font-display); font-size:clamp(1.6rem,3vw,2.4rem); font-weight:700; color:var(--text-primary); margin-bottom:1rem; }
    .cta-bottom-sub       { color:var(--text-muted); font-size:.92rem; margin-bottom:2rem; }

    /* ── Responsive how-to ── */
    @media (min-width:768px) {
        .how-connector    { display:flex; align-items:center; justify-content:center; color:var(--glass-border-md); font-size:1.5rem; padding-top:1.5rem; }
    }
    @media (max-width:576px) {
        .cta-bottom-inner { padding:2rem 1.25rem; }
    }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════
     HERO PREMIUM — Composant CL §1
     Variables transmises au composant
     ══════════════════════════════════════════ -->
<?php include __DIR__ . '/components/hero_premium.php'; ?>


<!-- ══════════════════════════════════════════
     SECTION "COMMENT ÇA MARCHE" — 3 étapes
     Répond au point de friction identifié dans l'audit
     (absence de proposition de valeur explicite)
     ══════════════════════════════════════════ -->
<section class="how-section" aria-labelledby="how-heading">
    <div class="container" style="max-width:var(--max-width);">
        <h2 id="how-heading" class="text-center mb-1" style="font-family:var(--font-display);font-size:clamp(1.4rem,3vw,2rem);font-weight:700;color:var(--text-primary);">
            Comment ça marche ?
        </h2>
        <p class="text-center mb-5" style="color:var(--text-muted);font-size:.88rem;">
            Réservez une coiffure à domicile en 3 étapes simples.
        </p>

        <div class="row g-4 align-items-start justify-content-center">
            <!-- Étape 1 -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="how-step">
                    <div class="how-step-number" aria-hidden="true">1</div>
                    <div class="how-step-title">Choisissez un coiffeur</div>
                    <p class="how-step-desc">Parcourez l'annuaire des professionnels certifiés disponibles dans votre quartier.</p>
                </div>
            </div>
            <!-- Connecteur -->
            <div class="col-lg-1 how-connector" aria-hidden="true">
                <i class="bi bi-arrow-right" style="color:var(--gold);"></i>
            </div>
            <!-- Étape 2 -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="how-step">
                    <div class="how-step-number" aria-hidden="true">2</div>
                    <div class="how-step-title">Sélectionnez un créneau</div>
                    <p class="how-step-desc">Consultez le planning du coiffeur et réservez l'heure qui vous convient.</p>
                </div>
            </div>
            <!-- Connecteur -->
            <div class="col-lg-1 how-connector" aria-hidden="true">
                <i class="bi bi-arrow-right" style="color:var(--gold);"></i>
            </div>
            <!-- Étape 3 -->
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="how-step">
                    <div class="how-step-number" aria-hidden="true">3</div>
                    <div class="how-step-title">Recevez le coiffeur chez vous</div>
                    <p class="how-step-desc">Le coiffeur se déplace à votre adresse. Profitez d'une expérience premium.</p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════
     SECTION "POURQUOI COIFFE CHEZ TOI"
     ══════════════════════════════════════════ -->
<section class="why-section" aria-labelledby="why-heading">
    <div class="container" style="max-width:var(--max-width);">
        <h2 id="why-heading" class="text-center mb-1" style="font-family:var(--font-display);font-size:clamp(1.4rem,3vw,2rem);font-weight:700;color:var(--text-primary);">
            Pourquoi Coiffe Chez Toi ?
        </h2>
        <p class="text-center mb-5" style="color:var(--text-muted);font-size:.88rem;">
            La coiffure professionnelle réinventée à domicile.
        </p>

        <div class="row g-4 justify-content-center">
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="why-card">
                    <div class="why-icon" aria-hidden="true"><i class="bi bi-house-heart"></i></div>
                    <div class="why-title">Service à domicile</div>
                    <p class="why-desc">Des coiffeurs professionnels se déplacent directement chez vous, à votre heure.</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="why-card">
                    <div class="why-icon" aria-hidden="true"><i class="bi bi-patch-check"></i></div>
                    <div class="why-title">Professionnels certifiés</div>
                    <p class="why-desc">Chaque coiffeur est vérifié par notre équipe avant d'être autorisé sur la plateforme.</p>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="why-card">
                    <div class="why-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></div>
                    <div class="why-title">Paiement sécurisé</div>
                    <p class="why-desc">Vos fonds sont gelés jusqu'à la confirmation. Remboursement garanti en cas d'annulation.</p>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════
     CTA BAS DE PAGE — Invitation à rejoindre
     ══════════════════════════════════════════ -->
<section class="cta-bottom-section" aria-labelledby="cta-heading">
    <div class="container" style="max-width:860px;">
        <div class="cta-bottom-inner">
            <h2 id="cta-heading" class="cta-bottom-title">
                Prêt à commencer ?
            </h2>
            <p class="cta-bottom-sub">
                Rejoignez Coiffe Chez Toi et vivez l'expérience de la coiffure premium à domicile.
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/coiffons/filter/annuaire_coiffeurs.php"
                   class="btn-gold btn-lg"
                   aria-label="Trouver un coiffeur">
                    Trouver un coiffeur
                </a>
                <a href="/coiffons/index.php?page=login"
                   class="btn-ghost btn-lg"
                   aria-label="Se connecter">
                    Se connecter
                </a>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════
     FOOTER GLOBAL — Composant CL §22
     ══════════════════════════════════════════ -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/components/footer_global.php';
?>

<!-- Bootstrap JS (accordéon, collapse si besoin) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
