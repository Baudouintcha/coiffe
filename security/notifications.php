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
     * @param int    $id_user Destinataire
     * @param string $message Texte de la notification
     * @param string $type    info | success | danger | warning
     * @return bool
     */
    function notifier(PDO $pdo, int $id_user, string $message, string $type = 'info'): bool {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO notifications (id_user, message, type, statut_lecture, date_notification)
                 VALUES (?, ?, ?, 'non_lu', NOW())"
            );
            return $stmt->execute([$id_user, $message, $type]);
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
