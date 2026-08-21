<?php
require_once __DIR__ . '/security/config.php';

echo "<h1>📊 Structure de la Base de Données DOMIZI</h1>";
echo "<hr>";

// Récupérer toutes les tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Tables (" . count($tables) . ")</h2>";

foreach ($tables as $table) {
    echo "<h3>$table</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th></tr>";
    
    $cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        // Surligner les colonnes problématiques
        $style = '';
        if (stripos($col['Field'], 'prestantaire') !== false) {
            $style = "style='background:#ff6666; color:white;'";
        } else if (stripos($col['Field'], 'prestataire_id') !== false) {
            $style = "style='background:#66ff66; color:black;'";
        }
        
        echo "<tr $style>";
        echo "<td><code>{$col['Field']}</code></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
}

echo "<hr>";
echo "<p style='color:#666;'>🟢 Vert = Correct | 🔴 Rouge = Erreur à corriger</p>";
