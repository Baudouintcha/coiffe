<?php
/**
 * security/notification_events.php — Événements de notification centralisés
 * Design System v2.0 — Coiffe Chez Toi V1.0
 *
 * Architecture extensible : prête pour Email, Push, SMS, WhatsApp
 * Pour activer email : SET_EMAIL_ENABLED=true dans .env
 * Pour activer push  : SET_PUSH_ENABLED=true dans .env
 *
 * Usage :
 *   require_once __DIR__ . '/../security/notification_events.php';
 *   notifier_evenement($pdo, NOTIF_RDV_ACCEPTE_CLIENT, $id_client, $donnees);
 */

require_once __DIR__ . '/notifications.php';

// ---------------------------------------------------------------------------
// Constantes — Événements disponibles
// ---------------------------------------------------------------------------

/** RDV créé par le client → coiffeur notifié */
define('NOTIF_RDV_CREE_COIFFEUR',     'notif_rdv_cree_coiffeur');

/** RDV accepté par le coiffeur → client notifié */
define('NOTIF_RDV_ACCEPTE_CLIENT',    'notif_rdv_accepte_client');

/** RDV refusé par le coiffeur → client notifié */
define('NOTIF_RDV_REFUSE_CLIENT',     'notif_rdv_refuse_client');

/** RDV annulé par le coiffeur → client notifié */
define('NOTIF_RDV_ANNULE_COIFFEUR',   'notif_rdv_annule_coiffeur');

/** RDV annulé par le client → coiffeur notifié */
define('NOTIF_RDV_ANNULE_CLIENT',     'notif_rdv_annule_client');

/** Avis reçu → coiffeur notifié */
define('NOTIF_AVIS_RECU_COIFFEUR',    'notif_avis_recu_coiffeur');

/** Abonnement activé → coiffeur notifié */
define('NOTIF_ABONNEMENT_ACTIVE',     'notif_abonnement_active');

/** Diplôme approuvé → coiffeur notifié */
define('NOTIF_DIPLOME_APPROUVE',      'notif_diplome_approuve');

/** Nouveau client inscrit → admins notifiés */
define('NOTIF_NOUVEAU_CLIENT_ADMIN',  'notif_nouveau_client_admin');

/** Nouveau coiffeur inscrit → admins notifiés */
define('NOTIF_NOUVEAU_COIFFEUR_ADMIN','notif_nouveau_coiffeur_admin');

/** Plainte déposée → admins notifiés */
define('NOTIF_PLAINTE_ADMIN',         'notif_plainte_admin');

// ---------------------------------------------------------------------------
// Templates de messages par événement
// ---------------------------------------------------------------------------

/**
 * Retourne le message et le type pour un événement donné.
 *
 * @param string $evenement  Une des constantes NOTIF_*
 * @param array  $donnees    Variables pour interpoler le message
 *                           Clés possibles : date, heure, coiffeur, client, nb_avis, note
 * @return array{message: string, type: string}
 */
function _notif_template(string $evenement, array $donnees = []): array
{
    $d   = htmlspecialchars($donnees['date']     ?? '');
    $h   = htmlspecialchars($donnees['heure']    ?? '');
    $cof = htmlspecialchars($donnees['coiffeur'] ?? 'votre coiffeur');
    $cli = htmlspecialchars($donnees['client']   ?? 'un client');
    $n   = (int)($donnees['note']   ?? 0);
    $nb  = (int)($donnees['nb_avis'] ?? 0);

    return match($evenement) {
        NOTIF_RDV_CREE_COIFFEUR    => ['message' => "Nouvelle demande de RDV de {$cli} pour le {$d} à {$h}.",                                  'type' => 'info'],
        NOTIF_RDV_ACCEPTE_CLIENT   => ['message' => "Votre RDV du {$d} a été accepté par {$cof}. À bientôt !",                                  'type' => 'success'],
        NOTIF_RDV_REFUSE_CLIENT    => ['message' => "Votre demande de RDV du {$d} a été refusée. Votre solde a été recrédité.",                  'type' => 'danger'],
        NOTIF_RDV_ANNULE_COIFFEUR  => ['message' => "Votre RDV du {$d} a été annulé par le coiffeur. Votre solde a été recrédité.",              'type' => 'danger'],
        NOTIF_RDV_ANNULE_CLIENT    => ['message' => "Le client {$cli} a annulé son RDV du {$d}.",                                               'type' => 'warning'],
        NOTIF_AVIS_RECU_COIFFEUR   => ['message' => "Vous avez reçu un nouvel avis ({$n}/5). Vous avez désormais {$nb} avis.",                  'type' => 'info'],
        NOTIF_ABONNEMENT_ACTIVE    => ['message' => "Votre abonnement a été activé avec succès. Vous êtes maintenant visible dans l'annuaire.", 'type' => 'success'],
        NOTIF_DIPLOME_APPROUVE     => ['message' => "Votre diplôme a été validé par l'équipe Coiffe Chez Toi. Bienvenue !",                     'type' => 'success'],
        NOTIF_NOUVEAU_CLIENT_ADMIN => ['message' => "Nouveau client inscrit : {$cli}.",                                                         'type' => 'info'],
        NOTIF_NOUVEAU_COIFFEUR_ADMIN=>['message' => "Nouveau coiffeur inscrit : {$cof}. Vérifiez son diplôme.",                                 'type' => 'warning'],
        NOTIF_PLAINTE_ADMIN        => ['message' => "Une plainte a été déposée. Vérifiez le tableau de bord admin.",                            'type' => 'danger'],
        default                    => ['message' => "Nouvelle notification.",                                                                   'type' => 'info'],
    };
}

// ---------------------------------------------------------------------------
// Fonction principale
// ---------------------------------------------------------------------------

if (!function_exists('notifier_evenement')) {
    /**
     * Déclenche une notification structurée pour un événement donné.
     *
     * @param PDO    $pdo       Connexion PDO
     * @param string $evenement Constante NOTIF_*
     * @param int    $id_user   Destinataire (0 = tous les admins)
     * @param array  $donnees   Données pour interpoler le message (date, heure, coiffeur, client…)
     * @return bool
     */
    function notifier_evenement(PDO $pdo, string $evenement, int $id_user, array $donnees = []): bool
    {
        $tpl = _notif_template($evenement, $donnees);

        // Événements admin → diffusion à tous les admins
        $admin_events = [
            NOTIF_NOUVEAU_CLIENT_ADMIN,
            NOTIF_NOUVEAU_COIFFEUR_ADMIN,
            NOTIF_PLAINTE_ADMIN,
        ];

        if (in_array($evenement, $admin_events, true)) {
            notifier_admins($pdo, $tpl['message'], $tpl['type']);
            return true;
        }

        return notifier($pdo, $id_user, $tpl['message'], $tpl['type']);
    }
}
