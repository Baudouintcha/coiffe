<?php
session_start();

// SÉCURITÉ : Si le client n'est pas connecté, on le dégage
if (!isset($_SESSION['id_client'])) {
    header('Location: login.php');
    exit();
}

$id_client = $_SESSION['id_client'];

// Vérifier qu'on a bien un ID de rendez-vous dans l'URL
if (!isset($_GET['rdv']) || empty($_GET['rdv'])) {
    die("Rendez-vous introuvable.");
}

$id_rdv = intval($_GET['rdv']);

// Connexion BDD
try {
    $pdo = new PDO("mysql:host=localhost;dbname=cft;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// REQUÊTE DE SÉCURITÉ : Le RDV appartient bien au client, est-il passé, et n'a-t-il pas déjà été noté ?
$query = "
    SELECT r.id, r.id_coiffeur, u.nom AS nom_coiffeur 
    FROM rendez_vous r
    JOIN users u ON r.id_coiffeur = u.id
    LEFT JOIN commentaires c ON r.id = c.id_rendez_vous
    WHERE r.id = ? AND r.id_client = ? AND CONCAT(r.date_rdv, ' ', r.heure_rdv) < NOW()
";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_rdv, $id_client]);
$rdv = $stmt->fetch();

if (!$rdv) {
    die("Action impossible : Ce rendez-vous n'existe pas, n'est pas encore terminé, ou a déjà été noté.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Laisser un avis</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 500px; background: white; padding: 30px; margin: 0 auto; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-top: 0; }
        
        /* --- STYLE MAGIQUE DES ÉTOILES DE NOTATION --- */
        .rating-box { display: flex; flex-direction: row-reverse; justify-content: flex-end; margin: 15px 0; }
        .rating-box input { display: none; } /* On cache les boutons radio ronds */
        .rating-box label { font-size: 40px; color: #ccc; cursor: pointer; transition: color 0.2s; }
        /* Quand on survole ou coche, on colore en jaune */
        .rating-box label:hover,
        .rating-box label:hover ~ label,
        .rating-box input:checked ~ label { color: #ffca28; }
        
        textarea { width: 100%; height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; resize: none; margin-bottom: 20px; }
        
        /* --- BLOCK SIGNALEMENT/PLAINTE --- */
        .alert-box { background: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 6px; margin-top: 20px; }
        .alert-box label { font-weight: bold; color: #856404; cursor: pointer; }
        .plainte-input { display: none; margin-top: 10px; }
        /* Astuce CSS pour afficher le champ texte de la plainte UNIQUEMENT si la case est cochée */
        #signaler:checked ~ .plainte-input { display: block; }
        
        button { background: #007bff; color: white; border: none; padding: 12px 20px; font-size: 16px; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="container">
    <h2>Avis sur votre coiffure</h2>
    <p>Comment s'est passée votre prestation avec <strong><?php echo htmlspecialchars($rdv['nom_coiffeur']); ?></strong> ?</p>
    
    <form action="../traitement_avis.php" method="POST">
        <input type="hidden" name="id_rendez_vous" value="<?php echo $rdv['id']; ?>">
        <input type="hidden" name="id_coiffeur" value="<?php echo $rdv['id_coiffeur']; ?>">

        <div class="rating-box">
            <input type="radio" id="star5" name="note" value="5" required><label for="star5">★</label>
            <input type="radio" id="star4" name="note" value="4"><label for="star4">★</label>
            <input type="radio" id="star3" name="note" value="3"><label for="star3">★</label>
            <input type="radio" id="star2" name="note" value="2"><label for="star2">★</label>
            <input type="radio" id="star1" name="note" value="1"><label for="star1">★</label>
        </div>

        <label>Votre commentaire public :</label>
        <textarea name="commentaire" placeholder="Partagez votre expérience avec les autres clients..." required></textarea>

        <div class="alert-box">
            <input type="checkbox" id="signaler" name="signaler_probleme" value="1">
            <label for="signaler">⚠️ Signaler un problème grave à l'administration</label>
            
            <div class="plainte-input">
                <p style="font-size: 13px; color: #666; margin: 0 0 10px 0;">Le coiffeur ne verra pas ce texte. Seul l'admin mènera l'enquête.</p>
                <textarea name="motif_plainte" placeholder="Expliquez ici ce qui s'est mal passé (absence, vol, comportement inapproprié...)"></textarea>
            </div>
        </div>
        
        <br>
        <button type="submit">Envoyer mon avis</button>
    </form>
</div>

</body>
</html>