<?php
/**
 * security/tests/OtpServiceTest.php
 * Unit tests for OtpService
 * 
 * Test Coverage:
 * - OTP Code Generation (6 digits, hashing, storage)
 * - OTP Verification (valid codes, invalid codes, expired codes)
 * - Brute Force Protection (5 attempts max)
 * - Rate Limiting (1/60s, max 3/hour)
 * - Code Invalidation
 * - Purpose Isolation
 */

use PHPUnit\Framework\TestCase;

class OtpServiceTest extends TestCase
{
    private PDO $pdo;
    private OtpService $otp;
    private int $test_user_id;

    protected function setUp(): void
    {
        // Get test database
        $this->pdo = TestDatabaseHelper::getConnection();
        TestDatabaseHelper::clearDatabase();

        // Create OtpService instance
        $this->otp = new OtpService($this->pdo);

        // Create a test user
        $this->test_user_id = TestDatabaseHelper::createTestUser('otp-test@example.com');
    }

    // ═══════════════════════════════════════════════════════
    // GENERATION TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * OTP generation should return success with a 6-digit code
     */
    public function testOtpGenerationSuccess(): void
    {
        $result = $this->otp->generate($this->test_user_id, 'registration');

        $this->assertTrue($result['success']);
        $this->assertIsString($result['code']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $result['code']);
        $this->assertEquals(300, $result['expires_in']); // 5 minutes
    }

