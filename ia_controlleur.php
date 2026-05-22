<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On force le format de réponse en JSON pour le JavaScript du footer
header('Content-Type: application/json');

// On cache les avertissements PHP pour ne pas polluer le rendu JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/security/config.php';

// Récupération du message ou de la photo depuis la chatbox
$input = json_decode(file_get_contents('php://input'), true);
$message_utilisateur = $input['message'] ?? '';
$image_data = $input['image'] ?? null;

if (empty($message_utilisateur) && empty($image_data)) {
    echo json_encode(['status' => 'error', 'reply' => 'Je n\'ai rien reçu.']);
    exit();
}

// =================================================================
// DÉTECTION MULTI-PROFIL DE LA SESSION (CLIENT, COIFFEUR, ADMIN, INVITÉ)
// =================================================================
$role_actuel = $_SESSION['role'] ?? 'invite';
$nom_user = $_SESSION['nom'] ?? 'Visiteur';
$id_user = $_SESSION['id_user'] ?? $_SESSION['id'] ?? null;
$ville_user = $_SESSION['ville'] ?? '';

// Configuration de base de la personnalité de l'IA
$contexte_systeme = "Tu es l'assistant IA omniscient et officiel de la plateforme 'Coiffe_Chez_Toi' au Bénin. \n";
$contexte_systeme .= "Tu t'adresses actuellement à un utilisateur connecté. Voici ses infos de session : Nom: $nom_user, Rôle: $role_actuel.\n\n";

// =================================================================
// DYNAMISATION DES INSTRUCTIONS SELON LE RÔLE DÉTECTÉ
// =================================================================

if ($role_actuel === 'admin') {
    $nomIA = "Victor";
    $contexte_systeme .= "Tu parles à l'ADMINISTRATEUR du site. Ton nom est $nomIA. Ton rôle est d'être son conseiller stratégique.\n";
    $nb_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $nb_coiffeurs_actifs = $pdo->query("SELECT COUNT(*) FROM users WHERE role='coiffeur' AND abonnement_status=1")->fetchColumn();
    $contexte_systeme .= "Voici les données en temps réel de la plateforme pour l'aider : \n- Nombre total d'utilisateurs inscrits : $nb_users\n- Nombre de coiffeurs avec abonnement actif : $nb_coiffeurs_actifs\n";

} elseif ($role_actuel === 'coiffeur') {
    $nomIA = "Victor";
    $contexte_systeme .= "Tu parles à un COIFFEUR partenaire. Ton nom est $nomIA. Tu es son assistant de gestion de salon.\n";
    
    $stmt = $pdo->prepare("SELECT abonnement_status FROM users WHERE id = ?");
    $stmt->execute([$id_user]);
    $abo = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendezvous WHERE id_coiffeur = ? AND status = 'en_attente'");
    $stmt->execute([$id_user]);
    $rdv_attente = $stmt->fetchColumn();

    $contexte_systeme .= "Données du coiffeur en temps réel :\n- Statut de son abonnement mensuel : " . ($abo ? "ACTIF" : "INACTIF") . "\n- Réservations en attente : $rdv_attente\n";

} elseif ($role_actuel === 'client') {
    $nomIA = "Vanessa";
    $contexte_systeme .= "Tu parles à un CLIENT de la plateforme. Ton nom est $nomIA. Tu es sa conseillère beauté et style. Localisation : $ville_user.\n";
    
    $stmt = $pdo->prepare("SELECT r.date_rdv, u.nom as coiffeur FROM rendezvous r JOIN users u ON r.id_coiffeur = u.id WHERE r.id_client = ? ORDER BY r.date_rdv DESC LIMIT 1");
    $stmt->execute([$id_user]);
    $dernier_rdv = $stmt->fetch();
    
    if ($dernier_rdv) {
        $contexte_systeme .= "- Historique client : Dernier rdv le " . $dernier_rdv['date_rdv'] . " avec " . $dernier_rdv['coiffeur'] . ".\n";
    }

    $stmt = $pdo->prepare("SELECT id, nom, quartier FROM users WHERE role = 'coiffeur' AND abonnement_status = 1 AND ville = ?");
    $stmt->execute([$ville_user]);
    $coiffeurs_locaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $contexte_systeme .= "Professionnels disponibles dans sa ville : " . json_encode($coiffeurs_locaux) . ".\n";

} else {
    $nomIA = "Vanessa";
    $contexte_systeme .= "L'utilisateur est un simple VISITEUR (Invité). Invite-le poliment à s'inscrire ou se connecter pour réserver près de chez lui au Bénin.\n";
}

// =================================================================
// ENVOI DE LA REQUÊTE À L'API GOOGLE GEMINI (MODÈLE STABLE)
// =================================================================
$api_key = 'AIzaSyAHxqVv-NSDE4JYVjl_6ztYtCSVqFTtaPA'; // <-- Remets ta clé API ici !

// Utilisation du modèle stable officiel accessible via l'URL v1 standard
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=" . $api_key;

if ($image_data) {
    $mime_type = "image/jpeg";
    if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
        $mime_type = "image/" . strtolower($type[1]);
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
    }

    $payload = [
        "contents" => [[
            "parts" => [
                ["text" => $contexte_systeme . "\nAnalyse cette photo de coiffure."],
                ["inlineData" => ["mimeType" => $mime_type, "data" => $image_data]]
            ]
        ]]
    ];
} else {
    $payload = [
        "contents" => [[
            "role" => "user",
            "parts" => [
                ["text" => $contexte_systeme . "\nMessage de l'utilisateur : " . $message_utilisateur]
            ]
        ]]
    ];
}

// =================================================================
// EXÉCUTION DE LA REQUÊTE (ALTERNATIVE ROBUSTE POUR XAMPP)
// =================================================================
$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($payload),
        'ignore_errors' => true // Permet de voir le vrai message si Google renvoie un code erreur
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

$resultat = json_decode($response, true);
$reponse_ia = $resultat['candidates'][0]['content']['parts'][0]['text'] ?? null;

// Rendu final vers ton footer
if ($reponse_ia) {
    echo json_encode([
        'status' => 'success',
        'reply' => $reponse_ia
    ]);
} else {
    // Si ça échoue encore, on affiche la réponse brute de Google pour comprendre le blocage exact
    echo json_encode([
        'status' => 'success', 
        'reply' => 'Diagnostic technique : ' . substr(strip_tags($response), 0, 300)
    ]);
}
exit();