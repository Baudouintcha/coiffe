<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coiffe Chez Toi — Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #D4AF37;
            --gold-dim: rgba(212,175,55,0.12);
            --dark: #0a0a0a;
            --dark-2: #111;
            --dark-3: #161616;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: var(--dark);
            color: #fff;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }
        /* ── NAVBAR ── */
        .cct-nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            padding: 0 2rem;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(10,10,10,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .cct-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--gold);
            text-decoration: none;
            letter-spacing: 1px;
        }
        .cct-nav-actions { display:flex; align-items:center; gap:1rem; }
        .nav-icon-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 50%;
            width: 38px; height: 38px;
            display: flex; align-items:center; justify-content:center;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 1rem;
        }
        .nav-icon-btn:hover { background: var(--gold-dim); color: var(--gold); border-color: var(--gold); }
        .nav-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            object-fit: cover;
            cursor: pointer;
        }
        .nav-avatar-placeholder {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: var(--gold-dim);
            border: 2px solid var(--gold);
            display: flex; align-items:center; justify-content:center;
            font-size: 0.85rem; font-weight: 700; color: var(--gold);
            cursor: pointer;
        }
        /* ── HERO — Capsule flottante centrée ── */
        .hero-wrapper {
            padding: calc(64px + 1.5rem) 0 0;
            display: flex;
            justify-content: center;
        }
        .hero {
            position: relative;
            width: 92%;
            max-width: 1400px;
            height: 420px;
            min-height: 380px;
            max-height: 450px;
            border-radius: 52px;
            overflow: hidden;
            box-shadow:
                0 24px 60px rgba(0,0,0,0.55),
                0 4px 16px rgba(0,0,0,0.3),
                0 0 0 1px rgba(255,255,255,0.06);
        }
        .hero-carousel {
            position: absolute; inset: 0; z-index: 0;
        }
        .hero-slide {
            position: absolute; inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.5s ease;
        }
        .hero-slide.active { opacity: 1; }
        .hero-overlay {
            position: absolute; inset: 0; z-index: 1;
            background: linear-gradient(
                180deg,
                rgba(10,10,10,0.35) 0%,
                rgba(10,10,10,0.15) 40%,
                rgba(10,10,10,0.75) 100%
            );
        }
        .hero-content {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding: 0 2rem 2rem;
            text-align: center;
        }
        /* Barre de recherche intégrée dans la capsule — glassmorphisme */
        .hero-search-wrap {
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
        }
        .search-bar {
            display: flex; align-items: center;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.22);
            border-radius: 50px;
            padding: 6px 6px 6px 20px;
            box-shadow:
                0 8px 32px rgba(0,0,0,0.35),
                inset 0 1px 0 rgba(255,255,255,0.1);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-bar:focus-within {
            border-color: var(--gold);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 0 3px rgba(212,175,55,0.15);
        }
        .search-bar input {
            flex: 1; background: none; border: none; outline: none;
            color: #fff; font-size: 0.92rem; padding: 9px 0;
        }
        .search-bar input::placeholder { color: rgba(255,255,255,0.45); }
        .search-bar .search-btn {
            background: var(--gold); color: #000;
            border: none; border-radius: 50px;
            padding: 10px 22px; font-weight: 700;
            font-size: 0.82rem; cursor: pointer;
            transition: all 0.2s; white-space: nowrap;
        }
        .search-bar .search-btn:hover { background: #c9a227; }
        /* Carte profil client dans le hero — en haut à droite de la capsule */
        .profile-glass-card {
            position: absolute;
            top: 84px; right: 24px;
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.14);
            border-radius: 16px;
            padding: 14px 18px;
            display: flex; align-items: center; gap: 12px;
            min-width: 220px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        .profile-glass-card .pc-avatar {
            width: 44px; height: 44px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            object-fit: cover;
            flex-shrink: 0;
        }
        .profile-glass-card .pc-avatar-ph {
            width: 44px; height: 44px;
            border-radius: 50%;
            background: var(--gold-dim);
            border: 2px solid var(--gold);
            display: flex; align-items:center; justify-content:center;
            font-weight: 700; color: var(--gold); font-size: 1rem;
            flex-shrink: 0;
        }
        .profile-glass-card .pc-info { min-width: 0; }
        .profile-glass-card .pc-greeting { font-size: 0.72rem; color: rgba(255,255,255,0.5); }
        .profile-glass-card .pc-name { font-weight: 700; font-size: 0.9rem; color: #fff; }
        .profile-glass-card .pc-stat { font-size: 0.7rem; color: var(--gold); margin-top: 2px; }
        /* Titre et sous-titre dans la capsule */
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 3.5vw, 2.6rem);
            font-weight: 900; margin-bottom: 0.4rem;
            line-height: 1.15; text-shadow: 0 2px 12px rgba(0,0,0,0.5);
        }
        .hero-sub {
            color: rgba(255,255,255,0.65);
            font-size: 0.9rem; margin-bottom: 1.2rem;
            font-weight: 300; text-shadow: 0 1px 6px rgba(0,0,0,0.4);
        }

        /* ── SECTION COIFFEURS ── */
        .section-coiffeurs { padding: 4rem 0 2rem; }
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem; font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .section-sub { color: rgba(255,255,255,0.4); font-size: 0.85rem; margin-bottom: 2rem; }

        /* Skeleton loader */
        .skeleton {
            background: linear-gradient(90deg, #1a1a1a 25%, #222 50%, #1a1a1a 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 12px;
        }
        @keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
        .skeleton-card { height: 340px; border-radius: 16px; }

        /* Carte coiffeur */
        .coiffeur-card {
            background: var(--dark-2);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.3s cubic-bezier(0.25,0.8,0.25,1), border-color 0.3s;
            text-decoration: none;
            color: #fff;
            display: block;
        }
        .coiffeur-card:hover {
            transform: translateY(-6px);
            border-color: rgba(212,175,55,0.4);
            box-shadow: 0 12px 40px rgba(0,0,0,0.5);
            color: #fff;
        }
        .card-cover {
            position: relative; height: 180px; overflow: hidden;
            background: var(--dark-3);
        }
        .card-cover img { width:100%; height:100%; object-fit:cover; }
        .card-cover-placeholder {
            width:100%; height:100%;
            background: linear-gradient(135deg, #151515, #1f1f1f);
            display: flex; align-items:center; justify-content:center;
            color: rgba(255,255,255,0.1); font-size: 2.5rem;
        }
        .verified-badge {
            position: absolute; top: 10px; right: 10px;
            background: rgba(25,135,84,0.9);
            backdrop-filter: blur(8px);
            color: #fff; font-size: 0.68rem; font-weight: 700;
            padding: 3px 8px; border-radius: 20px;
            display: flex; align-items:center; gap:4px;
        }
        .card-avatar-wrap {
            position: absolute; bottom: -20px; left: 16px;
        }
        .card-avatar {
            width: 44px; height: 44px; border-radius: 50%;
            border: 3px solid var(--dark-2);
            object-fit: cover;
        }
        .card-avatar-ph {
            width: 44px; height: 44px; border-radius: 50%;
            border: 3px solid var(--dark-2);
            background: var(--gold-dim);
            display: flex; align-items:center; justify-content:center;
            font-weight: 700; color: var(--gold); font-size: 0.85rem;
        }
        .card-body {
            padding: 28px 16px 16px;
        }
        .card-name { font-weight: 700; font-size: 0.95rem; margin-bottom: 4px; }
        .card-rating {
            display: flex; align-items: center; gap: 4px;
            font-size: 0.78rem; color: rgba(255,255,255,0.55);
            margin-bottom: 8px;
        }
        .card-rating .stars { color: var(--gold); font-size: 0.72rem; }
        .card-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 10px; }
        .card-tag {
            background: rgba(212,175,55,0.1);
            border: 1px solid rgba(212,175,55,0.2);
            color: var(--gold); font-size: 0.65rem;
            padding: 2px 8px; border-radius: 20px;
        }
        .card-footer-row {
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-price { font-size: 0.82rem; color: rgba(255,255,255,0.4); }
        .card-price strong { color: var(--gold); font-size: 0.95rem; }
        .card-btn {
            background: var(--gold); color: #000;
            font-size: 0.72rem; font-weight: 700;
            padding: 6px 14px; border-radius: 20px; border: none;
            transition: background 0.2s;
        }
        .card-btn:hover { background: #c9a227; }

        /* ── SECTION POURQUOI ── */
        .section-why { padding: 4rem 0; }
        .why-card {
            background: var(--dark-2);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            text-align: center;
            transition: border-color 0.3s, transform 0.3s;
        }
        .why-card:hover { border-color: rgba(212,175,55,0.3); transform: translateY(-4px); }
        .why-icon {
            width: 56px; height: 56px; border-radius: 16px;
            background: var(--gold-dim);
            display: flex; align-items:center; justify-content:center;
            font-size: 1.4rem; color: var(--gold);
            margin: 0 auto 1.2rem;
        }
        .why-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 0.5rem; }
        .why-desc { color: rgba(255,255,255,0.4); font-size: 0.82rem; line-height: 1.6; }

        /* ── FOOTER ── */
        .cct-footer {
            background: #000;
            border-top: 1px solid rgba(212,175,55,0.2);
            padding: 3rem 0 1.5rem;
        }
        .footer-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem; font-weight: 700;
            color: var(--gold); margin-bottom: 0.5rem;
        }
        .footer-desc { color: rgba(255,255,255,0.35); font-size: 0.82rem; line-height: 1.6; }
        .footer-link {
            color: rgba(255,255,255,0.4); text-decoration: none;
            font-size: 0.82rem; display: block; margin-bottom: 6px;
            transition: color 0.2s;
        }
        .footer-link:hover { color: var(--gold); }
        .footer-social a {
            color: rgba(255,255,255,0.4); font-size: 1.2rem;
            margin-right: 12px; transition: color 0.2s;
        }
        .footer-social a:hover { color: var(--gold); }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.05);
            margin-top: 2rem; padding-top: 1rem;
            color: rgba(255,255,255,0.2); font-size: 0.75rem;
            text-align: center;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .profile-glass-card { display: none; }
            .hero { height: 340px; border-radius: 28px; }
            .hero-wrapper { padding: 0.75rem 0.75rem 0; }
            .cct-nav { padding: 0 1rem; }
        }
        @media (max-width: 480px) {
            .hero-title { font-size: 1.4rem; }
            .hero { height: 300px; border-radius: 20px; }
            .search-bar { flex-direction: column; border-radius: 16px; padding: 12px; gap: 8px; }
            .search-bar .search-btn { width: 100%; border-radius: 10px; justify-content:center;display:flex; }
        }
    </style>
