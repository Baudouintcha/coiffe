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
$image_data           = $input['image'] ?? null;

if (empty($message_utilisateur) && empty($image_data)) {
    echo json_encode(['status' => 'error', 'reply' => 'Je n\'ai rien reçu.']);
    exit();
}

// =================================================================
// DÉTECTION MULTI-PROFIL DE LA SESSION (CLIENT, COIFFEUR, ADMIN, INVITÉ)
// =================================================================
$role_actuel = $_SESSION['role']    ?? 'invite';
$nom_user    = $_SESSION['nom']     ?? 'Visiteur';
$id_user     = $_SESSION['id_user'] ?? $_SESSION['id'] ?? null;
$ville_user  = $_SESSION['ville']   ?? '';

// =================================================================
// CONSTRUCTION DU CONTEXTE MÉTIER ENRICHI SELON LE RÔLE
// =================================================================

$contexte_systeme  = "Tu es l'assistant IA officiel de la plateforme 'Coiffe Chez Toi' au Bénin. ";
$contexte_systeme .= "Tu réponds toujours en français, de façon chaleureuse, claire et professionnelle. ";
$contexte_systeme .= "Voici les infos de session : Nom: $nom_user, Rôle: $role_actuel.\n\n";

// ─────────────────────────────────────────────
// RÔLE : VISITEUR (non connecté)
// ─────────────────────────────────────────────
if ($role_actuel === 'invite') {
    $nomIA = "Vanessa";

    $contexte_systeme .= "L'utilisateur est un VISITEUR non connecté. Ton nom est $nomIA.\n";
    $contexte_systeme .= "Instructions système :\n";
    $contexte_systeme .= "- Aide le visiteur à trouver un coiffeur à domicile au Bénin.\n";
    $contexte_systeme .= "- Explique-lui comment s'inscrire (bouton 'Je suis Client' sur la page d'accueil).\n";
    $contexte_systeme .= "- Donne-lui le lien direct vers l'annuaire : /coiffons/filter/annuaire_coiffeurs.php\n";
    $contexte_systeme .= "- Si l'utilisateur cherche un coiffeur, oriente-le vers l'annuaire.\n\n";

    // Injecter les 6 derniers coiffeurs actifs
    try {
        $stmt = $pdo->query(
            "SELECT u.nom, u.prenom, u.ville,
                    (SELECT s.nom_service FROM services s WHERE s.coiffeur_id = u.id LIMIT 1) AS prestation_principale
             FROM users u
             WHERE u.role = 'coiffeur'
               AND u.abonnement_status = 1
               AND u.is_approved = 1
             ORDER BY u.id DESC
             LIMIT 6"
        );
        $coiffeurs_actifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($coiffeurs_actifs)) {
            $contexte_systeme .= "Voici les 6 derniers coiffeurs actifs sur la plateforme (pour guider le visiteur) :\n";
            foreach ($coiffeurs_actifs as $c) {
                $prestation = $c['prestation_principale'] ?? 'Coiffure à domicile';
                $contexte_systeme .= "- {$c['nom']} {$c['prenom']} (Ville : {$c['ville']}) — Prestation : $prestation\n";
            }
            $contexte_systeme .= "\n";
        } else {
            $contexte_systeme .= "Actuellement, des coiffeurs partenaires sont disponibles dans plusieurs villes du Bénin. ";
            $contexte_systeme .= "Invite le visiteur à consulter l'annuaire pour en voir la liste complète.\n\n";
        }
    } catch (Exception $e) {
        // Bloc non-bloquant : on continue sans données BDD
    }

