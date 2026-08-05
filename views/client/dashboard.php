<?php
/**
 * views/client/dashboard.php — Dashboard client connecté
 * Design System v2.0 — Orchestrateur de composants
 * Refactorisation conforme COMPONENT_LIBRARY.md + DESIGN_SYSTEM.md v2.0
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, sessions, variables : inchangés.
 *
 * Architecture :
 *   Données PHP → navbar → hero_capsule → section coiffeurs → section pourquoi → footer + ia + bottom_nav
 */

// ═══════════════════════════════════════════
// 1. PRÉPARATION DES DONNÉES (toutes les
//    variables nécessaires à la vue et aux
//    composants sont définies ici avec valeurs
//    par défaut pour éviter tout Warning)
// ═══════════════════════════════════════════

// ── Variables session avec valeurs par défaut ──
$prenom_client = htmlspecialchars($_SESSION['prenom'] ?? 'Client');
$nom_client    = htmlspecialchars($_SESSION['nom']    ?? '');
$initiale      = strtoupper(substr($prenom_client, 0, 1));
$photo_profil  = $_SESSION['photo_profil'] ?? null;
$id_user       = (int)($_SESSION['id_user'] ?? 0);

// ── Photo profil : vérification physique ──
$photo_url_nav = null;
if ($photo_profil) {
    $photo_check_path = __DIR__ . '/../../access/' . $photo_profil;
    if (file_exists($photo_check_path)) {
        $photo_url_nav = '/coiffons/access/' . htmlspecialchars($photo_profil);
    }
}

// ── Notifications non lues ──
$nb_notifs = 0;
if (isset($pdo) && $id_user > 0) {
    try {
        $q_notifs = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE id_user = ? AND statut_lecture = 'non_lu'");
        $q_notifs->execute([$id_user]);
        $nb_notifs = (int)$q_notifs->fetchColumn();
    } catch (Exception $e) {}
}

// ── Statistique client (RDV terminés) ──
$nb_rdv_termines = 0;
if (isset($pdo) && $id_user > 0) {
    try {
        $q_rdv = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE client_id = ? AND statut_rdv = 'termine'");
        $q_rdv->execute([$id_user]);
        $nb_rdv_termines = (int)$q_rdv->fetchColumn();
    } catch (Exception $e) {}
}

// ── Images pour le slider hero ──
$hero_images_raw = glob(__DIR__ . '/../../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
if (empty($hero_images_raw)) {
    $hero_images_raw = glob(__DIR__ . '/../../images/*.{jpg,jpeg,png}', GLOB_BRACE);
}
$hc_images = [];
foreach ((array)$hero_images_raw as $img) {
    $hc_images[] = '/coiffons/imgid/' . basename($img);
}

// ── Coiffeurs matching (injectés par index.php) ──
$coiffeurs_matching = $coiffeurs_matching ?? [];
$has_real_data      = !empty($coiffeurs_matching);

// ── Variables pour navbar_client.php (composant CL §21) ──
$page_root = '/coiffons';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon espace — Coiffe Chez Toi</title>

    <!-- Design System v2.0 — Ordre officiel -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Sections spécifiques dashboard client ── */
    .dash-coiffeurs  { padding: 4rem 0 2rem; background: var(--dark); }
    .dash-why        { padding: 4rem 0; background: var(--dark-2); }
    .dash-section-title { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin-bottom: .25rem; }
    .dash-section-sub   { color: var(--text-muted); font-size: .85rem; margin-bottom: 2rem; }
    .why-card        { background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: var(--radius-lg); padding: 2rem 1.5rem; text-align: center; height: 100%; transition: var(--transition-spring), border-color .3s; }
    .why-card:hover  { border-color: rgba(212,175,55,.30); transform: translateY(-4px); }
    .why-icon        { width: 56px; height: 56px; border-radius: var(--radius-md); background: var(--gold-dim); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--gold); margin: 0 auto 1.2rem; }
    .why-title       { font-weight: 700; font-size: .95rem; color: var(--text-primary); margin-bottom: .5rem; }
    .why-desc        { color: var(--text-muted); font-size: .82rem; line-height: 1.6; margin: 0; }
    </style>
</head>
<body>

<!-- ══════════════════════════
     NAVBAR — Composant CL §21
     ══════════════════════════ -->
<?php include __DIR__ . '/../../views/components/navbar_client.php'; ?>

<!-- ══════════════════════════
     HERO CAPSULE — Composant CL §2
     ══════════════════════════ -->
