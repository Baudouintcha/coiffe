<?php
/**
 * coiffeurs/agenda_coiffeurs.php — Configuration des disponibilités hebdomadaires
 * Redesign : compact centered cards + edit/view toggle on same page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

$coiffeur_id = $_SESSION['user_id'] ?? null;
$role_actuel = $_SESSION['role'] ?? 'invite';

if (!$coiffeur_id || $role_actuel !== 'prestataire') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

// Récupération des horaires existants
try {
    $stmt = $pdo->prepare("SELECT * FROM disponibilites WHERE prestataire_id = ? ORDER BY FIELD(jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche')");
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

<style>
/* ─── Compact Centered Agenda Layout ─── */
.agenda-container {
    max-width: 650px;
    margin: 0 auto;
}

.agenda-card-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 576px) {
    .agenda-card-wrapper {
        grid-template-columns: 1fr;
    }
}

/* Edit mode: Compact card with checkbox + time inputs */
.agenda-edit-card {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(212,175,55,0.2);
    border-radius: 12px;
    padding: 16px;
    transition: all .2s ease;
    position: relative;
}

.agenda-edit-card.active {
    background: rgba(212,175,55,0.08);
    border-color: rgba(212,175,55,0.5);
}

.agenda-edit-card:hover {
    border-color: rgba(212,175,55,0.4);
}

/* Checkbox styling */
.agenda-check {
    width: 20px !important;
    height: 20px !important;
    cursor: pointer !important;
    background-color: rgba(255,255,255,0.08) !important;
    border: 2px solid rgba(212,175,55,0.45) !important;
    border-radius: 4px !important;
    appearance: none;
    -webkit-appearance: none;
    flex-shrink: 0;
    transition: all .2s;
    position: relative;
    margin-right: 12px;
}

.agenda-check:checked {
    background-color: #D4AF37 !important;
    border-color: #D4AF37 !important;
}

.agenda-check:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%,-50%);
    color: #000;
    font-size: 12px;
    font-weight: 800;
}

.agenda-check:hover:not(:checked) {
    border-color: rgba(212,175,55,0.75) !important;
    background-color: rgba(212,175,55,0.1) !important;
}

/* Day header with checkbox */
.agenda-day-header {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    font-weight: 700;
    color: var(--text-primary);
    font-size: 0.95rem;
}

/* Time inputs container */
.agenda-time-inputs {
    display: flex;
    gap: 8px;
    align-items: center;
    opacity: 0.4;
    pointer-events: none;
    transition: opacity .2s;
}

.agenda-edit-card.active .agenda-time-inputs {
    opacity: 1;
    pointer-events: auto;
}

.fc-dark {
    background-color: rgba(255,255,255,0.06) !important;
    border: 1px solid rgba(212,175,55,0.3) !important;
    color: var(--text-primary) !important;
    border-radius: 6px;
    padding: 6px 10px !important;
    font-size: 0.85rem;
    font-family: 'Courier New', monospace;
    transition: all .2s;
}

.fc-dark:focus {
    background-color: rgba(255,255,255,0.1) !important;
    border-color: #D4AF37 !important;
    outline: none;
    box-shadow: 0 0 0 2px rgba(212,175,55,0.2);
}

.fc-dark:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.time-sep {
    color: var(--text-muted);
    font-weight: 500;
}

/* View mode: Read-only summary cards */
.agenda-summary-card {
    background: rgba(212,175,55,0.08);
    border: 1px solid rgba(212,175,55,0.4);
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    transition: all .2s;
}

.agenda-summary-card:hover {
    border-color: rgba(212,175,55,0.7);
    box-shadow: 0 4px 12px rgba(212,175,55,0.1);
}

.day-badge {
    display: inline-block;
    background: rgba(212,175,55,0.2);
    color: #D4AF37;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
}

.time-range {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-primary);
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

/* Action bar */
.agenda-action-bar {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid rgba(212,175,55,0.2);
}

