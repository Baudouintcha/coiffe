<?php
$host = 'localhost';
$dbname = 'domizi';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== STRUCTURE TABLE users ===\n\n";
    $columns = $pdo->query("DESCRIBE users")->fetchAll();
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " | " . $col['Type'] . " | Null: " . $col['Null'] . " | Default: " . ($col['Default'] ?? 'NULL') . "\n";
    }
    
    echo "\n=== UTILISATEURS PAR RÔLE ===\n";
    $roles = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();
    foreach ($roles as $r) {
        echo "- " . $r['role'] . ": " . $r['count'] . "\n";
    }
    
    echo "\n=== CLIENTS ACTUELS ===\n";
    $clients = $pdo->query("SELECT id, email, nom, prenom, role, statut FROM users WHERE role = 'client' LIMIT 5")->fetchAll();
    if (empty($clients)) {
        echo "Aucun client enregistré\n";
    } else {
        foreach ($clients as $c) {
            echo "- ID: " . $c['id'] . " | Email: " . $c['email'] . " | Nom: " . $c['nom'] . " " . $c['prenom'] . " | Statut: " . ($c['statut'] ?? 'NULL') . "\n";
        }
    }
    
    // Vérifier les autres tables clés
    echo "\n=== TABLES PRÉSENTES ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    foreach ($tables as $t) {
        $tname = current($t);
        $count = $pdo->query("SELECT COUNT(*) FROM " . $tname)->fetchColumn();
        echo "- " . $tname . " (" . $count . " lignes)\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur BDD: " . $e->getMessage() . "\n";
}
?>
