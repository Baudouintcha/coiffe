<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $hash = $user['password'] ?? $user['Password'] ?? $user['mot_de_passe'] ?? null;

            if ($hash && password_verify($password, $hash)) {
                $_SESSION['id_user'] = $user['id'];
                $_SESSION['nom']     = $user['nom'] ?? '';
                $_SESSION['prenom']  = $user['prenom'] ?? '';
                $_SESSION['role']    = $user['role'] ?? 'client';
                $_SESSION['sexe']    = $user['sexe'] ?? '';

                $id_ville = $user['ville'] ?? null;
                $_SESSION['ville'] = $id_ville;
                if ($id_ville) {
                    $vn = $pdo->prepare("SELECT nom_ville FROM villes WHERE id = ?");
                    $vn->execute([$id_ville]);
                    $vd = $vn->fetch();
                    $_SESSION['nom_ville'] = $vd ? $vd['nom_ville'] : '';
                }

                $_SESSION['date_expiration_abo'] = $user['date_expiration_abo'] ?? null;

                if ($_SESSION['role'] === 'admin') {
                    header("Location: /coiffons/first/admin_dashboard.php");
                } else {
                    header("Location: /coiffons/index.php");
                }
                exit();
            } else {
                $error = "Identifiants incorrects.";
            }
        } else {
            $error = "Identifiants incorrects.";
        }
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}

// Récupère la première image du slider pour le fond
$images_bg = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$bg_image  = !empty($images_bg) ? '/coiffons/imgid/' . basename($images_bg[0]) : '/coiffons/images/burst.jpg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Coiffe Chez Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --gold: #D4AF37; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        /* Fond figé flou */
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
            background: rgba(0, 0, 0, 0.35);
        }

        /* ── CONTENEUR ── */
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
            max-width: 440px;
            color: #fff;
        }

        .glass-card h2 {
            color: var(--gold);
            letter-spacing: 2px;
            font-size: 1.4rem;
        }

        /* ── LABELS ── */
        .glass-card label {
            color: rgba(255, 255, 255, 0.65);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        /* ── CHAMPS ── */
        .glass-card .form-control {
            background: rgba(255, 255, 255, 0.12) !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
            color: #fff !important;
            border-radius: 10px !important;
            padding: 11px 14px !important;
            backdrop-filter: blur(4px);
            transition: border-color 0.2s, background 0.2s;
        }
        .glass-card .form-control:focus {
            background: rgba(255, 255, 255, 0.20) !important;
            border-color: var(--gold) !important;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15) !important;
            color: #fff !important;
        }
        .glass-card .form-control::placeholder {
            color: rgba(255, 255, 255, 0.35) !important;
        }

        /* ── INPUT GROUP (icône + champ + toggle) ── */
        .glass-card .input-group-text {
            background: rgba(255,255,255,0.08) !important;
            border: 1px solid rgba(255,255,255,0.22) !important;
            border-right: none !important;
            color: rgba(255,255,255,0.5) !important;
            border-radius: 10px 0 0 10px !important;
        }
        .glass-card .input-group .form-control {
            border-left: none !important;
            border-radius: 0 !important;
        }
        .glass-card .input-group .btn {
            background: rgba(255,255,255,0.08) !important;
            border: 1px solid rgba(255,255,255,0.22) !important;
            border-left: none !important;
            color: rgba(255,255,255,0.5) !important;
            border-radius: 0 10px 10px 0 !important;
        }
        .glass-card .input-group .btn:hover {
            background: rgba(212,175,55,0.15) !important;
            color: var(--gold) !important;
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
            box-shadow: 0 4px 15px rgba(212,175,55,0.35);
        }

        /* ── ALERTE ERREUR ── */
        .glass-card .alert-danger {
            background: rgba(220,53,69,0.22);
            border: 1px solid rgba(220,53,69,0.35);
            color: #ffb3b3;
            border-radius: 8px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

<!-- Fond flou fixe -->
<div class="auth-bg"></div>
<div class="auth-overlay"></div>

<!-- Formulaire centré -->
<div class="auth-wrapper">
    <div class="glass-card">

        <h2 class="text-center mb-1">SE CONNECTER</h2>
        <p class="text-center mb-4" style="color:rgba(255,255,255,0.4); font-size:0.82rem;">
            Accédez à votre espace Coiffe Chez Toi
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">

            <div class="mb-3">
                <label>Adresse email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="exemple@mail.com"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           required>
                </div>
            </div>

            <div class="mb-4">
                <label>Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="pwField" class="form-control"
                           placeholder="••••••••" required>
                    <button type="button" class="btn" onclick="togglePw()">
                        <i class="bi bi-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="connexion" class="btn btn-gold w-100 py-3">
                SE CONNECTER
            </button>

        </form>

        <p class="text-center mt-3" style="font-size:0.82rem; color:rgba(255,255,255,0.4);">
            Pas encore de compte ?
            <a href="/coiffons/index.php?page=register&role=client"
               style="color:var(--gold); text-decoration:none; font-weight:600;">
                Créer un compte
            </a>
        </p>

    </div>
</div>

<script>
function togglePw() {
    const f = document.getElementById('pwField');
    const i = document.getElementById('pwIcon');
    if (f.type === 'password') { f.type = 'text'; i.className = 'bi bi-eye-slash'; }
    else { f.type = 'password'; i.className = 'bi bi-eye'; }
}
</script>

</body>
</html>
