<?php
// On force le démarrage de la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de ton fichier de configuration PDO (ajuste le chemin si nécessaire)
require_once __DIR__ . '/../security/config.php'; 

$email = 'admin@coiffcheztoi.com';
$password_clair = 'Admin2026!'; // Le mot de passe que tu taperas pour te connecter
$password_hache = password_hash($password_clair, PASSWORD_DEFAULT);

echo "<h2>Création de l'administrateur en cours...</h2>";

try {
    // 1. On vérifie si l'admin existe déjà pour éviter les doublons
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        // Si l'email existe, on le supprime pour le recréer proprement
        $pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$email]);
    }

    // 2. Insertion propre dans la table 'users'
    // Note : Ajuste ou retire les colonnes (nom, prenom, role, ville) selon ta structure réelle
    $sql = "INSERT INTO users (nom, prenom, email, password, role, ville) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    // On met 'admin' dans le rôle et NULL pour la ville (ou l'ID d'une ville existante, ex: 1)
    $succes = $stmt->execute([
        'Super',          // nom
        'Admin',          // prenom
        $email,           // email
        $password_hache,  // password haché avec password_hash
        'admin',          // role
        null              // ville (id_ville)
    ]);

    if ($succes) {
        echo "<p style='color:green; font-weight:bold;'>L'administrateur a été créé avec succès !</p>";
        echo "<p>Rends-toi sur ta page de connexion et utilise ces identifiants :</p>";
        echo "<ul>";
        echo "<li><strong>Email :</strong> " . $email . "</li>";
        echo "<li><strong>Mot de passe :</strong> " . $password_clair . "</li>";
        echo "</ul>";
        echo "<p style='color:red;'>⚠️ N'oublie pas de supprimer ce fichier ('creer_admin.php') après le test pour des raisons de sécurité !</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold;'>Erreur SQL : " . $e->getMessage() . "</p>";
    echo "<p>Regarde le message d'erreur ci-dessus. Il t'indiquera s'il y a un nom de colonne mal orthographié dans la requête INSERT.</p>";
}
?>