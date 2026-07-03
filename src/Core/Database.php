<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database - Singleton PDO
 *
 * CONCEPT : Le pattern Singleton garantit qu'une seule connexion PDO
 * existe dans toute l'application, peu importe combien de fichiers
 * l'appellent. C'est économique et cohérent.
 *
 * USAGE :
 *   $pdo = Database::getInstance();
 *   $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 */
class Database
{
    private static ?PDO $instance = null;

    // Constructeur privé : personne ne peut faire "new Database()"
    private function __construct() {}

    // Clone bloqué : on ne peut pas dupliquer la connexion
    private function __clone() {}

    /**
     * Retourne l'instance unique PDO.
     * La crée si elle n'existe pas encore.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }

        return self::$instance;
    }

    /**
     * Crée la connexion PDO à partir du fichier .env
     */
    private static function connect(): PDO
    {
        $host    = $_ENV['DB_HOST']    ?? 'localhost';
        $dbname  = $_ENV['DB_NAME']    ?? 'domizi';
        $user    = $_ENV['DB_USER']    ?? 'root';
        $pass    = $_ENV['DB_PASS']    ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            return $pdo;

        } catch (PDOException $e) {
            // En production, on ne montre jamais les détails
            error_log('Erreur BDD : ' . $e->getMessage());
            http_response_code(500);
            die('Service temporairement indisponible.');
        }
    }
}
