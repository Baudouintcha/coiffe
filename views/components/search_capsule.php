<?php
/**
 * views/components/search_capsule.php — SearchCapsule
 * Design System v2.0 | CL §10
 *
 * Barre de recherche glassmorphisme niveau 3.
 * Peut être utilisée standalone ou intégrée dans un HeroCapsule.
 *
 * Variables attendues :
 *   $sc_action      — URL de destination du formulaire
 *   $sc_name        — name de l'input (défaut: 'q')
 *   $sc_placeholder — placeholder du champ
 *   $sc_value       — valeur pré-remplie (défaut: '')
 *   $sc_button_label— libellé du bouton (défaut: 'Rechercher')
 *   $sc_method      — méthode HTTP 'GET'|'POST' (défaut: 'GET')
 *   $sc_extra_fields— HTML de champs cachés supplémentaires
 */

$sc_action       = $sc_action       ?? '/coiffons/filter/annuaire_coiffeurs.php';
$sc_name         = $sc_name         ?? 'q';
$sc_placeholder  = $sc_placeholder  ?? 'Rechercher un coiffeur, un style...';
$sc_value        = $sc_value        ?? '';
$sc_button_label = $sc_button_label ?? 'Rechercher';
$sc_method       = $sc_method       ?? 'GET';
$sc_extra_fields = $sc_extra_fields ?? '';
?>

<form action="<?= htmlspecialchars($sc_action) ?>"
      method="<?= htmlspecialchars($sc_method) ?>"
      class="search-bar"
      role="search"
      aria-label="Recherche">
    <i class="bi bi-search"
       style="color:rgba(255,255,255,0.5);margin-right:6px;font-size:0.9rem;"
       aria-hidden="true"></i>
    <input type="search"
           name="<?= htmlspecialchars($sc_name) ?>"
           value="<?= htmlspecialchars($sc_value) ?>"
           placeholder="<?= htmlspecialchars($sc_placeholder) ?>"
           autocomplete="off"
           aria-label="<?= htmlspecialchars($sc_placeholder) ?>">
    <?= $sc_extra_fields ?>
    <button type="submit" class="search-btn">
        <i class="bi bi-search me-1" aria-hidden="true"></i>
        <?= htmlspecialchars($sc_button_label) ?>
    </button>
</form>
<?php unset($sc_action, $sc_name, $sc_placeholder, $sc_value, $sc_button_label, $sc_method, $sc_extra_fields); ?>
