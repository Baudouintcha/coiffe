<?php
/**
 * security/SecurePaymentOrchestrator.php — Orchestrateur de paiement sécurisé
 * Coordonne tous les services de sécurité pour une transaction
 * 
 * Architecture :
 * SecurePaymentOrchestrator
 * ├── ValidationService
 * ├── IdentityVerificationService
 * ├── RateLimitService
 * ├── IdempotencyService
 * ├── AuditService
 * └── PDO
 */

class SecurePaymentOrchestrator
{
    private PDO $pdo;
    private ValidationService $validator;
    private IdentityVerificationService $identity;
    private RateLimitService $rateLimit;
    private IdempotencyService $idempotency;
    private AuditService $audit;
    private PaymentService $payment;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->validator = new ValidationService($pdo);
        $this->identity = new IdentityVerificationService($pdo);
        $this->rateLimit = new RateLimitService($pdo);
        $this->idempotency = new IdempotencyService($pdo);
        $this->audit = new AuditService($pdo);
        $this->payment = new PaymentService($pdo);
    }

    /**
     * Procédure sécurisée d'activation d'abonnement
     * 
     * @param int $prestataire_id ID du prestataire
     * @param float $prix Prix de l'abonnement
     * @param int $duree_jours Durée en jours
     * @return array Résultat avec status, message, etc.
     */
    public function activateSubscriptionSecurely(int $prestataire_id, float $prix = 1500, int $duree_jours = 30): array
    {
        $user_role = 'prestataire';
        
        try {
            // 1. VALIDATION DES PARAMÈTRES
            $validation = $this->validator->validateAbonnement($prestataire_id, $prix, $duree_jours);
            if (!$validation['valid']) {
                $this->audit->log(
                    $prestataire_id, $user_role, 'abonnement', $prix, 'failed',
                    'Validation échouée', implode('; ', $validation['errors'])
                );
                return [
                    'success' => false,
                    'message' => 'Validation échouée : ' . implode(', ', $validation['errors']),
                    'errors' => $validation['errors'],
                ];
            }

            // 2. VÉRIFICATION D'IDENTITÉ
            $identity_check = $this->identity->verify($prestataire_id, 'prestataire');
            if (!$identity_check['verified']) {
                $this->audit->log(
                    $prestataire_id, $user_role, 'abonnement', $prix, 'failed',
                    'Vérification d\'identité échouée', implode('; ', $identity_check['errors'])
                );
                return [
                    'success' => false,
                    'message' => 'Vérification d\'identité échouée : ' . implode(', ', $identity_check['errors']),
                    'errors' => $identity_check['errors'],
                ];
            }

            // 3. VÉRIFICATION DE RATE LIMIT
            $rate_limit_check = $this->rateLimit->canActivateSubscription($prestataire_id);
            if (!$rate_limit_check['allowed']) {
                $this->audit->log(
                    $prestataire_id, $user_role, 'abonnement', $prix, 'rate_limited',
                    'Rate limit atteint', $rate_limit_check['reason']
                );
                return [
                    'success' => false,
                    'message' => $rate_limit_check['reason'],
                    'retry_after' => $rate_limit_check['retry_after'] ?? null,
                ];
            }

            // 4. VÉRIFICATION D'IDEMPOTENCE
            $idempotency_check = $this->idempotency->checkDuplicate($prestataire_id, 'abonnement', $prix);
            if ($idempotency_check['is_duplicate']) {
                if ($idempotency_check['status'] === 'completed') {
                    $this->audit->log(
                        $prestataire_id, $user_role, 'abonnement', $prix, 'duplicate_success',
                        'Retour du résultat précédent'
                    );
                    return [
                        'success' => true,
                        'message' => 'Abonnement déjà activé. ' . $idempotency_check['message'],
                        'data' => $idempotency_check['result'],
                        'is_duplicate' => true,
                    ];
                } elseif ($idempotency_check['status'] === 'pending') {
                    $this->audit->log(
                        $prestataire_id, $user_role, 'abonnement', $prix, 'pending',
                        'Réessai détecté (transaction en cours)'
                    );
                    return [
                        'success' => false,
                        'message' => $idempotency_check['message'],
                        'status' => 'pending',
                    ];
                }
                // Si failed, laisser l'utilisateur réessayer
            }

            // 5. CRÉER LA CLÉ IDEMPOTENTE ET MARQUER EN PENDING
            $idempotency_key = $this->idempotency->createPending($prestataire_id, 'abonnement', $prix);
            if (!$idempotency_key) {
                $this->audit->log(
                    $prestataire_id, $user_role, 'abonnement', $prix, 'failed',
                    'Impossible de créer la clé idempotente'
                );
                return [
                    'success' => false,
                    'message' => 'Erreur système. Veuillez réessayer.',
                ];
            }

            // 6. EXÉCUTER LA TRANSACTION PAYANTE
            $payment_result = $this->payment->activerAbonnement($prestataire_id, $prix, $duree_jours);

            if (!$payment_result['success']) {
                // Marquer comme échoué
                $this->idempotency->markFailed($prestataire_id, $idempotency_key, $payment_result['message']);

                $this->audit->log(
                    $prestataire_id, $user_role, 'abonnement', $prix, 'failed',
                    'Paiement échoué', $payment_result['message']
                );

                return [
                    'success' => false,
                    'message' => 'Activation échouée : ' . $payment_result['message'],
                ];
            }

            // 7. MARQUER COMME COMPLÉTÉE
            $this->idempotency->markCompleted($prestataire_id, $idempotency_key, $payment_result);

            // 8. AUDIT DU SUCCÈS
            $this->audit->log(
                $prestataire_id, $user_role, 'abonnement', $prix, 'success',
                'Abonnement activé avec succès'
            );

            return [
                'success' => true,
                'message' => $payment_result['message'],
                'data' => $payment_result,
                'idempotency_key' => $idempotency_key,
            ];

        } catch (Exception $e) {
            error_log("SecurePaymentOrchestrator::activateSubscriptionSecurely() erreur: " . $e->getMessage());

            $this->audit->log(
                $prestataire_id, $user_role, 'abonnement', $prix, 'error',
                'Exception non gérée', $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Erreur système. Veuillez contacter le support.',
            ];
        }
    }

    /**
     * Procédure sécurisée de recharge de portefeuille
     * 
     * @param int $user_id ID de l'utilisateur
     * @param float $montant Montant à recharger
     * @return array Résultat avec status, message, etc.
     */
    public function rechargeWalletSecurely(int $user_id, float $montant): array
    {
        $user_role = 'client';

        try {
            // 1. VALIDATION
            $validation = $this->validator->validateRecharge($user_id, $montant);
            if (!$validation['valid']) {
                $this->audit->log(
                    $user_id, $user_role, 'recharge', $montant, 'failed',
                    'Validation échouée', implode('; ', $validation['errors'])
                );
                return [
                    'success' => false,
                    'message' => 'Validation échouée : ' . implode(', ', $validation['errors']),
                ];
            }

            // 2. VÉRIFICATION D'IDENTITÉ
            $identity_check = $this->identity->verify($user_id, 'client');
            if (!$identity_check['verified']) {
                $this->audit->log(
                    $user_id, $user_role, 'recharge', $montant, 'failed',
                    'Vérification échouée', implode('; ', $identity_check['errors'])
                );
                return [
                    'success' => false,
                    'message' => 'Vérification d\'identité échouée',
                ];
            }

            // 3. IDEMPOTENCE
            $idempotency_check = $this->idempotency->checkDuplicate($user_id, 'recharge', $montant);
            if ($idempotency_check['is_duplicate'] && $idempotency_check['status'] === 'completed') {
                return [
                    'success' => true,
                    'message' => 'Recharge déjà effectuée',
                    'data' => $idempotency_check['result'],
                    'is_duplicate' => true,
                ];
            }

            $idempotency_key = $this->idempotency->createPending($user_id, 'recharge', $montant);

            // 4. EXÉCUTER LE PAIEMENT
            $payment_result = $this->payment->rechargerPortefeuille($user_id, $montant);

            if (!$payment_result['success']) {
                $this->idempotency->markFailed($user_id, $idempotency_key, $payment_result['message']);
                $this->audit->log($user_id, $user_role, 'recharge', $montant, 'failed', '', $payment_result['message']);
                return $payment_result;
            }

            // 5. SUCCÈS
            $this->idempotency->markCompleted($user_id, $idempotency_key, $payment_result);
            $this->audit->log($user_id, $user_role, 'recharge', $montant, 'success');

            return [
                'success' => true,
                'message' => $payment_result['message'],
                'data' => $payment_result,
            ];

        } catch (Exception $e) {
            error_log("SecurePaymentOrchestrator::rechargeWalletSecurely() erreur: " . $e->getMessage());
            $this->audit->log($user_id, $user_role, 'recharge', $montant, 'error', '', $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur système',
            ];
        }
    }

    /**
     * Accès aux services individuels (pour besoins spécifiques)
     */
    public function getAuditService(): AuditService { return $this->audit; }
    public function getRateLimitService(): RateLimitService { return $this->rateLimit; }
    public function getIdentityService(): IdentityVerificationService { return $this->identity; }
    public function getIdempotencyService(): IdempotencyService { return $this->idempotency; }
    public function getValidationService(): ValidationService { return $this->validator; }
    public function getPaymentService(): PaymentService { return $this->payment; }
}
?>
