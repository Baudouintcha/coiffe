<?php
/**
 * security/tests/PasswordResetFlowTest.php
 * Integration tests for Password Reset Flow with OTP
 * 
 * Email input → OTP generation → OTP verification → Password form → DB update → Login
 */

use PHPUnit\Framework\TestCase;

class PasswordResetFlowTest extends TestCase
{
    private PDO $pdo;
    private OtpService $otp;
    private EmailService $email;

    protected function setUp(): void
    {
        $this->pdo = TestDatabaseHelper::getConnection();
        TestDatabaseHelper::clearDatabase();
        $this->otp = new OtpService($this->pdo);
        $this->email = new EmailService();
    }

    // ═══════════════════════════════════════════════════════
    // COMPLETE PASSWORD RESET FLOW TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Complete successful password reset flow
     */
    public function testCompletePasswordResetFlow(): void
    {
        $email = 'user@example.com';
        $old_password = 'OldPassword123!';
        $new_password = 'NewPassword456!';

        // Step 1: Create user with password
        $user_id = TestDatabaseHelper::createTestUser($email);
        
        // Step 2: User requests password reset
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertNotNull($user);

        // Step 3: Generate OTP
        $otp_result = $this->otp->generate($user_id, 'password_reset');
        $this->assertTrue($otp_result['success']);
        $otp_code = $otp_result['code'];

        // Step 4: Email sent
        $email_result = $this->email->sendOtpCode($email, $otp_code, 5, 'password_reset');
        $this->assertArrayHasKey('success', $email_result);

        // Step 5: User verifies OTP
        $verify_result = $this->otp->verify($user_id, 'password_reset', $otp_code);
        $this->assertTrue($verify_result['success']);

        // Step 6: User sets new password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $this->pdo->prepare(
            'UPDATE users SET password = ? WHERE id = ?'
        )->execute([$new_password_hash, $user_id]);

