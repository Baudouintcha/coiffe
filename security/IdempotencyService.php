<?php
/**
 * security/IdempotencyService.php — Service d'idempotence des transactions
 * Empêche qu'une même transaction soit exécutée deux fois
 * 
 * Génère une clé idempotente à partir de :
 * user_id + action + montant + minute (timestamp)
 * 
 * Utilise SHA-256 pour la clé.
 * Conserve le résultat précédent et le retourne en cas de nouvelle requête identique.
 */

class IdempotencyService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Génère une clé idempotente
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $action Type d'action
     * @param float $montant Montant de la transaction
     * @param int|null $timestamp Timestamp (défaut: now)
     * @return string Clé SHA-256
     */
    public function generateKey(int $user_id, string $action, float $montant, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        
        // Grouper par minute (pas par seconde)
        $minute = floor($timestamp / 60);
        
        $raw = "{$user_id}|{$action}|{$montant}|{$minute}";
        return hash('sha256', $raw);
    }

    /**
     * Vérifie si une transaction est déjà en cours ou complétée
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $action Type d'action
     * @param float $montant Montant
     * @return array ['is_duplicate'=>bool, 'result'=>array|null, 'status'=>string]
     */
    public function checkDuplicate(int $user_id, string $action, float $montant): array
    {
        try {
            $idempotency_key = $this->generateKey($user_id, $action, $montant);

            $stmt = $this->pdo->prepare(
                "SELECT status, result_json FROM idempotency_transactions 
                 WHERE user_id = ? AND idempotency_key = ?"
            );
            $stmt->execute([$user_id, $idempotency_key]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                return [
                    'is_duplicate' => false,
                    'result' => null,
                    'status' => 'new',
                    'key' => $idempotency_key,
                ];
            }

            // Vérifier le statut
            if ($existing['status'] === 'pending') {
                return [
                    'is_duplicate' => true,
                    'result' => null,
                    'status' => 'pending',
                    'message' => 'Transaction en cours. Veuillez attendre...',
                ];
            } elseif ($existing['status'] === 'completed') {
                $result = json_decode($existing['result_json'], true);
                return [
                    'is_duplicate' => true,
                    'result' => $result,
                    'status' => 'completed',
                    'message' => 'Transaction déjà effectuée. Voici le résultat précédent.',
                ];
            } elseif ($existing['status'] === 'failed') {
                return [
                    'is_duplicate' => true,
                    'result' => null,
                    'status' => 'failed',
                    'message' => 'Transaction précédente échouée. Vous pouvez réessayer.',
                ];
            }

            return [
                'is_duplicate' => false,
                'result' => null,
                'status' => 'unknown',
                'key' => $idempotency_key,
            ];

        } catch (Exception $e) {
            error_log("IdempotencyService::checkDuplicate() erreur: " . $e->getMessage());
            return [
                'is_duplicate' => false,
                'result' => null,
                'status' => 'error',
            ];
        }
    }

    /**
     * Crée une nouvelle transaction idempotente en statut 'pending'
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $action Type d'action
     * @param float $montant Montant
     * @return string|false Clé idempotente ou false en cas d'erreur
     */
    public function createPending(int $user_id, string $action, float $montant): string|false
    {
        try {
            $key = $this->generateKey($user_id, $action, $montant);

            $stmt = $this->pdo->prepare(
                "INSERT INTO idempotency_transactions 
                 (user_id, idempotency_key, action, montant, status)
                 VALUES (?, ?, ?, ?, 'pending')
                 ON DUPLICATE KEY UPDATE status = 'pending', updated_at = NOW()"
            );

            $stmt->execute([$user_id, $key, $action, $montant]);
            return $key;

        } catch (Exception $e) {
            error_log("IdempotencyService::createPending() erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marque une transaction comme complétée avec son résultat
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $idempotency_key Clé idempotente
     * @param array $result Résultat de la transaction
     * @return bool Succès
     */
    public function markCompleted(int $user_id, string $idempotency_key, array $result): bool
    {
        try {
            $result_json = json_encode($result);

            $stmt = $this->pdo->prepare(
                "UPDATE idempotency_transactions 
                 SET status = 'completed', result_json = ?, updated_at = NOW()
                 WHERE user_id = ? AND idempotency_key = ?"
            );

            return $stmt->execute([$result_json, $user_id, $idempotency_key]);

        } catch (Exception $e) {
            error_log("IdempotencyService::markCompleted() erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marque une transaction comme échouée
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $idempotency_key Clé idempotente
     * @param string $error Message d'erreur
     * @return bool Succès
     */
    public function markFailed(int $user_id, string $idempotency_key, string $error): bool
    {
        try {
            $result = ['error' => $error, 'failed_at' => date('Y-m-d H:i:s')];
            $result_json = json_encode($result);

            $stmt = $this->pdo->prepare(
                "UPDATE idempotency_transactions 
                 SET status = 'failed', result_json = ?, updated_at = NOW()
                 WHERE user_id = ? AND idempotency_key = ?"
            );

            return $stmt->execute([$result_json, $user_id, $idempotency_key]);

        } catch (Exception $e) {
            error_log("IdempotencyService::markFailed() erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Nettoie les anciennes transactions (au-delà de 30 jours)
     * À exécuter régulièrement via cron
     * 
     * @return int Nombre de lignes supprimées
     */
    public function cleanup(): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM idempotency_transactions 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("IdempotencyService::cleanup() erreur: " . $e->getMessage());
            return 0;
        }
    }
}
?>
