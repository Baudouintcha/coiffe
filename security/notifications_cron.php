<?php
/**
 * security/notifications_cron.php — Notifications automatiques planifiées
 * Design System v2.0 — Coiffe Chez Toi V1.0
 *
 * Appel simulé au chargement du dashboard (pour la soutenance)
 * Production : cron job toutes les heures
 *
 * Usage depuis un dashboard :
 *   require_once __DIR__ . '/../security/notifications_cron.php';
 *   run_notifications_cron($pdo);
 */

require_once __DIR__ . '/notifications.php';

if (!function_exists('run_notifications_cron')) {

    /**
     * Lance tous les déclencheurs automatiques de notifications.
     * Non-bloquant : toute exception est silencieuse.
     *
     * @param PDO $pdo
     */
    function run_notifications_cron(PDO $pdo): void {

        _cron_clients_rdv_demain_non_confirme($pdo);
        _cron_clients_avis_non_laisse($pdo);
        _cron_coiffeurs_abonnement_expire_bientot($pdo);
        _cron_coiffeurs_rdv_en_attente($pdo);
        _cron_admins_nouveau_coiffeur_non_approuve($pdo);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CLIENTS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * RDV demain non encore confirmé par le coiffeur → notif client
     */
    function _cron_clients_rdv_demain_non_confirme(PDO $pdo): void {
        try {
            $stmt = $pdo->query(
                "SELECT r.client_id, r.id AS id_rdv
                 FROM rendez_vous r
                 WHERE r.date_rdv = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                   AND r.statut_rdv = 'en_attente'
                   AND NOT EXISTS (
                       SELECT 1 FROM notifications n
                       WHERE n.user_id = r.client_id
                         AND n.message LIKE CONCAT('%RDV demain%', r.id, '%')
                         AND n.date_demande >= CURDATE()
                   )"
            );
            $rdvs = $stmt->fetchAll();
            foreach ($rdvs as $rdv) {
                notifier(
                    $pdo,
                    (int) $rdv['client_id'],
                    "Rappel : votre RDV demain (n°{$rdv['id_rdv']}) n'est pas encore confirmé par le coiffeur.",
                    'warning'
                );
            }
        } catch (Exception $e) {
            // Non-bloquant
        }
    }

    /**
     * Avis non laissé 3 jours après un RDV terminé → notif client
     */
    function _cron_clients_avis_non_laisse(PDO $pdo): void {
        try {
            $stmt = $pdo->query(
                "SELECT r.client_id, r.id AS id_rdv, r.prestataire_id
                 FROM rendez_vous r
                 WHERE r.statut_rdv = 'termine'
                   AND r.date_rdv <= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                   AND NOT EXISTS (
                       SELECT 1 FROM avis a
                       WHERE a.client_id = r.client_id
                         AND a.prestataire_id = r.prestataire_id
                         AND a.rdv_id = r.id
                   )
                   AND NOT EXISTS (
                       SELECT 1 FROM notifications n
                       WHERE n.user_id = r.client_id
                         AND n.message LIKE CONCAT('%avis%', r.id, '%')
                         AND n.date_demande >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
                   )"
            );
            $rdvs = $stmt->fetchAll();
            foreach ($rdvs as $rdv) {
                notifier(
                    $pdo,
                    (int) $rdv['client_id'],
                    "Vous avez un RDV terminé il y a 3 jours (n°{$rdv['id_rdv']}) — laissez un avis !",
                    'info'
                );
            }
        } catch (Exception $e) {
            // Non-bloquant
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // COIFFEURS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Abonnement expirant dans ≤ 5 jours → notif coiffeur
     */
    function _cron_coiffeurs_abonnement_expire_bientot(PDO $pdo): void {
        try {
            $stmt = $pdo->query(
                "SELECT id, DATEDIFF(date_expiration_abo, CURDATE()) AS jours_restants
                 FROM users
                 WHERE role = 'prestataire'
                   AND date_expiration_abo BETWEEN CURDATE()
                       AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)
                   AND NOT EXISTS (
                       SELECT 1 FROM notifications n
                       WHERE n.user_id = users.id
                         AND n.message LIKE '%abonnement expire%'
                         AND n.date_demande >= CURDATE()
                   )"
            );
            $coiffeurs = $stmt->fetchAll();
            foreach ($coiffeurs as $c) {
                $jours = (int) $c['jours_restants'];
                $msg   = $jours <= 0
                    ? "Votre abonnement a expiré aujourd'hui. Renouvelez-le pour continuer à recevoir des réservations."
                    : "Votre abonnement expire dans {$jours} jour" . ($jours > 1 ? 's' : '') . ". Pensez à le renouveler.";
                notifier($pdo, (int) $c['id'], $msg, 'warning');
            }
        } catch (Exception $e) {
            // Non-bloquant
        }
    }

    /**
     * Demandes de RDV en attente depuis plus de 2 heures → notif coiffeur
     */
    function _cron_coiffeurs_rdv_en_attente(PDO $pdo): void {
        try {
            $stmt = $pdo->query(
                "SELECT prestataire_id, COUNT(*) AS nb
                 FROM rendez_vous
                 WHERE statut_rdv = 'en_attente'
                   AND date_demande <= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                 GROUP BY prestataire_id
                 HAVING nb > 0"
            );
            $coiffeurs = $stmt->fetchAll();
            foreach ($coiffeurs as $c) {
                $nb  = (int) $c['nb'];
                $msg = $nb === 1
                    ? "Vous avez 1 demande de rendez-vous en attente depuis plus de 2h."
                    : "Vous avez {$nb} demandes de rendez-vous en attente depuis plus de 2h.";

                // Éviter le doublon sur la même journée
                $check = $pdo->prepare(
                    "SELECT COUNT(*) FROM notifications
                     WHERE user_id = ? AND message LIKE '%demandes%attente%'
                       AND date_demande >= CURDATE()"
                );
                $check->execute([(int) $c['prestataire_id']]);
                if ((int) $check->fetchColumn() === 0) {
                    notifier($pdo, (int) $c['prestataire_id'], $msg, 'warning');
                }
            }
        } catch (Exception $e) {
            // Non-bloquant
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // ADMINS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Nouveau coiffeur non approuvé → notif admins
     */
    function _cron_admins_nouveau_coiffeur_non_approuve(PDO $pdo): void {
        try {
            $stmt = $pdo->query(
                "SELECT id, nom, prenom
                 FROM users
                 WHERE role = 'prestataire'
                   AND is_approved = 0
                   AND NOT EXISTS (
                       SELECT 1 FROM notifications n
                       JOIN users a ON a.id = n.user_id AND a.role = 'admin'
                       WHERE n.message LIKE CONCAT('%coiffeur%', users.id, '%diplôme%')
                         AND n.date_demande >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                   )"
            );
            $coiffeurs = $stmt->fetchAll();
            foreach ($coiffeurs as $c) {
                $nom = htmlspecialchars($c['prenom'] . ' ' . $c['nom']);
                notifier_admins(
                    $pdo,
                    "Nouveau coiffeur inscrit ({$nom}, ID {$c['id']}) — diplôme en attente de validation.",
                    'info'
                );
            }
        } catch (Exception $e) {
            // Non-bloquant
        }
    }
}
