<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once __DIR__ . '/security/config.php';

// Récupération des données envoyées par le chat (Texte ou Image en Base64)
$input = json_decode(file_get_contents('php://input'), true);
$message_utilisateur = $input['message'] ?? '';
$image_data = $input['image'] ?? null; // Si le client envoie une photo (format base64)

if (empty($message_utilisateur) && empty($image_data)) {
    echo json_encode(['status' => 'error', 'reply' => 'Je n\'ai pas compris votre demande.']);
    exit();
}

// 1. DÉTECTION DU CONTEXTE DE SESSION
$role_actuel = $_SESSION['role'] ?? 'invite';
$nom_user = $_SESSION['nom'] ?? 'Visiteur';
$id_user = $_SESSION['id_user'] ?? $_SESSION['id'] ?? null;
$ville_user = $_SESSION['ville'] ?? '';

// 2. INJECTION DES DONNÉES DYNAMIQUES DANS LE PROMPT (La mémoire du site)
$contexte_systeme = "Tu es l'assistant IA officiel de la plateforme 'Coiffe_Chez_Toi'. Ton prénom est Vanessa (si tu parles à un client) ou Victor (si tu parles à un coiffeur). Tu as un ton chaleureux, professionnel et amical.\n";
$contexte_systeme .= "L'utilisateur actuel s'appelle $nom_user, son rôle est '$role_actuel'.\n";

if ($role_actuel === 'client') {
    $contexte_systeme .= "Le client est situé à $ville_user. \n";
    
    // Récupération de son dernier RDV pour l'accueil personnalisé
    $stmt = $pdo->prepare("SELECT r.*, u.nom as nom_coiffeur FROM rendezvous r JOIN users u ON r.id_coiffeur = u.id WHERE r.id_client = ? ORDER BY r.date_rdv DESC LIMIT 1");
    $stmt->execute([$id_user]);
    $dernier_rdv = $stmt->fetch();
    
    if ($dernier_rdv) {
        $contexte_systeme .= "IMPORTANT : Le client a eu un rendez-vous le " . $dernier_rdv['date_rdv'] . " avec le coiffeur " . $dernier_rdv['nom_coiffeur'] . ". Si c'est le début de la conversation, salue-le en faisant référence à ce rendez-vous pour lui demander si ses cheveux ont besoin d'un rafraîchissement.\n";
    }

    // Extraction des coiffeurs abonnés dans sa ville pour le matching
    $stmt = $pdo->prepare("SELECT id, nom, quartier FROM users WHERE role = 'coiffeur' AND abonnement_status = 1 AND ville = ?");
    $stmt->execute([$ville_user]);
    $coiffeurs_locaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $contexte_systeme .= "Voici la liste des coiffeurs disponibles dans sa ville ($ville_user) : " . json_encode($coiffeurs_locaux) . ". Utilise UNIQUEMENT ces coiffeurs si le client cherche une prestation ou si tu dois faire un matching photo.\n";

} elseif ($role_actuel === 'coiffeur') {
    // Vérification de son abonnement
    $stmt = $pdo->prepare("SELECT abonnement_status FROM users WHERE id = ?");
    $stmt->execute([$id_user]);
    $abo = $stmt->fetchColumn();
    
    if (!$abo) {
        $contexte_systeme .= "ATTENTION : Ce coiffeur n'a pas payé son abonnement de 1500 FCFA. Rappelle-lui gentiment mais fermement qu'il doit activer sa vitrine via le module Kkiapay sur son tableau d'accueil pour être visible par les clients.\n";
    } else {
        $contexte_systeme .= "L'abonnement de ce coiffeur est actif. Tu peux l'aider à gérer ses rendez-vous.\n";
    }
} else {
    $contexte_systeme .= "L'utilisateur est un invité non connecté. S'il veut réserver ou voir les coiffeurs de sa ville, invite-le poliment à se connecter ou à se créer un compte.\n";
}

// 3. APPEL À L'API GEMINI PRO (OU GEMINI PRO VISION)
$api_key = 'AIzaSyDMsHNIu_0QGlT7i7t1L4_zJb9qCMnNkLg';

if ($image_data) {
    // Mode Vision : Si l'utilisateur envoie une photo pour un matching
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $api_key;
    
    // Nettoyage du format base64
    $mime_type = "image/jpeg";
    if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
        $mime_type = "image/" . strtolower($type[1]);
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
    }

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $contexte_systeme . "\nL'utilisateur t'envoie cette image de coiffure. Identifie le style exact, dis si un coiffeur de sa zone peut le faire, et propose-lui de l'orienter vers la réservation."],
                    [
                        "inlineData" => [
                            "mimeType" => $mime_type,
                            "data" => $image_data
                        ]
                    ]
                ]
            ]
        ]
    ];
} else {
    // Mode Texte Classique
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=" . $api_key;
    
    $payload = [
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => "Instructions système : " . $contexte_systeme],
                    ["text" => "Message de l'utilisateur : " . $message_utilisateur]
                ]
            ]
        ]
    ];
}

// Configuration de la requête CURL vers Google Gemini
$ch = curl_init($url);
curl_setopt($ch, curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false // À retirer en production si certificat SSL actif
]));

$response = curl_exec($ch);
curl_close($ch);

$data = json_encode($response, true);
$reponse_ia = $data['candidates'][0]['content']['parts'][0]['text'] ?? "Désolée, j'ai rencontré un petit problème technique pour analyser votre demande. Réessayez !";

// 4. RETOUR VERS LE CHATBOX DU FOOTER
echo json_encode([
    'status' => 'success',
    'reply' => $reponse_ia
]);