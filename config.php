<?php
// On définit les paramètres de connexion à la base de données
$host = 'localhost';      // L'adresse du serveur (en local c'est localhost)
$dbname = 'cft'; // REMPLACE PAR LE NOM QUE TU AS DONNÉ À TA BDD
$username = 'root';       // Identifiant par défaut sur XAMPP/WAMP
$password = '';           // Par défaut, il n'y a pas de mot de passe sur XAMPP

try {
    // On crée une instance de PDO pour se connecter à MySQL
    // On ajoute l'option pour que les erreurs SQL soient visibles (très important pour le développement)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Active l'affichage des erreurs SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC // Les résultats seront sous forme de tableaux associatifs
    ]);

    // Si la connexion réussit, rien ne s'affiche (c'est le but)
} catch (PDOException $e) {
    // Si la connexion échoue (mauvais mot de passe ou nom de BDD), on arrête tout et on affiche l'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
