<?php
/**
 * quick_login_test.php — Connexion de test directe (bypass CSRF pour diagnostiquer)
 * À exécuter pour tester si la connexion fonctionne
 */

session_start();

// Éviter les erreurs PHP
error_reporting(0);

try {
    // Connexion MySQL
    $pdo = new PDO('mysql:host=localhost;dbname=domizi', 'root', '');
    
    // Récupérer l'admin
    $stmt = $pdo->prepare("SELECT id, nom, prenom, role, sexe, password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute(['admin@domizi.local']);
    $user = $stmt->fetch();
    
    if (!$user) {
        die("❌ Admin non trouvé");
    }
    
    // Vérifier le password
    if (!password_verify('Admin2026!', $user['password'])) {
        die("❌ Password incorrect");
    }
    
    // Créer la session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['prenom'] = $user['prenom'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['sexe'] = $user['sexe'];
    $_SESSION['photo_profil'] = null;
    $_SESSION['id_ville'] = 0;
    $_SESSION['id_quartier'] = 0;
    
    echo "✅ Session créée avec succès\n";
    echo "📋 Données de session :\n";
    echo "   user_id : " . $_SESSION['user_id'] . "\n";
    echo "   role : " . $_SESSION['role'] . "\n";
    echo "   nom : " . $_SESSION['nom'] . " " . $_SESSION['prenom'] . "\n";
    echo "\n🔗 Redirection vers tableau de bord...\n";
    
    // Rediriger
    header("Location: /coiffons/first/admin_dashboard.php");
    exit();
    
} catch (Exception $e) {
    die("❌ Erreur : " . $e->getMessage());
}
?>
