<?php
/**
 * reset_admin_passwords.php — Réinitialiser les mots de passe admin
 * À exécuter en cas d'identifiants incorrects
 */

try {
    // Connexion à la base de données
    $pdo = new PDO(
        'mysql:host=localhost;dbname=domizi',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║        RÉINITIALISATION DES MOTS DE PASSE ADMINISTRATEUR     ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    // Données d'authentification
    $credentials = [
        [
            'email' => 'admin@domizi.local',
            'password' => 'Admin2026!',
            'name' => 'Admin Principal'
        ],
        [
            'email' => 'support@domizi.local',
            'password' => 'Support2026!',
            'name' => 'Support'
        ],
        [
            'email' => 'test@domizi.local',
            'password' => 'Test2026!',
            'name' => 'Test Account'
        ]
    ];
    
    // Créer les utilisateurs ou mettre à jour les mots de passe
    $sql = "INSERT INTO users (email, password, role, nom, prenom, sexe, telephone) 
            VALUES (?, ?, 'admin', ?, ?, 'homme', '+229')
            ON DUPLICATE KEY UPDATE 
            password = VALUES(password)";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($credentials as $cred) {
        $hash = password_hash($cred['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt->execute([
            $cred['email'],
            $hash,
            $cred['name'],
            $cred['name']
        ]);
        
        echo "✓ {$cred['name']}\n";
        echo "  Email    : {$cred['email']}\n";
        echo "  Password : {$cred['password']}\n";
        echo "  Hash bcrypt : " . substr($hash, 0, 30) . "...\n\n";
    }
    
    // Vérifier les mots de passe
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✓ VÉRIFICATION DES MOTS DE PASSE\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    $stmt_check = $pdo->prepare("SELECT id, email, password FROM users WHERE email = ? LIMIT 1");
    
    foreach ($credentials as $cred) {
        $stmt_check->execute([$cred['email']]);
        $user = $stmt_check->fetch();
        
        if ($user) {
            $verified = password_verify($cred['password'], $user['password']);
            $status = $verified ? "✅ CORRECT" : "❌ INCORRECT";
            
            echo "$status — {$cred['email']}\n";
            echo "  Password_verify: " . ($verified ? "true" : "false") . "\n";
            echo "  Hash stocké: {$user['password']}\n\n";
        }
    }
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ RÉINITIALISATION COMPLÈTE!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "📋 IDENTIFIANTS FINAL\n";
    echo "───────────────────────────────────────────────────────────────\n\n";
    
    echo "Admin Principal:\n";
    echo "  Email    : admin@domizi.local\n";
    echo "  Password : Admin2026!\n\n";
    
    echo "Support:\n";
    echo "  Email    : support@domizi.local\n";
    echo "  Password : Support2026!\n\n";
    
    echo "Test:\n";
    echo "  Email    : test@domizi.local\n";
    echo "  Password : Test2026!\n\n";
    
    echo "URL : http://localhost/coiffons/access/connexion.php\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>
