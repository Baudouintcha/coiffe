<?php
// index.php : Le routeur central
$page = $_GET['page'] ?? 'home';

switch($page) {
    case 'login':
        include 'access/connexion.php';
        break;

    case 'register':
        // On récupère le rôle pour que la page d'inscription s'adapte
        $role = $_GET['role'] ?? 'client';
        include 'access/inscription.php';
        break;

    case 'home':
    default:
        include 'views/home.php';
        break;
}
?>