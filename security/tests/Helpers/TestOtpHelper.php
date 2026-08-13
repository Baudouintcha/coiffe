<?php
/**
 * security/tests/Helpers/TestOtpHelper.php
 * Helper for OTP testing utilities
 */

class TestOtpHelper
{
    /**
     * Extract OTP code from database for a user and purpose (for testing resend)
     */
    public static function getLastOtpCode(PDO $pdo, int $user_id, string $purpose): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id, code_hash, expires_at, attempts, used_at FROM otp_codes 
             WHERE user_id = ? AND purpose = ? 
             ORDER BY created_at DESC LIMIT 1'
        );
        $stmt->execute([$user_id, $purpose]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Find a valid OTP code by brute force (for testing only)
     * Tries all possible 6-digit combinations
     */
    public static function findValidOtpCode(PDO $pdo, int $user_id, string $purpose): ?string
    {
        $otp_record = self::getLastOtpCode($pdo, $user_id, $purpose);
        if (!$otp_record) {
            return null;
        }

        // Try all 6-digit combinations
        for ($i = 0; $i <= 999999; $i++) {
            $code = str_pad($i, 6, '0', STR_PAD_LEFT);
            if (password_verify($code, $otp_record['code_hash'])) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Simulate user entering OTP code (for integration tests)
     * Returns the code used
     */
    public static function simulateOtpInput(OtpService $otp, int $user_id, string $purpose): array
    {
        // Generate an OTP
        $result = $otp->generate($user_id, $purpose);
        if (!$result['success']) {
            return ['success' => false, 'code' => null];
        }

        return [
            'success' => true,
            'code' => $result['code']
        ];
    }

    /**
     * Create an expired OTP for testing
     */
    public static function createExpiredOtp(PDO $pdo, int $user_id, string $purpose): void
    {
        $pdo->prepare(
            'DELETE FROM otp_codes WHERE user_id = ? AND purpose = ?'
        )->execute([$user_id, $purpose]);

        $expired_time = date('Y-m-d H:i:s', time() - 600); // 10 minutes ago
        $code_hash = password_hash('000000', PASSWORD_DEFAULT);

        $pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at, attempts) 
             VALUES (?, ?, ?, ?, 0)'
        )->execute([$user_id, $purpose, $code_hash, $expired_time]);
    }

    /**
     * Create a blocked OTP (5 attempts reached)
     */
    public static function createBlockedOtp(PDO $pdo, int $user_id, string $purpose): void
    {
        $pdo->prepare(
            'DELETE FROM otp_codes WHERE user_id = ? AND purpose = ?'
        )->execute([$user_id, $purpose]);

        $expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes from now
        $code_hash = password_hash('123456', PASSWORD_DEFAULT);

        $pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at, attempts, used_at) 
             VALUES (?, ?, ?, ?, 5, NOW())'
        )->execute([$user_id, $purpose, $code_hash, $expires_at]);
    }

    /**
     * Get OTP info from database
     */
    public static function getOtpInfo(PDO $pdo, int $user_id, string $purpose): ?array
    {
        $stmt = $pdo->prepare(
            'SELECT id, code_hash, expires_at, attempts, used_at, created_at 
             FROM otp_codes 
             WHERE user_id = ? AND purpose = ? AND used_at IS NULL'
        );
        $stmt->execute([$user_id, $purpose]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Verify code is hashed (not in plain text)
     */
    public static function verifyCodeIsHashed(string $hash): bool
    {
        // Password hashes typically start with $2y$, $2a$, $2b$, etc. (bcrypt)
        return preg_match('/^\$2[aby]\$\d{2}\$/', $hash) === 1;
    }

    /**
     * Mark OTP as used for testing
     */
    public static function markOtpAsUsed(PDO $pdo, int $user_id, string $purpose): void
    {
        $pdo->prepare(
            'UPDATE otp_codes SET used_at = NOW() 
             WHERE user_id = ? AND purpose = ? AND used_at IS NULL'
        )->execute([$user_id, $purpose]);
    }

    /**
     * Simulate multiple failed attempts
     */
    public static function simulateFailedAttempts(PDO $pdo, int $user_id, string $purpose, int $count): void
    {
        $pdo->prepare(
            'UPDATE otp_codes SET attempts = ? 
             WHERE user_id = ? AND purpose = ? AND used_at IS NULL'
        )->execute([$count, $user_id, $purpose]);
    }

    /**
     * Check if OTP is expired
     */
    public static function isOtpExpired(PDO $pdo, int $user_id, string $purpose): bool
    {
        $stmt = $pdo->prepare(
            'SELECT expires_at FROM otp_codes 
             WHERE user_id = ? AND purpose = ? AND used_at IS NULL'
        );
        $stmt->execute([$user_id, $purpose]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp) {
            return false;
        }

        return strtotime($otp['expires_at']) < time();
    }

    /**
     * Get time until OTP expiration (in seconds)
     */
    public static function getOtpTimeToExpiration(PDO $pdo, int $user_id, string $purpose): ?int
    {
        $stmt = $pdo->prepare(
            'SELECT expires_at FROM otp_codes 
             WHERE user_id = ? AND purpose = ? AND used_at IS NULL'
        );
        $stmt->execute([$user_id, $purpose]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp) {
            return null;
        }

        return max(0, strtotime($otp['expires_at']) - time());
    }
}
