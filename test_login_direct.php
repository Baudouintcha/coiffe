<?php
/**
 * test_login_direct.php — Test direct de connexion (bypass CSRF)
 * À utiliser uniquement pour diagnostiquer les problèmes
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║         TEST DIRECT DE CONNEXION — Diagnostic                ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

try {
    // Connexion à la base de données
    $pdo = new PDO(
        'mysql:host=localhost;dbname=domizi',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✓ Connexion MySQL établie\n\n";
    
    // Données de test
    $email = 'admin@domizi.local';
    $password = 'Admin2026!';
    
    echo "Test avec :\n";
    echo "  Email    : $email\n";
    echo "  Password : $password\n\n";
    
    // Récupérer l'utilisateur
    $stmt = $pdo->prepare("SELECT id, email, password, role, statut, nom, prenom FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ Utilisateur non trouvé\n";
        exit(1);
    }
    
    echo "✓ Utilisateur trouvé\n";
    echo "  ID     : {$user['id']}\n";
    echo "  Nom    : {$user['nom']} {$user['prenom']}\n";
    echo "  Rôle   : {$user['role']}\n";
    echo "  Statut : {$user['statut']}\n\n";
    
    // Vérifier le statut
    if ($user['statut'] === 'banni') {
        echo "❌ Compte banni\n";
        exit(1);
    }
    
    echo "✓ Compte actif (pas banni)\n\n";
    
    // Vérifier le mot de passe
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "Vérification du mot de passe :\n";
    echo "───────────────────────────────────────────────────────────────\n\n";
    
    echo "Hash stocké en base :\n";
    echo "{$user['password']}\n\n";
    
    echo "Mot de passe en clair :\n";
    echo "$password\n\n";
    
    // Test 1 : password_verify basique
    $verify_result = password_verify($password, $user['password']);
    echo "password_verify(\$password, \$hash) :\n";
    echo "  Résultat : " . ($verify_result ? "TRUE ✅" : "FALSE ❌") . "\n\n";
    
    // Test 2 : Vérifier si le hash est corrompu
    if (strlen($user['password']) !== 60) {
        echo "⚠️  WARNING : Hash non-standard (longueur: " . strlen($user['password']) . ", attendu: 60)\n\n";
    }
    
    // Test 3 : Extraire les infos du hash
    if (preg_match('/^\$2[aby]\$(\d+)\$/', $user['password'], $matches)) {
        $cost = $matches[1];
        echo "✓ Hash Bcrypt valide (coût: $cost)\n\n";
    } else {
        echo "❌ Hash invalide ou corrompu\n\n";
    }
    
    // Test 4 : Tester différentes variantes du mot de passe
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "Tests de variantes :\n";
    echo "───────────────────────────────────────────────────────────────\n\n";
    
    $test_passwords = [
        'Admin2026!' => 'Original (attendu)',
        'admin2026!' => 'Minuscule',
        'Admin2026' => 'Sans point d\'exclamation',
        'admin@domizi.local' => 'Email comme password',
        'Admin@2026!' => 'Avec @ au lieu de chiffre',
        'Admin2026!!' => 'Double point d\'exclamation',
        ' Admin2026!' => 'Espace au début',
        'Admin2026! ' => 'Espace à la fin',
    ];
    
    foreach ($test_passwords as $pwd => $desc) {
        $result = password_verify($pwd, $user['password']) ? "✅" : "❌";
        echo "$result $desc\n";
        echo "   '$pwd'\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    
    // Conclusion
    if ($verify_result) {
        echo "\n✅ CONNEXION RÉUSSIE!\n";
        echo "\nLe mot de passe est correct.\n";
        echo "Le problème vient peut-être d'ailleurs :\n";
        echo "  • Session PHP\n";
        echo "  • Vérification CSRF\n";
        echo "  • Redirection\n";
        echo "  • Cache navigateur\n";
    } else {
        echo "\n❌ CONNEXION ÉCHOUÉE\n";
        echo "\nLe mot de passe ne correspond pas.\n";
        echo "Raisons possibles :\n";
        echo "  • Mot de passe incorrect\n";
        echo "  • Hash corrompu en base de données\n";
        echo "  • Encodage différent\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>
