<?php
/**
 * views/components/badges.php — Badges (Badge + StatusBadge)
 * Design System v2.0 | CL §16
 *
 * Point d'entrée unique pour tous les badges.
 * Les classes CSS sont définies dans css/components.css.
 *
 * Helpers disponibles :
 *   render_badge_verified()                — "Certifié"
 *   render_badge_gold(string $label)       — badge gold générique
 *   render_badge(string $class, string $label, string $icon='') — badge libre
 *
 * Utilisation dans le HTML :
 *   <span class="badge-verified">Certifié</span>
 *   <span class="badge-gold">Coiffure</span>
 *   <span class="badge-pending">En attente</span>
 *   <span class="badge-confirmed">Confirmé</span>
 *   <span class="badge-done">Terminé</span>
 *   <span class="badge-cancelled">Annulé</span>
 */

if (!function_exists('render_badge')) {
    /**
     * Retourne le HTML d'un badge.
     * @param string $class Classe CSS (badge-verified, badge-gold, badge-pending, etc.)
     * @param string $label Libellé
     * @param string $icon  Classe bi-* optionnelle
     */
    function render_badge(string $class, string $label, string $icon = ''): string {
        $icon_html = $icon ? '<i class="bi ' . htmlspecialchars($icon) . ' me-1" aria-hidden="true"></i>' : '';
        return '<span class="' . htmlspecialchars($class) . '">' . $icon_html . htmlspecialchars($label) . '</span>';
    }
}

if (!function_exists('render_badge_verified')) {
    function render_badge_verified(string $label = 'Certifié'): string {
        return render_badge('badge-verified', $label, 'bi-patch-check-fill');
    }
}

if (!function_exists('render_badge_gold')) {
    function render_badge_gold(string $label): string {
        return render_badge('badge-gold', $label);
    }
}

// Rendu direct si $badge est défini
if (isset($badge)) {
    $bc    = $badge['class'] ?? 'badge-gold';
    $bl    = $badge['label'] ?? '';
    $bicon = $badge['icon']  ?? '';
    echo render_badge($bc, $bl, $bicon);
    unset($badge, $bc, $bl, $bicon);
}
?>
