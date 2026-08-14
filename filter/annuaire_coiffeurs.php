<?php
/**
 * filter/annuaire_coiffeurs.php — Annuaire Premium des coiffeurs
 * Refactoring UX/UI — Design System v2.0
 *
 * NOUVEAUTÉS :
 *  - Autocomplete AJAX sur la recherche par nom
 *  - Détection automatique de la zone client connecté
 *  - Bouton "Modifier ma zone" pour surcharge temporaire
 *  - Suppression filtres prix (hors parcours principal)
 *  - Hero premium glassmorphisme
 *  - Cartes coiffeurs premium améliorées
 *
 * CONSERVÉ INTACT :
 *  - PDO, sécurité, sessions
 *  - is_approved = 1, notes moyennes, nb_avis
 *  - Logique ville/quartier (AJAX get_quartiers.php)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

// ─────────────────────────────────────────────
// 1. DÉTECTION RÔLE & LOCALISATION
// ─────────────────────────────────────────────
$est_client_connecte = isset($_SESSION['id_user']) && ($_SESSION['role'] ?? '') === 'client';

// Localisation session client
$ville_session    = $est_client_connecte ? (int)($_SESSION['id_ville']       ?? 0) : 0;
$quartier_session = $est_client_connecte ? (int)($_SESSION['id_quartier'] ?? 0) : 0;

// Paramètres GET — surcharge temporaire possible
$ville_filtre    = isset($_GET['ville'])       ? intval($_GET['ville'])       : 0;
$quartier_filtre = isset($_GET['id_quartier']) ? intval($_GET['id_quartier']) : 0;
$q               = trim($_GET['q'] ?? '');

// Si client connecté et aucune surcharge GET → utiliser la session
$mode_auto = false;
if ($est_client_connecte && !isset($_GET['ville']) && !isset($_GET['q']) && !isset($_GET['_zone_override'])) {
    $ville_filtre    = $ville_session;
    $quartier_filtre = $quartier_session;
    $mode_auto       = ($ville_filtre > 0);
}

// Y-a-t-il eu une recherche active ?
// Nouvelle logique : deux recherches indépendantes
// - search_type=name : recherche par nom
// - search_type=ville : recherche par ville
$search_type    = $_GET['search_type'] ?? ''; // 'name', 'ville', ou vide
$form_submitted = $search_type !== '';
$a_recherche    = $form_submitted || $mode_auto;

// ─────────────────────────────────────────────
// 2. REQUÊTE COIFFEURS — Deux logiques indépendantes
// ─────────────────────────────────────────────
$coiffeurs = [];

if ($search_type === 'name') {
    // ── Recherche par nom ──
    if ($q !== '') {
        $sql    = "SELECT DISTINCT u.*, v.nom_ville, q.nom_quartier,
                       (SELECT ROUND(AVG(c.note), 1) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS note_moyenne,
                       (SELECT COUNT(c.id_commentaire) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS nb_avis
                   FROM users u
                   LEFT JOIN villes v ON u.ville = v.id
                   LEFT JOIN quartiers q ON u.id_quartier = q.id
                   WHERE u.role = 'coiffeur'
                     AND u.is_approved = 1
                     AND (u.abonnement_status = 1 OR LOWER(u.abonnement_status) = 'actif')
                     AND (u.nom LIKE ? OR u.prenom LIKE ? OR CONCAT(u.prenom,' ',u.nom) LIKE ? OR CONCAT(u.nom,' ',u.prenom) LIKE ?)
                   ORDER BY note_moyenne DESC, nb_avis DESC, u.created_at DESC";
        $frag      = '%' . $q . '%';
        $params   = [$frag, $frag, $frag, $frag];

        try {
            $stmt     = $pdo->prepare($sql);
            $stmt->execute($params);
            $coiffeurs = $stmt->fetchAll();
        } catch (PDOException $e) {
            $coiffeurs = [];
        }
    }

} elseif ($search_type === 'ville') {
    // ── Recherche par ville ──
    if ($ville_filtre > 0) {
        $sql    = "SELECT DISTINCT u.*, v.nom_ville, q.nom_quartier,
                       (SELECT ROUND(AVG(c.note), 1) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS note_moyenne,
                       (SELECT COUNT(c.id_commentaire) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS nb_avis
                   FROM users u
                   LEFT JOIN villes v ON u.ville = v.id
                   LEFT JOIN quartiers q ON u.id_quartier = q.id
                   WHERE u.role = 'coiffeur'
                     AND u.is_approved = 1
                     AND (u.abonnement_status = 1 OR LOWER(u.abonnement_status) = 'actif')
                     AND u.ville = ?
                   ORDER BY note_moyenne DESC, nb_avis DESC, u.created_at DESC";
        $params = [$ville_filtre];

        try {
            $stmt     = $pdo->prepare($sql);
            $stmt->execute($params);
            $coiffeurs = $stmt->fetchAll();
        } catch (PDOException $e) {
            $coiffeurs = [];
        }
    }

} elseif ($mode_auto && !isset($_GET['_zone_override'])) {
    // ── Mode auto pour client connecté : affiche sa zone par défaut ──
    $sql    = "SELECT DISTINCT u.*, v.nom_ville, q.nom_quartier,
                   (SELECT ROUND(AVG(c.note), 1) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS note_moyenne,
                   (SELECT COUNT(c.id_commentaire) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS nb_avis
               FROM users u
               LEFT JOIN villes v ON u.ville = v.id
               LEFT JOIN quartiers q ON u.id_quartier = q.id
               LEFT JOIN zones_coiffeur z ON u.id = z.id_coiffeur
               WHERE u.role = 'coiffeur'
                 AND u.is_approved = 1
                 AND (u.abonnement_status = 1 OR LOWER(u.abonnement_status) = 'actif')";
    $params = [];

    if ($ville_filtre > 0) {
        $sql      .= " AND u.ville = ?";
        $params[]  = $ville_filtre;
    }
    if ($quartier_filtre > 0) {
        $sql      .= " AND (u.id_quartier = ? OR z.id_quartier = ?)";
        $params[]  = $quartier_filtre;
        $params[]  = $quartier_filtre;
    }
    $sql .= " ORDER BY note_moyenne DESC, nb_avis DESC, u.created_at DESC";

    try {
        $stmt     = $pdo->prepare($sql);
        $stmt->execute($params);
        $coiffeurs = $stmt->fetchAll();
    } catch (PDOException $e) {
        $coiffeurs = [];
    }
}

// ─────────────────────────────────────────────
// 3. DONNÉES COMPLÉMENTAIRES
// ─────────────────────────────────────────────
// Villes
try {
    $villes = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC")->fetchAll();
} catch (Exception $e) { $villes = []; }

// Nom de la ville session (pour l'affichage zone auto)
$nom_ville_session    = '';
$nom_quartier_session = '';
if ($ville_session > 0) {
    foreach ($villes as $v) {
        if ((int)$v['id'] === $ville_session) { $nom_ville_session = $v['nom_ville']; break; }
    }
}
if ($quartier_session > 0) {
    try {
        $sq = $pdo->prepare("SELECT nom_quartier FROM quartiers WHERE id = ? LIMIT 1");
        $sq->execute([$quartier_session]);
        $nom_quartier_session = $sq->fetchColumn() ?: '';
    } catch (Exception $e) {}
}

// Fond hero
$hero_images = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$hero_bg_url = !empty($hero_images) ? '/coiffons/imgid/' . basename($hero_images[0]) : '';

// Quartiers présélectionnés pour la ville filtrée (pré-remplissage)
$quartiers_prefill = [];
if ($ville_filtre > 0) {
    try {
        $sq2 = $pdo->prepare("SELECT id, nom_quartier FROM quartiers WHERE id_ville = ? ORDER BY nom_quartier ASC");
        $sq2->execute([$ville_filtre]);
        $quartiers_prefill = $sq2->fetchAll();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuaire des coiffeurs — Coiffe Chez Toi</title>

    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ═══════════════════════════════════
       ANNUAIRE PREMIUM — Styles dédiés
       Utilise exclusivement var(--*)
       ═══════════════════════════════════ */

    /* ── HERO ── */
    .ann-hero {
        position: relative;
        min-height: 52vh;
        display: flex;
        align-items: flex-end;
        padding: 0 0 2.5rem;
        overflow: hidden;
    }
    .ann-hero-bg {
        position: absolute; inset: 0;
        background: url('<?= htmlspecialchars($hero_bg_url) ?>') center / cover no-repeat;
        filter: blur(5px) brightness(0.15);
        transform: scale(1.06);
        z-index: 0;
    }
    .ann-hero-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(
            to bottom,
            rgba(10,10,10,0.25) 0%,
            rgba(10,10,10,0.55) 40%,
            rgba(10,10,10,0.88) 100%
        );
        z-index: 1;
    }
    .ann-hero-content {
        position: relative; z-index: 2;
        width: 100%; max-width: var(--max-width);
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    /* ── SEARCH CARD ── */
    .ann-search-card {
        background: rgba(255,255,255,0.07);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid var(--glass-border-md);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        padding: 2rem 2rem 1.75rem;
        max-width: 780px;
        margin: 0 auto;
    }

    /* ── TITRE HERO ── */
    .ann-hero-title {
        font-family: var(--font-display);
        font-size: clamp(1.6rem, 3.5vw, 2.4rem);
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 4px;
        letter-spacing: .5px;
    }
    .ann-hero-sub {
        color: var(--text-muted);
        font-size: .88rem;
        margin: 0 0 1.5rem;
        font-weight: 300;
    }

    /* ── ZONE AUTO CLIENT ── */
    .ann-zone-auto {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(212,175,55,0.08);
        border: 1px solid rgba(212,175,55,0.25);
        border-radius: var(--radius-md);
        padding: 10px 16px;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }
    .ann-zone-info { flex: 1; min-width: 0; }
    .ann-zone-label { font-size: .68rem; text-transform: uppercase; letter-spacing: .8px; color: var(--gold); display: block; margin-bottom: 1px; }
    .ann-zone-value { font-size: .88rem; font-weight: 700; color: var(--text-primary); }
    .ann-zone-modify {
        font-size: .75rem; font-weight: 700;
        color: rgba(255,255,255,0.45);
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.10);
        border-radius: var(--radius-sm);
        padding: 5px 12px;
        cursor: pointer;
        transition: var(--transition-fast);
        text-decoration: none;
        white-space: nowrap;
    }
    .ann-zone-modify:hover { color: var(--gold); border-color: rgba(212,175,55,0.35); background: rgba(212,175,55,0.07); }

    /* ── CHAMP AUTOCOMPLETE ── */
    .ann-autocomplete-wrap { position: relative; }
    .ann-suggestions {
        position: absolute;
        top: calc(100% + 4px);
        left: 0; right: 0;
        background: rgba(18,15,8,0.97);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(212,175,55,0.3);
        border-radius: var(--radius-md);
        z-index: 50;
        box-shadow: var(--shadow-lg);
        max-height: 320px;
        overflow-y: auto;
        display: none;
    }
    .ann-suggestions.open { display: block; }
    .ann-suggestion-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        cursor: pointer;
        transition: background .15s;
        text-decoration: none;
        color: var(--text-primary);
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .ann-suggestion-item:last-child { border-bottom: none; }
    .ann-suggestion-item:hover, .ann-suggestion-item:focus { background: rgba(212,175,55,0.09); outline: none; }
    .ann-sug-avatar {
        width: 36px; height: 36px; border-radius: 50%;
        object-fit: cover; border: 1.5px solid rgba(212,175,55,0.3); flex-shrink: 0;
    }
    .ann-sug-avatar-ph {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        background: rgba(212,175,55,0.12); border: 1.5px solid rgba(212,175,55,0.3);
        display: flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 700; color: var(--gold);
    }
    .ann-sug-name  { font-size: .88rem; font-weight: 600; }
    .ann-sug-ville { font-size: .72rem; color: var(--text-muted); margin-top: 1px; }
    .ann-sug-empty { padding: 14px; text-align: center; color: var(--text-muted); font-size: .82rem; }

    /* ── BOUTON RECHERCHER ── */
    .ann-search-submit {
        height: 44px; padding: 0 28px;
        font-size: .88rem; font-weight: 700;
        white-space: nowrap;
    }

    /* ── RÉSULTATS ── */
    .ann-results { padding: 3rem 0 5rem; background: var(--dark); min-height: 50vh; }
    .ann-results-meta {
        font-size: .82rem; color: var(--text-muted); margin-bottom: 1.75rem;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem;
    }
    .ann-count { color: var(--gold); font-weight: 800; font-size: 1rem; }

    /* ── CARTE COIFFEUR PREMIUM ── */
    .coiffeur-card-premium {
        background: var(--dark-2);
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: var(--radius-lg);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: transform .3s cubic-bezier(.25,.8,.25,1), border-color .3s, box-shadow .3s;
    }
    .coiffeur-card-premium:hover {
        transform: translateY(-8px);
        border-color: rgba(212,175,55,0.40);
        box-shadow: 0 20px 50px rgba(0,0,0,0.55), 0 0 0 1px rgba(212,175,55,0.15);
    }

    /* Cover avec dégradé */
    .cp-cover {
        position: relative; width: 100%; height: 130px;
        background: linear-gradient(135deg, var(--dark-3) 0%, var(--dark-4) 100%);
        overflow: hidden;
    }
    .cp-cover-img { width: 100%; height: 100%; object-fit: cover; opacity: .55; }
    .cp-cover-ph  {
        width: 100%; height: 100%;
        background: linear-gradient(135deg,#0f0f0f,#1c1c1c);
        display: flex; align-items: center; justify-content: center;
        font-size: 2.5rem; color: rgba(255,255,255,.04);
    }
    .cp-cover-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to bottom, transparent 30%, var(--dark-2) 100%);
    }

    /* Avatar sur le cover */
    .cp-avatar-wrap {
        position: absolute; bottom: -28px; left: 50%;
        transform: translateX(-50%);
        z-index: 2;
    }
    .cp-avatar {
        width: 64px; height: 64px; border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--dark-2);
        box-shadow: 0 4px 16px rgba(0,0,0,0.5);
    }
    .cp-avatar-ph {
        width: 64px; height: 64px; border-radius: 50%;
        background: rgba(212,175,55,0.12);
        border: 3px solid var(--dark-2);
        box-shadow: 0 4px 16px rgba(0,0,0,0.5);
        display: flex; align-items: center; justify-content: center;
        font-family: var(--font-display); font-size: 1.4rem;
        font-weight: 700; color: var(--gold);
    }

    /* Badge certifié en haut à droite */
    .cp-badge-certified {
        position: absolute; top: 10px; right: 10px;
        background: rgba(25,135,84,0.90);
        color: #fff; font-size: .62rem; font-weight: 700;
        padding: 3px 9px; border-radius: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        display: flex; align-items: center; gap: 4px;
    }

    /* Corps de la carte */
    .cp-body {
        padding: 2.5rem 1.25rem 1.25rem;
        text-align: center;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .cp-name {
        font-weight: 800; font-size: .98rem;
        color: var(--text-primary);
        margin: 0 0 4px;
        letter-spacing: .3px;
    }
    .cp-location {
        font-size: .75rem; color: var(--text-muted);
        margin: 0 0 10px;
        display: flex; align-items: center; justify-content: center; gap: 4px;
    }
    .cp-stars {
        margin: 0 0 12px;
        font-size: .82rem; letter-spacing: 1px;
    }
    .cp-price-block {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        padding: 8px 12px;
        margin: 0 0 14px;
    }
    .cp-price-label { font-size: .62rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; display: block; }
    .cp-price-val   { font-size: 1.15rem; font-weight: 800; color: var(--gold); display: block; }

    /* Footer carte */
    .cp-footer {
        padding: 10px 14px;
        border-top: 1px solid var(--glass-border);
        background: var(--dark-3);
        display: flex; align-items: center; justify-content: space-between;
        font-size: .72rem;
    }

    /* ── EMPTY / START STATE ── */
    .ann-start-block {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        padding: 4rem 2rem;
        text-align: center;
        max-width: 580px;
        margin: 0 auto;
    }

    /* ── TABS SYSTEM ── */
    .ann-tabs {
        display: flex;
        gap: 8px;
        margin: 0 0 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        padding-bottom: 0;
    }
    .ann-tab {
        background: none;
        border: none;
        color: var(--text-muted);
        font-size: .82rem;
        font-weight: 600;
        padding: 10px 16px;
        cursor: pointer;
        transition: color .2s, border-color .2s;
        border-bottom: 2px solid transparent;
        text-transform: uppercase;
        letter-spacing: .5px;
        position: relative;
        top: 1px;
    }
    .ann-tab:hover {
        color: var(--gold);
    }
    .ann-tab.active {
        color: var(--gold);
        border-bottom-color: var(--gold);
    }

    /* ── TAB CONTENT ── */
    .ann-tab-content {
        display: none;
    }
    .ann-tab-content.active {
        display: block;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
        .ann-hero      { min-height: 62vh; }
        .ann-search-card { padding: 1.25rem 1.1rem 1rem; }
        .ann-hero-title { font-size: 1.5rem; }
        .ann-search-submit { width: 100%; }
        .cp-cover { height: 100px; }
    }
    @media (max-width: 480px) {
        .ann-hero { min-height: 70vh; }
    }
    </style>
</head>
<body>

<!-- ════════════ NAVBAR ════════════ -->
<?php
$prenom_client = htmlspecialchars($_SESSION['prenom'] ?? '');
$photo_profil  = $_SESSION['photo_profil'] ?? null;
$nb_notifs     = 0;
$page_root     = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<!-- ════════════════════════════════════════════
     HERO PREMIUM — fond flou + search card
     ════════════════════════════════════════════ -->
<section class="ann-hero" aria-labelledby="ann-heading">
    <div class="ann-hero-bg" aria-hidden="true"></div>
    <div class="ann-hero-overlay" aria-hidden="true"></div>

    <div class="ann-hero-content">
        <div class="ann-search-card">

            <!-- Titre -->
            <h1 id="ann-heading" class="ann-hero-title">
                Trouvez votre artiste capillaire
            </h1>
            <p class="ann-hero-sub">
                Des professionnels certifiés, directement à votre domicile.
            </p>

            <?php if ($mode_auto && !isset($_GET['_zone_override'])): ?>
            <!-- ── Zone auto client connecté ── -->
            <div class="ann-zone-auto" id="ann-zone-display">
                <i class="bi bi-geo-alt-fill" style="color:var(--gold);font-size:1.1rem;flex-shrink:0;" aria-hidden="true"></i>
                <div class="ann-zone-info">
                    <span class="ann-zone-label">Votre zone détectée</span>
                    <span class="ann-zone-value">
                        <?= htmlspecialchars($nom_ville_session) ?>
                        <?= $nom_quartier_session ? ' · ' . htmlspecialchars($nom_quartier_session) : '' ?>
                    </span>
                </div>
                <a href="/coiffons/filter/annuaire_coiffeurs.php?_zone_override=1"
                   class="ann-zone-modify"
                   aria-label="Modifier la zone de recherche">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Modifier ma zone
                </a>
            </div>
            <?php endif; ?>

            <!-- ── Onglets de recherche ── -->
            <div class="ann-tabs" role="tablist">
                <button type="button" class="ann-tab active" data-tab="search-name" role="tab" aria-selected="true" aria-controls="search-name-panel">
                    <i class="bi bi-person-circle me-1" aria-hidden="true"></i>Par nom
                </button>
                <button type="button" class="ann-tab" data-tab="search-ville" role="tab" aria-selected="false" aria-controls="search-ville-panel">
                    <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>Par ville
                </button>
            </div>

            <!-- ── TAB 1 : Recherche par nom ── -->
            <div id="search-name-panel" class="ann-tab-content active" role="tabpanel" aria-labelledby="tab-name">
                <form method="GET" role="search" aria-label="Rechercher par nom" id="ann-form-name">
                    <input type="hidden" name="search_type" value="name">
                    
                    <div class="ann-autocomplete-wrap mb-3">
                        <label for="annNomInput" class="form-label-cct" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);">
                            <i class="bi bi-search me-1" aria-hidden="true"></i>Nom du coiffeur
                        </label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.35);font-size:.95rem;pointer-events:none;">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </span>
                            <input type="text"
                                   id="annNomInput"
                                   name="q"
                                   class="fc-dark"
                                   style="padding-left:40px;"
                                   value="<?= htmlspecialchars($q) ?>"
                                   placeholder="Entrez un nom, prénom ou nom complet..."
                                   autocomplete="off"
                                   aria-autocomplete="list"
                                   aria-controls="ann-suggestions-list"
                                   minlength="2">
                        </div>
                        <div id="ann-suggestions-list" class="ann-suggestions" role="listbox" aria-label="Suggestions de coiffeurs"></div>
                    </div>

                    <button type="submit" class="btn-gold w-100 ann-search-submit" aria-label="Lancer la recherche par nom">
                        <i class="bi bi-search me-2" aria-hidden="true"></i>Rechercher
                    </button>
                </form>
            </div>

            <!-- ── TAB 2 : Recherche par ville ── -->
            <div id="search-ville-panel" class="ann-tab-content" role="tabpanel" aria-labelledby="tab-ville">
                <form method="GET" role="search" aria-label="Rechercher par ville" id="ann-form-ville">
                    <input type="hidden" name="search_type" value="ville">
                    
                    <!-- Sélection de la ville -->
                    <div class="mb-3">
                        <label for="villeSelectSimple" class="form-label-cct" style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);">
                            <i class="bi bi-geo-alt me-1" aria-hidden="true"></i>Choisir une ville
                        </label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(212,175,55,.5);font-size:.9rem;pointer-events:none;">
                                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                            </span>
                            <select name="ville"
                                    id="villeSelectSimple"
                                    class="fc-dark"
                                    style="padding-left:38px;"
                                    aria-label="Sélectionner une ville"
                                    required>
                                <option value="">-- Sélectionner une ville --</option>
                                <?php foreach ($villes as $v): ?>
                                    <option value="<?= $v['id'] ?>">
                                        <?= htmlspecialchars($v['nom_ville']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-gold w-100 ann-search-submit" aria-label="Lancer la recherche par ville">
                        <i class="bi bi-search me-2" aria-hidden="true"></i>Afficher tous les coiffeurs
                    </button>
                </form>
            </div>

        </div><!-- /.ann-search-card -->
    </div>
</section>

<!-- ════════════════════════════════════════════
     SECTION RÉSULTATS
     ════════════════════════════════════════════ -->
<section class="ann-results page-transition" aria-label="Résultats">
    <div class="container" style="max-width:var(--max-width);">

        <?php if (!$a_recherche): ?>
        <!-- ── Invitation initiale ── -->
        <div class="ann-start-block">
            <i class="bi bi-scissors"
               style="font-size:3rem;color:var(--gold);opacity:.4;display:block;margin-bottom:1.25rem;"
               aria-hidden="true"></i>
            <h2 style="font-family:var(--font-display);font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:.75rem;">
                Votre recherche commence ici
            </h2>
            <p style="color:var(--text-muted);font-size:.88rem;line-height:1.75;margin:0 auto;max-width:420px;">
                Entrez un nom, sélectionnez votre ville ou votre quartier
                pour découvrir nos coiffeurs certifiés à domicile.
            </p>
            <div style="display:flex;justify-content:center;gap:1.25rem;flex-wrap:wrap;margin-top:1.75rem;">
                <span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">
                    <i class="bi bi-patch-check-fill" style="color:var(--gold);" aria-hidden="true"></i>Diplômes vérifiés
                </span>
                <span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">
                    <i class="bi bi-clock" style="color:var(--gold);" aria-hidden="true"></i>À l'heure chez vous
                </span>
                <span style="display:flex;align-items:center;gap:6px;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">
                    <i class="bi bi-shield-lock-fill" style="color:var(--gold);" aria-hidden="true"></i>Paiement sécurisé
                </span>
            </div>
        </div>

        <?php elseif (empty($coiffeurs)): ?>
        <!-- ── Empty state DS ── -->
        <?php
        $es = [
            'icon'      => 'bi-emoji-frown',
            'message'   => 'Aucun coiffeur trouvé pour ces critères.',
            'cta_label' => 'Élargir la recherche',
            'cta_href'  => '/coiffons/filter/annuaire_coiffeurs.php',
        ];
        include __DIR__ . '/../views/components/empty_state.php';
        ?>

        <?php else: ?>
        <!-- ── Résultats ── -->
        <div class="ann-results-meta">
            <span>
                <span class="ann-count"><?= count($coiffeurs) ?></span>
                coiffeur<?= count($coiffeurs) > 1 ? 's' : '' ?> disponible<?= count($coiffeurs) > 1 ? 's' : '' ?>
                <?php if ($q): ?>
                    · <em style="color:var(--gold);">"<?= htmlspecialchars($q) ?>"</em>
                <?php endif; ?>
            </span>
            <?php if ($mode_auto && !isset($_GET['_zone_override'])): ?>
                <span style="font-size:.72rem;color:var(--text-muted);">
                    <i class="bi bi-geo-alt-fill" style="color:var(--gold);" aria-hidden="true"></i>
                    <?= htmlspecialchars($nom_ville_session) ?>
                    <?= $nom_quartier_session ? ' · ' . htmlspecialchars($nom_quartier_session) : '' ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php foreach ($coiffeurs as $c):
                $nom_aff       = htmlspecialchars(strtoupper($c['nom'] . ' ' . ($c['prenom'] ?? '')));
                $initiale      = strtoupper(substr($c['nom'], 0, 1));
                $ville_aff     = $c['nom_ville'] ?? 'Bénin';
                $quartier_aff  = (!empty($c['nom_quartier']) && ($c['id_quartier'] ?? 0) != 0)
                                 ? $c['nom_quartier'] : '';
                $nm            = floatval($c['note_moyenne'] ?? 0);
                $nba           = intval($c['nb_avis'] ?? 0);
                $tarif         = intval($c['tarif_base'] ?? 0);

                // Photo profil
                $photo_url = null;
                if (!empty($c['photo_profil'])) {
                    $check = __DIR__ . '/../access/' . $c['photo_profil'];
                    if (file_exists($check)) {
                        $photo_url = '/coiffons/access/' . htmlspecialchars($c['photo_profil']);
                    }
                }
            ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <article class="coiffeur-card-premium" aria-label="Profil de <?= $nom_aff ?>">

                    <!-- Cover -->
                    <div class="cp-cover">
                        <?php if ($photo_url): ?>
                            <img src="<?= $photo_url ?>" class="cp-cover-img" alt="" aria-hidden="true" loading="lazy">
                        <?php else: ?>
                            <div class="cp-cover-ph" aria-hidden="true">
                                <i class="bi bi-scissors"></i>
                            </div>
                        <?php endif; ?>
                        <div class="cp-cover-overlay" aria-hidden="true"></div>

                        <!-- Badge certifié -->
                        <span class="cp-badge-certified" aria-label="Coiffeur certifié">
                            <i class="bi bi-patch-check-fill" aria-hidden="true"></i>Certifié
                        </span>

                        <!-- Avatar centré -->
                        <div class="cp-avatar-wrap">
                            <?php if ($photo_url): ?>
                                <img src="<?= $photo_url ?>" class="cp-avatar" alt="Photo de <?= htmlspecialchars($c['nom']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="cp-avatar-ph" aria-hidden="true"><?= $initiale ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Corps -->
                    <div class="cp-body">
                        <div class="cp-name"><?= $nom_aff ?></div>

                        <div class="cp-location">
                            <i class="bi bi-geo-alt-fill" style="color:var(--gold);font-size:.7rem;" aria-hidden="true"></i>
                            <?= htmlspecialchars($ville_aff) ?>
                            <?= $quartier_aff ? ' · ' . htmlspecialchars($quartier_aff) : '' ?>
                        </div>

                        <!-- Étoiles -->
                        <div class="cp-stars" aria-label="Note: <?= $nm > 0 ? $nm . '/5' : 'Nouveau' ?>">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <span style="color:<?= ($nm > 0 && $s <= round($nm)) ? '#D4AF37' : 'rgba(255,255,255,0.15)' ?>;">★</span>
                            <?php endfor; ?>
                            <?php if ($nba > 0): ?>
                                <span style="color:var(--text-primary);font-weight:700;font-size:.8rem;margin-left:4px;"><?= $nm ?></span>
                                <span style="color:var(--text-muted);font-size:.72rem;">(<?= $nba ?> avis)</span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:.72rem;margin-left:4px;">Nouveau</span>
                            <?php endif; ?>
                        </div>

                        <!-- Prix -->
                        <div class="cp-price-block">
                            <span class="cp-price-label">Tarif de base</span>
                            <span class="cp-price-val">
                                <?= $tarif > 0 ? number_format($tarif, 0, ',', ' ') . ' <small style="font-size:.72rem;">FCFA</small>' : 'Sur devis' ?>
                            </span>
                        </div>

                        <!-- CTA -->
                        <a href="/coiffons/coiffeurs/profil_public.php?id=<?= (int)$c['id'] ?>"
                           class="btn-gold w-100 mt-auto"
                           style="padding:11px;"
                           aria-label="Voir le profil de <?= $nom_aff ?>">
                            <i class="bi bi-calendar3 me-2" aria-hidden="true"></i>
                            Voir le profil
                        </a>
                    </div>

                    <!-- Footer diplôme -->
                    <div class="cp-footer">
                        <span style="color:var(--text-muted);">Vérification :</span>
                        <?php if (!empty($c['diplome']) && file_exists(__DIR__ . '/../access/' . $c['diplome'])): ?>
                            <a href="/coiffons/access/<?= htmlspecialchars($c['diplome']) ?>"
                               target="_blank" rel="noopener"
                               style="color:var(--gold);text-decoration:none;display:flex;align-items:center;gap:4px;"
                               aria-label="Voir le diplôme de <?= htmlspecialchars($c['nom']) ?>">
                                <i class="bi bi-file-earmark-check" aria-hidden="true"></i>
                                Diplôme vérifié
                            </a>
                        <?php else: ?>
                            <span style="color:var(--text-disabled);font-style:italic;">En attente</span>
                        <?php endif; ?>
                    </div>

                </article>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

    </div>
</section>

<!-- ════════════ FOOTER + BOTTOM NAV ════════════ -->
<?php
$page_root    = '/coiffons';
include __DIR__ . '/../views/components/footer_global.php';
include __DIR__ . '/../views/components/ia_assistant.php';
$current_page = 'annuaire_coiffeurs.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ═══════════════════════════════════════════════
   ANNUAIRE — JavaScript
   1. Autocomplete AJAX sur le champ nom
   2. Chargement AJAX des quartiers selon ville
   ═══════════════════════════════════════════════ */

(function () {
    'use strict';

    const input      = document.getElementById('annNomInput');
    const listbox    = document.getElementById('ann-suggestions-list');
    const form       = document.getElementById('ann-form');

    if (!input || !listbox) return;

    let debounceTimer = null;
    let lastQuery     = '';
    let activeIndex   = -1;
    let suggestions   = [];

    /* ─── Debounce input ─── */
    input.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(debounceTimer);

        if (q.length < 2) {
            closeSuggestions();
            return;
        }
        if (q === lastQuery) return;

        debounceTimer = setTimeout(() => fetchSuggestions(q), 260);
    });

    /* ─── Navigation clavier dans les suggestions ─── */
    input.addEventListener('keydown', function (e) {
        const items = listbox.querySelectorAll('.ann-suggestion-item');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            updateActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            updateActive(items);
        } else if (e.key === 'Enter' && activeIndex >= 0) {
            e.preventDefault();
            items[activeIndex].click();
        } else if (e.key === 'Escape') {
            closeSuggestions();
        }
    });

    function updateActive(items) {
        items.forEach((el, i) => {
            if (i === activeIndex) {
                el.classList.add('active');
                el.setAttribute('aria-selected', 'true');
            } else {
                el.classList.remove('active');
                el.setAttribute('aria-selected', 'false');
            }
        });
    }

    /* ─── Appel AJAX ─── */
    function fetchSuggestions(q) {
        lastQuery = q;
        fetch('/coiffons/access/search_coiffeurs.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                suggestions = data;
                renderSuggestions(data, q);
            })
            .catch(() => closeSuggestions());
    }

    /* ─── Rendu HTML des suggestions ─── */
    function renderSuggestions(data, q) {
        activeIndex = -1;
        if (!data.length) {
            listbox.innerHTML = '<div class="ann-sug-empty">Aucun coiffeur trouvé pour "' + escapeHtml(q) + '"</div>';
            listbox.classList.add('open');
            return;
        }

        const html = data.map((c, idx) => {
            const fullName  = escapeHtml((c.nom || '').toUpperCase() + ' ' + (c.prenom || ''));
            const ville     = escapeHtml(c.ville || '');
            const initiale  = (c.nom || 'C').charAt(0).toUpperCase();
            const avatarHtml = c.photo_url
                ? `<img src="${escapeHtml(c.photo_url)}" class="ann-sug-avatar" alt="" loading="lazy">`
                : `<div class="ann-sug-avatar-ph" aria-hidden="true">${initiale}</div>`;

            return `<a class="ann-suggestion-item"
                       href="/coiffons/coiffeurs/profil_public.php?id=${encodeURIComponent(c.id)}"
                       role="option"
                       aria-selected="false"
                       tabindex="-1">
                        ${avatarHtml}
                        <div>
                            <div class="ann-sug-name">${fullName}</div>
                            <div class="ann-sug-ville">
                                <i class="bi bi-geo-alt" style="color:var(--gold);font-size:.7rem;"></i>
                                ${ville}
                            </div>
                        </div>
                    </a>`;
        }).join('');

        listbox.innerHTML = html;
        listbox.classList.add('open');
    }

    /* ─── Fermeture ─── */
    function closeSuggestions() {
        listbox.classList.remove('open');
        listbox.innerHTML = '';
        activeIndex = -1;
    }

    /* ─── Fermer sur clic extérieur ─── */
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !listbox.contains(e.target)) {
            closeSuggestions();
        }
    });

    /* ─── Escape HTML ─── */
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    /* ═══════════════════════════════════
       GESTION DES ONGLETS DE RECHERCHE
       ═══════════════════════════════════ */
    const tabs = document.querySelectorAll('.ann-tab');
    const tabContents = document.querySelectorAll('.ann-tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const tabName = this.dataset.tab;

            // Désactiver tous les onglets
            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });

            // Masquer tous les panneaux
            tabContents.forEach(content => {
                content.classList.remove('active');
            });

            // Activer l'onglet et son contenu
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');
            document.getElementById(tabName + '-panel').classList.add('active');
        });
    });

    /* ═══════════════════════════════════
       AJAX Quartiers selon ville
       ═══════════════════════════════════ */
    const villeSelect    = document.getElementById('villeSelectAnnuaire');
    const quartierSelect = document.getElementById('quartierSelectAnnuaire');

    if (villeSelect && quartierSelect) {
        villeSelect.addEventListener('change', function () {
            const idVille = this.value;

            if (!idVille) {
                quartierSelect.innerHTML = '<option value="">Tous</option>';
                quartierSelect.disabled  = true;
                return;
            }

            fetch('/coiffons/access/get_quartiers.php?id_ville=' + encodeURIComponent(idVille))
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(data => {
                    quartierSelect.innerHTML = '<option value="">Tous</option>';
                    data.forEach(q => {
                        const o = document.createElement('option');
                        o.value       = q.id;
                        o.textContent = q.nom_quartier;
                        quartierSelect.appendChild(o);
                    });
                    quartierSelect.disabled = false;
                })
                .catch(() => {
                    quartierSelect.innerHTML = '<option value="">Tous</option>';
                    quartierSelect.disabled  = false;
                });
        });
    }

})();
</script>

</body>
</html>
