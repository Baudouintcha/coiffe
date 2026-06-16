<?php
// 1. Inclure l'alternative officielle de Twilio (via Composer ou téléchargement direct)
// Si tu utilises Composer : require_once 'vendor/autoload.php';
// Sinon, télécharge la bibliothèque Twilio PHP et pointe vers le fichier autoload :
require_once 'twilio-php-main/src/Twilio/autoload.php'; 

use Twilio\Rest\Client;

// 2. Configuration de tes identifiants Twilio (À remplacer plus tard)
$sid    = "TON_ACCOUNT_SID_TWILIO"; 
$token  = "TON_AUTH_TOKEN_TWILIO"; 
$twilio = new Client($sid, $token);

// 3. Connexion à ta base de données (Exemple PDO)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=cft;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// 4. Requête SQL : Trouver les RDV terminés depuis moins d'une heure 
// et qui n'ont pas encore reçu d'avis, ni de notification WhatsApp envoyée.
// (Il faudra idéalement ajouter une colonne `whatsapp_envoye` INT DEFAULT 0 dans ta table rendez_vous)
$sql = "
    SELECT r.id AS id_rdv, r.id_client, u.telephone, u.nom AS nom_client
    FROM rendez_vous r
    JOIN users u ON r.id_client = u.id
    LEFT JOIN commentaires c ON r.id = c.id_rendez_vous
    WHERE CONCAT(r.date_rdv, ' ', r.heure_rdv) < NOW()
      AND r.statut != 'annule'
      AND c.id_commentaire IS NULL
      AND r.whatsapp_envoye = 0
    LIMIT 5
";

$stmt = $pdo->query($sql);
$rdv_a_notifier = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Boucle d'envoi des messages
foreach ($rdv_a_notifier as $rdv) {
    
    // Formatage du numéro de téléphone (Twilio exige l'indicatif, ex: whatsapp:+225XXXXXXXX)
    // On retire les espaces ou les caractères inutiles
    $telephone_client = preg_replace('/[^0-9]/', '', $rdv['telephone']);
    
    // ATTENTION : Remplace '+225' par l'indicatif par défaut de ton pays si les utilisateurs ne le tapent pas
    $to_whatsapp_number = "whatsapp:+" . $telephone_client; 
    
    // Lien unique vers ton formulaire de notation
    $lien_avis = "https://tonsite.com/laisser_avis.php?rdv=" . $rdv['id_rdv'];

    try {
        // Envoi du message via l'API Twilio
        $message = $twilio->messages->create(
            $to_whatsapp_number, // Destinataire
            [
                "from" => "whatsapp:+14155238886", // Le numéro Sandbox de Twilio (fourni sur ton panel)
                "body" => "Bonjour " . htmlspecialchars($rdv['nom_client']) . " ! Votre coiffure est maintenant terminée. Vos retours sont précieux pour notre communauté. Prenez 1 minute pour noter votre artisan ici : " . $lien_avis
            ]
        );

        // 6. Mise à jour de la BDD pour ne pas renvoyer le message au prochain tour
        $update_sql = "UPDATE rendez_vous SET whatsapp_envoye = 1 WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$rdv['id_rdv']]);

        echo "Notification envoyée avec succès au RDV #" . $rdv['id_rdv'] . "<br>";

    } catch (Exception $e) {
        // En cas d'erreur (mauvais numéro, solde Twilio épuisé...), on log l'erreur
        echo "Échec d'envoi pour le RDV #" . $rdv['id_rdv'] . " : " . $e->getMessage() . "<br>";
    }
}
?>