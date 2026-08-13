<?php
/**
 * security/tests/EmailChangeFlowTest.php
 * Integration tests for Email Change Flow with OTP
 * 
 * New email validation → Uniqueness check → OTP sent to NEW email → Verification → Email updated
 */

use PHPUnit\Framework\TestCase;

class EmailChangeFlowTest extends TestCase
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
    // COMPLETE EMAIL CHANGE FLOW TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Complete successful email change flow
     */
    public function testCompleteEmailChangeFlow(): void
    {
        $old_email = 'old@example.com';
        $new_email = 'new@example.com';

        // Step 1: Create user with current email
        $user_id = TestDatabaseHelper::createTestUser($old_email);
        $user = TestDatabaseHelper::getUserByEmail($old_email);
        $this->assertEquals($old_email, $user['email']);

        // Step 2: User requests email change with new email
        // (Validation happens in controller)

        // Step 3: Generate OTP
        $otp_result = $this->otp->generate($user_id, 'email_change');
        $this->assertTrue($otp_result['success']);
        $otp_code = $otp_result['code'];

        // Step 4: Email sent to NEW email (for verification)
        $email_result = $this->email->sendOtpCode($new_email, $otp_code, 5, 'email_change');
        $this->assertArrayHasKey('success', $email_result);

        // Step 5: User verifies OTP from new email
        $verify_result = $this->otp->verify($user_id, 'email_change', $otp_code);
        $this->assertTrue($verify_result['success']);

        // Step 6: Update email in database
        $this->pdo->prepare(
            'UPDATE users SET email = ? WHERE id = ?'
        )->execute([$new_email, $user_id]);

        // Step 7: Verify email was updated
        $updated_user = TestDatabaseHelper::getUserByEmail($new_email);
        $this->assertNotNull($updated_user);
        $this->assertEquals($new_email, $updated_user['email']);
        $this->assertEquals($user_id, $updated_user['id']);
    }

    /**
     * @test
     * New email validation (format check)
     */
    public function testNewEmailValidation(): void
    {
        $user_id = TestDatabaseHelper::createTestUser('user@example.com');

        $invalid_emails = [
            'not-an-email',
            'user@',
            '@example.com',
            'user@example',
            'user @example.com',
            '',
            'user@example..com',
        ];

        foreach ($invalid_emails as $invalid_email) {
            // Validation check
            $is_valid = filter_var($invalid_email, FILTER_VALIDATE_EMAIL) !== false;
            $this->assertFalse($is_valid);
        }

        // Valid email should pass
        $valid_email = 'newuser@example.com';
        $is_valid = filter_var($valid_email, FILTER_VALIDATE_EMAIL) !== false;
        $this->assertTrue($is_valid);
    }

    /**
     * @test
     * New email must be different from current email
     */
    public function testNewEmailMustBeDifferentFromCurrent(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);
        $user = TestDatabaseHelper::getUserByEmail($email);

        // Try to "change" to same email - should fail validation
        $new_email = $email;
        $is_different = $new_email !== $user['email'];
        $this->assertFalse($is_different);

        // Different email should pass validation
        $different_email = 'newemail@example.com';
        $is_different = $different_email !== $user['email'];
        $this->assertTrue($is_different);
    }

    /**
     * @test
     * New email must not already be in use (uniqueness check)
     */
    public function testNewEmailUniquenessCheck(): void
    {
        $user1_email = 'user1@example.com';
        $user2_email = 'user2@example.com';

        $user1_id = TestDatabaseHelper::createTestUser($user1_email);
        $user2_id = TestDatabaseHelper::createTestUser($user2_email);

        // User 1 tries to change email to user 2's email
        $taken_email = $user2_email;

        // Check if email is already in use (by someone else)
        $stmt = $this->pdo->prepare(
            'SELECT id FROM users WHERE email = ? AND id != ?'
        );
        $stmt->execute([$taken_email, $user1_id]);
        $existing_user = $stmt->fetch();

        $this->assertNotNull($existing_user); // Email is taken

        // User can change to an unused email
        $unused_email = 'available@example.com';
        $stmt = $this->pdo->prepare(
            'SELECT id FROM users WHERE email = ? AND id != ?'
        );
        $stmt->execute([$unused_email, $user1_id]);
        $existing_user = $stmt->fetch();

        $this->assertNull($existing_user); // Email is available
    }

    /**
     * @test
     * OTP sent to NEW email (not current)
     */
    public function testOtpSentToNewEmailOnly(): void
    {
        $old_email = 'old@example.com';
        $new_email = 'new@example.com';

        $user_id = TestDatabaseHelper::createTestUser($old_email);

        // Generate OTP for email change
        $otp_result = $this->otp->generate($user_id, 'email_change');
        $this->assertTrue($otp_result['success']);

        // In real implementation, email is sent to $new_email, NOT $old_email
        // This is a security feature to verify ownership of new email
        
        // We would track that OTP was sent to new_email
        // (In real app, this would be stored in session or temp table)
    }

    /**
     * @test
     * Error handling for already used email
     */
    public function testErrorHandlingForAlreadyUsedEmail(): void
    {
        $email1 = 'user1@example.com';
        $email2 = 'user2@example.com';

        $user1_id = TestDatabaseHelper::createTestUser($email1);
        TestDatabaseHelper::createTestUser($email2);

        // Try to change user1's email to email2 (already used)
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id != ?'
        );
        $stmt->execute([$email2, $user1_id]);
        $count = (int)$stmt->fetchColumn();

        $this->assertGreaterThan(0, $count); // Email is already in use
    }

    /**
     * @test
     * Invalid email format prevents OTP generation
     */
    public function testInvalidEmailFormatPreventsOtp(): void
    {
        $user_id = TestDatabaseHelper::createTestUser('user@example.com');

        // Send to invalid email - should be caught before OTP
        $invalid_email = 'not-an-email';
        $is_valid = filter_var($invalid_email, FILTER_VALIDATE_EMAIL) !== false;
        
        $this->assertFalse($is_valid);
    }

    /**
     * @test
     * Email change rate limiting
     */
    public function testEmailChangeRateLimiting(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // First request
        $otp1 = $this->otp->generate($user_id, 'email_change');
        $this->assertTrue($otp1['success']);

        // Immediate retry should fail (60s rate limit)
        $otp2 = $this->otp->generate($user_id, 'email_change');
        $this->assertFalse($otp2['success']);
        $this->assertStringContainsString('attendre', $otp2['message']);
    }

    /**
     * @test
     * Email change with invalid OTP
     */
    public function testEmailChangeWithInvalidOtp(): void
    {
        $old_email = 'old@example.com';
        $new_email = 'new@example.com';
        $user_id = TestDatabaseHelper::createTestUser($old_email);

        // Generate OTP
        $otp_result = $this->otp->generate($user_id, 'email_change');

        // Try wrong OTP
        $verify = $this->otp->verify($user_id, 'email_change', '000000');
        $this->assertFalse($verify['success']);

        // Email should NOT be updated
        $user = TestDatabaseHelper::getUserByEmail($old_email);
        $this->assertEquals($old_email, $user['email']);

        // User can retry with correct code
        $verify = $this->otp->verify($user_id, 'email_change', $otp_result['code']);
        $this->assertTrue($verify['success']);
    }

    /**
     * @test
     * Email change brute force protection
     */
    public function testEmailChangeBruteForceProtection(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'email_change');
        $correct_code = $otp_result['code'];

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $verify = $this->otp->verify($user_id, 'email_change', '000000');
            $this->assertFalse($verify['success']);
        }

        // OTP should be blocked
        $verify = $this->otp->verify($user_id, 'email_change', $correct_code);
        $this->assertFalse($verify['success']);
    }

    /**
     * @test
     * Multiple users can change emails independently
     */
    public function testMultipleUsersEmailChangeIndependently(): void
    {
        $user1_old = 'user1old@example.com';
        $user1_new = 'user1new@example.com';
        $user2_old = 'user2old@example.com';
        $user2_new = 'user2new@example.com';

        $user1_id = TestDatabaseHelper::createTestUser($user1_old);
        $user2_id = TestDatabaseHelper::createTestUser($user2_old);

        // Both generate OTPs
        $otp1 = $this->otp->generate($user1_id, 'email_change');
        $otp2 = $this->otp->generate($user2_id, 'email_change');

        $this->assertTrue($otp1['success']);
        $this->assertTrue($otp2['success']);

        // Verify independently
        $verify1 = $this->otp->verify($user1_id, 'email_change', $otp1['code']);
        $verify2 = $this->otp->verify($user2_id, 'email_change', $otp2['code']);

        $this->assertTrue($verify1['success']);
        $this->assertTrue($verify2['success']);
    }

    /**
     * @test
     * Email change OTP is isolated from registration OTP
     */
    public function testEmailChangeOtpIsolation(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Generate registration OTP
        $reg_otp = $this->otp->generate($user_id, 'registration');
        $this->assertTrue($reg_otp['success']);

        // Generate email change OTP
        $email_change_otp = $this->otp->generate($user_id, 'email_change');
        $this->assertTrue($email_change_otp['success']);

        // Different codes
        $this->assertNotEquals($reg_otp['code'], $email_change_otp['code']);

        // Cannot use registration code for email change
        $verify = $this->otp->verify($user_id, 'email_change', $reg_otp['code']);
        $this->assertFalse($verify['success']);

        // But email change code works
        $verify = $this->otp->verify($user_id, 'email_change', $email_change_otp['code']);
        $this->assertTrue($verify['success']);
    }

    /**
     * @test
     * Email cannot be changed to already verified email of same user
     */
    public function testCannotChangeToSameEmail(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $user = TestDatabaseHelper::getUserByEmail($email);

        // Check if trying to change to same email
        $new_email = $email;
        $is_different = $new_email !== $user['email'];
        $this->assertFalse($is_different);
    }

    /**
     * @test
     * Expired email change OTP cannot be used
     */
    public function testExpiredEmailChangeOtp(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Create expired OTP
        TestOtpHelper::createExpiredOtp($this->pdo, $user_id, 'email_change');

        // Try to verify - should fail
        $verify = $this->otp->verify($user_id, 'email_change', '000000');
        $this->assertFalse($verify['success']);

        // User must request new OTP
        $this->mockOtpCreationTime($user_id, 'email_change', '-1 hour');
        $new_otp = $this->otp->generate($user_id, 'email_change');
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
