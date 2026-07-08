<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

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
                if (in_array($fileExt, $allowed)) {
                    $diplome_path = 'uploads/diplomes/' . uniqid('', true) . '.' . $fileExt;
                    $target = __DIR__ . '/../uploads/diplomes/';
                    if (!is_dir($target)) mkdir($target, 0777, true);
                    move_uploaded_file($_FILES['diplome']['tmp_name'], $target . basename($diplome_path));
                } else {
                    $message = "<div class='alert-msg alert-msg-danger'>Format diplôme invalide (JPG, PNG, PDF).</div>";
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
                        $message = "<div class='alert-msg alert-msg-danger'>Format photo invalide (JPG, PNG).</div>";
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
                    if ($ins->execute([$nom, $prenom, $sexe, $email, $telephone, $password, $role, $id_ville, $id_quartier, $diplome_path, $photo_profil_path])) {
                        $_SESSION['id_user']  = $pdo->lastInsertId();
                        $_SESSION['nom']      = $nom;
                        $_SESSION['prenom']   = $prenom;
                        $_SESSION['role']     = $role;
                        $_SESSION['ville']    = $id_ville;
                        $vn = $pdo->prepare("SELECT nom_ville FROM villes WHERE id = ?");
                        $vn->execute([$id_ville]);
                        $vd = $vn->fetch();
                        $_SESSION['nom_ville'] = $vd ? $vd['nom_ville'] : '';
                        header("Location: /coiffons/index.php");
                        exit();
                    } else {
                        $message = "<div class='alert-msg alert-msg-danger'>Erreur lors de l'enregistrement.</div>";
                    }
                }
            }
        }
    }
}

$villes_stmt = $pdo->query("SELECT id, nom_ville FROM villes ORDER BY nom_ville ASC");
$villes_list = $villes_stmt->fetchAll();

// Récupère la première image du slider pour le fond
$images_bg = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$bg_image  = !empty($images_bg) ? '/coiffons/imgid/' . basename($images_bg[0]) : '/coiffons/images/burst.jpg';
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
                    <select name="sexe" class="form-select" required>
                        <option value="">—</option>
                        <option value="homme">Homme</option>
                        <option value="femme">Femme</option>
                    </select>
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
        </p>
    </div>
</div>

<script>
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
</script>

</body>
</html>
