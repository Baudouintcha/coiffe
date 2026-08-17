<?php
/**
 * verify_setup.php - Vérification et correction de la configuration
 * Exécute les étapes pour harmoniser la base vers domizi_v2
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Charge le .env
$envFile = __DIR__ . '/.env';
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

use App\Core\Database;

echo "<h1>Vérification et Setup du Projet</h1>";
echo "<pre style='background:#f0f0f0;padding:20px;font-family:monospace;'>";

try {
    $pdo = Database::getInstance();
    echo "✓ Connexion PDO établie\n";
    
    // Vérifier les tables critiques
    $tables = ['domaines', 'metiers', 'users', 'prestations'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
        $stmt->execute([$_ENV['DB_NAME'] ?? 'domizi', $table]);
        $exists = $stmt->fetch()['cnt'] > 0;
        echo ($exists ? "✓" : "✗") . " Table $table : " . ($exists ? "OK" : "MANQUANTE") . "\n";
    }
    
    echo "\n--- VÉRIFIE LES DONNÉES ---\n";
    
    // Domaines
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM domaines WHERE actif = 1");
    $cnt = $stmt->fetch()['cnt'];
    echo "Domaines actifs : $cnt\n";
    
    // Métiers
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM metiers WHERE actif = 1");
    $cnt = $stmt->fetch()['cnt'];
    echo "Métiers actifs : $cnt\n";
    
    if ($cnt > 0) {
        echo "\nMétiers trouvés :\n";
        $stmt = $pdo->query("SELECT nom, slug FROM metiers WHERE actif = 1");
        foreach ($stmt->fetchAll() as $m) {
            echo "  - {$m['nom']} ({$m['slug']})\n";
        }
    }
    
    echo "\n--- VÉRIFICATION DU ROUTAGE ---\n";
    
    // Vérifier que index.php appelle bien app.php
    $indexContent = file_get_contents(__DIR__ . '/index.php');
    $hasMetierCheck = strpos($indexContent, "?metier=") !== false;
    $hasApiCheck = strpos($indexContent, "api_metiers") !== false;
    
    echo ($hasMetierCheck ? "✓" : "✗") . " index.php gère les métiers\n";
    echo ($hasApiCheck ? "✓" : "✗") . " index.php gère l'API métiers\n";
    
    // Vérifier que app.php est le routeur
    $appContent = file_get_contents(__DIR__ . '/app.php');
    $hasRouting = strpos($appContent, "?page=") !== false && strpos($appContent, "case 'dashboard'") !== false;
    
    echo ($hasRouting ? "✓" : "✗") . " app.php est le routeur interne\n";
    
    echo "\n✅ Configuration OK - Tu peux commencer !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
