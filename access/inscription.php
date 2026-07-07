<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

// ── Rôle pré-défini depuis l'URL (/index.php?page=register&role=client)
// On n'affiche plus le choix de rôle dans le formulaire
$role_predefini = isset($_GET['role']) ? trim($_GET['role']) : 'client';
if (!in_array($role_predefini, ['client', 'coiffeur'])) {
    $role_predefini = 'client';
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (
        empty($_POST['nom']) || empty($_POST['prenom']) || empty($_POST['sexe']) ||
        empty($_POST['email']) || empty($_POST['telephone']) || empty($_POST['password']) ||
        empty($_POST['role']) || empty($_POST['ville']) || empty($_POST['id_quartier'])
    ) {
        $message = "<div class='alert alert-danger text-center'>Champs obligatoires manquants. Veuillez remplir tout le formulaire.</div>";
    } else {
        $nom      = trim($_POST['nom']);
        $prenom   = trim($_POST['prenom']);
        $sexe     = $_POST['sexe'];
        $email    = trim($_POST['email']);
        $telephone= trim($_POST['telephone']);
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $role     = $_POST['role'];
        $id_ville = intval($_POST['ville']);
        $id_quartier = intval($_POST['id_quartier']);

        if (!preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $nom) || !preg_match("/^[a-zA-ZÀ-ÿ\s\-]+$/u", $prenom)) {
            $message = "<div class='alert alert-danger text-center'>Nom et prénom : lettres uniquement.</div>";
        } elseif ($role == 'coiffeur' && (!isset($_FILES['diplome']) || $_FILES['diplome']['error'] !== 0)) {
            $message = "<div class='alert alert-danger text-center'>Le diplôme est obligatoire pour les coiffeurs.</div>";
        } else {
            $diplome_path = NULL;
            if ($role == 'coiffeur') {
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
                $fileExt = strtolower(pathinfo($_FILES['diplome']['name'], PATHINFO_EXTENSION));
                if (in_array($fileExt, $allowed)) {
                    $diplome_path = 'uploads/diplomes/' . uniqid('', true) . '.' . $fileExt;
                    $target = __DIR__ . '/../uploads/diplomes/';
                    if (!is_dir($target)) mkdir($target, 0777, true);
                    move_uploaded_file($_FILES['diplome']['tmp_name'], $target . basename($diplome_path));
                } else {
                    $message = "<div class='alert alert-danger text-center'>Format diplôme invalide (JPG, PNG, PDF).</div>";
                }
            }

            if (empty($message)) {
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
                        $message = "<div class='alert alert-danger text-center'>Format photo invalide (JPG, PNG).</div>";
                    }
                }
            }

            if (empty($message)) {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $check->execute([$email]);
                if ($check->rowCount() > 0) {
                    $message = "<div class='alert alert-danger text-center'>Cet email est déjà utilisé.</div>";
                } else {
                    $ins = $pdo->prepare("INSERT INTO users (nom, prenom, sexe, email, telephone, password, role, ville, id_quartier, diplome, photo_profil) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($ins->execute([$nom, $prenom, $sexe, $email, $telephone, $password, $role, $id_ville, $id_quartier, $diplome_path, $photo_profil_path])) {
                        $id_nouvel_user = $pdo->lastInsertId();

                        // Session après inscription — utilise $nom et $prenom (pas $nom_clean)
                        $_SESSION['id_user'] = $id_nouvel_user;
                        $_SESSION['nom']     = $nom;
                        $_SESSION['prenom']  = $prenom;
                        $_SESSION['role']    = $role;
                        $_SESSION['ville']   = $id_ville;

                        $vn = $pdo->prepare("SELECT nom_ville FROM villes WHERE id = ?");
                        $vn->execute([$id_ville]);
                        $vd = $vn->fetch();
                        $_SESSION['nom_ville'] = $vd ? $vd['nom_ville'] : '';

                        header("Location: /coiffons/index.php");
                        exit();
                    } else {
                        $message = "<div class='alert alert-danger text-center'>Une erreur est survenue lors de l'enregistrement.</div>";
                    }
                }
            }
        }
    }
}

