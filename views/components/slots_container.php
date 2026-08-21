<?php
/**
 * views/components/slots_container.php — SlotsContainer (créneaux horaires)
 * Design System v2.0 | CL §35
 *
 * Ce composant affiche le conteneur des créneaux horaires.
 * Il doit être placé après les AvailabilityCards.
 * La génération dynamique des boutons est gérée côté JS (selectionnerJour).
 *
 * Variables attendues :
 *   $sc_prestataire_id — ID du coiffeur (injecté en JS)
 *   $sc_busy_slots  — array PHP des créneaux occupés (clés = 'Y-m-d H:i')
 *   $sc_presta_id   — ID de la prestation présélectionnée (optionnel)
 *   $page_root      — chemin racine (ex: '/coiffons')
 */

$sc_prestataire_id = $sc_prestataire_id ?? 0;
$sc_busy_slots  = $sc_busy_slots  ?? [];
$sc_presta_id   = $sc_presta_id   ?? '';
$page_root      = $page_root      ?? '/coiffons';
?>
<div id="zone-slots-horaires"
     class="slots-container d-none"
     role="region"
     aria-live="polite"
     aria-label="Créneaux horaires disponibles">
    <div class="slots-container-title">
        Heures disponibles pour la date sélectionnée :
    </div>
    <div id="slots-container"
         class="d-flex flex-wrap justify-content-center gap-2"
         role="list">
    </div>
</div>

<script>
(function() {
    const slotsOccupes = <?= json_encode($sc_busy_slots) ?>;
    const coiffeurId   = <?= (int)$sc_prestataire_id ?>;
    const prestaId     = <?= json_encode((string)$sc_presta_id) ?>;
    const pageRoot     = <?= json_encode($page_root) ?>;

    // Exposer globalement pour que les AvailabilityCards puissent l'appeler
    window.selectionnerJour = function(element) {
        document.querySelectorAll('.day-selector-card').forEach(el => el.classList.remove('active-day'));

        if (element.getAttribute('data-open') === '0') {
            document.getElementById('zone-slots-horaires').classList.add('d-none');
            alert("Ce coiffeur ne travaille pas ce jour-là.");
            return;
        }

        element.classList.add('active-day');

        const dateChoisie  = element.getAttribute('data-date');
        const heureDebut   = element.getAttribute('data-start');
        const heureFin     = element.getAttribute('data-end');
        const container    = document.getElementById('slots-container');
        const zone         = document.getElementById('zone-slots-horaires');

        container.innerHTML = '';

        if (!heureDebut || !heureFin) { zone.classList.add('d-none'); return; }

        let [hStart] = heureDebut.split(':').map(Number);
        let [hEnd]   = heureFin.split(':').map(Number);

        for (let h = hStart; h < hEnd; h++) {
            const slotHeure = String(h).padStart(2,'0') + ':00';
            const cleVerif  = dateChoisie + ' ' + slotHeure;
            const btn = document.createElement('a');
            btn.setAttribute('role', 'listitem');

            if (slotsOccupes[cleVerif]) {
                btn.className = 'btn-ghost btn-sm disabled';
                btn.style.opacity = '0.25';
                btn.title = 'Déjà réservé';
                btn.setAttribute('aria-disabled', 'true');
                btn.textContent = slotHeure;
            } else {
                btn.className  = 'btn-outline-gold btn-sm';
                btn.href = `${pageRoot}/client/reserver.php?prestataire_id=${coiffeurId}&presta_id=${prestaId}&date_rdv=${dateChoisie}&heure_debut=${slotHeure}`;
                btn.textContent = slotHeure;
            }
            container.appendChild(btn);
        }

        zone.classList.remove('d-none');
    };
})();
</script>
<?php unset($sc_prestataire_id, $sc_busy_slots, $sc_presta_id, $page_root); ?>
