<?php
/**
 * security/RateLimitService.php — Service de limitation de débit
 * Empêche les abus (activations multiples d'abonnement, double réservation, etc.)
 * 
 * Règles pour activation d'abonnement :
 * - Maximum 1 abonnement actif
 * - Empêcher les doubles activations
 * - Empêcher les répétitions abusives
 * - Cooldown obligatoire entre les tentatives
 */

class RateLimitService
{
    private PDO $pdo;

    // Configuration
    const MAX_ATTEMPTS_PER_HOUR = 5;      // Max 5 tentatives par heure
    const COOLDOWN_MINUTES = 15;          // Cooldown de 15 minutes entre tentatives
    const MAX_ACTIVE_SUBSCRIPTIONS = 1;   // Max 1 abonnement actif

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Vérifie si un utilisateur peut activer un abonnement
     * 
     * @param int $prestataire_id ID du prestataire
     * @return array ['allowed'=>bool, 'reason'=>string, 'retry_after'=>int|null]
     */
    public function canActivateSubscription(int $prestataire_id): array
    {
        // 1. Vérifier s'il a déjà un abonnement actif
        try {
            $stmt = $this->pdo->prepare(
                "SELECT abonnement_status, date_expiration_abo FROM users 
                 WHERE id = ? AND role = 'prestataire'"
            );
            $stmt->execute([$prestataire_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return [
                    'allowed' => false,
                    'reason' => 'Utilisateur non trouvé ou non prestataire',
                ];
            }

            // Vérifier si l'abonnement est toujours actif
            if ($user['abonnement_status'] === 'actif') {
                $expiration = strtotime($user['date_expiration_abo']);
                if ($expiration > time()) {
                    return [
                        'allowed' => false,
                        'reason' => 'Un abonnement actif existe déjà. Expiration : ' . date('d/m/Y', $expiration),
                    ];
                }
            }

            // 2. Vérifier le cooldown
            $cooldown_check = $this->checkCooldown($prestataire_id, 'abonnement');
            if ($cooldown_check['limited']) {
                return [
                    'allowed' => false,
                    'reason' => 'Trop de tentatives. Veuillez réessayer plus tard.',
                    'retry_after' => $cooldown_check['retry_after'],
                ];
            }

            // 3. Vérifier le nombre de tentatives par heure
            $attempts = $this->countAttempts($prestataire_id, 'abonnement', 60);
            if ($attempts >= self::MAX_ATTEMPTS_PER_HOUR) {
                return [
                    'allowed' => false,
                    'reason' => 'Trop de tentatives cette heure. Limite : ' . self::MAX_ATTEMPTS_PER_HOUR,
                    'retry_after' => 3600, // Réessayer dans 1 heure
                ];
            }

            return [
                'allowed' => true,
                'reason' => 'Abonnement peut être activé',
            ];

        } catch (Exception $e) {
            error_log("RateLimitService::canActivateSubscription() erreur: " . $e->getMessage());
            return [
                'allowed' => false,
                'reason' => 'Erreur technique lors de la vérification',
            ];
        }
    }

    /**
     * Vérifie s'il y a un cooldown en cours
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $action Type d'action ('abonnement', 'reservation', etc.)
     * @return array ['limited'=>bool, 'retry_after'=>int]
     */
    public function checkCooldown(int $user_id, string $action): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT MAX(created_at) as last_attempt FROM audit_transactions 
                 WHERE user_id = ? AND transaction_type = ? 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
            );
            $stmt->execute([$user_id, $action, self::COOLDOWN_MINUTES]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['last_attempt']) {
                $last = strtotime($result['last_attempt']);
                $now = time();
                $elapsed = $now - $last;
                $retry_after = (self::COOLDOWN_MINUTES * 60) - $elapsed;

                return [
                    'limited' => true,
                    'retry_after' => max(1, $retry_after),
                ];
            }

            return [
                'limited' => false,
                'retry_after' => 0,
            ];
        } catch (Exception $e) {
            error_log("RateLimitService::checkCooldown() erreur: " . $e->getMessage());
            return ['limited' => false, 'retry_after' => 0];
        }
    }

    /**
     * Compte les tentatives d'un utilisateur dans une plage horaire
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $action Type d'action
     * @param int $minutes Plage horaire en minutes
     * @return int Nombre de tentatives
     */
    public function countAttempts(int $user_id, string $action, int $minutes = 60): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM audit_transactions 
                 WHERE user_id = ? AND transaction_type = ? 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
            );
            $stmt->execute([$user_id, $action, $minutes]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("RateLimitService::countAttempts() erreur: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Vérifie s'il y a du rate limiting sur une IP
     * Utile pour détecter les attaques automatisées
     * 
     * @param string $ip Adresse IP
     * @param int $minutes Plage horaire
     * @return array ['limited'=>bool, 'count'=>int]
     */
    public function checkIPRateLimit(string $ip, int $minutes = 60): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) as count FROM audit_transactions 
                 WHERE ip_address = ? 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
            );
            $stmt->execute([$ip, $minutes]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)$result['count'];

            // Considérer comme limité au-delà de 50 tentatives par heure
            return [
                'limited' => $count > 50,
                'count' => $count,
            ];
        } catch (Exception $e) {
            error_log("RateLimitService::checkIPRateLimit() erreur: " . $e->getMessage());
            return ['limited' => false, 'count' => 0];
        }
    }
}
?>