// Chargement des villes
$villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
$villes_list = $villes_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Coiffe Chez Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --gold: #D4AF37; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }

        /* ── FOND FIGÉ ET FLOU (même image que home.php) ── */
        .auth-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            /* On prend la première image du slider comme fond fixe */
            background-image: url('/coiffons/imgid/pexels-cottonbro-3993134.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            /* Flou prononcé style YouTube */
            filter: blur(18px) brightness(0.55) saturate(0.8);
            /* Légèrement agrandi pour éviter les bords blancs du blur */
            transform: scale(1.08);
        }

        /* Overlay sombre léger pour améliorer la lisibilité */
        .auth-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            background: rgba(0, 0, 0, 0.35);
        }

        /* ── CONTENEUR CENTRÉ ── */
        .auth-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* ── CARTE GLASSMORPHISM ── */
        .glass-card {
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 20px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.40),
                        0 2px 8px rgba(0, 0, 0, 0.20);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 620px;
            color: #fff;
        }

        .glass-card h2 {
            color: var(--gold);
            letter-spacing: 2px;
            font-size: 1.4rem;
            margin-bottom: 0.25rem;
        }

        /* ── LABELS ── */
        .glass-card label {
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        /* ── CHAMPS DE SAISIE — Semi-transparents, texte clair ── */
        .glass-card .form-control,
        .glass-card .form-select {
            background: rgba(255, 255, 255, 0.12) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            color: #fff !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            backdrop-filter: blur(4px);
            transition: border-color 0.2s, background 0.2s;
        }
        .glass-card .form-control:focus,
        .glass-card .form-select:focus {
            background: rgba(255, 255, 255, 0.20) !important;
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15) !important;
            color: #fff !important;
        }
        .glass-card .form-control::placeholder {
            color: rgba(255, 255, 255, 0.40) !important;
        }
        /* Options du select */
        .glass-card .form-select option {
            background: #1a1a1a;
            color: #fff;
        }

        /* ── BOUTON INPUT FILE ── */
        .glass-card .form-control[type="file"] {
            color: rgba(255,255,255,0.7) !important;
        }

        /* ── BOUTON TOGGLE MOT DE PASSE ── */
        .glass-card .btn-outline-warning {
            border-color: rgba(212,175,55,0.5) !important;
            color: var(--gold) !important;
            background: rgba(212,175,55,0.08) !important;
        }
        .glass-card .btn-outline-warning:hover {
            background: rgba(212,175,55,0.2) !important;
        }

        /* ── BOUTON PRINCIPAL ── */
        .btn-gold {
            background: var(--gold);
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 12px;
            letter-spacing: 0.5px;
            transition: all 0.25s;
        }
        .btn-gold:hover {
            background: #c9a227;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(212,175,55,0.3);
        }

        /* ── LIEN RETOUR ── */
        .back-link {
            position: fixed;
            top: 20px;
            left: 24px;
            z-index: 20;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--gold); }

        /* ── ALERTE ERREUR ADAPTÉE ── */
        .glass-card .alert-danger {
            background: rgba(220,53,69,0.25);
            border: 1px solid rgba(220,53,69,0.4);
            color: #ffb3b3;
            border-radius: 8px;
        }

        /* ── SCROLLBAR ── */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 10px; }
    </style>
</head>
<body>

<!-- Fond flou fixe -->
<div class="auth-bg"></div>
<div class="auth-overlay"></div>

<!-- Lien retour -->
<a href="/coiffons/index.php" class="back-link">
    <i class="bi bi-arrow-left"></i> Coiffe Chez Toi
</a>