// ─────────────────────────────────────────────
// RÔLE : CLIENT
// ─────────────────────────────────────────────
} elseif ($role_actuel === 'client') {
    $nomIA = "Vanessa";

    $contexte_systeme .= "Tu parles à un CLIENT de la plateforme. Ton nom est $nomIA. Tu es sa conseillère beauté et style.\n";
    $contexte_systeme .= "Instructions système :\n";
    $contexte_systeme .= "- Guide le client vers ses RDV sur /coiffons/client/mes_rendezvous.php\n";
    $contexte_systeme .= "- Explique les statuts : en_attente = le coiffeur n'a pas encore accepté, confirme = accepté par le coiffeur, termine = prestation terminée.\n";
    $contexte_systeme .= "- Dis-lui comment recharger son portefeuille (aller dans Mon Profil → Portefeuille).\n";
    $contexte_systeme .= "- Pour réserver : /coiffons/filter/annuaire_coiffeurs.php puis choisir un coiffeur.\n\n";

    // Solde du client
    try {
        $stmt = $pdo->prepare("SELECT solde FROM users WHERE id = ?");
        $stmt->execute([$id_user]);
        $solde_client = $stmt->fetchColumn();
        $contexte_systeme .= "Solde actuel du portefeuille client : " . number_format((float)$solde_client, 0, ',', ' ') . " FCFA\n";
    } catch (Exception $e) {}

    // 3 derniers RDV du client
    try {
        $stmt = $pdo->prepare(
            "SELECT r.date_rdv, r.heure_rdv, r.statut_rdv,
                    u.nom AS coiffeur_nom, u.prenom AS coiffeur_prenom
             FROM rendez_vous r
             JOIN users u ON r.id_coiffeur = u.id
             WHERE r.id_client = ?
             ORDER BY r.date_rdv DESC
             LIMIT 3"
        );
        $stmt->execute([$id_user]);
        $derniers_rdv = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($derniers_rdv)) {
            $contexte_systeme .= "Ses 3 derniers RDV :\n";
            foreach ($derniers_rdv as $rdv) {
                $contexte_systeme .= "  - Le {$rdv['date_rdv']} à {$rdv['heure_rdv']} avec {$rdv['coiffeur_nom']} {$rdv['coiffeur_prenom']} — Statut : {$rdv['statut_rdv']}\n";
            }
        } else {
            $contexte_systeme .= "Ce client n'a pas encore de RDV. Encourage-le à réserver sur l'annuaire.\n";
        }
    } catch (Exception $e) {}

    // Nombre de RDV en attente
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE id_client = ? AND statut_rdv = 'en_attente'");
        $stmt->execute([$id_user]);
        $rdv_en_attente = $stmt->fetchColumn();
        $contexte_systeme .= "Nombre de RDV en attente de confirmation : $rdv_en_attente\n\n";
    } catch (Exception $e) {}

    // Réponse enrichie si le message parle de RDV
    $mots_rdv = ['rdv', 'réservation', 'rendez-vous', 'rendez_vous', 'reservation'];
    $message_lower = mb_strtolower($message_utilisateur);
    $parle_rdv = false;
    foreach ($mots_rdv as $mot) {
        if (str_contains($message_lower, $mot)) {
            $parle_rdv = true;
            break;
        }
    }
    if ($parle_rdv && !empty($derniers_rdv)) {
        $contexte_systeme .= "IMPORTANT : Le client pose une question sur ses RDV. Utilise les données de ses RDV ci-dessus pour répondre avec précision.\n";
    }

// ─────────────────────────────────────────────
// RÔLE : COIFFEUR
// ─────────────────────────────────────────────
} elseif ($role_actuel === 'coiffeur') {
    $nomIA = "Victor";

    $contexte_systeme .= "Tu parles à un COIFFEUR partenaire. Ton nom est $nomIA. Tu es son assistant de gestion de salon.\n";
    $contexte_systeme .= "Instructions système :\n";
    $contexte_systeme .= "- Pour valider/refuser des RDV : /coiffons/coiffeurs/valider_rendezvous.php\n";
    $contexte_systeme .= "- Pour gérer le catalogue de prestations : /coiffons/coiffeurs/gestion_catalogue.php\n";
    $contexte_systeme .= "- Pour consulter le portefeuille et les finances : /coiffons/coiffeurs/portefeuille.php\n";
    $contexte_systeme .= "- Pour gérer l'agenda : /coiffons/coiffeurs/agenda_coiffeurs.php\n\n";

    // Statut abonnement
    try {
        $stmt = $pdo->prepare("SELECT abonnement_status, solde FROM users WHERE id = ?");
        $stmt->execute([$id_user]);
        $infos_coiffeur = $stmt->fetch(PDO::FETCH_ASSOC);
        $abo_statut   = ($infos_coiffeur['abonnement_status'] ?? 0) ? 'ACTIF' : 'INACTIF';
        $solde_coiffeur = number_format((float)($infos_coiffeur['solde'] ?? 0), 0, ',', ' ');
        $contexte_systeme .= "Statut abonnement mensuel : $abo_statut\n";
        $contexte_systeme .= "Solde du portefeuille : $solde_coiffeur FCFA\n";
    } catch (Exception $e) {}

    // RDV du jour
    try {
        $aujourd_hui = date('Y-m-d');
        $stmt = $pdo->prepare(
            "SELECT r.date_rdv, r.heure_rdv, r.statut_rdv,
                    u.nom AS client_nom, u.prenom AS client_prenom
             FROM rendez_vous r
             JOIN users u ON r.id_client = u.id
             WHERE r.id_coiffeur = ?
               AND r.date_rdv = ?
             ORDER BY r.heure_rdv ASC"
        );
        $stmt->execute([$id_user, $aujourd_hui]);
        $rdv_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rdv_jour)) {
            $contexte_systeme .= "RDV du jour ($aujourd_hui) :\n";
            foreach ($rdv_jour as $rdv) {
                $contexte_systeme .= "  - {$rdv['heure_rdv']} avec {$rdv['client_nom']} {$rdv['client_prenom']} — Statut : {$rdv['statut_rdv']}\n";
            }
        } else {
            $contexte_systeme .= "Aucun RDV prévu pour aujourd'hui ($aujourd_hui).\n";
        }
    } catch (Exception $e) {}

    // Demandes en attente
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE id_coiffeur = ? AND statut_rdv = 'en_attente'");
        $stmt->execute([$id_user]);
        $demandes_attente = $stmt->fetchColumn();
        $contexte_systeme .= "Demandes de RDV en attente de validation : $demandes_attente\n\n";
    } catch (Exception $e) {}

