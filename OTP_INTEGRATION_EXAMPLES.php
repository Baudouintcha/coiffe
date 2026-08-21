<?php
/**
 * OTP_INTEGRATION_EXAMPLES.php — Exemples d'Intégration
 * Coiffe Chez Toi — Code prêt à copier-coller dans vos pages
 *
 * Fichier de RÉFÉRENCE — À adapter selon votre contexte
 * NE PAS inclure ce fichier en production
 */

// ═══════════════════════════════════════════════════════════════════════════
// EXEMPLE 1: INSCRIPTION AVEC OTP (access/inscription.php)
// ═══════════════════════════════════════════════════════════════════════════

/*

// ⬇️ AJOUTER EN HAUT DU FICHIER:

require_once __DIR__ . '/../src/Services/OtpService.php';
require_once __DIR__ . '/../src/Services/EmailService.php';

// ⬇️ VARIABLE GLOBALE pour suivre l'étape:
$otp_step = isset($_SESSION['otp_registration_step']) ? $_SESSION['otp_registration_step'] : 'form';

// ⬇️ AJOUTER DANS LA SECTION POST:

// ... après validation et création du compte réussie ...

if (isset($ins) && $ins->rowCount() > 0) {
    $user_id = $pdo->lastInsertId();
    
    // ✅ ÉTAPE 1: Générer et envoyer l'OTP
    try {
        $otp_service = new OtpService($pdo);
        $otp_result = $otp_service->generate($user_id, 'registration');
        
        if ($otp_result['success']) {
            $email_service = new EmailService();
            $email_result = $email_service->sendOtpCode(
                $user_email,
                $otp_result['code'],
                5,              // 5 minutes
                'registration'
            );
            
            if ($email_result['success']) {
                // ✅ OTP généré et envoyé
                $_SESSION['otp_registration_step'] = 'verify';
                $_SESSION['otp_user_id'] = $user_id;
                $_SESSION['otp_email'] = $user_email;
                
                // Compte créé mais non encore activé
                $_SESSION['user_id']  = $user_id;
                $_SESSION['nom']      = $nom;
                $_SESSION['prenom']   = $prenom;
                $_SESSION['role']     = $role;
                
                // ✅ Le composant OTP s'affichera ci-dessous
                $show_otp_form = true;
            } else {
                $message = "msg-danger::Erreur lors de l'envoi du code. " . $email_result['message'];
            }
        } else {
            $message = "msg-danger::Erreur lors de la génération du code.";
        }
    } catch (Exception $e) {
        $message = "msg-danger::Erreur de sécurité. " . $e->getMessage();
    }
}

// ⬇️ AJOUTER DANS LA SECTION HTML (après le formulaire d'inscription):

<?php if ($otp_step === 'verify' && !empty($show_otp_form)): ?>
    
    <!-- COMPOSANT OTP DE VÉRIFICATION -->
    <?php
        $otp_purpose = 'registration';
        $user_email = $_SESSION['otp_email'] ?? 'user@example.com';
        $csrf_token = $_SESSION['_csrf_token'] ?? '';
        $error_message = '';
        include __DIR__ . '/../views/components/otp_verification.php';
    ?>
    
    <!-- TRAITEMENT DE LA VÉRIFICATION OTP -->
    <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_otp') {
            include __DIR__ . '/../access/verify_otp.php';
            
            if ($otp_verification['success']) {
                // ✅ OTP vérifié !
                try {
                    // Activer le compte
                    $pdo->prepare("UPDATE users SET email_verifie = 1, otp_verified_at = NOW() WHERE id = ?")
                        ->execute([$_SESSION['otp_user_id']]);
                    
                    // Marquer comme vérifié
                    $_SESSION['email_verified'] = true;
                    unset($_SESSION['otp_registration_step']);
                    unset($_SESSION['otp_user_id']);
                    
                    // Rediriger vers dashboard
                    header("Cache-Control: no-store, no-cache, must-revalidate");
                    header("Pragma: no-cache");
                    
                    if ($role === 'client') {
                        header("Location: /coiffons/index.php?page=dashboard");
                    } elseif ($role === 'prestataire') {
                        $_SESSION['nouveau_coiffeur'] = true;
                        header("Location: /coiffons/coiffeurs/bienvenue.php");
                    }
                    exit();
                    
                } catch (Exception $e) {
                    $error_message = "Erreur lors de l'activation du compte.";
                }
            } else {
                // ❌ OTP invalide
                $error_message = $otp_verification['message'];
            }
        }
    ?>
    
<?php endif; ?>

*/

