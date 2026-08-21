<?php
/**
 * src/Services/OtpService.php — Service OTP Centralisé
 * Coiffe Chez Toi — Système maison de vérification par OTP
 *
 * Responsabilités:
 * - Générer des codes OTP sécurisés (6 chiffres)
 * - Hasher et stocker les codes
 * - Vérifier les codes
 * - Gérer l'expiration (5 minutes)
 * - Implémenter rate limiting
 * - Invalider après usage
 * - Supporter plusieurs purposes
 *
 * SÉCURITÉ:
 * - Les codes sont HASHÉS en base (jamais en clair)
 * - Utilisation de password_hash() et password_verify()
 * - Protection brute-force (max 5 tentatives)
 * - Rate limiting côté serveur
 * - Invalidation automatique après 5 minutes
 *
 * USAGE:
 *   $otp = new OtpService($pdo);
 *   $result = $otp->generate($user_id, 'registration');
 *   if ($result['success']) {
 *       // $result['code'] contient le code à envoyer par email
 *       // Ensuite, utiliser verify()
 *   }
 */

class OtpService
{
    private PDO $pdo;

    // Configuration
    private const OTP_VALIDITY = 300;        // 5 minutes en secondes
    private const MAX_ATTEMPTS = 5;          // Max tentatives incorrectes
    private const RATE_LIMIT_SECONDS = 60;   // Min entre demandes d'OTP
    private const MAX_REQUESTS_PER_HOUR = 3; // Max demandes/heure

    // Purpose autorisés
    private const VALID_PURPOSES = [
        'registration',
        'password_reset',
        'email_change',
        'account_deletion',
        'domizi_action',
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ═══════════════════════════════════════════════════════
    // GÉNÉRER UN OTP
    // ═══════════════════════════════════════════════════════

    /**
     * Génère et stocke un nouvel OTP pour un utilisateur.
     *
     * @param int    $user_id ID de l'utilisateur
     * @param string $purpose Type d'OTP (registration, password_reset, etc.)
     * @return array [
     *     'success' => bool,
     *     'code' => string (6 chiffres, si success=true),
     *     'message' => string,
     *     'expires_in' => int (secondes)
     * ]
     */
    public function generate(int $user_id, string $purpose): array
    {
        // Validation du purpose
        if (!in_array($purpose, self::VALID_PURPOSES, true)) {
            return [
                'success' => false,
                'message' => "Purpose invalid: {$purpose}",
            ];
        }

        // Vérifier que l'utilisateur existe
        $user_check = $this->pdo->prepare("SELECT id, email FROM users WHERE id = ?");
        $user_check->execute([$user_id]);
        $user = $user_check->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utilisateur introuvable.',
            ];
        }

        // Vérifier le rate limiting
        $can_request = $this->canRequest($user_id, $purpose);
        if (!$can_request['allowed']) {
            return [
                'success' => false,
                'message' => $can_request['message'],
                'retry_after' => $can_request['retry_after'] ?? 0,
            ];
        }

