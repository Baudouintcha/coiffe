<?php
require_once __DIR__ . '/security/config.php';

echo "COLONNES TABLE USERS:\n";
$cols = $pdo->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}

echo "\nCOLONNES TABLE SERVICES:\n";
$cols = $pdo->query('DESCRIBE services')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}

echo "\nCOLONNES TABLE RENDEZ_VOUS:\n";
$cols = $pdo->query('DESCRIBE rendez_vous')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}
?>