.btn-gold {
    background: linear-gradient(135deg, #D4AF37 0%, #c99c24 100%);
    color: #000;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-gold:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(212,175,55,0.3);
}

.btn-outline-gold {
    background: transparent;
    color: #D4AF37;
    border: 1.5px solid #D4AF37;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all .2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-outline-gold:hover {
    background: rgba(212,175,55,0.1);
    border-color: #D4AF37;
}

.info-text {
    color: var(--text-muted);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 16px;
}

.info-text i {
    color: #D4AF37;
}
</style>

<?php if ($est_vide || $mode_edition): ?>
    <!-- ── MODE ÉDITION / PREMIER REMPLISSAGE ── -->
    <div class="glass-cct p-4 agenda-container">
        <div class="mb-4">
            <span class="badge-gold" style="display:inline-block;margin-bottom:10px;">Étape directe</span>
            <h3 style="font-family:var(--font-display);font-size:1.1rem;color:var(--text-primary);font-weight:700;margin-bottom:6px;">
                Définissez vos jours et plages horaires
            </h3>
            <p style="color:var(--text-muted);font-size:0.82rem;">
                Cochez les jours où vous acceptez les rendez-vous à domicile, puis définissez vos horaires.
            </p>
        </div>

        <form action="/coiffons/coiffeurs/sauvegarder_agenda.php" method="POST">
            <div class="info-text">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                Les clients ne peuvent réserver que sur les créneaux cochés ici.
            </div>

            <div class="agenda-card-wrapper">
                <?php foreach ($jours_semaine as $jour):
                    $cle_jour   = array_search($jour, array_column($horaires_existants, 'jour_semaine'));
                    $dispo_jour = ($cle_jour !== false) ? $horaires_existants[$cle_jour] : null;
                    $est_actif  = (bool)$dispo_jour;
                ?>
                    <div class="agenda-edit-card <?= $est_actif ? 'active' : '' ?>" id="card-<?= $jour ?>">
                        <div class="agenda-day-header">
                            <input type="checkbox"
                                   id="chk_<?= $jour ?>"
                                   name="dispo[<?= $jour ?>]"
                                   value="1"
                                   class="agenda-check"
                                   onchange="toggleCard('<?= $jour ?>', this.checked)"
                                   <?= $est_actif ? 'checked' : '' ?>>
                            <label for="chk_<?= $jour ?>" style="cursor:pointer;margin:0;flex:1;">
                                <?= $jour ?>
                            </label>
                        </div>
                        <div class="agenda-time-inputs">
                            <input type="time"
                                   id="debut_<?= $jour ?>"
                                   name="heure_debut[<?= $jour ?>]"
                                   class="fc-dark"
                                   value="<?= htmlspecialchars($dispo_jour['heure_debut'] ?? '08:00') ?>"
                                   <?= !$est_actif ? 'disabled' : '' ?>>
                            <span class="time-sep">—</span>
                            <input type="time"
                                   id="fin_<?= $jour ?>"
                                   name="heure_fin[<?= $jour ?>]"
                                   class="fc-dark"
                                   value="<?= htmlspecialchars($dispo_jour['heure_fin'] ?? '19:00') ?>"
                                   <?= !$est_actif ? 'disabled' : '' ?>>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="agenda-action-bar">
                <button type="submit" class="btn-gold">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    ENREGISTRER MON AGENDA
                </button>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- ── MODE LECTURE — RÉSUMÉ DE L'AGENDA ── -->
    <div class="glass-cct p-4 agenda-container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid rgba(212,175,55,0.2);">
            <div>
                <h4 style="font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
                    Votre planning hebdomadaire
                </h4>
                <p style="color:var(--text-muted);font-size:0.78rem;margin:0;">
                    Ces horaires sont visibles par les clients dans votre profil.
                </p>
            </div>
            <a href="/coiffons/coiffeurs/agenda_coiffeurs.php?action=edit"
               class="btn-outline-gold"
               aria-label="Modifier l'agenda">
                <i class="bi bi-pencil" aria-hidden="true"></i>Modifier
            </a>
        </div>

        <div class="agenda-card-wrapper">
            <?php foreach ($horaires_existants as $h): ?>
                <div class="agenda-summary-card">
                    <div class="day-badge">
                        <?= htmlspecialchars($h['jour_semaine']) ?>
                    </div>
                    <div class="time-range">
                        <?= date('H:i', strtotime($h['heure_debut'])) ?> – <?= date('H:i', strtotime($h['heure_fin'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
// Toggle card active state et enable/disable time inputs
function toggleCard(jour, isChecked) {
    const card = document.getElementById('card-' + jour);
    const debutInput = document.getElementById('debut_' + jour);
    const finInput = document.getElementById('fin_' + jour);

    if (isChecked) {
        card.classList.add('active');
        debutInput.disabled = false;
        finInput.disabled = false;
    } else {
        card.classList.remove('active');
        debutInput.disabled = true;
        finInput.disabled = true;
    }
}
</script>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
