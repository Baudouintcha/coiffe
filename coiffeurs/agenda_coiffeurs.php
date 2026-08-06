<?php
/**
 * coiffeurs/agenda_coiffeurs.php — Configuration des disponibilités hebdomadaires
 * Migration Design System v2.0 — Parcours Coiffeur — Page 2
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, disponibilités, sécurité : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

$coiffeur_id = $_SESSION['id_user'] ?? null;
$role_actuel = $_SESSION['role'] ?? 'invite';

if (!$coiffeur_id || $role_actuel !== 'coiffeur') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

// Récupération des horaires existants — SQL inchangé
try {
    $stmt = $pdo->prepare("SELECT * FROM disponibilites WHERE coiffeur_id = ? ORDER BY FIELD(jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche')");
    $stmt->execute([$coiffeur_id]);
    $horaires_existants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $horaires_existants = [];
}

$mode_edition = isset($_GET['action']) && $_GET['action'] === 'edit';
$est_vide     = empty($horaires_existants);
$jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

include __DIR__ . '/../layout/header_coiffeur.php';
?>


<!-- ActionBar -->
<?php
$ab = [
    'icon'     => 'bi-calendar3',
    'title'    => 'Mon Agenda',
    'subtitle' => 'Configurez vos disponibilités hebdomadaires',
];
include __DIR__ . '/../views/components/action_bar.php';
?>

<!-- Toast succès après sauvegarde -->
<?php if (isset($_GET['status']) && $_GET['status'] === 'success'):
    $toast_type    = 'success';
    $toast_message = 'Votre agenda a été enregistré avec succès.';
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<?php if ($est_vide || $mode_edition): ?>
    <!-- ── Mode édition / premier remplissage ── -->
    <div style="max-width:700px;margin:0 auto;">
        <div class="glass-cct p-4">
            <div class="mb-4">
                <span class="badge-gold" style="display:inline-block;margin-bottom:10px;">Étape directe</span>
                <h3 style="font-family:var(--font-display);font-size:1.1rem;color:var(--text-primary);font-weight:700;margin-bottom:6px;text-align:center;">
                    Définissez vos jours et plages horaires
                </h3>
                <p style="color:var(--text-muted);font-size:0.82rem;text-align:center;">
                    Cochez les jours où vous acceptez les rendez-vous à domicile, puis ajustez vos heures.
                </p>
            </div>

            <form action="/coiffons/coiffeurs/sauvegarder_agenda.php" method="POST" id="agendaForm">
                <style>
                /* Override Bootstrap pour les checkboxes dark — sans accents Bootstrap */
                .agenda-check {
                    width: 22px !important; height: 22px !important;
                    cursor: pointer !important;
                    background-color: rgba(255,255,255,0.08) !important;
                    border: 2px solid rgba(212,175,55,0.45) !important;
                    border-radius: 5px !important;
                    appearance: none; -webkit-appearance: none;
                    flex-shrink: 0;
                    transition: background-color .2s, border-color .2s;
                    position: relative;
                }
                .agenda-check:checked {
                    background-color: #D4AF37 !important;
                    border-color: #D4AF37 !important;
                }
                .agenda-check:checked::after {
                    content: '✓';
                    position: absolute;
                    top: 50%; left: 50%;
                    transform: translate(-50%,-50%);
                    color: #000;
                    font-size: 13px;
                    font-weight: 800;
                    line-height: 1;
                }
                .agenda-check:hover:not(:checked) {
                    border-color: rgba(212,175,55,0.75) !important;
                    background-color: rgba(212,175,55,0.10) !important;
                }
                .agenda-row-active td { background-color: rgba(212,175,55,0.04) !important; }
                
                /* Styles pour les inputs time */
                .fc-dark[type="time"] {
                    min-width: 110px;
                    padding: 8px 12px;
                    font-size: 0.9rem;
                    transition: opacity .2s ease;
                }
                .fc-dark[type="time"]:disabled {
                    pointer-events: none;
                }
                </style>

                <div class="table-responsive" style="border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--glass-border);">
                    <table class="table table-dark align-middle mb-0">
                        <thead>
                            <tr style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:.5px;background:rgba(255,255,255,0.03);">
                                <th class="text-center" style="width:60px;">Actif</th>
                                <th>Jour</th>
                                <th>Heure début</th>
                                <th>Heure fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jours_semaine as $jour):
                                $cle_jour   = array_search($jour, array_column($horaires_existants, 'jour_semaine'));
                                $dispo_jour = ($cle_jour !== false) ? $horaires_existants[$cle_jour] : null;
                                $est_actif  = (bool)$dispo_jour;
                            ?>
                                <tr class="<?= $est_actif ? 'agenda-row-active' : '' ?>" id="row-<?= $jour ?>">
                                    <td class="text-center">
                                        <label for="chk_<?= $jour ?>" style="cursor:pointer;display:flex;align-items:center;justify-content:center;margin:0;">
                                            <input type="checkbox"
                                                   id="chk_<?= $jour ?>"
                                                   name="dispo[<?= $jour ?>]"
                                                   value="1"
                                                   class="agenda-check"
                                                   onchange="toggleRow('<?= $jour ?>', this.checked)"
                                                   <?= $est_actif ? 'checked' : '' ?>>
                                        </label>
                                    </td>
                                    <td style="font-weight:700;color:var(--text-primary);font-size:0.9rem;"><?= $jour ?></td>
                                    <td>
                                        <input type="time"
                                               id="debut_<?= $jour ?>"
                                               name="heure_debut[<?= $jour ?>]"
                                               class="fc-dark"
                                               value="<?= htmlspecialchars($dispo_jour['heure_debut'] ?? '08:00') ?>"
                                               <?= !$est_actif ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="time"
                                               id="fin_<?= $jour ?>"
                                               name="heure_fin[<?= $jour ?>]"
                                               class="fc-dark"
                                               value="<?= htmlspecialchars($dispo_jour['heure_fin'] ?? '19:00') ?>"
                                               <?= !$est_actif ? 'disabled' : '' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 pt-3"
                     style="border-top:1px solid var(--glass-border);">
                    <small style="color:var(--text-muted);">
                        <i class="bi bi-info-circle" style="color:var(--gold);" aria-hidden="true"></i>
                        Les clients ne peuvent réserver que sur les créneaux cochés ici.
                    </small>
                    <button type="submit" class="btn-gold" style="padding:10px 28px;">
                        <i class="bi bi-check2-circle me-2" aria-hidden="true"></i>
                        ENREGISTRER MON AGENDA
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- ── Mode lecture — aperçu du planning ── -->
    <div style="max-width:700px;margin:0 auto;">
        <div class="glass-cct p-4">
            <div class="text-center mb-4 pb-3"
                 style="border-bottom:1px solid var(--glass-border);">
                <h4 style="font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
                    Votre planning hebdomadaire
                </h4>
                <p style="color:var(--text-muted);font-size:0.78rem;margin:0 0 12px 0;">
                    Ces horaires sont visibles par les clients dans votre profil.
                </p>
            </div>

            <div class="row g-2">
                <?php foreach ($horaires_existants as $h): ?>
                    <div class="col-6 col-sm-4">
                        <?php
                        // AvailabilityCard lecture seule
                        $av = [
                            'jour'   => htmlspecialchars($h['jour_semaine']),
                            'debut'  => date('H:i', strtotime($h['heure_debut'])),
                            'fin'    => date('H:i', strtotime($h['heure_fin'])),
                            'active' => true,
                        ];
                        ?>
                        <div class="availability-card availability-card--active" style="text-align:center;padding:1rem;border-radius:var(--radius-md);background:rgba(212,175,55,0.08);border:1px solid rgba(212,175,55,0.2);">
                            <span style="display:inline-block;margin-bottom:8px;font-size:0.75rem;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:.5px;">
                                <?= $av['jour'] ?>
                            </span>
                            <div style="font-size:1rem;font-weight:700;color:var(--text-primary);font-family:monospace;">
                                <?= $av['debut'] ?> – <?= $av['fin'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex gap-2 mt-4 pt-3" style="border-top:1px solid var(--glass-border);justify-content:center;">
                <a href="/coiffons/coiffeurs/agenda_coiffeurs.php?action=edit"
                   class="btn-outline-gold btn-sm"
                   style="padding:10px 24px;"
                   aria-label="Modifier l'agenda">
                    <i class="bi bi-pencil me-1" aria-hidden="true"></i>Modifier l'agenda
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// ── Fonction pour activer/désactiver les inputs de temps selon la checkbox ──
function toggleRow(jour, isChecked) {
    const debutInput = document.getElementById('debut_' + jour);
    const finInput = document.getElementById('fin_' + jour);
    const row = document.getElementById('row-' + jour);
    
    if (isChecked) {
        // Activer les inputs
        debutInput.disabled = false;
        finInput.disabled = false;
        debutInput.style.opacity = '1';
        finInput.style.opacity = '1';
        row.classList.add('agenda-row-active');
    } else {
        // Désactiver les inputs
        debutInput.disabled = true;
        finInput.disabled = true;
        debutInput.style.opacity = '0.35';
        finInput.style.opacity = '0.35';
        row.classList.remove('agenda-row-active');
    }
}
</script>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
