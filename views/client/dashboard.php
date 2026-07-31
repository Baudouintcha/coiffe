<?php
/**
 * views/client/dashboard.php — Dashboard client connecté
 * Migration Design System v2.0 — Parcours Client — Page 1
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — Seuls HTML/CSS/composants ont été modifiés.
 */

// ── DONNÉES PHP (injectées par index.php via la route ?page=dashboard) ──
$prenom_client   = htmlspecialchars($_SESSION['prenom'] ?? 'Client');
$initiale        = strtoupper(substr($prenom_client, 0, 1));
$photo_profil    = $_SESSION['photo_profil'] ?? null;
$nb_rdv_termines = 0;

if (isset($pdo)) {
    try {
        $q = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE client_id = ? AND statut_rdv = 'termine'");
        $q->execute([$_SESSION['id_user'] ?? 0]);
        $nb_rdv_termines = (int) $q->fetchColumn();
        if (empty($coiffeurs_matching ?? [])) { $coiffeurs_matching = []; }
    } catch (Exception $e) {
        $nb_rdv_termines = 0;
    }
}

$hero_images = glob(__DIR__ . '/../../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
if (empty($hero_images)) {
    $hero_images = glob(__DIR__ . '/../../images/*.{jpg,jpeg,png}', GLOB_BRACE);
}
$has_real_data = !empty($coiffeurs_matching ?? []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace — Coiffe Chez Toi</title>

    <!-- Design System v2.0 — Ordre de chargement officiel -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">

    <!-- Bootstrap 5.3.3 (grille uniquement) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Styles spécifiques au dashboard, exclusivement var(--*) DS ── */

    /* HeroCapsule */
    .hero-wrapper   { padding:calc(var(--navbar-height) + 1.5rem) 0 0; display:flex; justify-content:center; }
    .hero           { position:relative; width:92%; max-width:var(--max-width); height:420px; min-height:380px; max-height:450px; border-radius:52px; overflow:hidden; box-shadow:0 24px 60px rgba(0,0,0,0.55),0 4px 16px rgba(0,0,0,0.3),0 0 0 1px rgba(255,255,255,0.06); }
    .hero-overlay   { position:absolute; inset:0; z-index:1; background:linear-gradient(180deg,rgba(10,10,10,0.35) 0%,rgba(10,10,10,0.15) 40%,rgba(10,10,10,0.75) 100%); }
    .hero-content   { position:absolute; inset:0; z-index:2; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; padding:0 2rem 2rem; text-align:center; }

    /* Profile glass card dans le hero */
    .profile-glass-card { position:absolute; top:84px; right:24px; z-index:3; background:rgba(255,255,255,0.08); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border:1px solid var(--glass-border-md); border-radius:var(--radius-lg); padding:14px 18px; display:flex; align-items:center; gap:12px; min-width:220px; box-shadow:0 4px 20px rgba(0,0,0,0.4); }
    .pc-info        { min-width:0; }
    .pc-greeting    { font-size:.72rem; color:var(--text-muted); }
    .pc-name        { font-weight:700; font-size:.9rem; color:var(--text-primary); }
    .pc-stat        { font-size:.7rem; color:var(--gold); margin-top:2px; }

    /* Titre hero */
    .hero-title     { font-family:var(--font-display); font-size:clamp(1.6rem,3.5vw,2.6rem); font-weight:900; margin-bottom:.4rem; line-height:1.15; text-shadow:0 2px 12px rgba(0,0,0,0.5); }
    .hero-sub       { color:rgba(255,255,255,0.65); font-size:.9rem; margin-bottom:1.2rem; font-weight:300; }

    /* Section coiffeurs */
    .section-coiffeurs { padding:4rem 0 2rem; background:var(--dark); }
    .section-title  { font-family:var(--font-display); font-size:1.6rem; font-weight:700; margin-bottom:.3rem; }
    .section-sub    { color:var(--text-muted); font-size:.85rem; margin-bottom:2rem; }

    /* Section Pourquoi */
    .section-why    { padding:4rem 0; background:var(--dark-2); }
    .why-card       { background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:var(--radius-lg); padding:2rem 1.5rem; text-align:center; transition:var(--transition-spring),border-color .3s; height:100%; }
    .why-card:hover { border-color:rgba(212,175,55,.30); transform:translateY(-4px); }
    .why-icon       { width:56px; height:56px; border-radius:var(--radius-md); background:var(--gold-dim); display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:var(--gold); margin:0 auto 1.2rem; }
    .why-title      { font-weight:700; font-size:.95rem; margin-bottom:.5rem; color:var(--text-primary); }
    .why-desc       { color:var(--text-muted); font-size:.82rem; line-height:1.6; }

    /* Responsive hero */
    @media (max-width:768px) {
        .profile-glass-card { display:none; }
        .hero { height:340px; border-radius:28px; }
        .hero-wrapper { padding:.75rem .75rem 0; }
    }
    @media (max-width:480px) {
        .hero-title { font-size:1.4rem; }
        .hero { height:300px; border-radius:20px; }
    }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════
     NAVBAR CLIENT — Composant CL §21
     ══════════════════════════════════════════ -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../../views/components/navbar_client.php';
?>

<!-- ══════════════════════════════════════════
     HERO CAPSULE — Composant CL §2
     ══════════════════════════════════════════ -->
<div class="hero-wrapper">
    <section class="hero" aria-label="Hero dashboard client">
        <!-- Carousel images -->
        <div id="heroCarousel" aria-hidden="true">
            <?php foreach ($hero_images as $i => $img): ?>
                <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
                     style="background-image:url('/coiffons/imgid/<?= basename($img) ?>');"
                     role="img"></div>
            <?php endforeach; ?>
        </div>
        <div class="hero-overlay" aria-hidden="true"></div>

        <!-- Floating Card profil — Composant CL §4 -->
        <div class="profile-glass-card" aria-label="Profil de <?= $prenom_client ?>">
            <?php if ($photo_profil && file_exists(__DIR__ . '/../../' . $photo_profil)): ?>
                <img src="/coiffons/<?= htmlspecialchars($photo_profil) ?>"
                     class="avatar-placeholder avatar-sm"
                     alt="Photo de profil">
            <?php else: ?>
                <div class="avatar-placeholder avatar-sm" aria-hidden="true"><?= $initiale ?></div>
            <?php endif; ?>
            <div class="pc-info">
                <span class="pc-greeting">Bonjour 👋</span>
                <span class="pc-name d-block"><?= $prenom_client ?></span>
                <span class="pc-stat">
                    <?= $nb_rdv_termines > 0
                        ? $nb_rdv_termines . ' RDV terminé' . ($nb_rdv_termines > 1 ? 's' : '')
                        : 'Bienvenue sur Coiffe Chez Toi' ?>
                </span>
            </div>
        </div>

        <!-- Contenu bas de capsule -->
        <div class="hero-content">
            <h1 class="hero-title">Votre coiffeur,<br>directement chez vous</h1>
            <p class="hero-sub">Des professionnels certifiés dans votre quartier</p>

            <!-- SearchCapsule — Composant CL §10 -->
            <div style="width:100%;max-width:560px;margin:0 auto;">
                <form action="/coiffons/filter/annuaire_coiffeurs.php"
                      method="GET"
                      class="search-bar"
                      role="search"
                      aria-label="Rechercher un coiffeur">
                    <i class="bi bi-search" style="color:rgba(255,255,255,0.5);margin-right:6px;font-size:.9rem;" aria-hidden="true"></i>
                    <input type="search"
                           name="q"
                           id="searchInput"
                           placeholder="Rechercher un coiffeur, un salon..."
                           autocomplete="off"
                           aria-label="Terme de recherche">
                    <button type="submit" class="search-btn">
                        <i class="bi bi-search me-1" aria-hidden="true"></i>Rechercher
                    </button>
                </form>
            </div>
        </div>
    </section>
</div>

<!-- ══════════════════════════════════════════
     SECTION COIFFEURS PROCHES — ServiceCard CL §5
     ══════════════════════════════════════════ -->
<section class="section-coiffeurs page-transition" aria-labelledby="coiffeurs-heading">
    <div class="container" style="max-width:var(--max-width);">
        <h2 id="coiffeurs-heading" class="section-title">Coiffeurs près de vous</h2>
        <p class="section-sub">Professionnels disponibles dans votre zone</p>

        <div class="row g-4" id="coiffeurs-grid">
            <?php if ($has_real_data):
                foreach ($coiffeurs_matching as $c):
                    $nom_coiffeur  = htmlspecialchars($c['nom_coiffeur'] ?? 'Coiffeur');
                    $initiale_c    = strtoupper(substr($nom_coiffeur, 0, 1));
                    $prix_c        = number_format($c['prix'] ?? 0, 0, ',', ' ');
                    $ville_c       = htmlspecialchars($c['ville'] ?? '');
                    $quartier_c    = htmlspecialchars($c['quartier'] ?? '');
                    $id_coiffeur_c = $c['id_coiffeur'] ?? 0;
                    $note_c        = $c['note_moyenne'] ?? null;
                    $nb_avis_c     = $c['nb_avis'] ?? 0;
                    $is_verified   = (bool)($c['is_approved'] ?? false);
                    $photo_c       = $c['photo_style'] ?? null;
                    $photo_url_c   = ($photo_c && file_exists(__DIR__ . '/../../uploads/' . $photo_c))
                                     ? '/coiffons/uploads/' . htmlspecialchars($photo_c)
                                     : null;
            ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <!-- ServiceCard — Composant CL §5 -->
                    <?php
                    $card = [
                        'id'          => $id_coiffeur_c,
                        'nom'         => $nom_coiffeur,
                        'photo_cover' => $photo_url_c,
                        'initiale'    => $initiale_c,
                        'ville'       => $ville_c,
                        'quartier'    => $quartier_c,
                        'prix'        => $prix_c,
                        'note'        => $note_c,
                        'nb_avis'     => $nb_avis_c,
                        'is_verified' => $is_verified,
                        'tags'        => ['Coiffure'],
                        'href'        => '/coiffons/coiffeurs/profil_public.php?id=' . $id_coiffeur_c,
                        'cta_label'   => 'Voir les prestations',
                    ];
                    include __DIR__ . '/../../views/components/service_card.php';
                    ?>
                </div>
            <?php endforeach; ?>

            <?php else:
                // Skeleton loaders — composant DS §18
                for ($s = 0; $s < 6; $s++): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="skeleton skeleton-card" aria-hidden="true"></div>
                    </div>
                <?php endfor;

                // Empty state si BDD vide
                echo '<div class="col-12">';
                $es = [
                    'icon'      => 'bi-person-badge',
                    'message'   => 'Aucun coiffeur disponible dans votre zone pour le moment.',
                    'cta_label' => 'Explorer tous les coiffeurs',
                    'cta_href'  => '/coiffons/filter/annuaire_coiffeurs.php',
                ];
                include __DIR__ . '/../../views/components/empty_state.php';
                echo '</div>';
            endif; ?>
        </div>

        <?php if ($has_real_data): ?>
            <div class="text-center mt-4">
                <a href="/coiffons/filter/annuaire_coiffeurs.php"
                   class="btn-ghost btn-sm"
                   style="display:inline-flex;align-items:center;gap:6px;">
                    Voir tous les coiffeurs
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══════════════════════════════════════════
     SECTION POURQUOI COIFFE CHEZ TOI
     ══════════════════════════════════════════ -->
<section class="section-why" aria-labelledby="why-heading">
    <div class="container" style="max-width:var(--max-width);">
        <h2 id="why-heading" class="section-title text-center mb-1">Pourquoi Coiffe Chez Toi ?</h2>
        <p class="section-sub text-center mb-4">La coiffure professionnelle réinventée</p>
        <div class="row g-4 justify-content-center">
            <div class="col-12 col-md-4">
                <div class="why-card">
                    <div class="why-icon" aria-hidden="true"><i class="bi bi-house-heart"></i></div>
                    <div class="why-title">Service à domicile</div>
                    <p class="why-desc">Des coiffeurs professionnels se déplacent directement chez vous, à votre heure.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="why-card">
                    <div class="why-icon" aria-hidden="true"><i class="bi bi-patch-check"></i></div>
                    <div class="why-title">Professionnels vérifiés</div>
                    <p class="why-desc">Chaque coiffeur est vérifié et certifié par notre équipe avant d'être activé.</p>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="why-card">
                    <div class="why-icon" aria-hidden="true"><i class="bi bi-lightning-charge"></i></div>
                    <div class="why-title">Réservation rapide</div>
                    <p class="why-desc">Choisissez, réservez et confirmez en quelques clics. Simple et sécurisé.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════
     FOOTER + IA ASSISTANT + BOTTOM NAV
     ══════════════════════════════════════════ -->
<?php
include __DIR__ . '/../../views/components/footer_global.php';
include __DIR__ . '/../../views/components/ia_assistant.php';

$current_page = 'dashboard';
include __DIR__ . '/../../views/components/bottom_nav_client.php';
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ══ JS UI — carousel inchangé ══ -->
<script>
// Carousel hero — logique inchangée
const slides = document.querySelectorAll('.hero-slide');
let current = 0;
if (slides.length > 1) {
    setInterval(() => {
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }, 5000);
}
</script>

</body>
</html>