</head>
<body>
<?php
// ── DONNÉES PHP (injectées par index.php via la route ?page=dashboard) ──
$prenom_client      = htmlspecialchars($_SESSION['prenom'] ?? 'Client');
$initiale           = strtoupper(substr($prenom_client, 0, 1));
$photo_profil       = $_SESSION['photo_profil'] ?? null;
$nb_rdv_termines    = 0; // Sera dynamique quand la BDD sera active

// Récupère les vraies données si PDO disponible
if (isset($pdo)) {
    try {
        // Nombre de RDV terminés du client
        $q = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE client_id = ? AND statut_rdv = 'termine'");
        $q->execute([$_SESSION['id_user'] ?? 0]);
        $nb_rdv_termines = (int) $q->fetchColumn();

        // Coiffeurs proches (logique de matching existante)
        if (empty($coiffeurs_matching ?? [])) {
            $coiffeurs_matching = [];
        }
    } catch (Exception $e) {
        $nb_rdv_termines = 0;
    }
}

// Images pour le carousel hero
$hero_images = glob(__DIR__ . '/../../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
if (empty($hero_images)) {
    $hero_images = glob(__DIR__ . '/../../images/*.{jpg,jpeg,png}', GLOB_BRACE);
}
?>

<!-- ── NAVBAR ── -->
<nav class="cct-nav">
    <!--
    ╔══════════════════════════════════════════════════════╗
    ║  EMPLACEMENT LOGO — Pour ajouter un logo :           ║
    ║  Remplace le texte "Coiffe Chez Toi" ci-dessous par  ║
    ║  <img src="/coiffons/images/logo-cct.png"            ║
    ║       alt="Coiffe Chez Toi" style="height:36px">     ║
    ╚══════════════════════════════════════════════════════╝
    -->
    <a href="/coiffons/index.php" class="cct-brand">
        <!-- LOGO ICI : remplace ce texte par <img src="..."> quand le logo est prêt -->
        Coiffe Chez Toi
    </a>
    <div class="cct-nav-actions">
        <a href="/coiffons/filter/annuaire_coiffeurs.php" class="nav-icon-btn" title="Rechercher">
            <i class="bi bi-search"></i>
        </a>
        <a href="/coiffons/client/mes_rendezvous.php" class="nav-icon-btn" title="Mes RDV">
            <i class="bi bi-calendar3"></i>
        </a>
        <a href="/coiffons/profil.php">
            <?php if ($photo_profil && file_exists(__DIR__ . '/../../' . $photo_profil)): ?>
                <img src="/coiffons/<?= htmlspecialchars($photo_profil) ?>" class="nav-avatar" alt="Profil">
            <?php else: ?>
                <div class="nav-avatar-placeholder"><?= $initiale ?></div>
            <?php endif; ?>
        </a>
    </div>
</nav>

<!-- ── HERO — Capsule flottante centrée ── -->
<div class="hero-wrapper">
    <section class="hero">
        <!-- Carousel images — confinées dans la capsule -->
        <div class="hero-carousel" id="heroCarousel">
            <?php foreach ($hero_images as $i => $img): ?>
                <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
                     style="background-image: url('/coiffons/imgid/<?= basename($img) ?>')"
                     loading="lazy"></div>
            <?php endforeach; ?>
        </div>
        <div class="hero-overlay"></div>

        <!-- Carte profil glassmorphism — en haut à droite dans la capsule -->
        <div class="profile-glass-card">
            <?php if ($photo_profil && file_exists(__DIR__ . '/../../' . $photo_profil)): ?>
                <img src="/coiffons/<?= htmlspecialchars($photo_profil) ?>" class="pc-avatar" alt="">
            <?php else: ?>
                <div class="pc-avatar-ph"><?= $initiale ?></div>
            <?php endif; ?>
            <div class="pc-info">
                <div class="pc-greeting">Bonjour 👋</div>
                <div class="pc-name"><?= $prenom_client ?></div>
                <div class="pc-stat">
                    <?php if ($nb_rdv_termines > 0): ?>
                        <?= $nb_rdv_termines ?> RDV terminé<?= $nb_rdv_termines > 1 ? 's' : '' ?>
                    <?php else: ?>
                        Bienvenue sur Coiffe Chez Toi
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Contenu centré en bas de la capsule -->
        <div class="hero-content">
            <h1 class="hero-title">Votre coiffeur,<br>directement chez vous</h1>
            <p class="hero-sub">Des professionnels certifiés dans votre quartier</p>

            <!-- Barre de recherche intégrée dans la capsule -->
            <div class="hero-search-wrap">
                <div class="search-bar">
                    <i class="bi bi-search" style="color:rgba(255,255,255,0.5);margin-right:6px;font-size:0.9rem;"></i>
                    <input type="text"
                           id="searchInput"
                           placeholder="Rechercher un coiffeur, un salon..."
                           autocomplete="off">
                    <button class="search-btn" onclick="lancerRecherche()">
                        <i class="bi bi-search me-1"></i>Rechercher
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- ── SECTION COIFFEURS PROCHES ── -->
<section class="section-coiffeurs">
    <div class="container">
        <h2 class="section-title">Coiffeurs près de vous</h2>
        <p class="section-sub">Professionnels disponibles dans votre zone</p>

        <div class="row g-4" id="coiffeurs-grid">
            <?php
            // Données réelles si disponibles, sinon skeleton/demo
            $has_real_data = !empty($coiffeurs_matching ?? []);

            if ($has_real_data):
                foreach ($coiffeurs_matching as $c):
                    $nom_coiffeur   = htmlspecialchars($c['nom_coiffeur'] ?? 'Coiffeur');
                    $initiale_c     = strtoupper(substr($nom_coiffeur, 0, 1));
                    $photo_c        = $c['photo_style'] ?? null;
                    $prix_c         = number_format($c['prix'] ?? 0, 0, ',', ' ');
                    $ville_c        = htmlspecialchars($c['ville'] ?? '');
                    $quartier_c     = htmlspecialchars($c['quartier'] ?? '');
                    $id_coiffeur_c  = $c['id_coiffeur'] ?? 0;
                    // Données futures (seront dynamiques depuis profils_prestataires)
                    $note_c         = $c['note_moyenne'] ?? null;
                    $nb_avis_c      = $c['nb_avis'] ?? 0;
                    $is_verified    = (bool)($c['is_approved'] ?? false);
            ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="/coiffons/coiffeurs/profil_public.php?id=<?= $id_coiffeur_c ?>"
                       class="coiffeur-card">
                        <div class="card-cover">
                            <?php if ($photo_c && file_exists(__DIR__ . '/../../uploads/' . $photo_c)): ?>
                                <img src="/coiffons/uploads/<?= htmlspecialchars($photo_c) ?>"
                                     loading="lazy" alt="<?= $nom_coiffeur ?>">
                            <?php else: ?>
                                <div class="card-cover-placeholder">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($is_verified): ?>
                                <span class="verified-badge">
                                    <i class="bi bi-patch-check-fill"></i> Certifié
                                </span>
                            <?php endif; ?>
                            <div class="card-avatar-wrap">
                                <div class="card-avatar-ph"><?= $initiale_c ?></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="card-name"><?= $nom_coiffeur ?></div>
                            <div class="card-rating">
                                <?php if ($note_c): ?>
                                    <span class="stars">
                                        <?= str_repeat('★', round($note_c)) ?><?= str_repeat('☆', 5 - round($note_c)) ?>
                                    </span>
                                    <?= number_format($note_c, 1) ?> (<?= $nb_avis_c ?> avis)
                                <?php else: ?>
                                    <i class="bi bi-geo-alt" style="color:var(--gold)"></i>
                                    <?= $ville_c ?><?= $quartier_c ? ' · ' . $quartier_c : '' ?>
                                <?php endif; ?>
                            </div>
                            <div class="card-tags">
                                <span class="card-tag">Coiffure</span>
                            </div>
                            <div class="card-footer-row">
                                <div class="card-price">
                                    Dès <strong><?= $prix_c ?> FCFA</strong>
                                </div>
                                <button class="card-btn">Voir les prestations</button>
                            </div>
                        </div>
                    </a>
                </div>
            <?php
                endforeach;
            else:
                // ── SKELETON CARDS (disparaissent automatiquement quand les vraies données arrivent) ──
                for ($s = 0; $s < 6; $s++):
            ?>
                <div class="col-12 col-sm-6 col-lg-4 skeleton-wrapper">
                    <div class="skeleton skeleton-card"></div>
                </div>
            <?php
                endfor;

                // Message si BDD vide mais connecté
                echo '<div class="col-12 text-center mt-4">
                    <p style="color:rgba(255,255,255,0.35);font-size:0.85rem;">
                        Aucun coiffeur disponible dans votre zone pour le moment.<br>
                        <a href="/coiffons/filter/annuaire_coiffeurs.php" style="color:var(--gold);text-decoration:none;">
                            Explorer tous les coiffeurs →
                        </a>
                    </p>
                </div>';
            endif;
            ?>
        </div>

        <?php if ($has_real_data): ?>
        <div class="text-center mt-4">
            <a href="/coiffons/filter/annuaire_coiffeurs.php"
               style="color:var(--gold);text-decoration:none;font-size:0.85rem;font-weight:600;">
                Voir tous les coiffeurs →
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ── SECTION POURQUOI COIFFE CHEZ TOI ── -->
<section class="section-why">
    <div class="container">
        <h2 class="section-title text-center mb-1">Pourquoi Coiffe Chez Toi ?</h2>
        <p class="section-sub text-center mb-4">La coiffure professionnelle réinventée</p>
        <div class="row g-4 justify-content-center">
            <div class="col-12 col-md-4">
                <div class="why-card">
                    <div class="why-icon"><i class="bi bi-house-heart"></i></div>
                    <div class="why-title">Service à domicile</div>
                    <div class="why-desc">Des coiffeurs professionnels se déplacent directement chez vous, à votre heure.</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="why-card">
                    <div class="why-icon"><i class="bi bi-patch-check"></i></div>
                    <div class="why-title">Professionnels vérifiés</div>
                    <div class="why-desc">Chaque coiffeur est vérifié et certifié par notre équipe avant d'être activé.</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="why-card">
                    <div class="why-icon"><i class="bi bi-lightning-charge"></i></div>
                    <div class="why-title">Réservation rapide</div>
                    <div class="why-desc">Choisissez, réservez et confirmez en quelques clics. Simple et sécurisé.</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── FOOTER ── -->
<footer class="cct-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="footer-brand">Coiffe Chez Toi</div>
                <p class="footer-desc">L'élégance à domicile au Bénin. Nous connectons les meilleurs talents avec ceux qui exigent l'excellence.</p>
            </div>
            <div class="col-6 col-md-2">
                <div style="color:var(--gold);font-size:0.75rem;font-weight:700;letter-spacing:1px;margin-bottom:12px;text-transform:uppercase;">Navigation</div>
                <a href="/coiffons/index.php" class="footer-link">Accueil</a>
                <a href="/coiffons/filter/annuaire_coiffeurs.php" class="footer-link">Annuaire</a>
                <a href="/coiffons/client/mes_rendezvous.php" class="footer-link">Mes RDV</a>
            </div>
            <div class="col-6 col-md-2">
                <div style="color:var(--gold);font-size:0.75rem;font-weight:700;letter-spacing:1px;margin-bottom:12px;text-transform:uppercase;">Légal</div>
                <a href="#" class="footer-link">À propos</a>
                <a href="#" class="footer-link">Confidentialité</a>
                <a href="#" class="footer-link">Conditions</a>
            </div>
            <div class="col-12 col-md-4 text-md-end">
                <div style="color:var(--gold);font-size:0.75rem;font-weight:700;letter-spacing:1px;margin-bottom:12px;text-transform:uppercase;">Contact</div>
                <p style="color:rgba(255,255,255,0.35);font-size:0.82rem;margin-bottom:12px;">contact@coiffecheztoi.bj</p>
                <div class="footer-social">
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-whatsapp"></i></a>
                    <a href="#"><i class="bi bi-facebook"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> Coiffe Chez Toi · Une branche Domizi
        </div>
    </div>
</footer>

<!-- ── JS ── -->
<script>
// Carousel hero
const slides = document.querySelectorAll('.hero-slide');
let current = 0;
if (slides.length > 1) {
    setInterval(() => {
        slides[current].classList.remove('active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('active');
    }, 5000);
}

// Recherche coiffeur
function lancerRecherche() {
    const q = document.getElementById('searchInput').value.trim();
    if (q) {
        window.location.href = '/coiffons/filter/annuaire_coiffeurs.php?q=' + encodeURIComponent(q);
    } else {
        window.location.href = '/coiffons/filter/annuaire_coiffeurs.php';
    }
}
document.getElementById('searchInput')?.addEventListener('keypress', e => {
    if (e.key === 'Enter') lancerRecherche();
});
</script>

</body>
</html>
