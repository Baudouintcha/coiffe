<?php
/**
 * index.php - Routeur explicite
 */
session_start();
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang = $_SESSION['lang'] ?? 'FR';

$page = $_GET['page'] ?? 'home';
$role = $_GET['role'] ?? null;

switch ($page) {
    case 'login':
        // Redirection directe vers la page de connexion unique
        $file = 'access/connexion.php';
        break;

    case 'register':
        // Gestion explicite des rôles pour l'inscription
        if ($role === 'client' || $role === 'coiffeur') {
            $file = 'access/inscription.php';
        } else {
            // Sécurité : si aucun rôle ou rôle inconnu, retour home
            $file = 'views/home.php';
        }
        break;

    case 'home':
    default:
        $file = 'views/home.php';
        break;
}

if (file_exists($file)) {
    include $file;
} else {
    http_response_code(404);
    echo "<h1>404 - Page non trouvée</h1>";
}
?>