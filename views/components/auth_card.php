<?php
/**
 * views/components/auth_card.php — AuthCard
 * Design System v2.0 | CL §34
 *
 * Wrapper glassmorphisme pour pages d'authentification.
 * Fournit le fond flou + overlay + conteneur centré.
 *
 * Variables attendues :
 *   $authc_bg_url   — URL de l'image de fond floutée
 *   $authc_title    — titre de la page (ex: 'SE CONNECTER')
 *   $authc_subtitle — sous-titre optionnel
 *   $authc_variant  — 'sm' (440px) | 'lg' (620px) — défaut: 'sm'
 *   $authc_content  — HTML du formulaire / contenu de la carte
 *   $authc_footer   — HTML du footer de la carte (lien vers autre page auth)
 */

$authc_bg_url   = $authc_bg_url   ?? '';
$authc_title    = $authc_title    ?? '';
$authc_subtitle = $authc_subtitle ?? '';
$authc_variant  = $authc_variant  ?? 'sm';
$authc_content  = $authc_content  ?? '';
$authc_footer   = $authc_footer   ?? '';
?>

<!-- Fond flou fixe -->
<div class="auth-bg"
     <?= $authc_bg_url ? 'style="background-image:url(' . htmlspecialchars($authc_bg_url) . ');"' : '' ?>
     aria-hidden="true"></div>
<div class="auth-overlay" aria-hidden="true"></div>

<!-- Wrapper centré -->
<main class="auth-wrapper">
    <div class="auth-card auth-card--<?= htmlspecialchars($authc_variant) ?> fade-in-card">

        <?php if ($authc_title): ?>
            <h1 style="font-family:var(--font-display);color:var(--gold);letter-spacing:2px;font-size:1.4rem;text-align:center;margin-bottom:6px;">
                <?= htmlspecialchars($authc_title) ?>
            </h1>
        <?php endif; ?>

        <?php if ($authc_subtitle): ?>
            <p style="text-align:center;color:var(--text-muted);font-size:0.82rem;margin-bottom:1.5rem;">
                <?= $authc_subtitle /* HTML autorisé pour liens */ ?>
            </p>
        <?php endif; ?>

        <?= $authc_content ?>

        <?php if ($authc_footer): ?>
            <div style="margin-top:1rem;text-align:center;font-size:0.82rem;color:var(--text-muted);">
                <?= $authc_footer ?>
            </div>
        <?php endif; ?>

    </div>
</main>
<?php unset($authc_bg_url, $authc_title, $authc_subtitle, $authc_variant, $authc_content, $authc_footer); ?>
