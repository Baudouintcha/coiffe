<?php
/**
 * security/config.php
 * Configuration centralisée — CHARGE LES VARIABLES DEPUIS .env
 */

// Charger les variables d'environnement depuis .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Lire les paramètres depuis .env
$host     = $_ENV['DB_HOST']    ?? 'localhost';
$dbname   = $_ENV['DB_NAME']    ?? 'domizi';
$username = $_ENV['DB_USER']    ?? 'root';
$password = $_ENV['DB_PASS']    ?? '';
$charset  = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

try {
    // Créer une instance de PDO pour se connecter à MySQL
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // Si la connexion réussit, rien ne s'affiche (c'est le but)
} catch (PDOException $e) {
    // En développement : affiche les détails
    if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
    // En production : message générique
    http_response_code(500);
    die("Service temporairement indisponible.");
}

// BASE_URL pour les redirection et liens
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $domaine = $_SERVER['HTTP_HOST'];
    $url_calculee = ($domaine === 'localhost') ? $protocol . $domaine . '/coiffons/' : $protocol . $domaine . '/';
    define('BASE_URL', $url_calculee);
}
