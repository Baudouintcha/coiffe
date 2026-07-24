<?php
/**
 * views/components/appointment_card.php — AppointmentCard
 * Design System v2.0 | CL §27
 *
 * Carte rendez-vous — variantes desktop (tableau) et mobile (carte).
 * Ne contient aucune logique métier d'annulation.
 *
 * Variables attendues :
 *   $ac = [
 *     'id'             => int,
 *     'date'           => string,   // formaté 'd/m/Y'
 *     'heure'          => string,   // 'HH:MM'
 *     'nom_style'      => string,
 *     'prix'           => string,   // formaté '3 000'
 *     'statut'         => string,   // 'en_attente'|'confirme'|'termine'|'annule'
 *     'coiffeur_nom'   => string,   // optionnel
 *     'modal_id'       => string,   // ID de la modal de gestion
 *     'manage_label'   => string,   // label bouton (défaut: 'Gérer')
 *   ]
 *   $ac_mode — 'mobile'|'row' (défaut: 'mobile' — affiche carte)
 *              'row' = affiche uniquement le <tr> (pour usage dans tableau)
 *
 * Dépendances : status_badge.php, modal.php
 */

require_once __DIR__ . '/status_badge.php';

$ac      = $ac      ?? [];
$ac_mode = $ac_mode ?? 'mobile';

$a = array_merge([
    'id'           => 0,
    'date'         => '—',
    'heure'        => '—',
    'nom_style'    => 'Prestation',
    'prix'         => '0',
    'statut'       => 'en_attente',
    'coiffeur_nom' => '',
    'modal_id'     => 'modal-rdv-0',
    'manage_label' => 'Gérer',
], $ac);
?>

<?php if ($ac_mode === 'row'): ?>
<!-- ── Variante Desktop : ligne de tableau ── -->
<tr>
    <td>
        <strong style="color:#fff;"><?= htmlspecialchars($a['date']) ?></strong><br>
        <span style="color:var(--gold);font-family:monospace;"><?= htmlspecialchars($a['heure']) ?></span>
    </td>
    <td>
        <span style="font-weight:700;"><?= htmlspecialchars($a['nom_style']) ?></span><br>
        <small style="color:var(--success-text);font-weight:700;"><?= htmlspecialchars($a['prix']) ?> FCFA</small>
    </td>
    <td><?= render_status_badge($a['statut']) ?></td>
    <td>
        <button class="btn-outline-gold btn-sm"
                onclick="openModal('<?= htmlspecialchars($a['modal_id']) ?>')"
                aria-label="<?= htmlspecialchars($a['manage_label']) ?> le rendez-vous du <?= htmlspecialchars($a['date']) ?>">
            <?= htmlspecialchars($a['manage_label']) ?>
        </button>
    </td>
</tr>

<?php else: ?>
<!-- ── Variante Mobile : carte glass ── -->
<article class="appointment-card-mobile glass-cct p-3 mb-3"
         aria-label="Rendez-vous du <?= htmlspecialchars($a['date']) ?> à <?= htmlspecialchars($a['heure']) ?>">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span style="color:var(--gold);font-weight:700;">
            <?= htmlspecialchars($a['date']) ?> — <?= htmlspecialchars($a['heure']) ?>
        </span>
        <?= render_status_badge($a['statut']) ?>
    </div>

    <h6 style="color:#fff;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
        <?= htmlspecialchars($a['nom_style']) ?>
    </h6>

    <?php if ($a['coiffeur_nom']): ?>
        <p style="color:var(--text-muted);font-size:0.78rem;margin-bottom:8px;">
            <i class="bi bi-person me-1" aria-hidden="true"></i>
            <?= htmlspecialchars($a['coiffeur_nom']) ?>
        </p>
    <?php endif; ?>

    <p style="color:var(--success-text);font-weight:700;margin-bottom:12px;">
        <?= htmlspecialchars($a['prix']) ?> FCFA
    </p>

    <button class="btn-outline-gold btn-sm w-100"
            onclick="openModal('<?= htmlspecialchars($a['modal_id']) ?>')"
            aria-label="<?= htmlspecialchars($a['manage_label']) ?> le rendez-vous">
        <i class="bi bi-gear me-1" aria-hidden="true"></i>
        <?= htmlspecialchars($a['manage_label']) ?>
    </button>
</article>
<?php endif; ?>

<style>
/* AppointmentCard — CL §27 */
.appointment-card-mobile { border: 1px solid rgba(212,175,55,0.20); }
.appointment-card-mobile:hover { border-color: rgba(212,175,55,0.40); }
</style>
<?php unset($ac, $ac_mode, $a); ?>