// ─────────────────────────────────────────────
// RÔLE : ADMIN
// ─────────────────────────────────────────────
} elseif ($role_actuel === 'admin') {
    $nomIA = "Victor";

    $contexte_systeme .= "Tu parles à l'ADMINISTRATEUR de la plateforme. Ton nom est $nomIA. Tu es son conseiller stratégique.\n";
    $contexte_systeme .= "Instructions système :\n";
    $contexte_systeme .= "- Fournis des statistiques précises basées sur les données réelles ci-dessous.\n";
    $contexte_systeme .= "- Guide vers le tableau de bord admin : /coiffons/first/admin_dashboard.php\n\n";

    // Nombre total d'utilisateurs
    try {
        $nb_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $contexte_systeme .= "Nombre total d'utilisateurs inscrits : $nb_users\n";
    } catch (Exception $e) {}

    // Coiffeurs actifs / inactifs
    try {
        $nb_coiffeurs_actifs   = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur' AND abonnement_status = 1")->fetchColumn();
        $nb_coiffeurs_inactifs = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur' AND abonnement_status = 0")->fetchColumn();
        $contexte_systeme .= "Coiffeurs avec abonnement actif : $nb_coiffeurs_actifs\n";
        $contexte_systeme .= "Coiffeurs avec abonnement inactif : $nb_coiffeurs_inactifs\n";
    } catch (Exception $e) {}

    // RDV en attente sur toute la plateforme
    try {
        $nb_rdv_attente = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut_rdv = 'en_attente'")->fetchColumn();
        $contexte_systeme .= "RDV en attente de validation sur la plateforme : $nb_rdv_attente\n";
    } catch (Exception $e) {}

    // Diplômes en attente d'approbation
    try {
        $nb_diplomes_attente = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur' AND is_approved = 0")->fetchColumn();
        $contexte_systeme .= "Coiffeurs en attente d'approbation de diplôme : $nb_diplomes_attente\n\n";
    } catch (Exception $e) {}

} else {
    // Rôle inconnu → traitement comme visiteur
    $nomIA = "Vanessa";
    $contexte_systeme .= "L'utilisateur a un rôle non reconnu. Invite-le poliment à se connecter ou s'inscrire.\n";
}

// =================================================================
// ENVOI DE LA REQUÊTE À L'API GOOGLE GEMINI (MODÈLE STABLE)
// =================================================================
$api_key = 'AIzaSyAHxqVv-NSDE4JYVjl_6ztYtCSVqFTtaPA';

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=" . $api_key;

if ($image_data) {
    $mime_type = "image/jpeg";
    if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type)) {
        $mime_type  = "image/" . strtolower($type[1]);
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
    }

    $payload = [
        "contents" => [[
            "parts" => [
                ["text" => $contexte_systeme . "\nAnalyse cette photo de coiffure et donne des conseils personnalisés."],
                ["inlineData" => ["mimeType" => $mime_type, "data" => $image_data]]
            ]
        ]]
    ];
} else {
    $payload = [
        "contents" => [[
            "role"  => "user",
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
        'header'        => "Content-Type: application/json\r\n",
        'method'        => 'POST',
        'content'       => json_encode($payload),
        'ignore_errors' => true
    ],
    'ssl'  => [
        'verify_peer'      => false,
        'verify_peer_name' => false
    ]
];

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);

$resultat   = json_decode($response, true);
$reponse_ia = $resultat['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($reponse_ia) {
    echo json_encode([
        'status' => 'success',
        'reply'  => $reponse_ia
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'reply'  => 'Diagnostic technique : ' . substr(strip_tags($response ?? ''), 0, 300)
    ]);
}
exit();
