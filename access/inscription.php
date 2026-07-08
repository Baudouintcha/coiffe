<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

<<<<<<< HEAD
// ── RÉCUPÉRATION DU RÔLE DEPUIS L'URL ──
// Le rôle est passé depuis la page d'accueil : ?role=client ou ?role=prestataire
$role_url = isset($_GET['role']) ? trim($_GET['role']) : 'client';
// Sécurité : on n'accepte que client ou prestataire
if (!in_array($role_url, ['client', 'prestataire'])) {
    $role_url = 'client';
}
// Compatibilité avec l'ancien code qui utilise 'coiffeur'
if ($role_url === 'prestataire') {
    $role_bdd = 'coiffeur'; // valeur stockée en BDD pour l'instant
} else {
    $role_bdd = 'client';
=======
$role_predefini = isset($_GET['role']) ? trim($_GET['role']) : 'client';
if (!in_array($role_predefini, ['client', 'coiffeur'])) {
    $role_predefini = 'client';
>>>>>>> endo
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (
        empty($_POST['nom']) || empty($_POST['prenom']) || empty($_POST['sexe']) ||
        empty($_POST['email']) || empty($_POST['telephone']) || empty($_POST['password']) ||
        empty($_POST['role']) || empty($_POST['ville']) || empty($_POST['id_quartier'])
    ) {
<<<<<<< HEAD
        $message = "<div class='alert alert-danger text-center'>Champs obligatoires manquants.</div>";
    } else {
        $nom     = trim($_POST['nom']);
        $prenom  = trim($_POST['prenom']);
        $sexe    = $_POST['sexe'];
        $email   = trim($_POST['email']);
        $tel     = trim($_POST['telephone']);
        $password= password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role    = $_POST['role'];
        $id_ville= intval($_POST['ville']);
        $id_quar = intval($_POST['id_quartier']);

        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $nom) || !preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $prenom)) {
            $message = "<div class='alert alert-danger text-center'>Nom et prénom : lettres uniquement.</div>";
        } elseif ($role === 'coiffeur' && (!isset($_FILES['diplome']) || $_FILES['diplome']['error'] !== 0)) {
            $message = "<div class='alert alert-danger text-center'>Le diplôme/certification est obligatoire pour les prestataires.</div>";
        } else {
            $diplome_path = NULL;
            if ($role === 'coiffeur' && isset($_FILES['diplome']) && $_FILES['diplome']['error'] === 0) {
                $allowed  = ['jpg','jpeg','png','pdf'];
                $fileExt  = strtolower(pathinfo($_FILES['diplome']['name'], PATHINFO_EXTENSION));
=======
        $message = "<div class='alert-msg alert-msg-danger'>Champs obligatoires manquants.</div>";
    } else {
        $nom         = trim($_POST['nom']);
        $prenom      = trim($_POST['prenom']);
        $sexe        = $_POST['sexe'];
        $email       = trim($_POST['email']);
        $telephone   = trim($_POST['telephone']);
        $password    = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role        = $_POST['role'];
        $id_ville    = intval($_POST['ville']);
        $id_quartier = intval($_POST['id_quartier']);

        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $nom) || !preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $prenom)) {
            $message = "<div class='alert-msg alert-msg-danger'>Nom et prénom : lettres uniquement.</div>";
        } elseif ($role == 'coiffeur' && (!isset($_FILES['diplome']) || $_FILES['diplome']['error'] !== 0)) {
            $message = "<div class='alert-msg alert-msg-danger'>Le diplôme est obligatoire pour les coiffeurs.</div>";
        } else {
            $diplome_path = NULL;
            if ($role == 'coiffeur') {
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                $fileExt = strtolower(pathinfo($_FILES['diplome']['name'], PATHINFO_EXTENSION));
>>>>>>> endo
                if (in_array($fileExt, $allowed)) {
                    $diplome_path = 'uploads/diplomes/' . uniqid('', true) . '.' . $fileExt;
                    $target = __DIR__ . '/../uploads/diplomes/';
                    if (!is_dir($target)) mkdir($target, 0777, true);
                    move_uploaded_file($_FILES['diplome']['tmp_name'], $target . basename($diplome_path));
                } else {
<<<<<<< HEAD
                    $message = "<div class='alert alert-danger text-center'>Format diplôme invalide (JPG, PNG, PDF).</div>";
=======
                    $message = "<div class='alert-msg alert-msg-danger'>Format diplôme invalide (JPG, PNG, PDF).</div>";
>>>>>>> endo
                }
            }

            if (empty($message)) {
<<<<<<< HEAD
                $photo_path = null;
                if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === 0) {
                    $allowed2  = ['jpg','jpeg','png'];
                    $ext2      = strtolower(pathinfo($_FILES['photo_profil']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext2, $allowed2)) {
                        $photo_path = 'uploads/profil/' . uniqid('', true) . '.' . $ext2;
                        $target2    = __DIR__ . '/../uploads/profil/';
                        if (!is_dir($target2)) mkdir($target2, 0777, true);
                        move_uploaded_file($_FILES['photo_profil']['tmp_name'], $target2 . basename($photo_path));
                    } else {
                        $message = "<div class='alert alert-danger text-center'>Format photo invalide (JPG, PNG).</div>";
=======
                $photo_profil_path = null;
                if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === 0) {
                    $allowedPhoto = ['jpg', 'jpeg', 'png'];
                    $photoExt = strtolower(pathinfo($_FILES['photo_profil']['name'], PATHINFO_EXTENSION));
                    if (in_array($photoExt, $allowedPhoto)) {
                        $photo_profil_path = 'uploads/profil/' . uniqid('', true) . '.' . $photoExt;
                        $target2 = __DIR__ . '/../uploads/profil/';
                        if (!is_dir($target2)) mkdir($target2, 0777, true);
                        move_uploaded_file($_FILES['photo_profil']['tmp_name'], $target2 . basename($photo_profil_path));
                    } else {
                        $message = "<div class='alert-msg alert-msg-danger'>Format photo invalide (JPG, PNG).</div>";
>>>>>>> endo
                    }
                }
            }

            if (empty($message)) {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                if ($check->rowCount() > 0) {
                    $message = "<div class='alert-msg alert-msg-danger'>Cet email est déjà utilisé.</div>";
                } else {
                    $ins = $pdo->prepare("INSERT INTO users (nom, prenom, sexe, email, telephone, password, role, ville, id_quartier, diplome, photo_profil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
<<<<<<< HEAD
                    if ($ins->execute([$nom, $prenom, $sexe, $email, $tel, $password, $role, $id_ville, $id_quar, $diplome_path, $photo_path])) {
                        $id_user = $pdo->lastInsertId();
                        $_SESSION['id_user'] = $id_user;
                        $_SESSION['nom']     = $nom;
                        $_SESSION['prenom']  = $prenom;
                        $_SESSION['role']    = $role;
                        $_SESSION['ville']   = $id_ville;

=======
                    if ($ins->execute([$nom, $prenom, $sexe, $email, $telephone, $password, $role, $id_ville, $id_quartier, $diplome_path, $photo_profil_path])) {
                        $_SESSION['id_user']  = $pdo->lastInsertId();
                        $_SESSION['nom']      = $nom;
                        $_SESSION['prenom']   = $prenom;
                        $_SESSION['role']     = $role;
                        $_SESSION['ville']    = $id_ville;
>>>>>>> endo
                        $vn = $pdo->prepare("SELECT nom_ville FROM villes WHERE id = ?");
                        $vn->execute([$id_ville]);
                        $vd = $vn->fetch();
                        $_SESSION['nom_ville'] = $vd ? $vd['nom_ville'] : '';
<<<<<<< HEAD

                        header("Location: /coiffons/a__index.php");
                        exit();
                    } else {
                        $message = "<div class='alert alert-danger text-center'>Erreur d'enregistrement.</div>";
=======
                        header("Location: /coiffons/index.php");
                        exit();
                    } else {
                        $message = "<div class='alert-msg alert-msg-danger'>Erreur lors de l'enregistrement.</div>";
>>>>>>> endo
                    }
                }
            }
        }
    }
}