// ═══════════════════════════════════════════════════════════════════════════
// EXEMPLE 2: MOT DE PASSE OUBLIÉ (access/password_reset.php - NOUVEAU FICHIER)
// ═══════════════════════════════════════════════════════════════════════════

/*

<?php
// access/password_reset.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../src/Services/OtpService.php';
require_once __DIR__ . '/../src/Services/EmailService.php';

$message = '';
$step = $_SESSION['password_reset_step'] ?? 'email';

// ─────────────────────────────────────────────────────────────────────────
// ÉTAPE 1: Demander l'email
// ─────────────────────────────────────────────────────────────────────────

if ($step === 'email' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $message = "msg-danger::Email invalide.";
    } else {
        $user = $pdo->prepare("SELECT id FROM users WHERE email = ?")
            ->execute([$email])
            ->fetch();
        
        if (!$user) {
            // Ne pas révéler si l'email existe (sécurité)
            $message = "msg-success::Si cet email existe, un code sera envoyé.";
            $_SESSION['password_reset_step'] = 'email';
        } else {
            // ✅ Email trouvé — Générer OTP
            try {
                $otp_service = new OtpService($pdo);
                $otp_result = $otp_service->generate($user['id'], 'password_reset');
                
                if ($otp_result['success']) {
                    $email_service = new EmailService();
                    $email_service->sendOtpCode(
                        $email,
                        $otp_result['code'],
                        5,
                        'password_reset'
                    );
                    
                    $_SESSION['password_reset_step'] = 'otp';
                    $_SESSION['password_reset_user_id'] = $user['id'];
                    $_SESSION['password_reset_email'] = $email;
                    
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                }
            } catch (Exception $e) {
                $message = "msg-danger::Erreur lors de la génération du code.";
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────
// ÉTAPE 2: Vérifier l'OTP
// ─────────────────────────────────────────────────────────────────────────

if ($step === 'otp') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_otp') {
        include __DIR__ . '/verify_otp.php';
        
        if ($otp_verification['success']) {
            // ✅ OTP vérifié
            $_SESSION['password_reset_step'] = 'newpassword';
            $_SESSION['password_reset_verified'] = true;
            
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $message = "msg-danger::" . $otp_verification['message'];
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────
// ÉTAPE 3: Nouveau mot de passe
// ─────────────────────────────────────────────────────────────────────────

if ($step === 'newpassword' && !($_SESSION['password_reset_verified'] ?? false)) {
    header("Location: /coiffons/index.php?page=password_reset");
    exit();
}

if ($step === 'newpassword' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (strlen($password) < 6) {
        $message = "msg-danger::Le mot de passe doit faire au moins 6 caractères.";
    } elseif ($password !== $password_confirm) {
        $message = "msg-danger::Les mots de passe ne correspondent pas.";
    } else {
        try {
            $user_id = $_SESSION['password_reset_user_id'] ?? null;
            
            if (!$user_id) {
                throw new Exception('Erreur de session');
            }
            
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
                ->execute([password_hash($password, PASSWORD_DEFAULT), $user_id]);
            
            // Nettoyer la session
            unset($_SESSION['password_reset_step']);
            unset($_SESSION['password_reset_user_id']);
            unset($_SESSION['password_reset_verified']);
            unset($_SESSION['password_reset_email']);
            
            $message = "msg-success::Mot de passe réinitialisé avec succès !";
            
            // Rediriger vers connexion
            header("Refresh: 2; url=/coiffons/index.php?page=connexion");
            
        } catch (Exception $e) {
            $message = "msg-danger::Erreur lors de la mise à jour du mot de passe.";
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────
// HTML (affichage selon l'étape)
// ─────────────────────────────────────────────────────────────────────────
?>

<!DOCTYPE html>
<html>
<head>
    <title>Réinitialiser le mot de passe</title>
</head>
<body>

<?php if ($step === 'email'): ?>
    
    <form method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
        
        <h2>Réinitialiser votre mot de passe</h2>
        <p>Entrez votre adresse email:</p>
        
        <input type="email" name="email" required>
        <button type="submit">Envoyer le code</button>
    </form>
    
<?php elseif ($step === 'otp'): ?>
    
    <?php
        $otp_purpose = 'password_reset';
        $user_email = $_SESSION['password_reset_email'] ?? '';
        include __DIR__ . '/../views/components/otp_verification.php';
    ?>
    
<?php elseif ($step === 'newpassword'): ?>
    
    <form method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
        
        <h2>Nouveau mot de passe</h2>
        
        <input type="password" name="password" placeholder="Nouveau mot de passe" required>
        <input type="password" name="password_confirm" placeholder="Confirmer" required>
        <button type="submit">Réinitialiser</button>
    </form>
    
<?php endif; ?>

</body>
</html>

*/

