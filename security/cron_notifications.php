<?php
/**
 * security/cron_notifications.php — Notifications automatiques
 * Appel manuel : http://localhost/coiffons/security/cron_notifications.php
 * En production  : cron toutes les heures
 *
 * Design System v2.0 — Coiffe Chez Toi V1.0
 *
 * Ce script ne modifie AUCUNE logique métier existante.
 * Toutes les requêtes SQL sont non-bloquantes (try/catch).
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/notifications.php';

$count = 0; // Compteur total de notifications envoyées

// ===========================================================================
// 1. RDV DEMAIN — Rappel client
//    Pour chaque RDV avec date_rdv = CURDATE() + 1 et statut confirme/accepte
//    → notifier le client
// ===========================================================================
try {
    $stmt = $pdo->query(
        "SELECT r.id, r.client_id, r.date_rdv, r.heure_debut,
                u.prenom AS prenom_coiffeur, u.nom AS nom_coiffeur
         FROM rendez_vous r
         JOIN users u ON r.coiffeur_id = u.id
         WHERE r.date_rdv = CURDATE() + INTERVAL 1 DAY
           AND r.statut_rdv IN ('confirme', 'accepte')"
    );
    $rdvs_demain = $stmt->fetchAll();

    foreach ($rdvs_demain as $rdv) {
        $heure       = substr($rdv['heure_debut'], 0, 5);
        $nom_coiffeur = trim($rdv['prenom_coiffeur'] . ' ' . $rdv['nom_coiffeur']);
        $message = "Rappel : Votre RDV avec {$nom_coiffeur} est demain à {$heure}.";

        if (notifier($pdo, (int)$rdv['client_id'], $message, 'info')) {
            $count++;
        }
    }
} catch (Exception $e) {
    // Non-bloquant
}

// ===========================================================================
// 2. ABONNEMENT COIFFEUR EXPIRANT dans les 5 prochains jours
//    → notifier chaque coiffeur concerné
// ===========================================================================
try {
    $stmt = $pdo->query(
        "SELECT id, prenom, nom, date_expiration_abo
         FROM users
         WHERE role = 'coiffeur'
           AND date_expiration_abo BETWEEN CURDATE() AND CURDATE() + INTERVAL 5 DAY"
    );
    $coiffeurs_exp = $stmt->fetchAll();

    foreach ($coiffeurs_exp as $coiffeur) {
        $date_exp   = new DateTime($coiffeur['date_expiration_abo']);
        $aujourd_hui = new DateTime('today');
        $jours_restants = (int) $aujourd_hui->diff($date_exp)->days;

        $jours_label = $jours_restants <= 0 ? "aujourd'hui" : "dans {$jours_restants} jour" . ($jours_restants > 1 ? 's' : '');
        $message = "Votre abonnement expire {$jours_label}. Renouvelez-le pour rester visible.";

        if (notifier($pdo, (int)$coiffeur['id'], $message, 'warning')) {
            $count++;
        }
    }
} catch (Exception $e) {
    // Non-bloquant
}

// ===========================================================================
// 3. RDV DU JOUR — Résumé coiffeur
//    Pour chaque coiffeur avec des RDV aujourd'hui, notifier le nombre total
// ===========================================================================
try {
    $stmt = $pdo->query(
        "SELECT p.id_coiffeur, COUNT(*) AS nb_rdv
         FROM rendez_vous r
         JOIN prestations p ON r.coiffure_id = p.id_prestation
         WHERE r.date_rdv = CURDATE()
           AND r.statut_rdv IN ('confirme', 'accepte', 'en_attente')
         GROUP BY p.id_coiffeur
         HAVING nb_rdv > 0"
    );
    $rdvs_jour = $stmt->fetchAll();

    foreach ($rdvs_jour as $row) {
        $nb      = (int) $row['nb_rdv'];
        $label   = $nb > 1 ? "rendez-vous" : "rendez-vous";
        $message = "Vous avez {$nb} {$label} aujourd'hui.";

        if (notifier($pdo, (int)$row['id_coiffeur'], $message, 'info')) {
            $count++;
        }
    }
} catch (Exception $e) {
    // Non-bloquant
}

// ===========================================================================
// 4. DIPLÔMES EN ATTENTE — Alerte admin
//    S'il y a des coiffeurs avec is_approved = 0 ET diplome non null
//    → notifier tous les admins une seule fois
// ===========================================================================
try {
    $stmt = $pdo->query(
        "SELECT COUNT(*) AS nb
         FROM users
         WHERE is_approved = 0
           AND diplome IS NOT NULL
           AND diplome != ''
           AND role = 'coiffeur'"
    );
    $row = $stmt->fetch();
    $nb_diplomes = (int)($row['nb'] ?? 0);

    if ($nb_diplomes > 0) {
        $pluriel = $nb_diplomes > 1 ? 'diplômes en attente' : 'diplôme en attente';
        $message = "{$nb_diplomes} {$pluriel} de validation.";
        notifier_admins($pdo, $message, 'warning');
        $count++;
    }
} catch (Exception $e) {
    // Non-bloquant
}

// ===========================================================================
// Réponse JSON
// ===========================================================================
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'                 => 'ok',
    'notifications_envoyees' => $count,
    'horodatage'             => date('Y-m-d H:i:s'),
]);
