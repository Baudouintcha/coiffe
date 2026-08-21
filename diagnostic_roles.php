<?php
require 'security/config.php';

echo "=== DIAGNOSTIC RÔLES UTILISATEURS ===\n\n";

// 1. Total utilisateurs
$result = $pdo->query('SELECT COUNT(*) as total FROM users')->fetch();
echo "1️⃣ Total utilisateurs : " . $result['total'] . "\n";

// 2. Coiffeurs
$coiffeurs = $pdo->query('SELECT COUNT(*) as total FROM users WHERE LOWER(role) = "coiffeur"')->fetch();
echo "2️⃣ Coiffeurs (role='coiffeur') : " . $coiffeurs['total'] . "\n";

// 3. Tous les rôles
echo "\n3️⃣ Distribution des rôles : \n";
$roles = $pdo->query('SELECT role, COUNT(*) as nb FROM users GROUP BY role ORDER BY role')->fetchAll();
foreach($roles as $r) {
    echo "   - {$r['role']} : {$r['nb']}\n";
}

// 4. Colonnes de la table users
echo "\n4️⃣ Colonnes de la table users : \n";
$columns = $pdo->query('DESCRIBE users')->fetchAll();
foreach($columns as $col) {
    echo "   - {$col['Field']} ({$col['Type']})\n";
}

// 5. Vérifier si la colonne 'role' existe vraiment
$roleCol = $pdo->query('SHOW COLUMNS FROM users WHERE Field = "role"')->fetch();
if ($roleCol) {
    echo "\n5️⃣ ✅ Colonne 'role' existe\n";
} else {
    echo "\n5️⃣ ❌ Colonne 'role' N'EXISTE PAS dans users !\n";
}

// 6. Quelques enregistrements
echo "\n6️⃣ Premiers utilisateurs (id, nom, role) : \n";
$users = $pdo->query('SELECT id, nom, role FROM users LIMIT 5')->fetchAll();
foreach($users as $u) {
    echo "   - ID {$u['id']} : {$u['nom']} (role: {$u['role']})\n";
}

// 7. Vérifier la table profils_prestataires
echo "\n7️⃣ Profils prestataires : \n";
$count_pp = $pdo->query('SELECT COUNT(*) as total FROM profils_prestataires')->fetch();
echo "   - Total : " . $count_pp['total'] . "\n";

$pp_users = $pdo->query('SELECT user_id FROM profils_prestataires LIMIT 5')->fetchAll();
echo "   - Premiers user_id liés : \n";
foreach($pp_users as $p) {
    echo "     • {$p['user_id']}\n";
}