// ═══════════════════════════════════════════════════════════════════════════
// EXEMPLE 3: SUPPRESSION DE COMPTE (profil.php)
// ═══════════════════════════════════════════════════════════════════════════

/*

// Dans profil.php, section "Supprimer mon compte":

<?php
    $deletion_step = $_SESSION['deletion_step'] ?? 'confirm';
?>

<?php if ($deletion_step === 'confirm'): ?>
    
    <form method="POST">
        <p>Attention: Cette action est définitive et irréversible.</p>
        <button type="submit" name="action" value="request_deletion">
            Je veux supprimer mon compte
        </button>
    </form>
    
<?php elseif ($deletion_step === 'otp'): ?>
    
    <!-- Composant OTP -->
    <?php
        $otp_purpose = 'account_deletion';
        $user_email = $_SESSION['user_email'] ?? $user['email'];
        include __DIR__ . '/views/components/otp_verification.php';
    ?>
    
    <!-- Traiter vérification -->
    <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify_otp') {
            include __DIR__ . '/access/verify_otp.php';
            
            if ($otp_verification['success']) {
                $_SESSION['deletion_step'] = 'final_confirm';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
    ?>
    
<?php elseif ($deletion_step === 'final_confirm'): ?>
    
    <form method="POST">
        <p><strong>⚠️ Dernière confirmation</strong></p>
        <p>Êtes-vous absolument sûr ? Cette action est irréversible.</p>
        <button type="submit" name="action" value="confirm_deletion">
            OUI, supprimer définitivement mon compte
        </button>
        <button type="submit" name="action" value="cancel">
            Annuler
        </button>
    </form>
    
<?php endif; ?>

<?php
    // Traiter les actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'request_deletion') {
            try {
                $otp_service = new OtpService($pdo);
                $result = $otp_service->generate($user_id, 'account_deletion');
                
                if ($result['success']) {
                    $email_service = new EmailService();
                    $email_service->sendOtpCode(
                        $user['email'],
                        $result['code'],
                        5,
                        'account_deletion'
                    );
                    
                    $_SESSION['deletion_step'] = 'otp';
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                }
            } catch (Exception $e) {
                echo "Erreur: " . $e->getMessage();
            }
        }
        
        elseif ($action === 'confirm_deletion' && ($_SESSION['deletion_step'] ?? '') === 'final_confirm') {
            try {
                // Enregistrer avant suppression
                $pdo->prepare(
                    "INSERT INTO suppressions_comptes (user_id_supprime, nom, prenom, email, role, supprime_par, date_demande)
                     VALUES (?, ?, ?, ?, ?, NULL, NOW())"
                )->execute([$user_id, $user['nom'], $user['prenom'], $user['email'], $user['role']]);
                
                // Supprimer l'utilisateur
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
                
                // Nettoyer la session
                session_destroy();
                
                // Rediriger
                header("Location: /coiffons/index.php?deleted=true");
                exit();
                
            } catch (Exception $e) {
                echo "Erreur lors de la suppression.";
            }
        }
        
        elseif ($action === 'cancel') {
            unset($_SESSION['deletion_step']);
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
?>

*/