<!-- Formulaire centré -->
<div class="auth-wrapper">
    <div class="glass-card">

        <h2 class="text-center">REJOINDRE L'AVENTURE</h2>
        <p class="text-center mb-4" style="color:rgba(255,255,255,0.45); font-size:0.82rem;">
            Compte <strong style="color:var(--gold)"><?= $role_predefini === 'coiffeur' ? 'Coiffeur / Prestataire' : 'Client' ?></strong>
            &nbsp;·&nbsp;
            <a href="/coiffons/index.php?page=register&role=<?= $role_predefini === 'coiffeur' ? 'client' : 'coiffeur' ?>"
               style="color:rgba(255,255,255,0.45); text-decoration:none; font-size:0.8rem;">
                Je suis <?= $role_predefini === 'coiffeur' ? 'un client' : 'un coiffeur' ?> →
            </a>
        </p>

        <?php echo $message; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role_predefini) ?>">

            <div class="row g-3 mb-2">
                <div class="col-md-4">
                    <label>Nom</label>
                    <input type="text" name="nom" class="form-control" placeholder="Dossou" required>
                </div>
                <div class="col-md-4">
                    <label>Prénom</label>
                    <input type="text" name="prenom" class="form-control" placeholder="Jean" required>
                </div>
                <div class="col-md-4">
                    <label>Sexe</label>
                    <select name="sexe" class="form-select" required>
                        <option value="">—</option>
                        <option value="homme">Homme</option>
                        <option value="femme">Femme</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="exemple@mail.com" required>
                </div>
                <div class="col-md-6">
                    <label>Téléphone (WhatsApp)</label>
                    <input type="text" name="telephone" class="form-control" placeholder="+229..." required>
                </div>
            </div>

            <div class="row g-3 mb-2">
                <div class="col-md-6">
                    <label>Ville</label>
                    <select name="ville" id="villeSelect" class="form-select" required>
                        <option value="">Sélectionnez votre ville</option>
                        <?php foreach ($villes_list as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['nom_ville']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Quartier</label>
                    <select name="id_quartier" id="quartierSelect" class="form-select" required disabled>
                        <option value="">Choisissez d'abord une ville</option>
                    </select>
                </div>
            </div>

            <div class="mb-2">
                <label>Photo de profil <span style="color:rgba(255,255,255,0.35); font-size:0.75rem;">(Optionnel)</span></label>
                <input type="file" name="photo_profil" class="form-control" accept="image/*">
            </div>

            <?php if ($role_predefini === 'coiffeur'): ?>
            <div class="mb-2">
                <label>Diplôme / Certification <span style="color:#ff6b6b;">*</span></label>
                <input type="file" name="diplome" class="form-control" accept="image/*,application/pdf" required>
                <small style="color:rgba(255,255,255,0.35); font-size:0.72rem;">JPG, PNG ou PDF — vérifié par notre équipe</small>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <label>Mot de passe</label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordField" class="form-control" placeholder="••••••••" required>
                    <button class="btn btn-outline-warning" type="button" onclick="togglePw()">
                        <i class="bi bi-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-gold w-100 py-3 mt-1">CRÉER MON COMPTE</button>
        </form>

        <p class="text-center mt-3" style="font-size:0.82rem; color:rgba(255,255,255,0.4);">
            Déjà inscrit ?
            <a href="/coiffons/access/connexion.php" style="color:var(--gold); text-decoration:none; font-weight:600;">Se connecter</a>
        </p>
    </div>
</div>

<script>
document.getElementById('villeSelect').addEventListener('change', function() {
    const idVille = this.value;
    const qs = document.getElementById('quartierSelect');
    if (!idVille) {
        qs.innerHTML = '<option value="">Choisissez d\'abord une ville</option>';
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
    const f = document.getElementById('passwordField');
    const i = document.getElementById('pwIcon');
    if (f.type === 'password') { f.type = 'text'; i.className = 'bi bi-eye-slash'; }
    else { f.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>

</body>
</html>