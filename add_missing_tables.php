<?php
/**
 * Ajoute les tables manquantes à domizi
 * Exécute domizi_missing_tables.sql
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Ajout des tables manquantes à DOMIZI</h1>";
echo "<style>
    body { font-family: monospace; background: #f0f0f0; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: white; padding: 15px; border-left: 4px solid #4A90D9; max-width: 1000px; }
</style>";
echo "<pre>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=domizi;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<span class='success'>✓ Connexion à domizi établie</span>\n\n";
    
    $sql_file = __DIR__ . '/security/domizi_missing_tables.sql';
    if (!file_exists($sql_file)) {
        die("<span class='error'>✗ Fichier domizi_missing_tables.sql introuvable</span>");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Exécuter ligne par ligne pour les CREATE TABLE
    $lines = explode("\n", $sql_content);
    $current_statement = '';
    $count = 0;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Ignorer les commentaires et lignes vides
        if (empty($trimmed) || substr($trimmed, 0, 2) === '--') {
            continue;
        }
        
        $current_statement .= ' ' . $trimmed;
        
        // Si la ligne finit par ;, c'est la fin d'une instruction
        if (substr($trimmed, -1) === ';') {
            $sql_to_execute = trim(substr($current_statement, 0, -1)); // Enlever le ;
            
            if (!empty($sql_to_execute)) {
                try {
                    $pdo->exec($sql_to_execute);
                    $count++;
                    echo "<span class='success'>✓ Table créée</span>\n";
                } catch (Exception $e) {
                    // Les erreurs "already exists" sont normales
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "<span class='info'>ℹ Table déjà existante</span>\n";
                    } else {
                        echo "<span class='error'>✗ Erreur : " . $e->getMessage() . "</span>\n";
                    }
                }
            }
            $current_statement = '';
        }
    }
    
    echo "<span class='success'>✓ $count tables créées/mises à jour</span>\n\n";
    
    // Vérification
    echo "<span class='info'>═══ VÉRIFICATION FINALE ═══</span>\n\n";
    
    $tables = [
        'abonnements_paiements',
        'transactions_portefeuille',
        'plaintes',
        'email_actions',
        'otp_codes'
    ];
    
    foreach ($tables as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "<span class='success'>✓ $table : créée ($count lignes)</span>\n";
        } catch (Exception $e) {
            echo "<span class='error'>✗ $table : non créée</span>\n";
        }
    }
    
    echo "\n<span class='success'>════════════════════════════════════════</span>\n";
    echo "<span class='success'>✓ TABLES MANQUANTES AJOUTÉES !</span>\n";
    echo "<span class='success'>════════════════════════════════════════</span>\n\n";
    echo "Lis DOMIZI_DATABASE_STRUCTURE.md pour comprendre les relations\n";
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Erreur : " . $e->getMessage() . "</span>\n";
}

echo "</pre>";
