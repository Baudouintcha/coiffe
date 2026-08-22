<?php
/**
 * setup_database.php — Restauration de la base de données domizi
 * Supprime et recréé toutes les tables nécessaires
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'domizi';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ Connexion établie à la base $db\n";
    
    // Lister toutes les tables
    $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\nTablesexistantes : " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Désactiver les foreign keys pour la suppression
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // Supprimer toutes les tables
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "  ✓ Suppresssion de $table\n";
    }
    
    echo "\n✓ Toutes les tables ont été supprimées.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>
