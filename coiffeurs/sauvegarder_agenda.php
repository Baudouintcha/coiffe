<?php
// Fichier : coiffons/coiffeurs/sauvegarder_agenda.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion de la sécurité et de la base de données
require_once __DIR__ . '/../security/config.php';

$coiffeur_id = $_SESSION['user_id'] ?? null;
$role_actuel = $_SESSION['role'] ?? 'invite';

// Sécurisation stricte de l'accès
if (!$coiffeur_id || $role_actuel !== 'prestataire') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

// Vérification que les données viennent bien du formulaire en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Récupération des données du formulaire
    $dispo = $_POST['dispo'] ?? []; // Tableau des jours cochés
    $heures_debut = $_POST['heure_debut'] ?? []; // Tableau des heures de début
    $heures_fin = $_POST['heure_fin'] ?? []; // Tableau des heures de fin
    
    $jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

    try {
        // Début d'une transaction SQL pour garantir la sécurité des données
        $pdo->beginTransaction();

        // 1. Approche Ligne par Ligne : On nettoie d'abord TOUTES les lignes existantes de ce coiffeur
        $delete_stmt = $pdo->prepare("DELETE FROM disponibilites WHERE coiffeur_id = ?");
        $delete_stmt->execute([$coiffeur_id]);

        // 2. Préparation de l'insertion propre pour les nouveaux choix
        $insert_stmt = $pdo->prepare("INSERT INTO disponibilites (coiffeur_id, jour_semaine, heure_debut, heure_fin) VALUES (?, ?, ?, ?)");

        // 3. On parcourt les 7 jours pour insérer uniquement ceux qui ont été cochés
        foreach ($jours_semaine as $jour) {
            if (isset($dispo[$jour]) && $dispo[$jour] == '1') {
                $debut = $heures_debut[$jour] ?? '08:00';
                $fin = $heures_fin[$jour] ?? '19:00';

                // Insertion d'une ligne distincte pour ce jour précis
                $insert_stmt->execute([$coiffeur_id, $jour, $debut, $fin]);
            }
        }

        // Si tout s'est bien passé, on valide définitivement la transaction en BDD
        $pdo->commit();

        // Redirection vers l'agenda avec le paramètre de succès pour afficher les badges verts
        header('Location: /coiffons/coiffeurs/agenda_coiffeurs.php?status=success');
        exit();

    } catch (Exception $e) {
        // En cas de bug, on annule tout pour ne pas laisser la base de données dans un état instable
        $pdo->rollBack();
        die("Erreur critique lors de la sauvegarde de l'agenda : " . $e->getMessage());
    }
} else {
    // Redirection de sécurité si le fichier est accédé directement sans formulaire
    header('Location: /coiffons/coiffeurs/agenda_coiffeurs.php');
    exit();
}