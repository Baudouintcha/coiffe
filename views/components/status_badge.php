<?php
/**
 * views/components/status_badge.php — Wrapper PHP des badges de statut
 * Design System v2.0 | CL §31 (wrapper PHP de CL §16)
 *
 * Usage direct : render_status_badge('en_attente')
 * Usage include : $badge_status = 'confirme'; include 'views/components/status_badge.php';
 */

if (!function_exists('render_status_badge')) {
    /**
     * Retourne le HTML d'un badge de statut RDV.
     *
     * @param string $status  Valeur du statut BDD
     * @return string          HTML du badge
     */
    function render_status_badge(string $status): string {
        return match($status) {
            'en_attente'         => '<span class="badge-pending">En attente</span>',
            'confirme', 'accepte'=> '<span class="badge-confirmed">Confirmé</span>',
            'termine'            => '<span class="badge-done">Terminé</span>',
            'annule'             => '<span class="badge-cancelled">Annulé</span>',
            default              => '<span class="badge" style="background:#6C757D;color:#fff;border-radius:20px;padding:3px 10px;font-size:0.75rem;">'
                                    . htmlspecialchars($status)
                                    . '</span>',
        };
    }
}

/*
 * Si utilisé via include avec $badge_status défini, afficher directement.
 * Exemple : $badge_status = $rdv['statut_rdv']; include '...status_badge.php';
 */
if (isset($badge_status)) {
    echo render_status_badge($badge_status);
    unset($badge_status);
}
?>
