<?php
/**
 * Script de génération des identifiants administrateur
 * À exécuter une seule fois pour créer les comptes de test
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
    echo "║          GÉNÉRATION DES IDENTIFIANTS ADMINISTRATEUR          ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    // Compte admin principal
    $admin_email = 'admin@domizi.local';
    $admin_password = 'Admin@2026!';
    $admin_hash = password_hash($admin_password, PASSWORD_BCRYPT);
    
    // Compte support
    $support_email = 'support@domizi.local';
    $support_password = 'Support@2026!';
    $support_hash = password_hash($support_password, PASSWORD_BCRYPT);
    
    // Compte test
    $test_email = 'test@domizi.local';
    $test_password = 'Test@2026!';
    $test_hash = password_hash($test_password, PASSWORD_BCRYPT);
    
    // Insérer ou mettre à jour les comptes
    $sql = "INSERT INTO users (email, password, role, nom, prenom, statut, sexe, telephone, email_verifie) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            password = VALUES(password),
            role = VALUES(role),
            statut = VALUES(statut)";
    
    $stmt = $pdo->prepare($sql);
    
    // Compte admin
    $stmt->execute([$admin_email, $admin_hash, 'admin', 'Admin', 'Domizi', 'actif', 'homme', '+229', 1]);
    echo "✓ Compte administrateur créé\n";
    echo "  Email    : $admin_email\n";
    echo "  Password : $admin_password\n";
    echo "  Rôle     : admin\n\n";
    
    // Compte support
    $stmt->execute([$support_email, $support_hash, 'admin', 'Support', 'Domizi', 'actif', 'femme', '+229', 1]);
    echo "✓ Compte support créé\n";
    echo "  Email    : $support_email\n";
    echo "  Password : $support_password\n";
    echo "  Rôle     : admin\n\n";
    
    // Compte test
    $stmt->execute([$test_email, $test_hash, 'admin', 'Test', 'Account', 'actif', 'homme', '+229', 1]);
    echo "✓ Compte test créé\n";
    echo "  Email    : $test_email\n";
    echo "  Password : $test_password\n";
    echo "  Rôle     : admin\n\n";
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ Tous les comptes ont été créés/mis à jour avec succès!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "📋 RÉCAPITULATIF DES IDENTIFIANTS\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo "Compte Admin Principal :\n";
    echo "  Email    : admin@domizi.local\n";
    echo "  Password : Admin@2026!\n";
    echo "  URL      : http://localhost/coiffons/connexion\n\n";
    
    echo "Compte Support :\n";
    echo "  Email    : support@domizi.local\n";
    echo "  Password : Support@2026!\n\n";
    
    echo "Compte Test :\n";
    echo "  Email    : test@domizi.local\n";
    echo "  Password : Test@2026!\n\n";
    
    echo "═══════════════════════════════════════════════════════════════\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>