// ═══════════════════════════════════════════════════════════════════════════
// EXEMPLE 4: CHANGEMENT EMAIL (modifier_profil.php)
// ═══════════════════════════════════════════════════════════════════════════

/*

// Dans modifier_profil.php, section "Email":

<?php
    $email_step = $_SESSION['email_change_step'] ?? 'form';
?>

<?php if ($email_step === 'form'): ?>
    
    <form method="POST">
        <input type="hidden" name="_csrf_token" value="<?= $_SESSION['_csrf_token'] ?>">
        
        <p>Email actuel: <?= htmlspecialchars($user['email']) ?></p>
        <input type="email" name="new_email" placeholder="Nouvel email" required>
        <button type="submit" name="action" value="request_email_change">
            Changer mon email
        </button>
    </form>
    
<?php elseif ($email_step === 'otp'): ?>
    
    <!-- Composant OTP envoyé au NOUVEL email -->
    <?php
        $otp_purpose = 'email_change';
        $user_email = $_SESSION['email_change_new'] ?? '';
        include __DIR__ . '/views/components/otp_verification.php';
    ?>
    
<?php endif; ?>

<?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'request_email_change') {
            $new_email = filter_var(trim($_POST['new_email']), FILTER_VALIDATE_EMAIL);
            
            if (!$new_email) {
                echo "Email invalide";
            } elseif ($new_email === $user['email']) {
                echo "C'est le même email";
            } else {
                // Vérifier que le nouvel email n'existe pas
                $check = $pdo->prepare("SELECT id FROM users WHERE email = ?")->execute([$new_email]);
                
                if ($check->rowCount() > 0) {
                    echo "Cet email est déjà utilisé";
                } else {
                    try {
                        $otp_service = new OtpService($pdo);
                        $result = $otp_service->generate($user_id, 'email_change');
                        
                        if ($result['success']) {
                            $email_service = new EmailService();
                            
                            // Envoyer à la NEW adresse !
                            $email_service->sendOtpCode(
                                $new_email,
                                $result['code'],
                                5,
                                'email_change'
                            );
                            
                            $_SESSION['email_change_step'] = 'otp';
                            $_SESSION['email_change_new'] = $new_email;
                            $_SESSION['email_change_user_id'] = $user_id;
                            
                            header("Location: " . $_SERVER['REQUEST_URI']);
                            exit();
                        }
                    } catch (Exception $e) {
                        echo "Erreur: " . $e->getMessage();
                    }
                }
            }
        }
        
        elseif ($action === 'verify_otp' && $email_step === 'otp') {
            include __DIR__ . '/access/verify_otp.php';
            
            if ($otp_verification['success']) {
                try {
                    $new_email = $_SESSION['email_change_new'];
                    $user_id = $_SESSION['email_change_user_id'];
                    
                    // Mettre à jour l'email
                    $pdo->prepare("UPDATE users SET email = ?, email_verifie = 1 WHERE id = ?")
                        ->execute([$new_email, $user_id]);
                    
                    // Nettoyer
                    unset($_SESSION['email_change_step']);
                    unset($_SESSION['email_change_new']);
                    
                    echo "Email changé avec succès !";
                    
                } catch (Exception $e) {
                    echo "Erreur lors de la mise à jour.";
                }
            }
        }
    }
?>

*/

?>
