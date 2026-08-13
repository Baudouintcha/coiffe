<?php
/**
 * security/tests/Helpers/TestDatabaseHelper.php
 * Database helper for setting up test database
 */

class TestDatabaseHelper
{
    private static ?PDO $pdo = null;
    private static string $db_path = ':memory:'; // In-memory SQLite for testing

    /**
     * Get or create test database connection
     */
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = new PDO('sqlite:' . self::$db_path);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::setupDatabase();
        }
        return self::$pdo;
    }

    /**
     * Create database tables for testing
     */
    public static function setupDatabase(): void
    {
        $pdo = self::$pdo;

        // Create users table
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email_verifie INTEGER DEFAULT 0,
                email_verified_at DATETIME NULL,
                role VARCHAR(50) DEFAULT "client",
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Create otp_codes table
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS otp_codes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                purpose VARCHAR(50) NOT NULL,
                code_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                attempts TINYINT DEFAULT 0,
                used_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(user_id, purpose),
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ');

        // Create suppressions_comptes table
        $pdo->exec('
            CREATE TABLE IF NOT EXISTS suppressions_comptes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nom_utilisateur VARCHAR(255),
                email_utilisateur VARCHAR(255),
                role_utilisateur VARCHAR(50),
                raison TEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Create indexes
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_otp_user_id ON otp_codes(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_otp_expires_at ON otp_codes(expires_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_otp_purpose ON otp_codes(purpose)');
    }

    /**
     * Clear all tables (for test isolation)
     */
    public static function clearDatabase(): void
    {
        $pdo = self::getConnection();
        $pdo->exec('DELETE FROM otp_codes');
        $pdo->exec('DELETE FROM suppressions_comptes');
        $pdo->exec('DELETE FROM users');
    }

    /**
     * Create a test user
     */
    public static function createTestUser(string $email = 'test@example.com', string $role = 'client'): int
    {
        $pdo = self::getConnection();
        $password_hash = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password, role, email_verifie) VALUES (?, ?, ?, 0)'
        );
        $stmt->execute([$email, $password_hash, $role]);
        
        return $pdo->lastInsertId();
    }

    /**
     * Get a user by email
     */
    public static function getUserByEmail(string $email): ?array
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Get OTP codes for a user
     */
    public static function getOtpCodes(int $user_id, string $purpose = ''): array
    {
        $pdo = self::getConnection();
        
        if ($purpose) {
            $stmt = $pdo->prepare('SELECT * FROM otp_codes WHERE user_id = ? AND purpose = ?');
            $stmt->execute([$user_id, $purpose]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM otp_codes WHERE user_id = ?');
            $stmt->execute([$user_id]);
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get deletion audit logs
     */
    public static function getDeletionLogs(): array
    {
        $pdo = self::getConnection();
        $stmt = $pdo->query('SELECT * FROM suppressions_comptes ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Close database connection
     */
    public static function closeConnection(): void
    {
        self::$pdo = null;
    }
}