<?php
// Variables pour hero_capsule.php
$hc_title                = 'Votre coiffeur, directement chez vous';
$hc_subtitle             = 'Des professionnels certifiés dans votre quartier';
$hc_search_action        = '/coiffons/filter/annuaire_coiffeurs.php';
$hc_search_placeholder   = 'Rechercher un coiffeur, un style...';
$hc_profile = [
    'nom'       => $prenom_client,
    'initiale'  => $initiale,
    'photo_url' => $photo_url_nav,
    'stat'      => $nb_rdv_termines > 0
                   ? $nb_rdv_termines . ' RDV terminé' . ($nb_rdv_termines > 1 ? 's' : '')
                   : 'Bienvenue sur Coiffe Chez Toi',
];
include __DIR__ . '/../../views/components/hero_capsule.php';
?>

<!-- ══════════════════════════
     SECTION : COIFFEURS PROCHES
     ServiceCard — Composant CL §5
     ══════════════════════════ -->
<section class="dash-coiffeurs page-transition" aria-labelledby="coiffeurs-heading">
    <div class="container" style="max-width:var(--max-width);">

        <h2 id="coiffeurs-heading" class="dash-section-title">Coiffeurs près de vous</h2>
        <p class="dash-section-sub">Professionnels disponibles dans votre zone</p>

        <div class="row g-4">
            <?php if ($has_real_data):
                foreach ($coiffeurs_matching as $c):
                    $nom_coiffeur  = htmlspecialchars($c['nom_coiffeur'] ?? 'Coiffeur');
                    $initiale_c    = strtoupper(substr($nom_coiffeur, 0, 1));
                    $prix_c        = number_format($c['prix'] ?? 0, 0, ',', ' ');
                    $ville_c       = htmlspecialchars($c['ville'] ?? '');
                    $quartier_c    = htmlspecialchars($c['quartier'] ?? '');
                    $id_coiffeur_c = (int)($c['id_coiffeur'] ?? 0);
                    $note_c        = isset($c['note_moyenne']) && $c['note_moyenne'] > 0 ? floatval($c['note_moyenne']) : null;
                    $nb_avis_c     = (int)($c['nb_avis'] ?? 0);
                    $is_verified   = (bool)($c['is_approved'] ?? false);
                    $photo_c       = $c['photo_style'] ?? null;
                    $photo_url_c   = ($photo_c && file_exists(__DIR__ . '/../../uploads/' . $photo_c))
                                     ? '/coiffons/uploads/' . htmlspecialchars($photo_c)
                                     : null;
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
            ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <?php include __DIR__ . '/../../views/components/service_card.php'; ?>
                </div>
            <?php
                endforeach;
            else:
                // Skeletons pendant le chargement
                for ($s = 0; $s < 3; $s++): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="skeleton skeleton-card" aria-hidden="true"></div>
                    </div>
                <?php endfor; ?>
                <div class="col-12">
                    <?php
                    $es = [
                        'icon'      => 'bi-person-badge',
                        'message'   => 'Aucun coiffeur disponible dans votre zone pour le moment.',
                        'cta_label' => 'Explorer tous les coiffeurs',
                        'cta_href'  => '/coiffons/filter/annuaire_coiffeurs.php',
                    ];
                    include __DIR__ . '/../../views/components/empty_state.php';
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($has_real_data): ?>
            <div class="text-center mt-4">
                <a href="/coiffons/filter/annuaire_coiffeurs.php"
                   class="btn-ghost btn-sm d-inline-flex align-items-center gap-2">
                    Voir tous les coiffeurs
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- ══════════════════════════
     SECTION : POURQUOI CCT
     ══════════════════════════ -->
<section class="dash-why" aria-labelledby="why-heading">
    <div class="container" style="max-width:var(--max-width);">
        <h2 id="why-heading" class="dash-section-title text-center mb-1">Pourquoi Coiffe Chez Toi ?</h2>
        <p class="dash-section-sub text-center">La coiffure professionnelle réinventée</p>
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
                    <p class="why-desc">Chaque coiffeur est certifié par notre équipe avant d'être activé sur la plateforme.</p>
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

<!-- ══════════════════════════
     FOOTER + IA + BOTTOM NAV
     ══════════════════════════ -->
<?php
include __DIR__ . '/../../views/components/footer_global.php';
include __DIR__ . '/../../views/components/ia_assistant.php';
$current_page = 'dashboard';
include __DIR__ . '/../../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