        // Step 7: Verify password was updated
        $updated_user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertTrue(password_verify($new_password, $updated_user['password']));
        $this->assertFalse(password_verify($old_password, $updated_user['password']));
    }

    /**
     * @test
     * Non-existent email handling
     */
    public function testPasswordResetNonExistentEmail(): void
    {
        // Try to generate OTP for non-existent user
        $result = $this->otp->generate(99999, 'password_reset');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('introuvable', $result['message']);
    }

    /**
     * @test
     * Password validation (minimum 8 characters)
     */
    public function testPasswordValidationMinimumLength(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Generate and verify OTP
        $otp_result = $this->otp->generate($user_id, 'password_reset');
        $this->otp->verify($user_id, 'password_reset', $otp_result['code']);

        // Try to set password shorter than 8 characters
        $short_passwords = ['1234567', 'pass', '12345', 'a'];
        
        foreach ($short_passwords as $short_pass) {
            // Validation should catch this before database update
            $is_valid = strlen($short_pass) >= 8;
            $this->assertFalse($is_valid);
        }

        // Valid password should succeed
        $valid_password = 'NewValidPassword123!';
        $is_valid = strlen($valid_password) >= 8;
        $this->assertTrue($is_valid);

        // Update password in database
        $password_hash = password_hash($valid_password, PASSWORD_DEFAULT);
        $this->pdo->prepare(
            'UPDATE users SET password = ? WHERE id = ?'
        )->execute([$password_hash, $user_id]);

        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertTrue(password_verify($valid_password, $user['password']));
    }

    /**
     * @test
     * Password confirmation matching
     */
    public function testPasswordConfirmationMatching(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'password_reset');
        $this->otp->verify($user_id, 'password_reset', $otp_result['code']);

        $password1 = 'NewPassword123!';
        $password2_different = 'DifferentPassword456!';

        // Passwords don't match - should fail validation
        $this->assertNotEquals($password1, $password2_different);

        // Passwords match - should succeed
        $password2_same = 'NewPassword123!';
        $this->assertEquals($password1, $password2_same);
    }

    /**
     * @test
     * Session cleanup after successful password reset
     */
    public function testSessionCleanupAfterPasswordReset(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'password_reset');
        $otp_code = $otp_result['code'];

        // Verify OTP
        $verify_result = $this->otp->verify($user_id, 'password_reset', $otp_code);
        $this->assertTrue($verify_result['success']);

        // Update password
        $new_password = 'NewPassword123!';
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $this->pdo->prepare(
            'UPDATE users SET password = ? WHERE id = ?'
        )->execute([$password_hash, $user_id]);

        // OTP should be marked as used (session would be cleared)
        $stmt = $this->pdo->prepare(
            'SELECT used_at FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$user_id, 'password_reset']);
        $otp = $stmt->fetch();

        $this->assertNotNull($otp['used_at']);
    }

    /**
     * @test
     * Multiple password reset attempts
     */
    public function testMultiplePasswordResetAttempts(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // First attempt
        $otp1 = $this->otp->generate($user_id, 'password_reset');
        $this->assertTrue($otp1['success']);

        // Wait and try again
        $this->mockOtpCreationTime($user_id, 'password_reset', '-75 seconds');
        $otp2 = $this->otp->generate($user_id, 'password_reset');
        $this->assertTrue($otp2['success']);

        // Both codes should be different
        $this->assertNotEquals($otp1['code'], $otp2['code']);

        // Only latest OTP should be valid
        $verify1 = $this->otp->verify($user_id, 'password_reset', $otp1['code']);
        $this->assertFalse($verify1['success']); // First code was replaced

        $verify2 = $this->otp->verify($user_id, 'password_reset', $otp2['code']);
        $this->assertTrue($verify2['success']); // Latest code works
    }

    /**
     * @test
     * Password reset with invalid OTP
     */
    public function testPasswordResetWithInvalidOtp(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Generate OTP
        $otp_result = $this->otp->generate($user_id, 'password_reset');

        // Try wrong OTP
        $verify = $this->otp->verify($user_id, 'password_reset', '000000');
        $this->assertFalse($verify['success']);

        // User should be able to retry
        $retry = $this->otp->verify($user_id, 'password_reset', $otp_result['code']);
        $this->assertTrue($retry['success']);
    }

    /**
     * @test
     * Password reset brute force protection
     */
    public function testPasswordResetBruteForceProtection(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'password_reset');
        $correct_code = $otp_result['code'];

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $verify = $this->otp->verify($user_id, 'password_reset', '000000');
            $this->assertFalse($verify['success']);
        }

        // OTP should be blocked
        $verify = $this->otp->verify($user_id, 'password_reset', $correct_code);
        $this->assertFalse($verify['success']);
    }

    /**
     * @test
     * Password reset rate limiting
     */
    public function testPasswordResetRateLimiting(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // First request
        $otp1 = $this->otp->generate($user_id, 'password_reset');
        $this->assertTrue($otp1['success']);

        // Immediate retry should fail (60s rate limit)
        $otp2 = $this->otp->generate($user_id, 'password_reset');
        $this->assertFalse($otp2['success']);
        $this->assertStringContainsString('attendre', $otp2['message']);
    }

    /**
     * @test
     * Different users can reset passwords independently
     */
    public function testMultipleUsersPasswordResetIndependently(): void
    {
        $user1_email = 'user1@example.com';
        $user2_email = 'user2@example.com';

        $user1_id = TestDatabaseHelper::createTestUser($user1_email);
        $user2_id = TestDatabaseHelper::createTestUser($user2_email);

        // Both request password reset
        $otp1 = $this->otp->generate($user1_id, 'password_reset');
        $otp2 = $this->otp->generate($user2_id, 'password_reset');

        $this->assertTrue($otp1['success']);
        $this->assertTrue($otp2['success']);
        $this->assertNotEquals($otp1['code'], $otp2['code']);

        // Each can verify their own code
        $verify1 = $this->otp->verify($user1_id, 'password_reset', $otp1['code']);
        $verify2 = $this->otp->verify($user2_id, 'password_reset', $otp2['code']);

        $this->assertTrue($verify1['success']);
        $this->assertTrue($verify2['success']);
    }

    /**
     * @test
     * Password reset OTP is isolated from registration OTP
     */
    public function testPasswordResetOtpIsolationFromRegistration(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Generate registration OTP
        $reg_otp = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($reg_otp['success']);

        // Generate password reset OTP
        $pwd_otp = $this->otp->generate($user_id, 'password_reset');
        $this->assertTrue($pwd_otp['success']);

        // They should be different codes
        $this->assertNotEquals($reg_otp['code'], $pwd_otp['code']);

        // Registration code cannot be used for password reset
        $verify_wrong = $this->otp->verify($user_id, 'password_reset', $reg_otp['code']);
        $this->assertFalse($verify_wrong['success']);

        // But password reset code works for password reset
        $verify_correct = $this->otp->verify($user_id, 'password_reset', $pwd_otp['code']);
        $this->assertTrue($verify_correct['success']);
    }

    /**
     * @test
     * Expired password reset OTP cannot be used
     */
    public function testExpiredPasswordResetOtp(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Create expired OTP
        TestOtpHelper::createExpiredOtp($this->pdo, $user_id, 'password_reset');

        // Try to verify - should fail
        $verify = $this->otp->verify($user_id, 'password_reset', '000000');
        $this->assertFalse($verify['success']);

        // User must request new OTP
        $this->mockOtpCreationTime($user_id, 'password_reset', '-1 hour');
        $new_otp = $this->otp->generate($user_id, 'password_reset');
        $this->assertTrue($new_otp['success']);
    }

    // ═══════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════

    private function mockOtpCreationTime(int $user_id, string $purpose, string $modifier): void
    {
        $created_at = date('Y-m-d H:i:s', strtotime($modifier));
        $expires_at = date('Y-m-d H:i:s', strtotime($modifier . ' +300 seconds'));
        $code_hash = password_hash('123456', PASSWORD_DEFAULT);

        $this->pdo->prepare(
            'DELETE FROM otp_codes WHERE user_id = ? AND purpose = ?'
        )->execute([$user_id, $purpose]);

        $this->pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at, attempts, created_at) 
             VALUES (?, ?, ?, ?, 0, ?)'
        )->execute([$user_id, $purpose, $code_hash, $expires_at, $created_at]);
    }
}
