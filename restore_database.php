<?php
/**
 * restore_database.php — Restauration complète de la base domizi
 */

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'domizi';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    echo "✓ Connexion à $db\n";
    
    // Lire le fichier SQL complet
    $sql_file = __DIR__ . '/security/domizi_v2.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("Fichier $sql_file non trouvé");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Diviser par ';' mais ignorer les points-virgules dans les commentaires
    $statements = array_filter(
        preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql),
        fn($s) => trim($s) && !str_starts_with(trim($s), '--')
    );
    
    echo "Nombre de statements : " . count($statements) . "\n";
    
    $count = 0;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
            $count++;
        } catch (Exception $e) {
            // Ignorer les créations doublées
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "⚠️  Erreur sur statement $count: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✓ $count statements exécutés\n";
    
    // Vérifier les tables créées
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $table_count = $stmt->fetchColumn();
    
    echo "✓ Nombre de tables créées : $table_count\n";
    
    // Lister les tables
    $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n✅ Restauration complétée avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
?>
