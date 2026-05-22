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
    // 1. COMPORTEMENT POUR L'ADMINISTRATEUR
    $nomIA = "Victor";
    $contexte_systeme .= "Tu parles à l'ADMINISTRATEUR du site. Ton nom est $nomIA. Ton rôle est d'être son conseiller stratégique.\n";
    
    // On extrait rapidement quelques statistiques pour l'Admin
    $nb_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $nb_coiffeurs_actifs = $pdo->query("SELECT COUNT(*) FROM users WHERE role='coiffeur' AND abonnement_status=1")->fetchColumn();
    
    $contexte_systeme .= "Voici les données en temps réel de la plateforme pour l'aider : \n";
    $contexte_systeme .= "- Nombre total d'utilisateurs inscrits : $nb_users\n";
    $contexte_systeme .= "- Nombre de coiffeurs avec abonnement actif : $nb_coiffeurs_actifs\n";
    $contexte_systeme .= "Parle-lui avec le respect dû à son rang (vouvoiement, ton professionnel). Aide-le à analyser ces chiffres s'il te demande l'état du site.\n";

} elseif ($role_actuel === 'coiffeur') {
    // 2. COMPORTEMENT POUR LE COIFFEUR
    $nomIA = "Victor";
    $contexte_systeme .= "Tu parles à un COIFFEUR partenaire. Ton nom est $nomIA. Tu es son assistant de gestion de salon.\n";
    
    // Vérification de son abonnement et de ses rendez-vous
    $stmt = $pdo->prepare("SELECT abonnement_status FROM users WHERE id = ?");
    $stmt->execute([$id_user]);
    $abo = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendezvous WHERE id_coiffeur = ? AND status = 'en_attente'");
    $stmt->execute([$id_user]);
    $rdv_attente = $stmt->fetchColumn();

    $contexte_systeme .= "Données du coiffeur en temps réel :\n";
    $contexte_systeme .= "- Statut de son abonnement mensuel (1500 FCFA) : " . ($abo ? "ACTIF / PAYÉ" : "INACTIF / NON PAYÉ") . "\n";
    $contexte_systeme .= "- Nombre de réservations de clients en attente de sa validation : $rdv_attente\n";
    
    if (!$abo) {
        $contexte_systeme .= "ATTENTION CRITIQUE : Son abonnement est expiré. Rappelle-lui amicalement dès le début qu'il doit régulariser son abonnement de 1500 FCFA sur son tableau de bord via Kkiapay pour rester visible dans l'annuaire du Bénin.\n";
    }
    if ($rdv_attente > 0) {
        $contexte_systeme .= "Notification : Dis-le lui qu'il a $rdv_attente demande(s) de rendez-vous en attente de validation dans son espace coiffeur.\n";
    }

} elseif ($role_actuel === 'client') {
    // 3. COMPORTEMENT POUR LE CLIENT
    $nomIA = "Vanessa";
    $contexte_systeme .= "Tu parles à un CLIENT de la plateforme. Ton nom est $nomIA. Tu es sa conseillère beauté et style.\n";
    $contexte_systeme .= "Le client est situé dans la ville de : $ville_user.\n";
    
    // Récupération de son dernier rendez-vous historique
    $stmt = $pdo->prepare("SELECT r.date_rdv, u.nom as coiffeur FROM rendezvous r JOIN users u ON r.id_coiffeur = u.id WHERE r.id_client = ? ORDER BY r.date_rdv DESC LIMIT 1");
    $stmt->execute([$id_user]);
    $dernier_rdv = $stmt->fetch();
    
    if ($dernier_rdv) {
        $contexte_systeme .= "- Historique client : Son dernier rendez-vous s'est déroulé le " . $dernier_rdv['date_rdv'] . " avec le coiffeur " . $dernier_rdv['coiffeur'] . ".\n";
        $contexte_systeme .= "- Consigne : S'il s'agit du tout premier message, fais-y allusion subtilement pour lui demander si sa coupe a besoin d'être rafraîchie ou s'il veut changer de look ! 😉\n";
    }

    // Extraction des coiffeurs disponibles dans sa zone pour faire du matching direct
    $stmt = $pdo->prepare("SELECT id, nom, quartier FROM users WHERE role = 'coiffeur' AND abonnement_status = 1 AND ville = ?");
    $stmt->execute([$ville_user]);
    $coiffeurs_locaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $contexte_systeme .= "Voici les professionnels disponibles dans sa ville ($ville_user) : " . json_encode($coiffeurs_locaux) . ".\n";
    $contexte_systeme .= "S'il te demande une coiffure ou t'envoie une photo, recommande-lui explicitement l'un de ces professionnels de sa zone géographique et explique-lui comment cliquer sur 'Prendre RDV'.\n";

} else {
    // 4. COMPORTEMENT POUR LE VISITEUR ANONYME / NON CONNECTÉ
    $nomIA = "Vanessa";
    $contexte_systeme .= "L'utilisateur est un simple VISITEUR (Invité). Il n'est pas connecté.\n";
    $contexte_systeme .= "Ton but est d'être ultra-accueillante, de répondre à ses questions sur la coiffure, mais de lui rappeler de manière très séduisante que pour voir les meilleurs barbiers et coiffeurs du Bénin près de chez lui et réserver un créneau à domicile, il doit créer un compte gratuitement en cliquant sur 'Créer un compte' dans la barre latérale.\n";
}

// =================================================================
// ENVOI DE LA REQUÊTE À L'API GOOGLE GEMINI (MODÈLE GEMINI 2.5 FLASH)
// =================================================================
$api_key = 'AIzaSyDMsHNIu_0QGlT7i7t1L4_zJb9qCMnNkLg'; // <-- Remplace impérativement par ta clé Google AI Studio !

if ($image_data) {
    // Mode Vision (Analyse de la photo)
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;
    
    $mime_type = "image/jpeg";
    if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
        $mime_type = "image/" . strtolower($type[1]);
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
    }

    $payload = [
        "contents" => [[
            "parts" => [
                ["text" => $contexte_systeme . "\nAnalyse visuellement cette photo de coiffure. Identifie le style précis (Braid, Dégradé, Locks, Twist, etc.), dis si c'est réalisable, regarde dans les données fournies si un coiffeur de sa zone est dispo et conseille-le."],
                ["inlineData" => ["mimeType" => $mime_type, "data" => $image_data]]
            ]
        ]]
    ];
} else {
    // Mode Discussion Texte Classique
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;
    
    $payload = [
        "contents" => [[
            "role" => "user",
            "parts" => [
                ["text" => $contexte_systeme . "\nVoici le message de l'utilisateur : " . $message_utilisateur]
            ]
        ]]
    ];
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($ch);
curl_close($ch);

$resultat = json_decode($response, true);
$reponse_ia = $resultat['candidates'][0]['content']['parts'][0]['text'] ?? null;

// Rendu final vers ton footer
if ($reponse_ia) {
    echo json_encode([
        'status' => 'success',
        'reply' => $reponse_ia
    ]);
} else {
    echo json_encode([
        'status' => 'success', 
        'reply' => 'Oups ! J\'ai rencontré une anomalie lors de la liaison avec le système Google Gemini : ' . json_encode($resultat)
    ]);
}
exit();