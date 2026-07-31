<?php
/**
 * payment/PaymentService.php — Service de paiement abstrait
 * Architecture : simulation maintenant, Kkiapay après soutenance
 * Pour passer en prod : remplacer les méthodes par les appels SDK Kkiapay
 */

class PaymentService {
    private PDO $pdo;
    private bool $simulationMode = true; // false en prod avec Kkiapay
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Recharge le portefeuille d'un utilisateur
     * En prod : initier un paiement Kkiapay et attendre le webhook
     */
    public function recharger(int $userId, float $montant, string $motif = 'Recharge'): array {
        if ($montant < 500) return ['success' => false, 'message' => 'Montant minimum : 500 FCFA'];
        if ($montant > 500000) return ['success' => false, 'message' => 'Montant maximum : 500 000 FCFA'];
        
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("UPDATE users SET solde = solde + ? WHERE id = ?")->execute([$montant, $userId]);
            $this->pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif, date_creation) VALUES (?, 'recharge', ?, ?, NOW())")->execute([$userId, $montant, $motif . ($this->simulationMode ? ' (simulation)' : '')]);
            $this->pdo->commit();
            return ['success' => true, 'message' => number_format($montant, 0, ',', ' ') . ' FCFA ajoutés'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Erreur technique'];
        }
    }
    
    /**
     * Gèle des fonds (lors d'une réservation)
     */
    public function geler(int $userId, float $montant, string $motif): array {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("SELECT solde FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $solde = floatval($stmt->fetchColumn());
            
            if ($solde < $montant) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'Solde insuffisant'];
            }
            
            $this->pdo->prepare("UPDATE users SET solde = solde - ? WHERE id = ?")->execute([$montant, $userId]);
            $this->pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif, date_creation) VALUES (?, 'gel', ?, ?, NOW())")->execute([$userId, $montant, $motif]);
            $this->pdo->commit();
            return ['success' => true, 'solde_restant' => $solde - $montant];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Erreur technique'];
        }
    }
    
    /**
     * Active l'abonnement coiffeur (simulation)
     */
    public function activerAbonnement(int $coiffeurId, float $montant = 1500): array {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("SELECT solde FROM users WHERE id = ?");
            $stmt->execute([$coiffeurId]);
            $solde = floatval($stmt->fetchColumn());
            
            if ($solde < $montant) {
                // En simulation : activer quand même pour la démo
                if (!$this->simulationMode) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => 'Solde insuffisant pour l\'abonnement'];
                }
            }
            
            $expiration = date('Y-m-d', strtotime('+30 days'));
            $this->pdo->prepare("UPDATE users SET abonnement_status = 1, date_expiration_abo = ? WHERE id = ?")->execute([$expiration, $coiffeurId]);
            
            if ($solde >= $montant) {
                $this->pdo->prepare("UPDATE users SET solde = solde - ? WHERE id = ?")->execute([$montant, $coiffeurId]);
                $this->pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif, date_creation) VALUES (?, 'abonnement', ?, 'Abonnement mensuel', NOW())")->execute([$coiffeurId, $montant]);
            }
            
            $this->pdo->commit();
            return ['success' => true, 'expiration' => $expiration];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Erreur technique'];
        }
    }
    
    /**
     * Squelette Webhook Kkiapay (prod uniquement)
     * En simulation : non utilisé
     */
    public function handleKkiapayWebhook(array $payload): void {
        if ($this->simulationMode) return;
        // TODO: Implémenter après soutenance
        // $transactionId = $payload['transactionId'];
        // $status = $payload['status'];
        // if ($status === 'SUCCESS') { ... }
    }
}
