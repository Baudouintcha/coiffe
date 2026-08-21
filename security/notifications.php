<?php
/**
 * security/notifications.php — Helper centralisé de notifications in-app
 * Design System v2.0 — Coiffe Chez Toi V1.0
 * Architecture extensible : prêt pour Email, Push, SMS, WhatsApp
 */

if (!function_exists('notifier')) {
    /**
     * Insère une notification in-app dans la BDD
     * Architecture extensible : ajouter email/push/sms ici quand disponible
     *
     * @param PDO    $pdo     Connexion PDO
     * @param int    $user_id Destinataire
     * @param string $message Texte de la notification
     * @param string $type    info | success | danger | warning
     * @return bool
     */
    function notifier(PDO $pdo, int $user_id, string $message, string $type = 'info'): bool {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO notifications (user_id, message, type, lu, date_demande)
                 VALUES (?, ?, ?, 0, NOW())"
            );
            return $stmt->execute([$user_id, $message, $type]);
        } catch (Exception $e) {
            return false; // Non-bloquant
        }
    }

    /**
     * Notifie tous les admins
     *
     * @param PDO    $pdo
     * @param string $message
     * @param string $type
     */
    function notifier_admins(PDO $pdo, string $message, string $type = 'info'): void {
        try {
            $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $admin) {
                notifier($pdo, (int)$admin['id'], $message, $type);
            }
        } catch (Exception $e) {
            // Non-bloquant
        }
    }
}