    /**
     * @test
     * OTP code should be stored as hash, not plain text
     */
    public function testOtpCodeIsHashed(): void
    {
        $result = $this->otp->generate($this->test_user_id, 'registration');
        $code = $result['code'];

        // Get the hash from database
        $stmt = $this->pdo->prepare(
            'SELECT code_hash FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$this->test_user_id, 'registration']);
        $otp = $stmt->fetch();

        $this->assertNotNull($otp);
        $this->assertNotEquals($code, $otp['code_hash']);
        // Verify it's a bcrypt hash
        $this->assertTrue(TestOtpHelper::verifyCodeIsHashed($otp['code_hash']));
        // Verify password_verify works
        $this->assertTrue(password_verify($code, $otp['code_hash']));
    }

    /**
     * @test
     * OTP should have correct expiration time (5 minutes)
     */
    public function testOtpExpirationTime(): void
    {
        $before = time();
        $result = $this->otp->generate($this->test_user_id, 'password_reset');
        $after = time();

        // Get the expiration from database
        $stmt = $this->pdo->prepare(
            'SELECT expires_at FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$this->test_user_id, 'password_reset']);
        $otp = $stmt->fetch();

        $expires_timestamp = strtotime($otp['expires_at']);
        $expected_min = $before + 300;
        $expected_max = $after + 300;

        $this->assertGreaterThanOrEqual($expected_min, $expires_timestamp);
        $this->assertLessThanOrEqual($expected_max, $expires_timestamp);
    }

    /**
     * @test
     * OTP generation should replace previous OTP for same user+purpose
     */
    public function testOtpReplacementForSamePurpose(): void
    {
        // Generate first OTP
        $result1 = $this->otp->generate($this->test_user_id, 'registration');
        $code1 = $result1['code'];

        // Generate second OTP
        $result2 = $this->otp->generate($this->test_user_id, 'registration');
        $code2 = $result2['code'];

        // Codes should be different
        $this->assertNotEquals($code1, $code2);

        // Only one active OTP should exist
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM otp_codes 
             WHERE user_id = ? AND purpose = ? AND used_at IS NULL'
        );
        $stmt->execute([$this->test_user_id, 'registration']);
        $count = (int)$stmt->fetchColumn();

        $this->assertEquals(1, $count);
    }

    /**
     * @test
     * OTP generation should fail for invalid purpose
     */
    public function testOtpGenerationFailsForInvalidPurpose(): void
    {
        $result = $this->otp->generate($this->test_user_id, 'invalid_purpose');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Purpose invalid', $result['message']);
    }

    /**
     * @test
     * OTP generation should fail for non-existent user
     */
    public function testOtpGenerationFailsForNonExistentUser(): void
    {
        $result = $this->otp->generate(99999, 'registration');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('introuvable', $result['message']);
    }

    // ═══════════════════════════════════════════════════════
    // VERIFICATION TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Valid OTP code should verify successfully
     */
    public function testVerifyValidOtpCode(): void
    {
        $result = $this->otp->generate($this->test_user_id, 'registration');
        $code = $result['code'];

        $verify_result = $this->otp->verify($this->test_user_id, 'registration', $code);

        $this->assertTrue($verify_result['success']);
        $this->assertStringContainsString('succès', $verify_result['message']);
    }

    /**
     * @test
     * Invalid OTP code should fail verification
     */
    public function testVerifyInvalidOtpCode(): void
    {
        $this->otp->generate($this->test_user_id, 'registration');

        $verify_result = $this->otp->verify($this->test_user_id, 'registration', '000000');

        $this->assertFalse($verify_result['success']);
        $this->assertStringContainsString('invalide ou expiré', $verify_result['message']);
    }

    /**
     * @test
     * Verification should fail for non-existent OTP
     */
    public function testVerifyNonExistentOtp(): void
    {
        $verify_result = $this->otp->verify($this->test_user_id, 'registration', '123456');

        $this->assertFalse($verify_result['success']);
    }

    /**
     * @test
     * Expired OTP should fail verification
     */
    public function testVerifyExpiredOtp(): void
    {
        // Create expired OTP
        TestOtpHelper::createExpiredOtp($this->pdo, $this->test_user_id, 'registration');

        $verify_result = $this->otp->verify($this->test_user_id, 'registration', '000000');

        $this->assertFalse($verify_result['success']);
        $this->assertStringContainsString('invalide ou expiré', $verify_result['message']);
    }

    /**
     * @test
     * OTP should be marked as used after successful verification
     */
    public function testOtpMarkedAsUsedAfterVerification(): void
    {
        $result = $this->otp->generate($this->test_user_id, 'registration');
        $code = $result['code'];

        $this->otp->verify($this->test_user_id, 'registration', $code);

        // Check that OTP is marked as used
        $stmt = $this->pdo->prepare(
            'SELECT used_at FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$this->test_user_id, 'registration']);
        $otp = $stmt->fetch();

        $this->assertNotNull($otp['used_at']);
    }

    /**
     * @test
     * OTP code format validation (must be 6 digits)
     */
    public function testOtpCodeFormatValidation(): void
    {
        // Generate an OTP first
        $this->otp->generate($this->test_user_id, 'registration');

        // Test various invalid formats
        $invalid_codes = ['12345', '1234567', 'abcdef', '12-34-56', '', 'code'];

        foreach ($invalid_codes as $invalid_code) {
            $result = $this->otp->verify($this->test_user_id, 'registration', $invalid_code);
            $this->assertFalse($result['success']);
        }
    }

    // ═══════════════════════════════════════════════════════
    // BRUTE FORCE PROTECTION TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * After 5 failed attempts, OTP should be invalidated
     */
    public function testBruteForceBlockingAfter5Attempts(): void
    {
        $result = $this->otp->generate($this->test_user_id, 'registration');
        $correct_code = $result['code'];

        // Simulate 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $verify = $this->otp->verify($this->test_user_id, 'registration', '000000');
            $this->assertFalse($verify['success']);
        }

        // Even with correct code, should fail now
        $verify = $this->otp->verify($this->test_user_id, 'registration', $correct_code);
        $this->assertFalse($verify['success']);
        $this->assertStringContainsString('invalide ou expiré', $verify['message']);

        // Check that OTP is marked as used (blocked)
        $stmt = $this->pdo->prepare(
            'SELECT attempts, used_at FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$this->test_user_id, 'registration']);
        $otp = $stmt->fetch();

        $this->assertEquals(5, $otp['attempts']);
        $this->assertNotNull($otp['used_at']);
    }

    /**
     * @test
     * Attempts counter should increment on failed verification
     */
    public function testAttemptsCounterIncrement(): void
    {
        $this->otp->generate($this->test_user_id, 'registration');

        // First attempt fails
        $this->otp->verify($this->test_user_id, 'registration', '000000');
        $info1 = TestOtpHelper::getOtpInfo($this->pdo, $this->test_user_id, 'registration');
        $this->assertEquals(1, $info1['attempts']);

        // Second attempt fails
        $this->otp->verify($this->test_user_id, 'registration', '111111');
        $info2 = TestOtpHelper::getOtpInfo($this->pdo, $this->test_user_id, 'registration');
        $this->assertEquals(2, $info2['attempts']);

        // Third attempt fails
        $this->otp->verify($this->test_user_id, 'registration', '222222');
        $info3 = TestOtpHelper::getOtpInfo($this->pdo, $this->test_user_id, 'registration');
        $this->assertEquals(3, $info3['attempts']);
    }

    /**
     * @test
     * Brute force error message should be generic (no info leak)
     */
    public function testBruteForceErrorMessageIsGeneric(): void
    {
        $result = $this->otp->generate($this->test_user_id, 'registration');

        // Multiple wrong attempts
        for ($i = 0; $i < 5; $i++) {
            $verify = $this->otp->verify($this->test_user_id, 'registration', '999999');
            $this->assertFalse($verify['success']);
            // Should always be the same generic message
            $this->assertEquals('Code invalide ou expiré.', $verify['message']);
        }
    }

    // ═══════════════════════════════════════════════════════
    // RATE LIMITING TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Rate limiting: Cannot request OTP twice within 60 seconds
     */
    public function testRateLimitingWithin60Seconds(): void
    {
        // First request should succeed
        $result1 = $this->otp->generate($this->test_user_id, 'registration');
        $this->assertTrue($result1['success']);

        // Second request within 60 seconds should fail
        $result2 = $this->otp->generate($this->test_user_id, 'registration');
        $this->assertFalse($result2['success']);
        $this->assertStringContainsString('attendre', $result2['message']);
        $this->assertIsInt($result2['retry_after']);
        $this->assertGreaterThan(0, $result2['retry_after']);
        $this->assertLessThanOrEqual(60, $result2['retry_after']);
    }

    /**
     * @test
     * Rate limiting: Max 3 requests per hour per user+purpose
     */
    public function testRateLimitingMax3PerHour(): void
    {
        // Create 3 OTPs with different purposes (same user, different purpose) - should all succeed
        $result1 = $this->otp->generate($this->test_user_id, 'registration');
        $this->assertTrue($result1['success']);

        // Create another user to test per-user rate limit
        $user2_id = TestDatabaseHelper::createTestUser('test2@example.com');

        // Wait to bypass 60s limit (mock by directly inserting)
        $this->mockOtpCreationTime($user2_id, 'registration', '-75 seconds');
        $result2 = $this->otp->generate($user2_id, 'registration');
        $this->assertTrue($result2['success']);

        $this->mockOtpCreationTime($user2_id, 'registration', '-150 seconds');
        $result3 = $this->otp->generate($user2_id, 'registration');
        $this->assertTrue($result3['success']);

        // Fourth request should fail (rate limit)
        $this->mockOtpCreationTime($user2_id, 'registration', '-200 seconds');
        $result4 = $this->otp->generate($user2_id, 'registration');
        $this->assertFalse($result4['success']);
        $this->assertStringContainsString('Trop de demandes', $result4['message']);
    }

    /**
     * @test
     * Rate limiting should be per user+purpose combination
     */
    public function testRateLimitingPerUserPurposeCombination(): void
    {
        // Generate OTP for registration
        $result1 = $this->otp->generate($this->test_user_id, 'registration');
        $this->assertTrue($result1['success']);

        // Should still be able to generate for different purpose immediately
        $result2 = $this->otp->generate($this->test_user_id, 'password_reset');
        $this->assertTrue($result2['success']);

        // But not same purpose again
        $result3 = $this->otp->generate($this->test_user_id, 'registration');
        $this->assertFalse($result3['success']);
    }

    // ═══════════════════════════════════════════════════════
    // PURPOSE ISOLATION TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * OTP purpose must match for verification
     */
    public function testPurposeIsolation(): void
    {
        $result = $this->otp->generate($this->test_user_id, 'registration');
        $code = $result['code'];

        // Try to verify with different purpose
        $verify = $this->otp->verify($this->test_user_id, 'password_reset', $code);
        $this->assertFalse($verify['success']);

        // Correct purpose should work
        $verify = $this->otp->verify($this->test_user_id, 'registration', $code);
        $this->assertTrue($verify['success']);
    }

    /**
     * @test
     * Each purpose should have independent OTP
     */
    public function testMultiplePurposesHaveIndependentOtp(): void
    {
        $code1 = $this->otp->generate($this->test_user_id, 'registration')['code'];
        $code2 = $this->otp->generate($this->test_user_id, 'password_reset')['code'];

        $this->assertNotEquals($code1, $code2);

        // Both should be in database
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM otp_codes WHERE user_id = ? AND (purpose = ? OR purpose = ?)'
        );
        $stmt->execute([$this->test_user_id, 'registration', 'password_reset']);
        $count = (int)$stmt->fetchColumn();

        $this->assertGreaterThanOrEqual(2, $count);
    }

    // ═══════════════════════════════════════════════════════
    // INVALIDATION TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Manual invalidation should mark OTP as used
     */
    public function testManualInvalidation(): void
    {
        $this->otp->generate($this->test_user_id, 'registration');

        $result = $this->otp->invalidate($this->test_user_id, 'registration');
        $this->assertTrue($result['success']);

        // OTP should now fail verification
        $verify = $this->otp->verify($this->test_user_id, 'registration', '123456');
        $this->assertFalse($verify['success']);
    }

    // ═══════════════════════════════════════════════════════
    // CAN REQUEST TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * canRequest should return allowed=true for first request
     */
    public function testCanRequestFirstTime(): void
    {
        $user3_id = TestDatabaseHelper::createTestUser('user3@example.com');

        $result = $this->otp->canRequest($user3_id, 'registration');
        $this->assertTrue($result['allowed']);
    }

    /**
     * @test
     * getActiveOtpInfo should return correct info (for admin use)
     */
    public function testGetActiveOtpInfo(): void
    {
        $this->otp->generate($this->test_user_id, 'registration');

        $info = $this->otp->getActiveOtpInfo($this->test_user_id, 'registration');

        $this->assertTrue($info['found']);
        $this->assertFalse($info['is_expired']);
        $this->assertFalse($info['is_blocked']);
        $this->assertIsString($info['expires_at']);
        $this->assertIsInt($info['attempts']);
    }

    // ═══════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════

    private function mockOtpCreationTime(int $user_id, string $purpose, string $modifier): void
    {
        // First delete existing OTP
        $this->pdo->prepare(
            'DELETE FROM otp_codes WHERE user_id = ? AND purpose = ?'
        )->execute([$user_id, $purpose]);

        // Create new one with modified timestamp
        $created_at = date('Y-m-d H:i:s', strtotime($modifier));
        $expires_at = date('Y-m-d H:i:s', strtotime($modifier . ' +300 seconds'));
        $code_hash = password_hash('123456', PASSWORD_DEFAULT);

        $this->pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at, attempts, created_at) 
             VALUES (?, ?, ?, ?, 0, ?)'
        )->execute([$user_id, $purpose, $code_hash, $expires_at, $created_at]);
    }
}
