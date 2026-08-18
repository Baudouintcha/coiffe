<?php
/**
 * security/PaymentService.php — Couche d'abstraction paiement
 * Coiffe Chez Toi V1.0 — Architecture prête pour Kkiapay
 *
 * En mode simulation : toutes les opérations sont traitées directement en BDD.
 * En mode réel (futur) : remplacer les méthodes par les appels SDK Kkiapay
 * sans modifier les modules appelants.
 *
 * Usage :
 *   $payment = new PaymentService($pdo);
 *   $result  = $payment->rechargerPortefeuille($id_user, $montant, 'simulation');
 *   $result  = $payment->activerAbonnement($prestataire_id, 1500, 30, 'simulation');
 */

class PaymentService
{
    private PDO $pdo;

    // Modes disponibles
    const MODE_SIMULATION = 'simulation';
    const MODE_KKIAPAY    = 'kkiapay';   // Futur

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ═══════════════════════════════════════════════════════
    // RECHARGE PORTEFEUILLE CLIENT
    // ═══════════════════════════════════════════════════════

    /**
     * Crédite le solde d'un utilisateur et enregistre la transaction.
     *
     * @param int    $id_user  ID utilisateur
     * @param float  $montant  Montant à créditer (FCFA)
     * @param string $mode     'simulation' | 'kkiapay'
     * @param string $motif    Libellé de la transaction
     * @return array ['success'=>bool, 'message'=>string, 'nouveau_solde'=>float]
     */
    public function rechargerPortefeuille(int $id_user, float $montant, string $mode = self::MODE_SIMULATION, string $motif = 'Recharge portefeuille'): array
    {
        if ($montant < 500) {
            return ['success' => false, 'message' => 'Montant minimum : 500 FCFA.'];
        }
        if ($montant > 500000) {
            return ['success' => false, 'message' => 'Montant maximum : 500 000 FCFA par recharge.'];
        }

        if ($mode === self::MODE_SIMULATION) {
            return $this->_simulerRecharge($id_user, $montant, $motif);
        }

        // Futur : appel Kkiapay ici
        return ['success' => false, 'message' => 'Mode de paiement non configuré.'];
    }

    // ═══════════════════════════════════════════════════════
    // ACTIVATION / RENOUVELLEMENT ABONNEMENT COIFFEUR
    // ═══════════════════════════════════════════════════════

    /**
     * Active ou renouvelle l'abonnement mensuel d'un coiffeur.
     *
     * @param int    $prestataire_id   ID coiffeur
     * @param float  $prix          Prix de l'abonnement (FCFA)
     * @param int    $duree_jours   Durée en jours (défaut: 30)
     * @param string $mode          'simulation' | 'kkiapay'
     * @return array ['success'=>bool, 'message'=>string, 'date_expiration'=>string]
     */
    public function activerAbonnement(int $prestataire_id, float $prix = 1500, int $duree_jours = 30, string $mode = self::MODE_SIMULATION): array
    {
        if ($mode === self::MODE_SIMULATION) {
            return $this->_simulerAbonnement($prestataire_id, $prix, $duree_jours);
        }

        // Futur : appel Kkiapay + webhook ici
        return ['success' => false, 'message' => 'Mode de paiement non configuré.'];
    }

    // ═══════════════════════════════════════════════════════
    // DÉBIT CLIENT (gel de fonds lors d'une réservation)
    // ═══════════════════════════════════════════════════════

    /**
     * Déduit un montant du solde client pour sécuriser une réservation.
     *
     * @param int   $client_id ID client
     * @param float $montant   Montant à geler
     * @param int   $id_rdv    ID rendez-vous
     * @return array ['success'=>bool, 'message'=>string, 'solde_restant'=>float]
     */
    public function gelerFonds(int $client_id, float $montant, int $id_rdv): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT solde FROM users WHERE id = ?");
            $stmt->execute([$client_id]);
            $solde = (float)$stmt->fetchColumn();

            if ($solde < $montant) {
                return [
                    'success' => false,
                    'message' => 'Solde insuffisant (' . number_format($solde, 0, ',', ' ') . ' FCFA disponible).',
                ];
            }

