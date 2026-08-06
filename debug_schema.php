<?php
require_once __DIR__ . '/security/config.php';

// Afficher les colonnes de la table users
$result = $pdo->query("SHOW COLUMNS FROM users");
$columns = $result->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Colonnes actuelles de la table 'users':</h2>";
echo "<pre>";
foreach ($columns as $col) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
}
echo "</pre>";
?>
