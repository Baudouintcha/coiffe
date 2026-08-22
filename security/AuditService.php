<?php
/**
 * security/AuditService.php — Service d'audit des transactions
 * Enregistre toutes les opérations sensibles pour la traçabilité
 * 
 * Champs tracés :
 * - user_id, user_role
 * - transaction_type, montant, status
 * - motif, error_message
 * - IP, user_agent, session_id
 * - timestamp
 */

class AuditService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Enregistre une transaction dans l'audit
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $user_role Rôle de l'utilisateur (client, prestataire, admin)
     * @param string $transaction_type Type de transaction (recharge, abonnement, gel, etc.)
     * @param float $montant Montant de la transaction
     * @param string $status Statut de la transaction (success, failed, pending)
     * @param string $motif Motif/description de la transaction
     * @param string|null $error_message Message d'erreur si applicable
     * @return bool Succès de l'insertion
     */
    public function log(
        int $user_id,
        string $user_role,
        string $transaction_type,
        float $montant,
        string $status,
        string $motif = '',
        ?string $error_message = null
    ): bool {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $session_id = session_id() ?? '';

            $stmt = $this->pdo->prepare(
                "INSERT INTO audit_transactions 
                 (user_id, user_role, transaction_type, montant, status, motif, 
                  error_message, ip_address, user_agent, session_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            return $stmt->execute([
                $user_id,
                $user_role,
                $transaction_type,
                $montant,
                $status,
                $motif,
                $error_message,
                $ip_address,
                $user_agent,
                $session_id,
            ]);
        } catch (Exception $e) {
            error_log("AuditService::log() erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère l'historique d'audit d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $limit Nombre d'entrées à récupérer
     * @return array Historique des transactions auditées
     */
    public function getHistory(int $user_id, int $limit = 50): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM audit_transactions 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?"
            );
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AuditService::getHistory() erreur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les transactions échouées (potentielles fraudes)
     * 
     * @param int $minutes Minutes à vérifier en arrière
     * @return array Transactions échouées
     */
    public function getFailedTransactions(int $minutes = 60): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM audit_transactions 
                 WHERE status = 'failed' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                 ORDER BY created_at DESC"
            );
            $stmt->execute([$minutes]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AuditService::getFailedTransactions() erreur: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte les transactions d'un utilisateur dans une plage horaire
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $minutes Minutes à vérifier en arrière
     * @return int Nombre de transactions
     */
    public function countRecentTransactions(int $user_id, int $minutes = 60): int
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM audit_transactions 
                 WHERE user_id = ? 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)"
            );
            $stmt->execute([$user_id, $minutes]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("AuditService::countRecentTransactions() erreur: " . $e->getMessage());
            return 0;
        }
    }
}
?>
