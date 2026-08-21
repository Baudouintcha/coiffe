<?php
/**
 * access/verify_otp.php — Gestionnaire de Vérification OTP
 * Coiffe Chez Toi — Point d'entrée unique pour vérifier les codes OTP
 *
 * USAGE:
 * Les pages d'inscription, password reset, etc. incluent ce fichier
 * pour traiter la vérification OTP.
 *
 * FLUX:
 * 1. User soumet le formulaire OTP
 * 2. Vérifier le CSRF token
 * 3. Appeler OtpService::verify()
 * 4. Retourner succès/erreur
 * 5. Page appelante gère la redirection
 *
 * RÉPONSE:
 *   - Si succès: $otp_verification = ['success' => true, 'message' => '...']
 *   - Si erreur: $otp_verification = ['success' => false, 'message' => '...']
 */

// À inclure dans une page qui traite POST avec action=verify_otp

if (!session_id()) {
    session_start();
}

// Vérifier que c'est un POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Vérifier que c'est une demande OTP
if (($_POST['action'] ?? '') !== 'verify_otp') {
    exit('Invalid action');
}

// Charger les services
require_once __DIR__ . '/../src/Services/OtpService.php';
require_once __DIR__ . '/../src/Services/EmailService.php';
require_once __DIR__ . '/../security/csrf.php';
require_once __DIR__ . '/../security/config.php';

// Vérifier le CSRF
try {
    csrf_verify();
} catch (Exception $e) {
    $otp_verification = [
        'success' => false,
        'message' => 'Erreur de sécurité (CSRF). Veuillez réessayer.',
    ];
    return; // ou redirige selon le contexte
}

// Récupérer les données
$user_id = $_SESSION['user_id'] ?? null;
$purpose = $_POST['otp_purpose'] ?? '';
$code = $_POST['otp_code'] ?? '';

// Validation basique
if (!$user_id) {
    $otp_verification = [
        'success' => false,
        'message' => 'Veuillez être connecté.',
    ];
    return;
}

if (!preg_match('/^\d{6}$/', $code)) {
    $otp_verification = [
        'success' => false,
        'message' => 'Code invalide ou expiré.',
    ];
    return;
}

// Vérifier l'OTP
$otp_service = new OtpService($pdo);
$result = $otp_service->verify((int)$user_id, $purpose, $code);

if ($result['success']) {
    // ✅ OTP valide

    // Mettre à jour le statut de vérification selon le purpose
    switch ($purpose) {
        case 'registration':
            $pdo->prepare("UPDATE users SET email_verifie = 1, otp_verified_at = NOW() WHERE id = ?")
                ->execute([$user_id]);
            $_SESSION['email_verified'] = true;
            break;

        case 'password_reset':
            // Marquer pour la réinitialisation du mot de passe
            $_SESSION['password_reset_verified'] = true;
            $_SESSION['password_reset_user_id'] = $user_id;
            break;

        case 'email_change':
            // À gérer dans le contexte de modifier_profil.php
            $_SESSION['email_change_verified'] = true;
            break;

        case 'account_deletion':
            // À gérer dans le contexte du profil
            $_SESSION['account_deletion_verified'] = true;
            break;

        case 'domizi_action':
            // À gérer selon l'action spécifique
            $_SESSION['domizi_action_verified'] = true;
            break;
    }

    $otp_verification = [
        'success' => true,
        'message' => 'Code vérifié avec succès.',
        'purpose' => $purpose,
    ];

} else {
    // ❌ OTP invalide
    $otp_verification = [
        'success' => false,
        'message' => $result['message'] ?? 'Code invalide ou expiré.',
    ];
}

// La variable $otp_verification est maintenant disponible dans la page appelante
