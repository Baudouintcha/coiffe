<?php
/**
 * security/IdentityVerificationService.php — Service de vérification d'identité
 * Vérifie que l'utilisateur qui effectue une opération est bien celui qu'il prétend être
 * 
 * Vérifie :
 * - Existence de l'utilisateur
 * - Rôle cohérent
 * - Profil prestataire (si applicable)
 * - État du compte
 * - Cohérence user_id/profil
 */

class IdentityVerificationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Vérifie l'identité complète d'un utilisateur pour une opération sensible
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $expected_role Rôle attendu ('client', 'prestataire', 'admin')
     * @return array ['verified'=>bool, 'errors'=>array, 'user'=>array]
     */
    public function verify(int $user_id, string $expected_role = ''): array
    {
        $errors = [];
        $user = null;

        try {
            // 1. Vérifier l'existence de l'utilisateur
            $stmt = $this->pdo->prepare(
                "SELECT id, nom, prenom, email, role, statut, is_approved FROM users WHERE id = ?"
            );
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $errors[] = 'Utilisateur non trouvé';
                return [
                    'verified' => false,
                    'errors' => $errors,
                    'user' => null,
                ];
            }

            // 2. Vérifier le rôle s'il est spécifié
            if (!empty($expected_role) && $user['role'] !== $expected_role) {
                $errors[] = "Rôle attendu : $expected_role, rôle trouvé : {$user['role']}";
            }

            // 3. Vérifier l'état du compte
            if ($user['statut'] === 'banni') {
                $errors[] = 'Compte banni';
            } elseif ($user['statut'] === 'inactif') {
                $errors[] = 'Compte inactif';
            }

            // 4. Pour les prestataires : vérifier le profil et l'approbation
            if ($user['role'] === 'prestataire') {
                $stmt_profile = $this->pdo->prepare(
                    "SELECT id, user_id, abonnement_status FROM profils_prestataires WHERE user_id = ?"
                );
                $stmt_profile->execute([$user_id]);
                $profile = $stmt_profile->fetch(PDO::FETCH_ASSOC);

                if (!$profile) {
                    $errors[] = 'Profil prestataire non trouvé';
                }

                // Vérifier que le profil appartient bien à cet utilisateur
                if ($profile && $profile['user_id'] != $user_id) {
                    $errors[] = 'Incohérence profil/utilisateur';
                }

                // Vérifier que le prestataire a un abonnement actif pour les opérations sensibles
                if ($profile && $profile['abonnement_status'] !== 'actif') {
                    $errors[] = 'Abonnement non actif. Statut : ' . $profile['abonnement_status'];
                }
            }

            return [
                'verified' => empty($errors),
                'errors' => $errors,
                'user' => $user,
            ];

        } catch (Exception $e) {
            error_log("IdentityVerificationService::verify() erreur: " . $e->getMessage());
            return [
                'verified' => false,
                'errors' => ['Erreur technique lors de la vérification'],
                'user' => $user,
            ];
        }
    }

    /**
     * Vérifie que l'utilisateur en session correspond à celui en paramètre
     * Utile pour éviter les opérations sur le compte d'un autre utilisateur
     * 
     * @param int $session_user_id ID de l'utilisateur en session
     * @param int $target_user_id ID de l'utilisateur visé par l'opération
     * @return bool Vrai si les ID correspondent ou si la session est admin
     */
    public function isAuthorized(int $session_user_id, int $target_user_id): bool
    {
        try {
            // Si les IDs correspondent, autoriser
            if ($session_user_id === $target_user_id) {
                return true;
            }

            // Vérifier si l'utilisateur en session est admin
            $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$session_user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user && $user['role'] === 'admin';
        } catch (Exception $e) {
            error_log("IdentityVerificationService::isAuthorized() erreur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie que c'est un prestataire avec un abonnement actif
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array ['is_active_provider'=>bool, 'reason'=>string]
     */
    public function isActiveProvider(int $user_id): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT u.role, u.statut, p.abonnement_status, p.date_expiration_abo
                 FROM users u
                 LEFT JOIN profils_prestataires p ON u.id = p.user_id
                 WHERE u.id = ?"
            );
            $stmt->execute([$user_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$data) {
                return [
                    'is_active_provider' => false,
                    'reason' => 'Utilisateur non trouvé',
                ];
            }

            if ($data['role'] !== 'prestataire') {
                return [
                    'is_active_provider' => false,
                    'reason' => 'Pas un prestataire',
                ];
            }

            if ($data['statut'] !== 'actif') {
                return [
                    'is_active_provider' => false,
                    'reason' => 'Compte inactif',
                ];
            }

            if ($data['abonnement_status'] !== 'actif') {
                return [
                    'is_active_provider' => false,
                    'reason' => 'Abonnement non actif',
                ];
            }

            $expiration = strtotime($data['date_expiration_abo']);
            if ($expiration < time()) {
                return [
                    'is_active_provider' => false,
                    'reason' => 'Abonnement expiré',
                ];
            }

            return [
                'is_active_provider' => true,
                'reason' => 'Prestataire actif',
            ];
        } catch (Exception $e) {
            error_log("IdentityVerificationService::isActiveProvider() erreur: " . $e->getMessage());
            return [
                'is_active_provider' => false,
                'reason' => 'Erreur technique',
            ];
        }
    }
}
?>
