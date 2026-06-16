<?php
session_start();

// 1. SÉCURITÉ : Vérifier que le client est bien connecté et que les données viennent du formulaire
if (!isset($_SESSION['id_client']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$id_client = $_SESSION['id_client'];
$id_rendez_vous = intval($_POST['id_rendez_vous']);
$id_coiffeur = intval($_POST['id_coiffeur']);
$note = intval($_POST['note']);
$commentaire = trim($_POST['commentaire']);

// 2. Connexion à la Base de Données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=cft;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// 3. double vérification : Est-ce que ce RDV n'a pas déjà été noté ?
$verif = $pdo->prepare("SELECT id_commentaire FROM commentaires WHERE id_rendez_vous = ?");
$verif->execute([$id_rendez_vous]);
if ($verif->fetch()) {
    die("Vous avez déjà laissé un avis pour ce rendez-vous.");
}

try {
    // ÉTAPE A : Commencer une transaction (Sécurité BDD : si un enregistrement échoue, tout s'annule)
    $pdo->beginTransaction();

    // ÉTAPE B : Insertion dans ta table `commentaires`
    // J'utilise les colonnes classiques, adapte les noms si nécessaire (ex: id_client, id_coiffeur, etc.)
    $sql_avis = "INSERT INTO commentaires (id_rendez_vous, id_coiffeur, id_client, commentaire, note, date_commentaire) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt_avis = $pdo->prepare($sql_avis);
    $stmt_avis->execute([$id_rendez_vous, $id_coiffeur, $id_client, $commentaire, $note]);

    // ÉTAPE C : Est-ce que le client a coché la case "Signaler un problème" ?
    if (isset($_POST['signaler_probleme']) && $_POST['signaler_probleme'] == '1') {
        $motif_plainte = trim($_POST['motif_plainte']);
        
        if (!empty($motif_plainte)) {
            // Insertion dans notre toute nouvelle table `plaintes`
            $sql_plainte = "INSERT INTO plaintes (id_rendez_vous, id_coiffeur, id_client, motif, statut, date_plainte) 
                            VALUES (?, ?, ?, ?, 'active', NOW())";
            $stmt_plainte = $pdo->prepare($sql_plainte);
            $stmt_plainte->execute([$id_rendez_vous, $id_coiffeur, $id_client, $motif_plainte]);
        }
    }

    // Si tout est bon, on valide définitivement les écritures en BDD
    $pdo->commit();

    // 4. Redirection vers l'espace client avec un message de succès
    // (Tu pourras afficher ce message avec un $_GET['success'] sur ta page d'accueil)
    header('Location: client/index.php?status=avis_enregistre');
    exit();

} catch (Exception $e) {
    // En cas d'erreur réseau ou BDD, on annule tout pour ne pas avoir de données corrompues
    $pdo->rollBack();
    die("Une erreur est survenue lors de l'enregistrement : " . $e->getMessage());
}