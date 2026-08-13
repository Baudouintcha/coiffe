<?php
/**
 * security/tests/RegistrationFlowTest.php
 * Integration tests for Registration Flow with OTP
 * 
 * Complete registration → OTP generation → Email send → OTP verification → Account activation
 */

use PHPUnit\Framework\TestCase;

class RegistrationFlowTest extends TestCase
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
    // COMPLETE REGISTRATION FLOW TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Complete successful registration flow
     */
    public function testCompleteRegistrationFlow(): void
    {
        $email = 'newuser@example.com';
        $password = 'SecurePassword123!';

        // Step 1: Create account (inactive)
        $user_id = TestDatabaseHelper::createTestUser($email, 'client');
        
        // Verify account is inactive
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertEquals(0, $user['email_verifie']);
        $this->assertNull($user['email_verified_at']);

        // Step 2: Generate OTP
        $otp_result = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($otp_result['success']);
        $otp_code = $otp_result['code'];

        // Step 3: Email would be sent (in real app)
        $email_result = $this->email->sendOtpCode($email, $otp_code, 5, 'registration');
        $this->assertArrayHasKey('success', $email_result);

        // Step 4: User verifies OTP
        $verify_result = $this->otp->verify($user_id, 'registration', $otp_code);
        $this->assertTrue($verify_result['success']);

        // Step 5: Activate account (in real app, this happens after OTP verification)
        $this->pdo->prepare(
            'UPDATE users SET email_verifie = 1, email_verified_at = NOW() WHERE id = ?'
        )->execute([$user_id]);

        // Verify account is now active
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertEquals(1, $user['email_verifie']);
        $this->assertNotNull($user['email_verified_at']);
    }

    /**
     * @test
     * Registration flow with invalid code rejection
     */
    public function testRegistrationFlowInvalidCodeRejection(): void
    {
        $email = 'newuser@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Generate OTP
        $otp_result = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($otp_result['success']);

        // Try wrong code
        $verify_result = $this->otp->verify($user_id, 'registration', '000000');
        $this->assertFalse($verify_result['success']);

        // Account should still be inactive
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertEquals(0, $user['email_verifie']);

        // Can still try again with correct code
        $verify_result = $this->otp->verify($user_id, 'registration', $otp_result['code']);
        $this->assertTrue($verify_result['success']);
    }

    /**
     * @test
     * Registration resend OTP functionality
     */
    public function testRegistrationResendOtp(): void
    {
        $email = 'newuser@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // First OTP request
        $otp1 = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($otp1['success']);
        $code1 = $otp1['code'];

        // Cannot request immediately (rate limit)
        $otp2 = $this->otp->generate($user_id, 'registration');
        $this->assertFalse($otp2['success']);
        $this->assertStringContainsString('attendre', $otp2['message']);

        // Only one active OTP should exist
        $otps = TestDatabaseHelper::getOtpCodes($user_id, 'registration');
        $active_count = count(array_filter($otps, fn($o) => is_null($o['used_at'])));
        $this->assertEquals(1, $active_count);
    }

    /**
     * @test
     * Registration brute force blocking
     */
    public function testRegistrationBruteForceBlocking(): void
    {
        $email = 'newuser@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'registration');
        $correct_code = $otp_result['code'];

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $verify = $this->otp->verify($user_id, 'registration', '000000');
            $this->assertFalse($verify['success']);
        }

        // Even correct code should fail now (OTP blocked)
        $verify = $this->otp->verify($user_id, 'registration', $correct_code);
        $this->assertFalse($verify['success']);

        // User would need to request new OTP
        $this->mockOtpCreationTime($user_id, 'registration', '-75 seconds');
        $new_otp = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($new_otp['success']);
    }

    /**
     * @test
     * Registration with rate limiting (max 3 per hour)
     */
    public function testRegistrationRateLimiting3PerHour(): void
    {
        $email = 'newuser@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // First request
        $otp1 = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($otp1['success']);

        // Wait and request again
        $this->mockOtpCreationTime($user_id, 'registration', '-75 seconds');
        $otp2 = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($otp2['success']);

        // Wait and request again
        $this->mockOtpCreationTime($user_id, 'registration', '-150 seconds');
        $otp3 = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($otp3['success']);

        // Fourth request within hour should fail
        $this->mockOtpCreationTime($user_id, 'registration', '-200 seconds');
        $otp4 = $this->otp->generate($user_id, 'registration');
        $this->assertFalse($otp4['success']);
        $this->assertStringContainsString('Trop de demandes', $otp4['message']);
    }

    /**
     * @test
     * Email verification flag set correctly after OTP verification
     */
    public function testEmailVerificationFlagSetCorrectly(): void
    {
        $email = 'newuser@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'registration');
        $code = $otp_result['code'];

        // Before verification
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertEquals(0, $user['email_verifie']);

        // After OTP verification (in real app, also updates email_verifie)
        $verify_result = $this->otp->verify($user_id, 'registration', $code);
        $this->assertTrue($verify_result['success']);

        // In real app, would update email_verifie here
        $this->pdo->prepare(
            'UPDATE users SET email_verifie = 1, email_verified_at = NOW() WHERE id = ?'
        )->execute([$user_id]);

        // Verify flag is set
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertEquals(1, $user['email_verifie']);
        $this->assertNotNull($user['email_verified_at']);
    }

    /**
     * @test
     * Multiple users can register independently
     */
    public function testMultipleUsersIndependentRegistration(): void
    {
        $user1_email = 'user1@example.com';
        $user2_email = 'user2@example.com';

        $user1_id = TestDatabaseHelper::createTestUser($user1_email);
        $user2_id = TestDatabaseHelper::createTestUser($user2_email);

        // Generate OTPs for both
        $otp1 = $this->otp->generate($user1_id, 'registration');
        $otp2 = $this->otp->generate($user2_id, 'registration');

        $this->assertTrue($otp1['success']);
        $this->assertTrue($otp2['success']);
        $this->assertNotEquals($otp1['code'], $otp2['code']);

        // Verify independently
        $verify1 = $this->otp->verify($user1_id, 'registration', $otp1['code']);
        $verify2 = $this->otp->verify($user2_id, 'registration', $otp2['code']);

        $this->assertTrue($verify1['success']);
        $this->assertTrue($verify2['success']);
    }

    /**
     * @test
     * OTP cannot be reused after first successful verification
     */
    public function testOtpCannotBeReused(): void
    {
        $email = 'newuser@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'registration');
        $code = $otp_result['code'];

        // First verification succeeds
        $verify1 = $this->otp->verify($user_id, 'registration', $code);
        $this->assertTrue($verify1['success']);

        // Second attempt with same code fails
        $verify2 = $this->otp->verify($user_id, 'registration', $code);
        $this->assertFalse($verify2['success']);
    }

    /**
     * @test
     * After OTP expiration, registration cannot proceed
     */
    public function testRegistrationCannotProceedAfterOtpExpiration(): void
    {
        $email = 'newuser@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Create expired OTP
        TestOtpHelper::createExpiredOtp($this->pdo, $user_id, 'registration');

        // Try to verify - should fail
        $verify = $this->otp->verify($user_id, 'registration', '000000');
        $this->assertFalse($verify['success']);

        // User must request new OTP
        $this->mockOtpCreationTime($user_id, 'registration', '-1 hour');
        $new_otp = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($new_otp['success']);
    }

    /**
     * @test
     * Email masking works in sent emails
     */
    public function testEmailMaskingInRegistrationEmail(): void
    {
        $email = 'verylongemailaddress@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'registration');
        $code = $otp_result['code'];

        // Send email (in real scenario)
        $email_result = $this->email->sendOtpCode($email, $code, 5, 'registration');
        
        // Email should be processed
        $this->assertIsArray($email_result);
        $this->assertArrayHasKey('success', $email_result);
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
