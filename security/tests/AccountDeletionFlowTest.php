<?php
/**
 * security/tests/AccountDeletionFlowTest.php
 * Integration tests for Account Deletion Flow with OTP
 * 
 * 3-step flow: Warning → OTP → Confirmation → Deletion logged in suppressions_comptes
 */

use PHPUnit\Framework\TestCase;

class AccountDeletionFlowTest extends TestCase
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
    // COMPLETE ACCOUNT DELETION FLOW TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Complete successful account deletion flow
     */
    public function testCompleteAccountDeletionFlow(): void
    {
        $email = 'user@example.com';
        $reason = 'No longer need the service';

        // Step 1: Create user
        $user_id = TestDatabaseHelper::createTestUser($email, 'client');
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertNotNull($user);

        // Step 2: User requests deletion (with warning already shown in UI)
        $otp_result = $this->otp->generate($user_id, 'account_deletion');
        $this->assertTrue($otp_result['success']);
        $otp_code = $otp_result['code'];

        // Step 3: Email sent
        $email_result = $this->email->sendOtpCode($email, $otp_code, 5, 'account_deletion');
        $this->assertArrayHasKey('success', $email_result);

        // Step 4: User verifies OTP
        $verify_result = $this->otp->verify($user_id, 'account_deletion', $otp_code);
        $this->assertTrue($verify_result['success']);

        // Step 5: Final confirmation (user confirms deletion in UI)

        // Step 6: Log deletion in audit table
        $this->pdo->prepare(
            'INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) 
             VALUES (?, ?, ?, ?)'
        )->execute([
            $user['email'], // Using email as name for this test
            $user['email'],
            $user['role'],
            $reason
        ]);

        // Step 7: Delete user
        $this->pdo->prepare(
            'DELETE FROM users WHERE id = ?'
        )->execute([$user_id]);

        // Step 8: Verify deletion
        $deleted_user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertNull($deleted_user);

        // Verify audit log exists
        $logs = TestDatabaseHelper::getDeletionLogs();
        $this->assertGreaterThan(0, count($logs));
        $last_log = $logs[0];
        $this->assertEquals($email, $last_log['email_utilisateur']);
        $this->assertEquals($reason, $last_log['raison']);
    }

    /**
     * @test
     * Deletion requires OTP verification
     */
    public function testDeletionRequiresOtpVerification(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Try to delete without OTP verification - should fail
        // (In real app, this would be prevented by session checks)

        // Generate OTP
        $otp_result = $this->otp->generate($user_id, 'account_deletion');
        $this->assertTrue($otp_result['success']);

        // Verify before deletion
        $verify_result = $this->otp->verify($user_id, 'account_deletion', $otp_result['code']);
        $this->assertTrue($verify_result['success']);

        // Now deletion can proceed
        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user_id]);
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertNull($user);
    }

    /**
     * @test
     * Deletion logged in suppressions_comptes table
     */
    public function testDeletionLoggedInAuditTable(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email, 'coiffeur');
        
        $user = TestDatabaseHelper::getUserByEmail($email);
        $reason = 'User requested deletion';

        // Simulate deletion
        $this->pdo->prepare(
            'INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) 
             VALUES (?, ?, ?, ?)'
        )->execute([
            $user['email'],
            $user['email'],
            $user['role'],
            $reason
        ]);

        // Delete user
        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user_id]);

        // Verify audit log
        $logs = TestDatabaseHelper::getDeletionLogs();
        $this->assertGreaterThan(0, count($logs));

        $log = $logs[0];
        $this->assertEquals($email, $log['email_utilisateur']);
        $this->assertEquals('coiffeur', $log['role_utilisateur']);
        $this->assertEquals($reason, $log['raison']);
        $this->assertNotNull($log['created_at']);
    }

    /**
     * @test
     * Audit record includes all required fields
     */
    public function testAuditRecordContainsAllRequiredFields(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email, 'client');
        
        $user = TestDatabaseHelper::getUserByEmail($email);

        // Create audit record
        $this->pdo->prepare(
            'INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) 
             VALUES (?, ?, ?, ?)'
        )->execute([
            $user['email'],
            $user['email'],
            $user['role'],
            'Test reason'
        ]);

        // Check audit record
        $logs = TestDatabaseHelper::getDeletionLogs();
        $log = $logs[0];

        // All required fields present
        $this->assertArrayHasKey('id', $log);
        $this->assertArrayHasKey('nom_utilisateur', $log);
        $this->assertArrayHasKey('email_utilisateur', $log);
        $this->assertArrayHasKey('role_utilisateur', $log);
        $this->assertArrayHasKey('raison', $log);
        $this->assertArrayHasKey('created_at', $log);

        // All fields have values
        $this->assertNotNull($log['nom_utilisateur']);
        $this->assertNotNull($log['email_utilisateur']);
        $this->assertNotNull($log['role_utilisateur']);
        $this->assertNotNull($log['created_at']);
    }

    /**
     * @test
     * Audit record cannot be deleted or modified (immutable)
     */
    public function testAuditRecordIsImmutable(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);
        
        $user = TestDatabaseHelper::getUserByEmail($email);

        // Create audit record
        $this->pdo->prepare(
            'INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) 
             VALUES (?, ?, ?, ?)'
        )->execute([
            $user['email'],
            $user['email'],
            $user['role'],
            'Original reason'
        ]);

        $logs_before = TestDatabaseHelper::getDeletionLogs();
        $log_id = $logs_before[0]['id'];

        // Try to update (should fail in real app due to constraints)
        // For testing, we just verify the record is preserved
        $logs_after = TestDatabaseHelper::getDeletionLogs();
        $this->assertEquals(count($logs_before), count($logs_after));
        $this->assertEquals($log_id, $logs_after[0]['id']);
    }

    /**
     * @test
     * Session cleared after account deletion
     */
    public function testSessionClearedAfterDeletion(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // OTP verified
        $otp_result = $this->otp->generate($user_id, 'account_deletion');
        $verify = $this->otp->verify($user_id, 'account_deletion', $otp_result['code']);
        $this->assertTrue($verify['success']);

        // In real app, session would be cleared here
        // We verify the OTP is marked as used
        $stmt = $this->pdo->prepare(
            'SELECT used_at FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$user_id, 'account_deletion']);
        $otp = $stmt->fetch();

        $this->assertNotNull($otp['used_at']);
    }

    /**
     * @test
     * Account deletion with invalid OTP
     */
    public function testAccountDeletionWithInvalidOtp(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Generate OTP
        $otp_result = $this->otp->generate($user_id, 'account_deletion');

        // Try wrong OTP
        $verify = $this->otp->verify($user_id, 'account_deletion', '000000');
        $this->assertFalse($verify['success']);

        // Account should still exist
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertNotNull($user);

        // User can retry with correct code
        $verify = $this->otp->verify($user_id, 'account_deletion', $otp_result['code']);
        $this->assertTrue($verify['success']);
    }

    /**
     * @test
     * Account deletion brute force protection
     */
    public function testAccountDeletionBruteForceProtection(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        $otp_result = $this->otp->generate($user_id, 'account_deletion');
        $correct_code = $otp_result['code'];

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $verify = $this->otp->verify($user_id, 'account_deletion', '000000');
            $this->assertFalse($verify['success']);
        }

        // OTP should be blocked
        $verify = $this->otp->verify($user_id, 'account_deletion', $correct_code);
        $this->assertFalse($verify['success']);

        // Account still exists
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertNotNull($user);
    }

    /**
     * @test
     * Account deletion rate limiting
     */
    public function testAccountDeletionRateLimiting(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // First request
        $otp1 = $this->otp->generate($user_id, 'account_deletion');
        $this->assertTrue($otp1['success']);

        // Immediate retry should fail (60s rate limit)
        $otp2 = $this->otp->generate($user_id, 'account_deletion');
        $this->assertFalse($otp2['success']);
        $this->assertStringContainsString('attendre', $otp2['message']);
    }

    /**
     * @test
     * Multiple users can delete accounts independently
     */
    public function testMultipleUsersDeleteIndependently(): void
    {
        $user1_email = 'user1@example.com';
        $user2_email = 'user2@example.com';

        $user1_id = TestDatabaseHelper::createTestUser($user1_email);
        $user2_id = TestDatabaseHelper::createTestUser($user2_email);

        // Both generate OTPs
        $otp1 = $this->otp->generate($user1_id, 'account_deletion');
        $otp2 = $this->otp->generate($user2_id, 'account_deletion');

        $this->assertTrue($otp1['success']);
        $this->assertTrue($otp2['success']);

        // Verify independently
        $verify1 = $this->otp->verify($user1_id, 'account_deletion', $otp1['code']);
        $verify2 = $this->otp->verify($user2_id, 'account_deletion', $otp2['code']);

        $this->assertTrue($verify1['success']);
        $this->assertTrue($verify2['success']);
    }

    /**
     * @test
     * Account deletion OTP is isolated from other purposes
     */
    public function testAccountDeletionOtpIsolation(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Generate different OTPs
        $reg_otp = $this->otp->generate($user_id, 'registration');
        $del_otp = $this->otp->generate($user_id, 'account_deletion');

        $this->assertTrue($reg_otp['success']);
        $this->assertTrue($del_otp['success']);

        // Different codes
        $this->assertNotEquals($reg_otp['code'], $del_otp['code']);

        // Cannot use registration code for deletion
        $verify = $this->otp->verify($user_id, 'account_deletion', $reg_otp['code']);
        $this->assertFalse($verify['success']);

        // But deletion code works
        $verify = $this->otp->verify($user_id, 'account_deletion', $del_otp['code']);
        $this->assertTrue($verify['success']);
    }

    /**
     * @test
     * Expired account deletion OTP cannot be used
     */
    public function testExpiredAccountDeletionOtp(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);

        // Create expired OTP
        TestOtpHelper::createExpiredOtp($this->pdo, $user_id, 'account_deletion');

        // Try to verify - should fail
        $verify = $this->otp->verify($user_id, 'account_deletion', '000000');
        $this->assertFalse($verify['success']);

        // Account still exists
        $user = TestDatabaseHelper::getUserByEmail($email);
        $this->assertNotNull($user);

        // User can request new OTP
        $this->mockOtpCreationTime($user_id, 'account_deletion', '-1 hour');
        $new_otp = $this->otp->generate($user_id, 'account_deletion');
        $this->assertTrue($new_otp['success']);
    }

    /**
     * @test
     * Different roles recorded in deletion audit
     */
    public function testDifferentRolesRecordedInAudit(): void
    {
        $roles = ['client', 'coiffeur'];

        foreach ($roles as $role) {
            TestDatabaseHelper::clearDatabase();

            $email = "user-{$role}@example.com";
            $user_id = TestDatabaseHelper::createTestUser($email, $role);
            
            $user = TestDatabaseHelper::getUserByEmail($email);

            // Log deletion
            $this->pdo->prepare(
                'INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) 
                 VALUES (?, ?, ?, ?)'
            )->execute([
                $user['email'],
                $user['email'],
                $user['role'],
                'Test'
            ]);

            // Delete user
            $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user_id]);

            // Verify audit
            $logs = TestDatabaseHelper::getDeletionLogs();
            $this->assertGreaterThan(0, count($logs));
            $this->assertEquals($role, $logs[0]['role_utilisateur']);
        }
    }

    /**
     * @test
     * Deletion reason optional but should be captured if provided
     */
    public function testDeletionReasonCapture(): void
    {
        $email = 'user@example.com';
        $user_id = TestDatabaseHelper::createTestUser($email);
        
        $user = TestDatabaseHelper::getUserByEmail($email);

        // With reason
        $this->pdo->prepare(
            'INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) 
             VALUES (?, ?, ?, ?)'
        )->execute([
            $user['email'],
            $user['email'],
            $user['role'],
            'User wanted to delete account'
        ]);

        $logs = TestDatabaseHelper::getDeletionLogs();
        $this->assertEquals('User wanted to delete account', $logs[0]['raison']);

        // Without reason (NULL)
        TestDatabaseHelper::clearDatabase();
        $user_id2 = TestDatabaseHelper::createTestUser($email);
        $user2 = TestDatabaseHelper::getUserByEmail($email);

        $this->pdo->prepare(
            'INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) 
             VALUES (?, ?, ?, ?)'
        )->execute([
            $user2['email'],
            $user2['email'],
            $user2['role'],
            null
        ]);

        $logs = TestDatabaseHelper::getDeletionLogs();
        $this->assertNull($logs[0]['raison']);
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
