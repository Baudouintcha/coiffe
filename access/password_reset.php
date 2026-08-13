<?php
/**
 * access/password_reset.php — Réinitialisation du mot de passe
 * Phase 2: OTP Centralized System Integration
 * 
 * FLUX:
 * 1. User entre email → validé en BDD
 * 2. OTP généré et envoyé par email
 * 3. User entre le code OTP (6 chiffres)
 * 4. Code vérifié → affiche formulaire nouveau mot de passe
 * 5. Nouveau mot de passe enregistré → redirection login
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../src/Services/OtpService.php';
require_once __DIR__ . '/../src/Services/EmailService.php';

$step = 1; // Step 1: Email, Step 2: OTP, Step 3: New Password
$message = '';
$error_message = '';
$success_message = '';

// ═══ HANDLE FORM SUBMISSIONS ═══

// STEP 1: Email submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === '1') {
    csrf_verify();
    
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Adresse email invalide.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error_message = "Aucun compte trouvé avec cet email.";
        } else {
            $user_id = $user['id'];
            
            // Generate OTP
            $otp_service = new OtpService($pdo);
            $otp_result = $otp_service->generate($user_id, 'password_reset');
            
            if ($otp_result['success']) {
                // Send OTP via email
                $email_service = new EmailService();
                $email_send_result = $email_service->sendOtpCode($email, $otp_result['code'], 5, 'password_reset');
                
                if ($email_send_result['success']) {
                    $_SESSION['password_reset_user_id'] = $user_id;
                    $_SESSION['password_reset_email'] = $email;
                    $_SESSION['password_reset_step'] = 2;
                    header("Location: /coiffons/access/password_reset.php");
                    exit();
                } else {
                    $error_message = "Erreur lors de l'envoi du code: " . htmlspecialchars($email_send_result['message']);
                }
            } else {
                $error_message = "Erreur lors de la génération du code de vérification.";
            }
        }
    }
}

// STEP 2: OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    csrf_verify();
    
    $user_id = $_SESSION['password_reset_user_id'] ?? null;
    $code = $_POST['otp_code'] ?? '';
    
    if (!$user_id) {
        $error_message = "Session invalide. Veuillez recommencer.";
    } elseif (!preg_match('/^\d{6}$/', $code)) {
        $error_message = "Code invalide ou expiré.";
    } else {
        $otp_service = new OtpService($pdo);
        $result = $otp_service->verify($user_id, 'password_reset', $code);
        
        if ($result['success']) {
            $_SESSION['password_reset_verified'] = true;
            $_SESSION['password_reset_step'] = 3;
            header("Location: /coiffons/access/password_reset.php");
            exit();
        } else {
            $error_message = $result['message'] ?? "Code invalide ou expiré.";
        }
    }
}

// STEP 3: New password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === '3') {
    csrf_verify();
    
    $user_id = $_SESSION['password_reset_user_id'] ?? null;
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (!$user_id || !isset($_SESSION['password_reset_verified'])) {
        $error_message = "Session invalide. Veuillez recommencer.";
    } elseif (strlen($new_password) < 8) {
        $error_message = "Le mot de passe doit faire au moins 8 caractères.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } else {
        // Update password in database
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$password_hash, $user_id]);
        
        // Clear session
        unset($_SESSION['password_reset_user_id']);
        unset($_SESSION['password_reset_email']);
        unset($_SESSION['password_reset_verified']);
        unset($_SESSION['password_reset_step']);
        
        $_SESSION['password_reset_success'] = true;
        header("Location: /coiffons/access/connexion.php");
        exit();
    }
}

// Determine current step
$current_step = $_SESSION['password_reset_step'] ?? 1;
if ($current_step < 1) $current_step = 1;
if ($current_step > 3) $current_step = 1;

// Image de fond
$images_bg = glob(__DIR__ . '/../imgid/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
$bg_image = !empty($images_bg) ? '/coiffons/imgid/' . basename($images_bg[0]) : '/coiffons/images/burst.jpg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe — Coiffe Chez Toi</title>

    <!-- Design System v2.0 -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Background -->
<div class="auth-bg" style="background-image:url('<?= htmlspecialchars($bg_image) ?>');" aria-hidden="true"></div>
<div class="auth-overlay" aria-hidden="true"></div>

<!-- Main Card -->
<main class="auth-wrapper" id="main-content">
    <div class="auth-card auth-card--sm fade-in-card" role="main">

        <!-- Back link -->
        <a href="/coiffons/access/connexion.php" class="btn-ghost btn-sm d-inline-flex align-items-center gap-1 mb-4" aria-label="Retour à la connexion">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Connexion
        </a>

        <!-- Title -->
        <h1 style="font-family:var(--font-display);color:var(--gold);letter-spacing:2px;font-size:1.4rem;text-align:center;margin-bottom:6px;">
            RÉINITIALISER MOT DE PASSE
        </h1>
        <p style="text-align:center;color:var(--text-muted);font-size:.82rem;margin-bottom:1.75rem;">
            <?php if ($current_step === 1): ?>
                Entrez votre adresse email
            <?php elseif ($current_step === 2): ?>
                Entrez le code reçu par email
            <?php else: ?>
                Définissez un nouveau mot de passe
            <?php endif; ?>
        </p>

        <!-- Error message -->
        <?php if (!empty($error_message)): ?>
            <div class="msg-danger mb-4" role="alert" aria-live="polite">
                <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- ═══ STEP 1: EMAIL ═══ -->
        <?php if ($current_step === 1): ?>
            <form method="POST" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="step" value="1">

                <div class="mb-4">
                    <label for="email" class="form-label-cct">Adresse email</label>
                    <div class="input-group-cct">
                        <span class="ig-prefix" aria-hidden="true">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email" id="email" name="email" class="fc-dark"
                               placeholder="exemple@mail.com" required aria-required="true"
                               autocomplete="email">
                    </div>
                </div>

                <button type="submit" class="btn-gold w-100" style="padding:13px;font-size:.95rem;">
                    ENVOYER UN CODE
                </button>
            </form>

        <!-- ═══ STEP 2: OTP VERIFICATION ═══ -->
        <?php elseif ($current_step === 2): ?>
            <?php
                $otp_purpose = 'password_reset';
                $user_email = $_SESSION['password_reset_email'] ?? '';
                $email_parts = explode('@', $user_email);
                $masked_email = substr($email_parts[0], 0, 1) . str_repeat('*', max(0, strlen($email_parts[0]) - 2)) . '@' . $email_parts[1];
                
                $submit_action = 'verify_otp';
                $csrf_token = $_SESSION['_csrf_token'] ?? '';
                $show_resend = true;
                
                include __DIR__ . '/../views/components/otp_verification.php';
            ?>
            
            <!-- Resend OTP form (hidden) -->
            <form method="POST" id="resend-form" style="display:none;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="resend_otp">
                <input type="hidden" name="otp_purpose" value="password_reset">
            </form>

        <!-- ═══ STEP 3: NEW PASSWORD ═══ -->
        <?php elseif ($current_step === 3): ?>
            <form method="POST" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="step" value="3">

                <div class="mb-3">
                    <label for="new_password" class="form-label-cct">Nouveau mot de passe</label>
                    <div class="input-group-cct">
                        <span class="ig-prefix" aria-hidden="true">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" id="new_password" name="new_password" class="fc-dark"
                               placeholder="Min. 8 caractères" minlength="8" required aria-required="true"
                               autocomplete="new-password">
                        <button type="button" class="ig-suffix" onclick="togglePassword('new_password', this)"
                                aria-label="Afficher/masquer mot de passe">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label-cct">Confirmer le mot de passe</label>
                    <div class="input-group-cct">
                        <span class="ig-prefix" aria-hidden="true">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" class="fc-dark"
                               placeholder="Confirmez votre mot de passe" minlength="8" required aria-required="true"
                               autocomplete="new-password">
                        <button type="button" class="ig-suffix" onclick="togglePassword('confirm_password', this)"
                                aria-label="Afficher/masquer mot de passe">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-gold w-100" style="padding:13px;font-size:.95rem;">
                    RÉINITIALISER MOT DE PASSE
                </button>
            </form>

        <?php endif; ?>

        <!-- Help link -->
        <p style="text-align:center;margin-top:1.25rem;font-size:.82rem;color:var(--text-muted);">
            Vous vous souvenez votre mot de passe ?
            <a href="/coiffons/access/connexion.php" style="color:var(--gold);text-decoration:none;font-weight:600;">
                Se connecter
            </a>
        </p>

    </div>
</main>

<script>
function togglePassword(fieldId, button) {
    const field = document.getElementById(fieldId);
    const icon = button.querySelector('i');
    const isHidden = field.type === 'password';
    field.type = isHidden ? 'text' : 'password';
    icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
}
</script>

</body>
</html>