<<<<<<< HEAD
// Chargement des villes pour le dropdown
$villes = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC")->fetchAll();
=======
$villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
$villes_list = $villes_stmt->fetchAll();

// Récupère la première image du slider pour le fond
$images_bg = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$bg_image  = !empty($images_bg) ? '/coiffons/imgid/' . basename($images_bg[0]) : '/coiffons/images/burst.jpg';
>>>>>>> endo
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Coiffe Chez Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<<<<<<< HEAD
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #D4AF37; --gold-dim: rgba(212,175,55,0.15); }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #0a0a0a;
            color: #fff;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            overflow: hidden;
        }

        /* ── CAROUSEL BACKGROUND ── */
        .bg-carousel {
            position: fixed;
            inset: 0;
            z-index: 0;
        }
        .bg-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.2s ease;
        }
        .bg-slide.active { opacity: 1; }
        .bg-slide.blurred { filter: blur(18px) brightness(0.25); transition: filter 0.8s ease, opacity 1.2s ease; }

        /* ── OVERLAY GRADIENT ── */
        .bg-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: linear-gradient(135deg, rgba(10,10,10,0.85) 0%, rgba(10,10,10,0.6) 100%);
            opacity: 0;
            transition: opacity 0.8s ease;
        }
        .bg-overlay.visible { opacity: 1; }

        /* ── HEADER ── */
        .page-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gold);
            text-decoration: none;
        }
        .back-btn {
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .back-btn:hover { color: var(--gold); }

        /* ── HERO SECTION (visible au départ) ── */
        #hero-section {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 100px 2rem 4rem;
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        #hero-section.hidden {
            opacity: 0;
            transform: translateY(-30px);
            pointer-events: none;
        }

        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--gold-dim);
            border: 1px solid rgba(212,175,55,0.3);
            border-radius: 30px;
            padding: 6px 18px;
            font-size: 0.72rem;
            color: var(--gold);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 2rem;
        }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 6vw, 4rem);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1rem;
        }
        .hero-sub {
            color: rgba(255,255,255,0.45);
            font-size: 1rem;
            margin-bottom: 3rem;
            font-weight: 300;
        }

        /* ── BOUTON PRINCIPAL D'INSCRIPTION ── */
        .btn-open-form {
            background: var(--gold);
            color: #000;
            font-weight: 700;
            padding: 14px 40px;
            border-radius: 50px;
            border: none;
            font-size: 0.9rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }
        .btn-open-form::after {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255,255,255,0.15);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .btn-open-form:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212,175,55,0.4);
        }
        .btn-open-form:hover::after { opacity: 1; }

        .login-link {
            margin-top: 1.5rem;
            color: rgba(255,255,255,0.35);
            font-size: 0.82rem;
        }
        .login-link a { color: var(--gold); text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

        /* ── FORMULAIRE (caché puis déplié) ── */
        #form-section {
            position: fixed;
            inset: 0;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 1rem 2rem;
            opacity: 0;
            transform: scale(0.92) translateY(30px);
            pointer-events: none;
            transition: opacity 0.5s cubic-bezier(0.25, 0.8, 0.25, 1),
                        transform 0.5s cubic-bezier(0.25, 0.8, 0.25, 1);
            overflow-y: auto;
        }
        #form-section.open {
            opacity: 1;
            transform: scale(1) translateY(0);
            pointer-events: all;
        }

        .form-card {
            background: rgba(15, 15, 15, 0.92);
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 560px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
        }
        .form-sub {
            color: rgba(255,255,255,0.35);
            font-size: 0.8rem;
            margin-bottom: 2rem;
        }

        .form-control, .form-select {
            background: rgba(255,255,255,0.06) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: #fff !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            font-size: 0.88rem !important;
            transition: border-color 0.2s !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--gold) !important;
            background: rgba(255,255,255,0.08) !important;
            box-shadow: none !important;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.25) !important; }
        .form-select option { background: #111; color: #fff; }

        .form-label {
            color: rgba(255,255,255,0.5);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        /* Champ diplôme — affiché seulement pour prestataire */
        #diplome-group { display: <?= $role_bdd === 'coiffeur' ? 'block' : 'none' ?>; }

        .btn-submit {
            background: var(--gold);
            color: #000;
            font-weight: 700;
            width: 100%;
            padding: 13px;
            border-radius: 10px;
            border: none;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.25s;
            margin-top: 0.5rem;
        }
        .btn-submit:hover {
            background: #c9a227;
            transform: translateY(-1px);
        }

        .close-form-btn {
            position: absolute;
            top: 16px;
            right: 20px;
            background: none;
            border: none;
            color: rgba(255,255,255,0.35);
            font-size: 1.4rem;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
        }
        .close-form-btn:hover { color: #fff; }

        /* Scroll dans la modale */
        @media (max-height: 700px) {
            #form-section { align-items: flex-start; padding-top: 60px; }
        }
    </style>
</head>
<body>

<!-- ── CAROUSEL BACKGROUND ── -->
<div class="bg-carousel" id="bgCarousel">
    <div class="bg-slide active" style="background-image: url('/coiffons/images/burst.jpg')"></div>
    <div class="bg-slide" style="background-image: url('/coiffons/images/dame.jpg')"></div>
    <div class="bg-slide" style="background-image: url('/coiffons/images/mid_fade.jpg')"></div>
    <div class="bg-slide" style="background-image: url('/coiffons/images/coupe.jpg')"></div>
</div>
<div class="bg-overlay" id="bgOverlay"></div>

<!-- ── HEADER ── -->
<div class="page-header">
    <a href="/coiffons/a__index.php" class="brand">CCT</a>
    <a href="/coiffons/a__index.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<!-- ── SECTION HERO (avant ouverture du formulaire) ── -->
<div id="hero-section">
    <div class="role-badge">
        <i class="bi bi-<?= $role_bdd === 'coiffeur' ? 'briefcase' : 'person' ?>"></i>
        <?= $role_bdd === 'coiffeur' ? 'Espace Prestataire' : 'Espace Client' ?>
    </div>

    <h1 class="hero-title">
        <?= $role_bdd === 'coiffeur'
            ? 'Rejoignez notre réseau<br>de professionnels'
            : 'Votre salon de luxe<br>à domicile'
        ?>
    </h1>
    <p class="hero-sub">
        <?= $role_bdd === 'coiffeur'
            ? 'Créez votre profil, gérez votre agenda et recevez des clients'
            : 'Réservez les meilleurs coiffeurs de votre quartier'
        ?>
    </p>

    <button class="btn-open-form" onclick="ouvrirFormulaire()">
        CRÉER MON COMPTE
    </button>

    <p class="login-link">
        Déjà inscrit ? <a href="/coiffons/access/connexion.php">Se connecter</a>
    </p>
</div>

<!-- ── FORMULAIRE D'INSCRIPTION ── -->
<div id="form-section">
    <div class="form-card position-relative">
        <button class="close-form-btn" onclick="fermerFormulaire()">
            <i class="bi bi-x"></i>
        </button>

        <h2 class="form-title">Créer mon compte</h2>
        <p class="form-sub">
            <?= $role_bdd === 'coiffeur' ? 'Compte Prestataire' : 'Compte Client' ?>
            · Coiffe Chez Toi
        </p>

        <?php if (!empty($message)) echo $message; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role_bdd) ?>">

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label">Nom</label>
                    <input type="text" name="nom" class="form-control" placeholder="Dossou" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Prénom</label>
                    <input type="text" name="prenom" class="form-control" placeholder="Jean" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Sexe</label>
=======
    <style>
        :root { --gold: #D4AF37; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { min-height: 100vh; font-family: 'Segoe UI', sans-serif; }

        /* Fond figé flou — même image que home.php slider */
        .auth-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background-image: url('<?= $bg_image ?>');
            background-size: cover;
            background-position: center;
            filter: blur(20px) brightness(0.5);
            transform: scale(1.1);
        }
        .auth-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: rgba(0,0,0,0.3);
        }

        /* Wrapper centré */
        .auth-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            overflow-y: auto;
        }

        /* Carte glassmorphism */
        .glass-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.5);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 620px;
            color: #fff;
        }
        .glass-card h2 {
            color: var(--gold);
            font-size: 1.4rem;
            letter-spacing: 2px;
        }

        /* Labels */
        .glass-card label {
            display: block;
            color: rgba(255,255,255,0.65);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        /* Champs semi-transparents */
        .glass-card .form-control,
        .glass-card .form-select {
            background: rgba(255,255,255,0.10) !important;
            border: 1px solid rgba(255,255,255,0.20) !important;
            color: #fff !important;
            border-radius: 10px !important;
        }
        .glass-card .form-control:focus,
        .glass-card .form-select:focus {
            background: rgba(255,255,255,0.18) !important;
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 3px rgba(212,175,55,0.15) !important;
            color: #fff !important;
            outline: none;
        }
        .glass-card .form-control::placeholder { color: rgba(255,255,255,0.30) !important; }
        .glass-card .form-select option { background: #1a1a1a; color: #fff; }
        .glass-card .form-control[type="file"] { color: rgba(255,255,255,0.6) !important; }

        /* Toggle password */
        .glass-card .btn-pw {
            background: rgba(255,255,255,0.10) !important;
            border: 1px solid rgba(255,255,255,0.20) !important;
            border-left: none !important;
            color: rgba(255,255,255,0.5) !important;
            border-radius: 0 10px 10px 0 !important;
        }
        .glass-card .btn-pw:hover { background: rgba(212,175,55,0.15) !important; color: var(--gold) !important; }
        .glass-card .input-group .form-control { border-right: none !important; border-radius: 10px 0 0 10px !important; }

        /* Bouton submit */
        .btn-gold {
            background: var(--gold);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 12px;
            transition: all 0.25s;
            width: 100%;
        }
        .btn-gold:hover { background: #c9a227; transform: translateY(-1px); }

        /* Messages d'erreur */
        .alert-msg {
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            text-align: center;
        }
        .alert-msg-danger {
            background: rgba(220,53,69,0.20);
            border: 1px solid rgba(220,53,69,0.35);
            color: #ffb3b3;
        }

        small { color: rgba(255,255,255,0.30); font-size: 0.70rem; }
    </style>
</head>
<body>

<div class="auth-bg"></div>
<div class="auth-overlay"></div>

<div class="auth-wrapper">
    <div class="glass-card">

        <h2 class="text-center mb-1">REJOINDRE L'AVENTURE</h2>
        <p class="text-center mb-4" style="color:rgba(255,255,255,0.40);font-size:0.8rem;">
            Compte
            <strong style="color:var(--gold)"><?= $role_predefini === 'coiffeur' ? 'Coiffeur / Prestataire' : 'Client' ?></strong>
            &nbsp;&middot;&nbsp;
            <a href="/coiffons/index.php?page=register&role=<?= $role_predefini === 'coiffeur' ? 'client' : 'coiffeur' ?>"
               style="color:rgba(255,255,255,0.40);text-decoration:none;">
                Je suis <?= $role_predefini === 'coiffeur' ? 'un client' : 'un coiffeur' ?> &rarr;
            </a>
        </p>

        <?= $message ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role_predefini) ?>">

            <div class="row g-3 mb-2">
                <div class="col-4">
                    <label>Nom</label>
                    <input type="text" name="nom" class="form-control" placeholder="Dossou" required>
                </div>
                <div class="col-4">
                    <label>Prénom</label>
                    <input type="text" name="prenom" class="form-control" placeholder="Jean" required>
                </div>
                <div class="col-4">
                    <label>Sexe</label>
>>>>>>> endo
                    <select name="sexe" class="form-select" required>
                        <option value="">—</option>
                        <option value="homme">Homme</option>
                        <option value="femme">Femme</option>
                    </select>
<<<<<<< HEAD
                </div>
                <div class="col-6">
                    <label class="form-label">Téléphone (WhatsApp)</label>
                    <input type="text" name="telephone" class="form-control" placeholder="+229..." required>
                </div>
                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="exemple@mail.com" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Ville</label>
                    <select name="ville" id="villeSelect" class="form-select" required>
                        <option value="">Sélectionnez</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nom_ville']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Quartier</label>
                    <select name="id_quartier" id="quartierSelect" class="form-select" required disabled>
                        <option value="">Choisir la ville d'abord</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">
                        Photo de profil <span style="color:rgba(255,255,255,0.25);">(optionnel)</span>
                    </label>
                    <input type="file" name="photo_profil" class="form-control" accept="image/*">
                </div>

                <!-- Diplôme : visible uniquement pour les prestataires -->
                <div class="col-12" id="diplome-group">
                    <label class="form-label">
                        Diplôme / Certification <span style="color:#e74c3c;">*</span>
                    </label>
                    <input type="file" name="diplome" class="form-control" accept="image/*,application/pdf"
                           <?= $role_bdd === 'coiffeur' ? 'required' : '' ?>>
                    <small style="color:rgba(255,255,255,0.25);font-size:0.72rem;">
                        JPG, PNG ou PDF — sera vérifié par notre équipe
                    </small>
                </div>

                <div class="col-12">
                    <label class="form-label">Mot de passe</label>
                    <div style="position:relative;">
                        <input type="password" name="password" id="pwField" class="form-control"
                               placeholder="••••••••" required style="padding-right: 44px;">
                        <button type="button" onclick="togglePw()"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,0.4);cursor:pointer;">
                            <i class="bi bi-eye" id="pwIcon"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit mt-3">CRÉER MON COMPTE</button>
        </form>

        <p style="text-align:center;margin-top:1.2rem;color:rgba(255,255,255,0.3);font-size:0.78rem;">
            Déjà inscrit ? <a href="/coiffons/access/connexion.php" style="color:var(--gold);text-decoration:none;">Se connecter</a>
=======
                </div>
            </div>

            <div class="row g-3 mb-2">
                <div class="col-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="exemple@mail.com" required>
                </div>
                <div class="col-6">
                    <label>Téléphone</label>
                    <input type="text" name="telephone" class="form-control" placeholder="+229..." required>
                </div>
            </div>

            <div class="row g-3 mb-2">
                <div class="col-6">
                    <label>Ville</label>
                    <select name="ville" id="villeSelect" class="form-select" required>
                        <option value="">Sélectionnez</option>
                        <?php foreach ($villes_list as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nom_ville']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label>Quartier</label>
                    <select name="id_quartier" id="quartierSelect" class="form-select" required disabled>
                        <option value="">Choisissez la ville d'abord</option>
                    </select>
                </div>
            </div>

            <div class="mb-2">
                <label>Photo de profil <small>(Optionnel)</small></label>
                <input type="file" name="photo_profil" class="form-control" accept="image/*">
            </div>

            <?php if ($role_predefini === 'coiffeur'): ?>
            <div class="mb-2">
                <label>Diplôme / Certification <span style="color:#ff6b6b;">*</span></label>
                <input type="file" name="diplome" class="form-control" accept="image/*,application/pdf" required>
                <small>JPG, PNG ou PDF — vérifié par notre équipe avant activation</small>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label>Mot de passe</label>
                <div class="input-group">
                    <input type="password" name="password" id="pwField" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="btn-pw btn" onclick="togglePw()">
                        <i class="bi bi-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-gold">CRÉER MON COMPTE</button>
        </form>

        <p class="text-center mt-3" style="font-size:0.80rem;color:rgba(255,255,255,0.35);">
            Déjà inscrit ?
            <a href="/coiffons/access/connexion.php"
               style="color:var(--gold);text-decoration:none;font-weight:700;">Se connecter</a>
>>>>>>> endo
        </p>
    </div>
</div>

<script>
<<<<<<< HEAD
// ── CAROUSEL BACKGROUND ──
const slides = document.querySelectorAll('.bg-slide');
let current  = 0;
let interval = null;

function nextSlide() {
    slides[current].classList.remove('active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('active');
}
interval = setInterval(nextSlide, 4000);

// ── OUVERTURE DU FORMULAIRE ──
function ouvrirFormulaire() {
    // Stop le carousel
    clearInterval(interval);

    // Fige et blur le fond
    slides.forEach(s => s.classList.add('blurred'));
    document.getElementById('bgOverlay').classList.add('visible');

    // Cache le hero
    document.getElementById('hero-section').classList.add('hidden');

    // Affiche le formulaire avec animation dépliage
    setTimeout(() => {
        document.getElementById('form-section').classList.add('open');
    }, 150);
}

function fermerFormulaire() {
    document.getElementById('form-section').classList.remove('open');
    document.getElementById('hero-section').classList.remove('hidden');
    slides.forEach(s => s.classList.remove('blurred'));
    document.getElementById('bgOverlay').classList.remove('visible');

    // Reprend le carousel
    interval = setInterval(nextSlide, 4000);
}

// ── SI ERREUR PHP → ouvre directement le formulaire ──
<?php if (!empty($message)): ?>
document.addEventListener('DOMContentLoaded', ouvrirFormulaire);
<?php endif; ?>

// ── TOGGLE MOT DE PASSE ──
function togglePw() {
    const f = document.getElementById('pwField');
    const i = document.getElementById('pwIcon');
    if (f.type === 'password') {
        f.type = 'text';
        i.className = 'bi bi-eye-slash';
    } else {
        f.type = 'password';
        i.className = 'bi bi-eye';
    }
}

// ── CHARGEMENT AJAX DES QUARTIERS ──
document.getElementById('villeSelect').addEventListener('change', function() {
    const idVille = this.value;
    const qs = document.getElementById('quartierSelect');
    if (!idVille) {
        qs.innerHTML = '<option value="">Choisir la ville d\'abord</option>';
        qs.disabled = true;
        return;
    }
    fetch('/coiffons/access/get_quartiers.php?id_ville=' + idVille)
        .then(r => r.json())
        .then(data => {
            qs.innerHTML = '<option value="0">Centre-ville / Autre</option>';
            qs.disabled = false;
            data.forEach(q => {
                const opt = document.createElement('option');
                opt.value = q.id;
                opt.textContent = q.nom_quartier;
                qs.appendChild(opt);
            });
        })
        .catch(() => {
            qs.innerHTML = '<option value="0">Centre-ville</option>';
            qs.disabled = false;
        });
});
=======
document.getElementById('villeSelect').addEventListener('change', function() {
    const idVille = this.value;
    const qs = document.getElementById('quartierSelect');
    if (!idVille) {
        qs.innerHTML = '<option value="">Choisissez la ville d\'abord</option>';
        qs.disabled = true;
        return;
    }
    fetch('/coiffons/access/get_quartiers.php?id_ville=' + idVille)
        .then(r => r.json())
        .then(data => {
            qs.innerHTML = '<option value="0">Centre-ville / Autre</option>';
            qs.disabled = false;
            data.forEach(q => {
                const o = document.createElement('option');
                o.value = q.id;
                o.textContent = q.nom_quartier;
                qs.appendChild(o);
            });
        })
        .catch(() => { qs.innerHTML = '<option value="0">Centre-ville</option>'; qs.disabled = false; });
});

function togglePw() {
    const f = document.getElementById('pwField');
    const i = document.getElementById('pwIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
>>>>>>> endo
</script>

</body>
</html>
