<?php
/**
 * ia_controlleur.php — Contrôleur IA métier Coiffe Chez Toi
 * Version 2.0 — Assistant contextuel enrichi
 *
 * Rôles gérés : visiteur | client | coiffeur | admin
 * Modèle      : Google Gemini 2.0 Flash
 * API key     : variable d'environnement (sécurité)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/security/config.php';

// ─── Entrée ─────────────────────────────────────────────────────────────────
$input               = json_decode(file_get_contents('php://input'), true);
$message_utilisateur = trim($input['message'] ?? '');
$image_data          = $input['image'] ?? null;

if (empty($message_utilisateur) && empty($image_data)) {
    echo json_encode(['status' => 'error', 'reply' => "Je n'ai rien reçu."]);
    exit();
}

// ─── Session ─────────────────────────────────────────────────────────────────
$role_actuel = $_SESSION['role']    ?? 'invite';
$nom_user    = htmlspecialchars($_SESSION['nom']    ?? 'Visiteur');
$prenom_user = htmlspecialchars($_SESSION['prenom'] ?? '');
$id_user     = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : null;
$ville_user  = $_SESSION['ville'] ?? 0;

$message_lower = mb_strtolower($message_utilisateur, 'UTF-8');

// ─── Helper : détecter mots-clés ────────────────────────────────────────────
function ia_contient(string $texte, array $mots): bool {
    foreach ($mots as $mot) {
        if (mb_strpos($texte, $mot, 0, 'UTF-8') !== false) return true;
    }
    return false;
}

// =============================================================================
// CONSTRUCTION DU CONTEXTE SYSTÈME
// =============================================================================

$contexte_systeme  = "Tu es l'assistant IA officiel de la plateforme **Coiffe Chez Toi** au Bénin.\n";
$contexte_systeme .= "Tu t'appelles **Vanessa** (pour les clients et visiteurs) ou **Victor** (pour les coiffeurs et admins).\n";
$contexte_systeme .= "Tu réponds toujours en français, de façon chaleureuse, claire, concise.\n";
$contexte_systeme .= "Quand tu mentionnes une page du site, fournis le lien cliquable en Markdown : [Texte du lien](url).\n";
$contexte_systeme .= "Ne dépasse pas 150 mots par réponse sauf si l'utilisateur pose une question technique.\n\n";
$contexte_systeme .= "Utilisateur actuel : $prenom_user $nom_user — Rôle : $role_actuel\n\n";

// =============================================================================
// BLOC VISITEUR
// =============================================================================
if ($role_actuel === 'invite') {

    $nomIA = "Vanessa";
    $contexte_systeme .= "## Contexte : VISITEUR NON CONNECTÉ\n";
    $contexte_systeme .= "Objectif : aider ce visiteur à comprendre la plateforme et à trouver un coiffeur.\n";
    $contexte_systeme .= "- Annuaire : [Voir les coiffeurs](/coiffons/filter/annuaire_coiffeurs.php)\n";
    $contexte_systeme .= "- Inscription client : [Créer un compte](/coiffons/index.php?page=register&role=client)\n";
    $contexte_systeme .= "- Connexion : [Se connecter](/coiffons/index.php?page=login)\n\n";

    // Coiffeurs actifs
    try {
        $stmt = $pdo->query(
            "SELECT u.id, u.nom, u.prenom, v.nom_ville,
                    (SELECT p.nom_style FROM prestations p WHERE p.id_coiffeur = u.id LIMIT 1) AS prestation
             FROM users u
             LEFT JOIN villes v ON u.ville = v.id
             WHERE u.role = 'coiffeur' AND u.abonnement_status = 1 AND u.is_approved = 1
             ORDER BY RAND() LIMIT 6"
        );
        $coiffeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($coiffeurs)) {
            $contexte_systeme .= "### Coiffeurs actifs disponibles (exemples) :\n";
            foreach ($coiffeurs as $c) {
                $prestation = $c['prestation'] ?? 'Coiffure à domicile';
                $lien = "/coiffons/coiffeurs/profil_public.php?id={$c['id']}";
                $contexte_systeme .= "- [{$c['nom']} {$c['prenom']}]($lien) — {$c['nom_ville']} — $prestation\n";
            }
        }
    } catch (Exception $e) {}

    // Détection intention recherche coiffeur
    if (ia_contient($message_lower, ['coiffeur', 'coiffeuse', 'coiffure', 'tresse', 'natte', 'dégradé', 'loco', 'cheveux'])) {
        $contexte_systeme .= "\nL'utilisateur cherche visiblement un coiffeur. Propose-lui directement de visiter l'annuaire avec le lien ci-dessus.\n";
    }

// =============================================================================
// BLOC CLIENT
// =============================================================================
} elseif ($role_actuel === 'client') {

    $nomIA = "Vanessa";
    $contexte_systeme .= "## Contexte : CLIENT CONNECTÉ\n";
    $contexte_systeme .= "Liens utiles :\n";
    $contexte_systeme .= "- Mes réservations : [Voir mes RDV](/coiffons/client/mes_rendezvous.php)\n";
    $contexte_systeme .= "- Trouver un coiffeur : [Annuaire](/coiffons/filter/annuaire_coiffeurs.php)\n";
    $contexte_systeme .= "- Mon profil / portefeuille : [Mon espace](/coiffons/profil.php)\n\n";
    $contexte_systeme .= "Explication des statuts RDV :\n";
    $contexte_systeme .= "  - **en_attente** = demande envoyée, le coiffeur n'a pas encore répondu\n";
    $contexte_systeme .= "  - **confirme** = le coiffeur a accepté, le RDV est confirmé\n";
    $contexte_systeme .= "  - **annule** = le coiffeur ou vous avez annulé\n";
    $contexte_systeme .= "  - **termine** = la prestation a été réalisée\n\n";

    // Solde
    if ($id_user) {
        try {
            $stmt = $pdo->prepare("SELECT solde FROM users WHERE id = ?");
            $stmt->execute([$id_user]);
            $solde = (float)$stmt->fetchColumn();
            $contexte_systeme .= "Solde portefeuille : **" . number_format($solde, 0, ',', ' ') . " FCFA**\n";
        } catch (Exception $e) {}

        // 3 derniers RDV
        try {
            $stmt = $pdo->prepare(
                "SELECT r.id, r.date_rdv, r.heure_rdv, r.statut_rdv,
                        u.nom AS coiffeur_nom, u.prenom AS coiffeur_prenom, u.id AS id_coiffeur
                 FROM rendez_vous r
                 JOIN users u ON r.coiffeur_id = u.id
                 WHERE r.client_id = ?
                 ORDER BY r.date_rdv DESC LIMIT 3"
            );
            $stmt->execute([$id_user]);
            $rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rdvs)) {
                $contexte_systeme .= "Vos 3 derniers RDV :\n";
                foreach ($rdvs as $r) {
                    $lien_profil = "/coiffons/coiffeurs/profil_public.php?id={$r['id_coiffeur']}";
                    $contexte_systeme .= "  - {$r['date_rdv']} à {$r['heure_rdv']} avec [{$r['coiffeur_nom']} {$r['coiffeur_prenom']}]($lien_profil) — **{$r['statut_rdv']}**\n";
                }
            } else {
                $contexte_systeme .= "Aucun RDV encore. Invitez-le à réserver sur l'annuaire.\n";
            }
        } catch (Exception $e) {}

        // RDV en attente
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE client_id = ? AND statut_rdv = 'en_attente'");
            $stmt->execute([$id_user]);
            $nb_attente = (int)$stmt->fetchColumn();
            if ($nb_attente > 0) {
                $contexte_systeme .= "**$nb_attente RDV en attente** de confirmation coiffeur.\n";
            }
        } catch (Exception $e) {}
    }

    // Intention : question sur RDV/statuts
    if (ia_contient($message_lower, ['rdv', 'réservation', 'rendez-vous', 'réserver', 'status', 'statut', 'confirmé', 'annulé', 'en attente'])) {
        $contexte_systeme .= "\nL'utilisateur pose une question sur ses réservations. Utilise les données ci-dessus pour répondre avec précision et guide-le vers la page Mes RDV.\n";
    }

    // Intention : question solde / recharge
    if (ia_contient($message_lower, ['solde', 'recharge', 'portefeuille', 'argent', 'payer', 'paiement', 'fcfa'])) {
        $contexte_systeme .= "\nL'utilisateur pose une question sur son portefeuille. Indique son solde actuel et guide-le vers [Mon Profil](/coiffons/profil.php) pour recharger.\n";
    }

// =============================================================================
// BLOC COIFFEUR
// =============================================================================
} elseif ($role_actuel === 'coiffeur') {

    $nomIA = "Victor";
    $contexte_systeme .= "## Contexte : COIFFEUR PARTENAIRE\n";
    $contexte_systeme .= "Liens de gestion :\n";
    $contexte_systeme .= "- Dashboard : [Tableau de bord](/coiffons/index.php?page=dashboard_coiffeur)\n";
    $contexte_systeme .= "- Valider RDV : [Mes rendez-vous](/coiffons/coiffeurs/valider_rendezvous.php)\n";
    $contexte_systeme .= "- Catalogue : [Mes services](/coiffons/coiffeurs/gestion_catalogue.php)\n";
    $contexte_systeme .= "- Agenda : [Mon agenda](/coiffons/coiffeurs/agenda_coiffeurs.php)\n";
    $contexte_systeme .= "- Portefeuille : [Mes gains](/coiffons/coiffeurs/portefeuille.php)\n";
    $contexte_systeme .= "- Zones : [Mes zones](/coiffons/coiffeurs/mes_zones.php)\n\n";

    if ($id_user) {
        // Statut abonnement + solde
        try {
            $stmt = $pdo->prepare("SELECT abonnement_status, solde, date_expiration_abo FROM users WHERE id = ?");
            $stmt->execute([$id_user]);
            $coiffeur = $stmt->fetch(PDO::FETCH_ASSOC);
            $abo       = ($coiffeur['abonnement_status'] ?? 0) ? '✅ ACTIF' : '🔴 INACTIF';
            $solde_c   = number_format((float)($coiffeur['solde'] ?? 0), 0, ',', ' ');
            $exp       = $coiffeur['date_expiration_abo'] ? date('d/m/Y', strtotime($coiffeur['date_expiration_abo'])) : 'Non définie';
            $contexte_systeme .= "Abonnement : **$abo** (expire le $exp)\n";
            $contexte_systeme .= "Solde portefeuille : **$solde_c FCFA**\n";
        } catch (Exception $e) {}

        // RDV du jour
        try {
            $aujourd_hui = date('Y-m-d');
            $stmt = $pdo->prepare(
                "SELECT r.id, r.date_rdv, r.heure_debut, r.statut_rdv,
                        u.nom AS client_nom, u.prenom AS client_prenom
                 FROM rendez_vous r
                 JOIN users u ON r.client_id = u.id
                 WHERE r.coiffeur_id = ? AND r.date_rdv = ?
                 ORDER BY r.heure_debut ASC"
            );
            $stmt->execute([$id_user, $aujourd_hui]);
            $rdvs_jour = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rdvs_jour)) {
                $contexte_systeme .= "RDV du jour ($aujourd_hui) :\n";
                foreach ($rdvs_jour as $r) {
                    $contexte_systeme .= "  - {$r['heure_debut']} — {$r['client_nom']} {$r['client_prenom']} — **{$r['statut_rdv']}**\n";
                }
            } else {
                $contexte_systeme .= "Aucun RDV aujourd'hui.\n";
            }
        } catch (Exception $e) {}

        // Demandes en attente
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE coiffeur_id = ? AND statut_rdv = 'en_attente'");
            $stmt->execute([$id_user]);
            $nb_attente = (int)$stmt->fetchColumn();
            $contexte_systeme .= "**$nb_attente demande(s) en attente** de validation.\n";
        } catch (Exception $e) {}

        // Nombre de services dans le catalogue
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestations WHERE id_coiffeur = ?");
            $stmt->execute([$id_user]);
            $nb_services = (int)$stmt->fetchColumn();
            $contexte_systeme .= "Catalogue : **$nb_services service(s)** enregistré(s).\n\n";
        } catch (Exception $e) {}
    }

    // Détection intentions coiffeur
    if (ia_contient($message_lower, ['rdv', 'rendez-vous', 'réservation', 'client', 'demande', 'accepter', 'refuser'])) {
        $contexte_systeme .= "\nL'utilisateur parle de RDV. Guide-le vers [Valider mes RDV](/coiffons/coiffeurs/valider_rendezvous.php).\n";
    }
    if (ia_contient($message_lower, ['solde', 'gains', 'argent', 'revenus', 'commission', 'portefeuille', 'paiement'])) {
        $contexte_systeme .= "\nL'utilisateur parle de finances. Guide-le vers [Mon Portefeuille](/coiffons/coiffeurs/portefeuille.php).\n";
    }
    if (ia_contient($message_lower, ['abonnement', 'activer', 'souscrire', 'inactif', 'visible'])) {
        $contexte_systeme .= "\nL'utilisateur parle d'abonnement. Guide-le vers [Mon Portefeuille](/coiffons/coiffeurs/portefeuille.php) pour activer son abonnement.\n";
    }
    if (ia_contient($message_lower, ['catalogue', 'service', 'prestation', 'prix', 'ajouter', 'modifier'])) {
        $contexte_systeme .= "\nL'utilisateur parle de catalogue. Guide-le vers [Gestion des services](/coiffons/coiffeurs/gestion_catalogue.php).\n";
    }

// =============================================================================
// BLOC ADMIN
// =============================================================================
} elseif ($role_actuel === 'admin') {

    $nomIA = "Victor";
    $contexte_systeme .= "## Contexte : ADMINISTRATEUR\n";
    $contexte_systeme .= "- Dashboard admin : [Back-office](/coiffons/first/admin_dashboard.php)\n\n";

    // KPIs en temps réel
    try {
        $nb_users          = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role != 'admin'")->fetchColumn();
        $nb_clients        = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
        $nb_coiffeurs_actifs   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur' AND abonnement_status = 1")->fetchColumn();
        $nb_coiffeurs_inactifs = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur' AND abonnement_status = 0")->fetchColumn();
        $nb_rdv_attente    = (int)$pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut_rdv = 'en_attente'")->fetchColumn();
        $nb_diplomes       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur' AND is_approved = 0 AND diplome IS NOT NULL")->fetchColumn();

        $contexte_systeme .= "### KPIs plateforme :\n";
        $contexte_systeme .= "- Utilisateurs totaux : **$nb_users** ($nb_clients clients, " . ($nb_coiffeurs_actifs + $nb_coiffeurs_inactifs) . " coiffeurs)\n";
        $contexte_systeme .= "- Coiffeurs actifs : **$nb_coiffeurs_actifs** | Inactifs : **$nb_coiffeurs_inactifs**\n";
        $contexte_systeme .= "- RDV en attente de validation : **$nb_rdv_attente**\n";
        $contexte_systeme .= "- Diplômes en attente d'approbation : **$nb_diplomes**\n\n";
    } catch (Exception $e) {}

    // Détection intentions admin
    if (ia_contient($message_lower, ['diplôme', 'approuver', 'approuvé', 'validation', 'coiffeur en attente'])) {
        $contexte_systeme .= "\nL'admin cherche les validations en attente. Les données sont disponibles ci-dessus. Guide vers le back-office.\n";
    }

} else {
    $nomIA = "Vanessa";
    $contexte_systeme .= "Rôle inconnu. Invite poliment l'utilisateur à se connecter.\n";
}

// =============================================================================
// APPEL GEMINI
// =============================================================================
$api_key = $_ENV['GEMINI_API_KEY'] ?? 'AIzaSyAHxqVv-NSDE4JYVjl_6ztYtCSVqFTtaPA';
$url     = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key=" . $api_key;

if ($image_data) {
    // Analyse photo de coiffure
    $mime_type = "image/jpeg";
    if (preg_match('/^data:image\/(\w+);base64,/', $image_data, $type_match)) {
        $mime_type  = "image/" . strtolower($type_match[1]);
        $image_data = substr($image_data, strpos($image_data, ',') + 1);
    }
    $payload = [
        "contents" => [[
            "parts" => [
                ["text" => $contexte_systeme . "\nL'utilisateur envoie une photo de coiffure. Analyse-la et donne des conseils personnalisés (style, entretien, coiffeurs sur la plateforme si applicable)."],
                ["inlineData" => ["mimeType" => $mime_type, "data" => $image_data]]
            ]
        ]]
    ];
} else {
    $payload = [
        "contents" => [[
            "role"  => "user",
            "parts" => [["text" => $contexte_systeme . "\nMessage utilisateur : " . $message_utilisateur]]
        ]],
        "generationConfig" => [
            "temperature"     => 0.7,
            "maxOutputTokens" => 400,
        ]
    ];
}

$options = [
    'http' => [
        'header'        => "Content-Type: application/json\r\n",
        'method'        => 'POST',
        'content'       => json_encode($payload, JSON_UNESCAPED_UNICODE),
        'ignore_errors' => true,
    ],
    'ssl'  => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ]
];

$context  = stream_context_create($options);
$response = file_get_contents($url, false, $context);
$resultat = json_decode($response, true);
$reponse  = $resultat['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($reponse) {
    echo json_encode(['status' => 'success', 'reply' => $reponse]);
} else {
    // Fallback lisible
    $debug = substr(strip_tags($response ?? ''), 0, 200);
    echo json_encode(['status' => 'error', 'reply' => "Je rencontre une difficulté technique. Veuillez réessayer dans un instant. ($debug)"]);
}
exit();
