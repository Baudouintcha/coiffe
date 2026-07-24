<?php
/**
 * views/components/buttons.php — Boutons (PrimaryButton, SecondaryButton, GhostButton, DangerButton)
 * Design System v2.0 | CL §12–§15
 *
 * Point d'entrée unique pour tous les boutons du projet.
 * Les classes CSS sont définies dans css/components.css.
 *
 * Usage PHP direct :
 *   $btn = ['type'=>'primary', 'label'=>'Enregistrer', 'href'=>'#'];
 *   include 'views/components/buttons.php';
 *
 * Ou utiliser les helpers :
 *   echo render_button_primary('Enregistrer', '/url', 'btn-lg');
 *   echo render_button_outline('Annuler', '#');
 *   echo render_button_ghost('← Retour', '/prev');
 *   echo render_button_danger('Supprimer', '/delete');
 *
 * Ou utiliser directement les classes dans le HTML :
 *   <a href="..." class="btn-gold">Action</a>
 *   <button class="btn-outline-gold btn-sm">Secondaire</button>
 *   <button class="btn-ghost">← Retour</button>
 *   <button class="btn-danger-cct">Supprimer</button>
 */

if (!function_exists('render_button_primary')) {
    /**
     * Bouton primaire gold.
     * @param string $label      Libellé
     * @param string $href       URL (null = <button>)
     * @param string $extra_class Classes supplémentaires (ex: 'btn-lg', 'btn-sm', 'w-100')
     * @param array  $attrs      Attributs HTML supplémentaires (ex: ['name'=>'submit','type'=>'submit'])
     */
    function render_button_primary(string $label, string $href = '', string $extra_class = '', array $attrs = []): string {
        $attr_str = implode(' ', array_map(fn($k,$v) => htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"', array_keys($attrs), $attrs));
        if ($href) {
            return '<a href="' . htmlspecialchars($href) . '" class="btn-gold ' . htmlspecialchars($extra_class) . '" ' . $attr_str . '>' . htmlspecialchars($label) . '</a>';
        }
        return '<button class="btn-gold ' . htmlspecialchars($extra_class) . '" ' . $attr_str . '>' . htmlspecialchars($label) . '</button>';
    }
}

if (!function_exists('render_button_outline')) {
    /** Bouton secondaire outline gold. */
    function render_button_outline(string $label, string $href = '', string $extra_class = '', array $attrs = []): string {
        $attr_str = implode(' ', array_map(fn($k,$v) => htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"', array_keys($attrs), $attrs));
        if ($href) {
            return '<a href="' . htmlspecialchars($href) . '" class="btn-outline-gold ' . htmlspecialchars($extra_class) . '" ' . $attr_str . '>' . htmlspecialchars($label) . '</a>';
        }
        return '<button class="btn-outline-gold ' . htmlspecialchars($extra_class) . '" ' . $attr_str . '>' . htmlspecialchars($label) . '</button>';
    }
}

if (!function_exists('render_button_ghost')) {
    /** Bouton ghost tertiaire. */
    function render_button_ghost(string $label, string $href = '', string $extra_class = '', array $attrs = []): string {
        $attr_str = implode(' ', array_map(fn($k,$v) => htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"', array_keys($attrs), $attrs));
        if ($href) {
            return '<a href="' . htmlspecialchars($href) . '" class="btn-ghost ' . htmlspecialchars($extra_class) . '" ' . $attr_str . '>' . htmlspecialchars($label) . '</a>';
        }
        return '<button class="btn-ghost ' . htmlspecialchars($extra_class) . '" ' . $attr_str . '>' . htmlspecialchars($label) . '</button>';
    }
}

if (!function_exists('render_button_danger')) {
    /** Bouton danger (suppression, annulation). */
    function render_button_danger(string $label, string $href = '', string $extra_class = '', array $attrs = []): string {
        $attr_str = implode(' ', array_map(fn($k,$v) => htmlspecialchars($k) . '="' . htmlspecialchars($v) . '"', array_keys($attrs), $attrs));
        if ($href) {
            return '<a href="' . htmlspecialchars($href) . '" class="btn-danger-cct ' . htmlspecialchars($extra_class) . '" ' . $attr_str . '>' . htmlspecialchars($label) . '</a>';
        }
        return '<button class="btn-danger-cct ' . htmlspecialchars($extra_class) . '" ' . $attr_str . '>' . htmlspecialchars($label) . '</button>';
    }
}

// Rendu direct si $btn est défini
if (isset($btn)) {
    $type = $btn['type'] ?? 'primary';
    $label = $btn['label'] ?? 'Action';
    $href  = $btn['href']  ?? '';
    $extra = $btn['extra_class'] ?? '';
    $a     = $btn['attrs'] ?? [];
    echo match($type) {
        'outline' => render_button_outline($label, $href, $extra, $a),
        'ghost'   => render_button_ghost($label, $href, $extra, $a),
        'danger'  => render_button_danger($label, $href, $extra, $a),
        default   => render_button_primary($label, $href, $extra, $a),
    };
    unset($btn, $type, $label, $href, $extra, $a);
}
?>
