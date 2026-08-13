<?php
/**
 * src/Services/EmailService.php — Service d'Envoi d'Email
 * Coiffe Chez Toi — Gestion centralisée des emails
 *
 * Utilise PHPMailer pour:
 * - Support SMTP/sendmail/mail()
 * - Robustesse et gestion d'erreurs
 * - Logging sécurisé
 *
 * CONFIGURATION (.env):
 *   MAIL_DRIVER=mail               # mail, smtp, sendmail
 *   MAIL_FROM_ADDRESS=noreply@coiffeschez-toi.local
 *   MAIL_FROM_NAME=Coiffe Chez Toi
 *   SMTP_HOST=localhost
 *   SMTP_PORT=25
 *   SMTP_USERNAME=
 *   SMTP_PASSWORD=
 *   SMTP_ENCRYPTION=none            # none, tls, ssl
 *
 * USAGE:
 *   $email = new EmailService();
 *   $result = $email->sendOtpCode('user@example.com', '123456', 5);
 *   if ($result['success']) { ... }
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mailer;
    private string $from_address;
    private string $from_name;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->from_address = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@coiffeschez-toi.local';
        $this->from_name = $_ENV['MAIL_FROM_NAME'] ?? 'Coiffe Chez Toi';

        $this->configureMailer();
    }

    // ═══════════════════════════════════════════════════════
    // CONFIGURATION DU MAILER
    // ═══════════════════════════════════════════════════════

    private function configureMailer(): void
    {
        try {
            $driver = $_ENV['MAIL_DRIVER'] ?? 'mail';

            if ($driver === 'smtp') {
                $this->mailer->isSMTP();
                $this->mailer->Host       = $_ENV['SMTP_HOST'] ?? 'localhost';
                $this->mailer->Port       = (int)($_ENV['SMTP_PORT'] ?? 25);
                $this->mailer->SMTPAuth   = !empty($_ENV['SMTP_USERNAME']);
                $this->mailer->Username   = $_ENV['SMTP_USERNAME'] ?? '';
                $this->mailer->Password   = $_ENV['SMTP_PASSWORD'] ?? '';
                $this->mailer->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_NONE;
            } elseif ($driver === 'sendmail') {
                $this->mailer->isSendmail();
            } else {
                // mail() — driver par défaut
                $this->mailer->isMail();
            }

            // Configuration commune
            $this->mailer->setFrom($this->from_address, $this->from_name);
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);

            // Logs (production: ERROR uniquement)
            if ($_ENV['APP_ENV'] === 'production') {
                $this->mailer->SMTPDebug = 0; // No debug in production
            } else {
                $this->mailer->SMTPDebug = 1; // INFO level
            }

        } catch (Exception $e) {
            error_log("EmailService configuration error: " . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════
    // ENVOI D'OTP PAR EMAIL
    // ═══════════════════════════════════════════════════════

    /**
     * Envoie un code OTP à l'utilisateur.
     *
     * @param string $to_email    Adresse email du destinataire
     * @param string $otp_code    Code OTP (6 chiffres)
     * @param int    $validity    Validité en minutes
     * @param string $purpose     Type d'OTP (registration, password_reset, etc.)
     * @return array ['success' => bool, 'message' => string]
     */
    public function sendOtpCode(string $to_email, string $otp_code, int $validity = 5, string $purpose = 'registration'): array
    {
        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Adresse email invalide.',
            ];
        }

        if (!preg_match('/^\d{6}$/', $otp_code)) {
            return [
                'success' => false,
                'message' => 'Code OTP invalide.',
            ];
        }

        try {
            // Masquer l'adresse email partiellement pour la confidentialité
            $masked_email = $this->maskEmail($to_email);

            // Déterminer le sujet et le contenu selon le purpose
            $subject_text = match($purpose) {
                'registration' => 'Vérification de votre compte Coiffe Chez Toi',
                'password_reset' => 'Réinitialisation de votre mot de passe',
                'email_change' => 'Vérification de votre nouvelle adresse email',
                'account_deletion' => 'Confirmation de suppression de votre compte',
                'domizi_action' => 'Confirmation d\'action sensible',
                default => 'Code de vérification Coiffe Chez Toi',
            };

            $body_intro = match($purpose) {
                'registration' => 'Bienvenue sur Coiffe Chez Toi ! Pour activer votre compte, veuillez entrer le code ci-dessous:',
                'password_reset' => 'Vous avez demandé une réinitialisation de votre mot de passe. Entrez le code ci-dessous pour continuer:',
                'email_change' => 'Vous avez demandé de changer votre adresse email. Entrez le code ci-dessous pour confirmer:',
                'account_deletion' => '⚠️ Vous avez demandé la suppression de votre compte. Entrez le code ci-dessous pour confirmer cette action définitive:',
                'domizi_action' => 'Une action sensible requiert votre confirmation. Entrez le code ci-dessous:',
                default => 'Entrez le code ci-dessous:',
            };

            // Construire l'email HTML
            $html = $this->buildOtpEmailHtml(
                $otp_code,
                $validity,
                $body_intro,
                $masked_email
            );

            // Configurer le destinataire
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to_email);

            // Configurer le message
            $this->mailer->Subject = $subject_text;
            $this->mailer->Body = $html;

            // Alternative texte
            $this->mailer->AltBody = "Votre code de vérification:\n\n$otp_code\n\nCe code expire dans {$validity} minutes.";

            // Envoyer
            if ($this->mailer->send()) {
                error_log("OTP email sent to {$to_email} for purpose: {$purpose}");
                return [
                    'success' => true,
                    'message' => 'Code d\'envoyé par email.',
                ];
            } else {
                error_log("Failed to send OTP email to {$to_email}: " . $this->mailer->ErrorInfo);
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi du code.',
                ];
            }

        } catch (Exception $e) {
            error_log("EmailService::sendOtpCode exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code.',
            ];
        }
    }

    // ═══════════════════════════════════════════════════════
    // ENVOI D'EMAIL DE NOTIFICATION
    // ═══════════════════════════════════════════════════════

    /**
     * Envoie un email de notification générique.
     */
    public function sendNotification(string $to_email, string $subject, string $message, string $action_text = '', string $action_url = ''): array
    {
        if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Adresse email invalide.',
            ];
        }

        try {
            $html = $this->buildNotificationEmailHtml($message, $action_text, $action_url);

            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to_email);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $html;
            $this->mailer->AltBody = strip_tags($message);

            if ($this->mailer->send()) {
                error_log("Notification email sent to {$to_email}");
                return [
                    'success' => true,
                    'message' => 'Email envoyé.',
                ];
            } else {
                error_log("Failed to send notification email to {$to_email}: " . $this->mailer->ErrorInfo);
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi de l\'email.',
                ];
            }

        } catch (Exception $e) {
            error_log("EmailService::sendNotification exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email.',
            ];
        }
    }

    // ═══════════════════════════════════════════════════════
    // TEMPLATES HTML
    // ═══════════════════════════════════════════════════════

    /**
     * Construit le template HTML pour l'email OTP.
     */
    private function buildOtpEmailHtml(string $otp_code, int $validity, string $intro, string $masked_email): string
    {
        // Design réutilisable, compatible Design System Coiffe Chez Toi
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #D4AF37 0%, #c9a227 100%);
            padding: 24px;
            text-align: center;
            color: #000;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .body {
            padding: 32px 24px;
            color: #333;
            line-height: 1.6;
        }
        .body p {
            margin: 0 0 16px 0;
            font-size: 14px;
        }
        .otp-container {
            background-color: #f9f9f9;
            border: 2px dashed #D4AF37;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: 700;
            color: #D4AF37;
            letter-spacing: 4px;
            font-family: 'Courier New', monospace;
        }
        .otp-validity {
            font-size: 12px;
            color: #666;
            margin-top: 12px;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        .security-notice {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px;
            margin: 16px 0;
            font-size: 12px;
            color: #856404;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Coiffe Chez Toi</h1>
        </div>
        <div class="body">
            <p><strong>Bonjour,</strong></p>
            <p>{$intro}</p>
            
            <div class="otp-container">
                <div class="otp-code">{$otp_code}</div>
                <div class="otp-validity">Code valable pendant {$validity} minutes</div>
            </div>

            <div class="security-notice">
                ⚠️ <strong>Important :</strong> Ne partagez jamais ce code avec quiconque. Notre équipe ne vous le demandera jamais par email ou téléphone.
            </div>

            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email et votre compte reste sécurisé.</p>
        </div>
        <div class="footer">
            <p>Coiffe Chez Toi — Plateforme de services beauté à domicile<br>
            © 2025 Tous droits réservés.</p>
            <p style="margin-top: 8px; color: #999;">Email envoyé à: {$masked_email}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Construit le template HTML pour un email de notification générique.
     */
    private function buildNotificationEmailHtml(string $message, string $action_text = '', string $action_url = ''): string
    {
        $action_button = '';
        if (!empty($action_text) && !empty($action_url)) {
            $action_button = <<<HTML
            <div style="text-align: center; margin: 24px 0;">
                <a href="{$action_url}" style="display: inline-block; background-color: #D4AF37; color: #000; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 700; font-size: 14px;">
                    {$action_text}
                </a>
            </div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #D4AF37 0%, #c9a227 100%); padding: 24px; text-align: center; color: #000; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .body { padding: 32px 24px; color: #333; line-height: 1.6; }
        .footer { background-color: #f5f5f5; padding: 16px 24px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Coiffe Chez Toi</h1>
        </div>
        <div class="body">
            {$message}
            {$action_button}
        </div>
        <div class="footer">
            <p>© 2025 Coiffe Chez Toi. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    // ═══════════════════════════════════════════════════════
    // UTILITAIRES
    // ═══════════════════════════════════════════════════════

    /**
     * Masquer l'adresse email pour la confidentialité.
     * Ex: user@example.com → u***@example.com
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masked_local = substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 2)) . (strlen($local) > 1 ? '' : '');
        return $masked_local . '@' . $domain;
    }

    /**
     * Test de connexion SMTP (utile pour le setup).
     */
    public function testConnection(): array
    {
        try {
            if ($this->mailer->smtpConnect()) {
                $this->mailer->smtpClose();
                return ['success' => true, 'message' => 'Connexion SMTP OK.'];
            } else {
                return ['success' => false, 'message' => 'Erreur SMTP: ' . $this->mailer->ErrorInfo];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}
