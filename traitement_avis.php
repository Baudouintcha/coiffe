<?php
session_start();

// Chemin de redirection client (à adapter si besoin)
$url_client = 'historique_rdv.php'; 

if (!isset($_SESSION['id_client']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$id_client = $_SESSION['id_client'];
$id_rdv = intval($_POST['id_rendez_vous']);
$id_coiffeur = intval($_POST['id_coiffeur']);
$note = intval($_POST['note']);
$commentaire = htmlspecialchars(trim($_POST['commentaire']));
$signaler = isset($_POST['signaler_probleme']) ? intval($_POST['signaler_probleme']) : 0;
$motif = trim($_POST['motif_plainte']);

// 1. VALIDATION MÉTIER
if ($note < 1 || $note > 5) {
    $_SESSION['error'] = "Veuillez attribuer une note valide.";
    header("Location: laisser_avis.php?rdv=$id_rdv");
    exit();
}

// Règle : Si note basse (<= 2) et plainte manquante
if ($note <= 2 && $signaler === 1 && empty($motif)) {
    $_SESSION['error'] = "Veuillez expliquer le problème pour une note basse.";
    header("Location: laisser_avis.php?rdv=$id_rdv");
    exit();
}

// 2. CONNEXION ET TRANSACTION
try {
    $pdo = new PDO("mysql:host=localhost;dbname=cft;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->beginTransaction();

    // Insertion avis
    $stmt = $pdo->prepare("INSERT INTO commentaires (id_rendez_vous, id_coiffeur, id_client, commentaire, note, date_commentaire) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$id_rdv, $id_coiffeur, $id_client, $commentaire, $note]);

    // Insertion plainte si nécessaire
    if ($signaler === 1 && !empty($motif)) {
        $stmt_p = $pdo->prepare("INSERT INTO plaintes (id_rendez_vous, id_coiffeur, id_client, motif, statut, date_plainte) VALUES (?, ?, ?, ?, 'active', NOW())");
        $stmt_p->execute([$id_rdv, $id_coiffeur, $id_client, $motif]);
    }

    $pdo->commit();
    $_SESSION['success'] = "Votre avis a été enregistré avec succès.";
    header("Location: $url_client");
    exit();

} catch (Exception $e) {
    if (isset($pdo)) $pdo->rollBack();
    $_SESSION['error'] = "Erreur technique : " . $e->getMessage();
    header("Location: laisser_avis.php?rdv=$id_rdv");
    exit();
}