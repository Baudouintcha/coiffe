<?php
/**
 * access/connexion.php — Page de connexion
 * Migration Design System v2.0 — Parcours Visiteur — Page 2/6
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — Seuls HTML/CSS/composants ont été modifiés.
 *     Aucune requête SQL, variable PHP, session ou sécurité touchée.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/csrf.php';

$error = "";

// Show password reset success message
$success_message = '';
if (isset($_SESSION['password_reset_success']) && $_SESSION['password_reset_success']) {
    $success_message = "Votre mot de passe a été réinitialisé avec succès. Connectez-vous avec votre nouveau mot de passe.";
    unset($_SESSION['password_reset_success']);
}

// ── PROTECTION BRUTE FORCE ── (inchangé)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_last_attempt'] = 0;
}
$now = time();
$blocked = false;
if ($_SESSION['login_attempts'] >= 5 && ($now - $_SESSION['login_last_attempt']) < 900) {
    $blocked = true;
    $error = "Trop de tentatives. Réessayez dans " . ceil((900 - ($now - $_SESSION['login_last_attempt'])) / 60) . " minutes.";
}
if (($now - $_SESSION['login_last_attempt']) >= 900) {
    $_SESSION['login_attempts'] = 0;
}

if (!$blocked && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {

    // ── VÉRIFICATION CSRF ── (inchangé)
    csrf_verify();

    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
        $error = "Email ou mot de passe invalide.";
    } else {
        $stmt = $pdo->prepare("SELECT id, nom, prenom, role, sexe, photo_profil, password, statut FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && isset($user['statut']) && $user['statut'] === 'banni') {
            $error = "Ce compte a été suspendu.";
        } elseif ($user && password_verify($password, $user['password'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['id_user']        = $user['id'];
            $_SESSION['nom']            = $user['nom'];
            $_SESSION['prenom']         = $user['prenom'];
            $_SESSION['role']           = $user['role'];
            $_SESSION['sexe']           = $user['sexe'];
            $_SESSION['photo_profil']   = $user['photo_profil'] ?? null;

            if ($user['role'] === 'admin') {
                header("Location: /coiffons/first/admin_dashboard.php");
            } elseif ($user['role'] === 'client') {
                header("Location: /coiffons/index.php?page=dashboard");
            } elseif ($user['role'] === 'coiffeur') {
                header("Location: /coiffons/index.php?page=dashboard_coiffeur");
            } else {
                header("Location: /coiffons/index.php");
            }
            exit();
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['login_last_attempt'] = $now;
            $error = "Identifiants incorrects. Tentative " . $_SESSION['login_attempts'] . "/5.";
        }
    }
}

// Image de fond — logique PHP inchangée
$images_bg = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$bg_image  = !empty($images_bg) ? '/coiffons/imgid/' . basename($images_bg[0]) : '/coiffons/images/burst.jpg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Coiffe Chez Toi</title>

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
</head>
<body>

<!-- ══════════════════════════════════════════
     AUTH CARD — Composant CL §34
     Fond flou + overlay + carte glassmorphisme
     ══════════════════════════════════════════ -->

<!-- Fond flou fixe — image du slider (logique PHP inchangée) -->
<div class="auth-bg"
     style="background-image:url('<?= htmlspecialchars($bg_image) ?>');"
     aria-hidden="true"></div>
<div class="auth-overlay" aria-hidden="true"></div>

<!-- Wrapper centré -->
<main class="auth-wrapper" id="main-content">
    <div class="auth-card auth-card--sm fade-in-card" role="main">

        <!-- Lien retour accueil -->
        <a href="/coiffons/index.php"
           class="btn-ghost btn-sm d-inline-flex align-items-center gap-1 mb-4"
           aria-label="Retour à l'accueil">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Accueil
        </a>

        <!-- Titre -->
        <h1 style="font-family:var(--font-display);color:var(--gold);letter-spacing:2px;font-size:1.4rem;text-align:center;margin-bottom:6px;">
            SE CONNECTER
        </h1>
        <p style="text-align:center;color:var(--text-muted);font-size:.82rem;margin-bottom:1.75rem;">
            Accédez à votre espace Coiffe Chez Toi
        </p>

        <!-- Message d'erreur — composant .msg-danger DS §11 -->
        <?php if (!empty($error)): ?>
            <div class="msg-danger mb-4" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Message de succès -->
        <?php if (!empty($success_message)): ?>
            <div class="msg-success mb-4" role="alert" aria-live="polite">
                <i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire — logique PHP inchangée, HTML migré DS -->
        <form action="" method="POST" novalidate>
            <?= csrf_field() ?>

            <!-- Champ email — FormField CL §33 -->
            <div class="mb-3">
                <label for="email" class="form-label-cct">
                    Adresse email
                </label>
                <div class="input-group-cct">
                    <span class="ig-prefix" aria-hidden="true">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email"
                           id="email"
                           name="email"
                           class="fc-dark"
                           placeholder="exemple@mail.com"
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                           autocomplete="email"
                           required
                           aria-required="true">
                </div>
            </div>

            <!-- Champ mot de passe — FormField avec toggle DS §11 -->
            <div class="mb-4">
                <label for="pwField" class="form-label-cct">
                    Mot de passe
                </label>
                <div class="input-group-cct">
                    <span class="ig-prefix" aria-hidden="true">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password"
                           id="pwField"
                           name="password"
                           class="fc-dark"
                           placeholder="••••••••"
                           autocomplete="current-password"
                           required
                           aria-required="true">
                    <button type="button"
                            class="ig-suffix"
                            id="pwToggle"
                            onclick="togglePw()"
                            aria-label="Afficher ou masquer le mot de passe"
                            aria-pressed="false">
                        <i class="bi bi-eye" id="pwIcon" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Bouton submit — PrimaryButton CL §12 -->
            <button type="submit"
                    name="connexion"
                    class="btn-gold w-100"
                    style="padding:13px;font-size:.95rem;">
                SE CONNECTER
            </button>

        </form>

        <!-- Lien mot de passe oublié -->
        <p style="text-align:center;margin-top:1rem;font-size:.75rem;color:var(--text-muted);">
            <a href="/coiffons/access/password_reset.php"
               style="color:var(--text-disabled);text-decoration:none;">
                Mot de passe oublié ?
            </a>
        </p>

        <!-- Lien inscription -->
        <p style="text-align:center;margin-top:.6rem;font-size:.82rem;color:var(--text-muted);">
            Pas encore de compte ?
            <a href="/coiffons/index.php?page=register&role=client"
               style="color:var(--gold);text-decoration:none;font-weight:600;">
                Créer un compte
            </a>
        </p>

        <!-- Séparateur + lien coiffeur -->
        <p style="text-align:center;margin-top:.6rem;font-size:.75rem;color:var(--text-disabled);">
            Vous êtes coiffeur ?
            <a href="/coiffons/index.php?page=register&role=coiffeur"
               style="color:var(--text-muted);text-decoration:none;">
                Rejoindre en tant que coiffeur →
            </a>
        </p>

    </div>
</main>

<!-- Toggle mot de passe — JS UI uniquement, aucune logique métier -->
<script>
function togglePw() {
    const field  = document.getElementById('pwField');
    const icon   = document.getElementById('pwIcon');
    const button = document.getElementById('pwToggle');
    const isHidden = field.type === 'password';
    field.type          = isHidden ? 'text' : 'password';
    icon.className      = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    button.setAttribute('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
}
</script>

</body>
</html>