            $this->pdo->beginTransaction();
            $this->pdo->prepare("UPDATE users SET solde = solde - ? WHERE id = ?")
                      ->execute([$montant, $client_id]);
            $this->pdo->prepare(
                "INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif, date_creation)
                 VALUES (?, 'gel_rdv', ?, ?, NOW())"
            )->execute([$client_id, -$montant, "Gel fonds RDV #$id_rdv"]);
            $this->pdo->commit();

            return [
                'success'       => true,
                'message'       => number_format($montant, 0, ',', ' ') . ' FCFA gelés.',
                'solde_restant' => $solde - $montant,
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Erreur technique lors du gel des fonds.'];
        }
    }

    // ═══════════════════════════════════════════════════════
    // MÉTHODES INTERNES — SIMULATION
    // ═══════════════════════════════════════════════════════

    private function _simulerRecharge(int $id_user, float $montant, string $motif): array
    {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->prepare("UPDATE users SET solde = solde + ? WHERE id = ?")
                      ->execute([$montant, $id_user]);
            $this->pdo->prepare(
                "INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif, date_creation)
                 VALUES (?, 'recharge', ?, ?, NOW())"
            )->execute([$id_user, $montant, $motif . ' (simulation)']);
            $this->pdo->commit();

            $stmt = $this->pdo->prepare("SELECT solde FROM users WHERE id = ?");
            $stmt->execute([$id_user]);
            $nouveau_solde = (float)$stmt->fetchColumn();

            return [
                'success'       => true,
                'message'       => number_format($montant, 0, ',', ' ') . ' FCFA rechargés avec succès.',
                'nouveau_solde' => $nouveau_solde,
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Erreur technique lors de la recharge.'];
        }
    }

    private function _simulerAbonnement(int $prestataire_id, float $prix, int $duree_jours): array
    {
        try {
            $this->pdo->beginTransaction();

            // Date d'expiration = aujourd'hui + durée
            $nouvelle_expiration = date('Y-m-d', strtotime("+{$duree_jours} days"));

            $this->pdo->prepare(
                "UPDATE users SET abonnement_status = 1, date_expiration_abo = ? WHERE id = ?"
            )->execute([$nouvelle_expiration, $prestataire_id]);

            $this->pdo->prepare(
                "INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif, date_creation)
                 VALUES (?, 'abonnement', ?, ?, NOW())"
            )->execute([$prestataire_id, -$prix, "Abonnement mensuel (simulation) — expire le $nouvelle_expiration"]);

            $this->pdo->commit();

            // ── Notifier l'admin qu'un diplôme est à vérifier ──
            // Déclenché uniquement si le coiffeur a soumis un diplôme et n'est pas encore approuvé
            try {
                $stmt_check = $this->pdo->prepare(
                    "SELECT id, nom, prenom, diplome, is_approved FROM users WHERE id = ?"
                );
                $stmt_check->execute([$prestataire_id]);
                $coiffeur_data = $stmt_check->fetch();

                if ($coiffeur_data && !empty($coiffeur_data['diplome']) && !$coiffeur_data['is_approved']) {
                    // Récupérer les admins
                    $admins = $this->pdo->query(
                        "SELECT id FROM users WHERE role = 'admin' LIMIT 5"
                    )->fetchAll();

                    $nom_coiffeur = htmlspecialchars($coiffeur_data['prenom'] . ' ' . $coiffeur_data['nom']);
                    $msg_admin    = "💰 Paiement reçu — {$nom_coiffeur} a réglé son abonnement. "
                                  . "Un diplôme est en attente de vérification. "
                                  . "Veuillez valider son profil dans le tableau de bord admin.";

                    $stmt_notif = $this->pdo->prepare(
                        "INSERT INTO notifications (id_user, message, type, lu, date_notification)
                         VALUES (?, ?, 'info', 0, NOW())"
                    );
                    foreach ($admins as $admin) {
                        $stmt_notif->execute([$admin['id'], $msg_admin]);
                    }
                }
            } catch (\Exception $e) {
                // Notification non bloquante — ne pas faire échouer le paiement
            }

            return [
                'success'          => true,
                'message'          => 'Abonnement activé jusqu\'au ' . date('d/m/Y', strtotime($nouvelle_expiration)) . '. Votre diplôme est en cours de vérification par notre équipe.',
                'date_expiration'  => $nouvelle_expiration,
            ];
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            return ['success' => false, 'message' => 'Erreur technique lors de l\'activation.'];
        }
    }
}