        try {
            // Générer le code OTP (6 chiffres)
            $otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Hasher le code pour stockage
            $otp_hash = password_hash($otp_code, PASSWORD_DEFAULT);

            // Calculer la date d'expiration
            $expires_at = date('Y-m-d H:i:s', time() + self::OTP_VALIDITY);

            // Invalider tout OTP existant pour ce user + purpose
            $this->pdo->prepare(
                "UPDATE otp_codes SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND purpose = ? AND used_at IS NULL"
            )->execute([$user_id, $purpose]);

            // Insérer le nouvel OTP
            $this->pdo->prepare(
                "INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at, tentatives)
                 VALUES (?, ?, ?, ?, 0)"
            )->execute([$user_id, $purpose, $otp_hash, $expires_at]);

            // En production, ne JAMAIS logger le code en clair
            // Log seulement: "OTP généré pour user_id X, purpose Y"
            error_log("OTP generated for user_id={$user_id}, purpose={$purpose}");

            return [
                'success' => true,
                'code' => $otp_code,
                'expires_in' => self::OTP_VALIDITY,
                'user_email' => $user['email'],
            ];

        } catch (Exception $e) {
            error_log("OtpService::generate error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la génération du code.',
            ];
        }
    }

    // ═══════════════════════════════════════════════════════
    // VÉRIFIER UN OTP
    // ═══════════════════════════════════════════════════════

    /**
     * Vérifie un code OTP saisi par l'utilisateur.
     *
     * @param int    $user_id ID de l'utilisateur
     * @param string $purpose Type d'OTP
     * @param string $code    Code saisi par l'utilisateur (6 chiffres)
     * @return array ['success' => bool, 'message' => string]
     */
    public function verify(int $user_id, string $purpose, string $code): array
    {
        // Validation basique
        if (!preg_match('/^\d{6}$/', $code)) {
            return [
                'success' => false,
                'message' => 'Code invalide ou expiré.',
            ];
        }

        if (!in_array($purpose, self::VALID_PURPOSES, true)) {
            return [
                'success' => false,
                'message' => 'Purpose invalide.',
            ];
        }

        try {
            // Chercher l'OTP actif
            $stmt = $this->pdo->prepare(
                "SELECT id, code_hash, expires_at, tentatives, used_at
                 FROM otp_codes
                 WHERE user_id = ? AND purpose = ? AND used_at IS NULL
                 LIMIT 1"
            );
            $stmt->execute([$user_id, $purpose]);
            $otp = $stmt->fetch();

            if (!$otp) {
                return [
                    'success' => false,
                    'message' => 'Code invalide ou expiré.',
                ];
            }

            // Vérifier l'expiration
            if (strtotime($otp['expires_at']) < time()) {
                // OTP expiré — l'invalider
                $this->pdo->prepare(
                    "UPDATE otp_codes SET used_at = CURRENT_TIMESTAMP WHERE id = ?"
                )->execute([$otp['id']]);

                return [
                    'success' => false,
                    'message' => 'Code invalide ou expiré.',
                ];
            }

            // Vérifier le nombre de tentatives
            if ($otp['tentatives'] >= self::MAX_ATTEMPTS) {
                // OTP bloqué après 5 tentatives
                return [
                    'success' => false,
                    'message' => 'Code invalide ou expiré.',
                ];
            }

            // Vérifier le code contre le hash
            if (!password_verify($code, $otp['code_hash'])) {
                // Code incorrect — incrémenter les tentatives
                $new_tentatives = $otp['tentatives'] + 1;
                $this->pdo->prepare(
                    "UPDATE otp_codes SET tentatives = ? WHERE id = ?"
                )->execute([$new_tentatives, $otp['id']]);

                // Si >= 5 tentatives, invalider l'OTP
                if ($new_tentatives >= self::MAX_ATTEMPTS) {
                    $this->pdo->prepare(
                        "UPDATE otp_codes SET used_at = CURRENT_TIMESTAMP WHERE id = ?"
                    )->execute([$otp['id']]);
                }

                return [
                    'success' => false,
                    'message' => 'Code invalide ou expiré.',
                ];
            }

            // ✅ Code correct — marquer comme utilisé
            $this->pdo->prepare(
                "UPDATE otp_codes SET used_at = CURRENT_TIMESTAMP WHERE id = ?"
            )->execute([$otp['id']]);

            // Log du succès (sans exposer le code)
            error_log("OTP verified successfully for user_id={$user_id}, purpose={$purpose}");

            return [
                'success' => true,
                'message' => 'Code vérifié avec succès.',
            ];

        } catch (Exception $e) {
            error_log("OtpService::verify error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Code invalide ou expiré.',
            ];
        }
    }

    // ═══════════════════════════════════════════════════════
    // RATE LIMITING
    // ═══════════════════════════════════════════════════════

    /**
     * Vérifier si l'utilisateur peut demander un nouvel OTP.
     * Rate limiting:
     * - 1 OTP toutes les 60 secondes
     * - Max 3 demandes par heure
     *
     * @return array ['allowed' => bool, 'message' => string, 'retry_after' => int?]
     */
    public function canRequest(int $user_id, string $purpose): array
    {
        try {
            // Chercher le dernier OTP généré (peu importe l'état)
            $stmt = $this->pdo->prepare(
                "SELECT date_demande FROM otp_codes
                 WHERE user_id = ? AND purpose = ?
                 ORDER BY date_demande DESC LIMIT 1"
            );
            $stmt->execute([$user_id, $purpose]);
            $last_otp = $stmt->fetch();

            if ($last_otp) {
                $last_created = strtotime($last_otp['date_demande']);
                $now = time();
                $seconds_elapsed = $now - $last_created;

                // Rule 1: Min 60 secondes entre demandes
                if ($seconds_elapsed < self::RATE_LIMIT_SECONDS) {
                    $retry_after = self::RATE_LIMIT_SECONDS - $seconds_elapsed;
                    return [
                        'allowed' => false,
                        'message' => "Veuillez attendre {$retry_after} secondes avant de renvoyer le code.",
                        'retry_after' => $retry_after,
                    ];
                }
            }

            // Rule 2: Max 3 demandes par heure
            $one_hour_ago = date('Y-m-d H:i:s', time() - 3600);
            $count_stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM otp_codes
                 WHERE user_id = ? AND purpose = ? AND date_demande > ?"
            );
            $count_stmt->execute([$user_id, $purpose, $one_hour_ago]);
            $count = (int)$count_stmt->fetchColumn();

            if ($count >= self::MAX_REQUESTS_PER_HOUR) {
                return [
                    'allowed' => false,
                    'message' => 'Trop de demandes. Veuillez réessayer dans une heure.',
                ];
            }

            return ['allowed' => true];

        } catch (Exception $e) {
            error_log("OtpService::canRequest error: " . $e->getMessage());
            // En cas d'erreur, autoriser (ne pas bloquer l'utilisateur)
            return ['allowed' => true];
        }
    }

    // ═══════════════════════════════════════════════════════
    // INVALIDATE MANUALLY
    // ═══════════════════════════════════════════════════════

    /**
     * Invalider manuellement un OTP (ex: après suppression de compte).
     */
    public function invalidate(int $user_id, string $purpose): array
    {
        try {
            $this->pdo->prepare(
                "UPDATE otp_codes SET used_at = CURRENT_TIMESTAMP
                 WHERE user_id = ? AND purpose = ? AND used_at IS NULL"
            )->execute([$user_id, $purpose]);

            return ['success' => true];
        } catch (Exception $e) {
            error_log("OtpService::invalidate error: " . $e->getMessage());
            return ['success' => false];
        }
    }

    // ═══════════════════════════════════════════════════════
    // NETTOYAGE DES ANCIENS OTP (À appeler via cron)
    // ═══════════════════════════════════════════════════════

    /**
     * Supprimer les OTP expirés (plus vieux que 24h).
     * À appeler via un cron job toutes les heures.
     */
    public static function cleanupExpiredOtps(PDO $pdo): array
    {
        try {
            $twenty_four_hours_ago = date('Y-m-d H:i:s', time() - 86400);
            $stmt = $pdo->prepare(
                "DELETE FROM otp_codes WHERE expires_at < ?"
            );
            $stmt->execute([$twenty_four_hours_ago]);
            $deleted = $stmt->rowCount();

            error_log("Cleanup: {$deleted} expired OTP codes deleted");

            return ['success' => true, 'deleted' => $deleted];
        } catch (Exception $e) {
            error_log("OtpService::cleanupExpiredOtps error: " . $e->getMessage());
            return ['success' => false];
        }
    }

    // ═══════════════════════════════════════════════════════
    // GET ACTIVE OTP INFO (for debugging/admin)
    // ═══════════════════════════════════════════════════════

    /**
     * Récupérer les infos d'un OTP actif (sans le code ou hash).
     * USAGE ADMIN UNIQUEMENT.
     */
    public function getActiveOtpInfo(int $user_id, string $purpose): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, expires_at, tentatives, date_demande
                 FROM otp_codes
                 WHERE user_id = ? AND purpose = ? AND used_at IS NULL
                 LIMIT 1"
            );
            $stmt->execute([$user_id, $purpose]);
            $otp = $stmt->fetch();

            if (!$otp) {
                return ['found' => false];
            }

            return [
                'found' => true,
                'expires_at' => $otp['expires_at'],
                'tentatives' => $otp['tentatives'],
                'is_expired' => strtotime($otp['expires_at']) < time(),
                'is_blocked' => $otp['tentatives'] >= self::MAX_ATTEMPTS,
                'date_demande' => $otp['date_demande'],
            ];
        } catch (Exception $e) {
            error_log("OtpService::getActiveOtpInfo error: " . $e->getMessage());
            return ['found' => false];
        }
    }
}
