<?php
/**
 * views/components/availability_card.php — AvailabilityCard (7 jours)
 * Design System v2.0 | CL §8
 *
 * Variables attendues :
 *   $planning_coiffeur — array PHP : ['lundi'=>['debut'=>'08:00','fin'=>'19:00'], ...]
 *   $nb_jours          — nombre de jours à afficher (défaut: 7)
 *
 * Ce composant génère les 7 cartes de jours.
 * Il doit être placé avant slots_container.php dans le DOM.
 */

$planning_coiffeur = $planning_coiffeur ?? [];
$nb_jours          = $nb_jours          ?? 7;

$jours_traduction = [
    'monday'=>'lundi','tuesday'=>'mardi','wednesday'=>'mercredi',
    'thursday'=>'jeudi','friday'=>'vendredi','saturday'=>'samedi','sunday'=>'dimanche',
];
?>
<div class="d-flex justify-content-center flex-wrap gap-2 mb-4" role="list" aria-label="Jours disponibles">
    <?php for ($i = 0; $i < $nb_jours; $i++):
        $timestamp  = strtotime("+{$i} days");
        $date_cle   = date('Y-m-d', $timestamp);
        $jour_en    = strtolower(date('l', $timestamp));
        $jour_fr    = $jours_traduction[$jour_en];
        $num_jour   = date('d', $timestamp);
        $nom_mois   = date('M', $timestamp);

        $travaille    = isset($planning_coiffeur[$jour_fr]);
        $class_statut = $travaille ? 'day-item-open' : 'day-item-closed';
        $heure_debut  = $travaille ? $planning_coiffeur[$jour_fr]['debut'] : '';
        $heure_fin    = $travaille ? $planning_coiffeur[$jour_fr]['fin']   : '';
    ?>
    <div class="day-selector-card <?= $class_statut ?>"
         data-date="<?= htmlspecialchars($date_cle) ?>"
         data-open="<?= $travaille ? '1' : '0' ?>"
         data-start="<?= htmlspecialchars($heure_debut) ?>"
         data-end="<?= htmlspecialchars($heure_fin) ?>"
         onclick="selectionnerJour(this)"
         role="button"
         tabindex="0"
         onkeypress="if(event.key==='Enter')selectionnerJour(this)"
         aria-label="<?= htmlspecialchars($jour_fr) ?> <?= $num_jour ?> <?= $nom_mois ?> — <?= $travaille ? 'disponible' : 'indisponible' ?>"
         <?= !$travaille ? 'aria-disabled="true"' : '' ?>>
        <span class="small text-uppercase opacity-70 d-block">
            <?= htmlspecialchars(substr($jour_fr, 0, 3)) ?>.
        </span>
        <span class="fs-4 fw-bold my-1 d-block"><?= $num_jour ?></span>
        <span class="small opacity-50 d-block"><?= $nom_mois ?></span>
    </div>
    <?php endfor; ?>
</div>
<?php unset($planning_coiffeur, $nb_jours, $jours_traduction, $i, $timestamp, $date_cle, $jour_en, $jour_fr, $num_jour, $nom_mois, $travaille, $class_statut, $heure_debut, $heure_fin); ?>
