<?php
/**
 * security/tests/DatabaseTest.php
 * Database schema and integrity tests for OTP Central System
 * 
 * - otp_codes table structure
 * - Unique constraint on (user_id, purpose)
 * - Foreign key to users table
 * - Indexes on user_id, expires_at
 * - suppressions_comptes table
 */

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = TestDatabaseHelper::getConnection();
    }

    // ═══════════════════════════════════════════════════════
    // OTP_CODES TABLE STRUCTURE TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * otp_codes table exists with correct schema
     */
    public function testOtpCodesTableExists(): void
    {
        $stmt = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='otp_codes'");
        $stmt->execute();
        $table = $stmt->fetch();

        $this->assertNotNull($table);
        $this->assertEquals('otp_codes', $table['name']);
    }

    /**
     * @test
     * otp_codes table has all required columns
     */
    public function testOtpCodesTableColumns(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(otp_codes)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $column_names = array_column($columns, 'name');

        // Required columns
        $required_columns = [
            'id',
            'user_id',
            'purpose',
            'code_hash',
            'expires_at',
            'attempts',
            'used_at',
            'created_at'
        ];

        foreach ($required_columns as $required) {
            $this->assertContains($required, $column_names);
        }
    }

    /**
     * @test
     * id column is primary key and auto-increment
     */
    public function testIdIsPrimaryKey(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(otp_codes)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $id_column = array_filter($columns, fn($c) => $c['name'] === 'id');
        $id_column = reset($id_column);

        $this->assertNotNull($id_column);
        $this->assertEquals(1, $id_column['pk']); // pk=1 means primary key
    }

    /**
     * @test
     * Column types are correct
     */
    public function testColumnTypes(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(otp_codes)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $column_types = [];
        foreach ($columns as $col) {
            $column_types[$col['name']] = $col['type'];
        }

        // Check data types (SQLite types)
        $this->assertStringContainsString('INT', $column_types['id']);
        $this->assertStringContainsString('INT', $column_types['user_id']);
        $this->assertStringContainsString('VARCHAR', $column_types['purpose']);
        $this->assertStringContainsString('VARCHAR', $column_types['code_hash']);
        $this->assertStringContainsString('DATETIME', $column_types['expires_at']);
        $this->assertStringContainsString('TINYINT', $column_types['attempts']);
        $this->assertStringContainsString('DATETIME', $column_types['used_at']);
        $this->assertStringContainsString('DATETIME', $column_types['created_at']);
    }

    // ═══════════════════════════════════════════════════════
    // CONSTRAINT TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Unique constraint on (user_id, purpose)
     */
    public function testUniqueConstraintOnUserPurpose(): void
    {
        TestDatabaseHelper::clearDatabase();
        $user_id = TestDatabaseHelper::createTestUser('test@example.com');

        $code_hash1 = password_hash('123456', PASSWORD_DEFAULT);
        $code_hash2 = password_hash('654321', PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 300);

        // Insert first OTP
        $this->pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at) 
             VALUES (?, ?, ?, ?)'
        )->execute([$user_id, 'registration', $code_hash1, $expires_at]);

        // Try to insert second OTP with same user and purpose
        // Should either fail or replace the first one (depends on REPLACE vs INSERT)
        try {
            $this->pdo->prepare(
                'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at) 
                 VALUES (?, ?, ?, ?)'
            )->execute([$user_id, 'registration', $code_hash2, $expires_at]);
            
            // If it succeeds, it's using REPLACE, so one should exist
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM otp_codes WHERE user_id = ? AND purpose = ? AND used_at IS NULL'
            );
            $stmt->execute([$user_id, 'registration']);
            $count = (int)$stmt->fetchColumn();
            
            $this->assertLessThanOrEqual(1, $count);
        } catch (PDOException $e) {
            // If it fails, unique constraint is enforced - this is fine
            $this->assertTrue(true);
        }
    }

    /**
     * @test
     * Foreign key constraint on user_id
     */
    public function testForeignKeyConstraint(): void
    {
        TestDatabaseHelper::clearDatabase();

        $code_hash = password_hash('123456', PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 300);

        // Try to insert OTP for non-existent user (user_id=99999)
        // Should fail due to foreign key constraint
        try {
            $this->pdo->prepare(
                'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at) 
                 VALUES (?, ?, ?, ?)'
            )->execute([99999, 'registration', $code_hash, $expires_at]);

            // If no exception, check if insertion was prevented
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM otp_codes WHERE user_id = ?');
            $stmt->execute([99999]);
            $count = (int)$stmt->fetchColumn();

            $this->assertEquals(0, $count);
        } catch (PDOException $e) {
            // Foreign key constraint prevented insertion - expected
            $this->assertTrue(true);
        }
    }

    // ═══════════════════════════════════════════════════════
    // INDEX TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Index exists on user_id for performance
     */
    public function testIndexOnUserId(): void
    {
        $stmt = $this->pdo->query("PRAGMA index_list(otp_codes)");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $user_id_indexes = array_filter($indexes, fn($idx) => 
            stripos($idx['name'], 'user') !== false
        );

        $this->assertGreaterThan(0, count($user_id_indexes));
    }

    /**
     * @test
     * Index exists on expires_at for cleanup queries
     */
    public function testIndexOnExpiresAt(): void
    {
        $stmt = $this->pdo->query("PRAGMA index_list(otp_codes)");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $expires_indexes = array_filter($indexes, fn($idx) => 
            stripos($idx['name'], 'expire') !== false || 
            stripos($idx['name'], 'expires') !== false
        );

        $this->assertGreaterThan(0, count($expires_indexes));
    }

    // ═══════════════════════════════════════════════════════
    // DEFAULT VALUES TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * attempts column defaults to 0
     */
    public function testAttemptsDefaultsTo0(): void
    {
        TestDatabaseHelper::clearDatabase();
        $user_id = TestDatabaseHelper::createTestUser('test@example.com');

        $code_hash = password_hash('123456', PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 300);

        $this->pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at) 
             VALUES (?, ?, ?, ?)'
        )->execute([$user_id, 'registration', $code_hash, $expires_at]);

        $stmt = $this->pdo->prepare(
            'SELECT attempts FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$user_id, 'registration']);
        $otp = $stmt->fetch();

        $this->assertEquals(0, $otp['attempts']);
    }

    /**
     * @test
     * used_at defaults to NULL (not used)
     */
    public function testUsedAtDefaultsToNull(): void
    {
        TestDatabaseHelper::clearDatabase();
        $user_id = TestDatabaseHelper::createTestUser('test@example.com');

        $code_hash = password_hash('123456', PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 300);

        $this->pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at) 
             VALUES (?, ?, ?, ?)'
        )->execute([$user_id, 'registration', $code_hash, $expires_at]);

        $stmt = $this->pdo->prepare(
            'SELECT used_at FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$user_id, 'registration']);
        $otp = $stmt->fetch();

        $this->assertNull($otp['used_at']);
    }

    /**
     * @test
     * created_at auto-timestamp
     */
    public function testCreatedAtTimestamp(): void
    {
        TestDatabaseHelper::clearDatabase();
        $user_id = TestDatabaseHelper::createTestUser('test@example.com');

        $before = time();
        $code_hash = password_hash('123456', PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 300);

        $this->pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at) 
             VALUES (?, ?, ?, ?)'
        )->execute([$user_id, 'registration', $code_hash, $expires_at]);

        $after = time();

        $stmt = $this->pdo->prepare(
            'SELECT created_at FROM otp_codes WHERE user_id = ? AND purpose = ?'
        );
        $stmt->execute([$user_id, 'registration']);
        $otp = $stmt->fetch();

        $created_timestamp = strtotime($otp['created_at']);
        $this->assertGreaterThanOrEqual($before, $created_timestamp);
        $this->assertLessThanOrEqual($after + 1, $created_timestamp); // +1 for rounding
    }

    // ═══════════════════════════════════════════════════════
    // SUPPRESSIONS_COMPTES TABLE TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * suppressions_comptes table exists
     */
    public function testSuppressionsComptesTableExists(): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='suppressions_comptes'"
        );
        $stmt->execute();
        $table = $stmt->fetch();

        $this->assertNotNull($table);
    }

    /**
     * @test
     * suppressions_comptes table has required columns
     */
    public function testSuppressionsComptesColumns(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(suppressions_comptes)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $column_names = array_column($columns, 'name');

        $required = [
            'id',
            'nom_utilisateur',
            'email_utilisateur',
            'role_utilisateur',
            'raison',
            'created_at'
        ];

        foreach ($required as $col) {
            $this->assertContains($col, $column_names);
        }
    }

    /**
     * @test
     * Audit record can be inserted and retrieved
     */
    public function testAuditRecordInsertionAndRetrieval(): void
    {
        $this->pdo->prepare(
            'INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) 
             VALUES (?, ?, ?, ?)'
        )->execute([
            'John Doe',
            'john@example.com',
            'client',
            'User requested deletion'
        ]);

        $stmt = $this->pdo->query('SELECT * FROM suppressions_comptes ORDER BY id DESC LIMIT 1');
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotNull($log);
        $this->assertEquals('John Doe', $log['nom_utilisateur']);
        $this->assertEquals('john@example.com', $log['email_utilisateur']);
        $this->assertEquals('client', $log['role_utilisateur']);
        $this->assertEquals('User requested deletion', $log['raison']);
    }

    // ═══════════════════════════════════════════════════════
    // USERS TABLE INTEGRITY TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * users table exists with OTP-related columns
     */
    public function testUsersTableHasOtpColumns(): void
    {
        $stmt = $this->pdo->query("PRAGMA table_info(users)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $column_names = array_column($columns, 'name');

        // OTP-related columns
        $this->assertContains('email_verifie', $column_names);
        $this->assertContains('email_verified_at', $column_names);
    }

    /**
     * @test
     * email_verifie defaults to 0 (not verified)
     */
    public function testEmailVerifieDefaultsTo0(): void
    {
        TestDatabaseHelper::clearDatabase();
        $user_id = TestDatabaseHelper::createTestUser('test@example.com');

        $stmt = $this->pdo->prepare('SELECT email_verifie FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        $this->assertEquals(0, $user['email_verifie']);
    }

    // ═══════════════════════════════════════════════════════
    // CASCADING DELETE TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Deleting user cascades to delete OTPs
     */
    public function testCascadingDeleteWhenUserDeleted(): void
    {
        TestDatabaseHelper::clearDatabase();
        $user_id = TestDatabaseHelper::createTestUser('cascade@example.com');

        $code_hash = password_hash('123456', PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', time() + 300);

        // Create OTP
        $this->pdo->prepare(
            'INSERT INTO otp_codes (user_id, purpose, code_hash, expires_at) 
             VALUES (?, ?, ?, ?)'
        )->execute([$user_id, 'registration', $code_hash, $expires_at]);

        // Verify OTP exists
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM otp_codes WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $before = (int)$stmt->fetchColumn();
        $this->assertGreaterThan(0, $before);

        // Delete user
        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$user_id]);

        // OTPs should be deleted
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM otp_codes WHERE user_id = ?');
        $stmt->execute([$user_id]);
        $after = (int)$stmt->fetchColumn();
        $this->assertEquals(0, $after);
    }

    // ═══════════════════════════════════════════════════════
    // PERFORMANCE TESTS
    // ═══════════════════════════════════════════════════════

    /**
     * @test
     * Query on user_id is indexed for performance
     */
    public function testUserIdQueryPerformance(): void
    {
        TestDatabaseHelper::clearDatabase();

        // Create multiple users
        for ($i = 0; $i < 100; $i++) {
            TestDatabaseHelper::createTestUser("user{$i}@example.com");
        }

        $start = microtime(true);

        // Query should be fast due to index
        $stmt = $this->pdo->prepare(
            'SELECT * FROM otp_codes WHERE user_id = ? AND used_at IS NULL'
        );
        $stmt->execute([1]);
        $otp = $stmt->fetch();

        $elapsed = (microtime(true) - $start) * 1000; // Convert to ms

        // Should complete in reasonable time (< 100ms)
        $this->assertLessThan(100, $elapsed);
    }
}
