<?php
// 1. On initialise la session pour pouvoir la manipuler
session_start();

// 2. On vide TOUTES les variables de session (user_id, role, nom, etc.)
$_SESSION = array();

// 3. Si un cookie de session existe, on le détruit aussi (sécurité pro)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. On détruit complètement la session sur le serveur
session_destroy();

// 5. On redirige instantanément vers app.php (point d'entrée du circuit)
header("Location: /coiffons/index.php");
exit();
?>