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
    <div class="glass-cct p-4">
        <div class="mb-4">
            <span class="badge-gold" style="display:inline-block;margin-bottom:10px;">Étape directe</span>
            <h3 style="font-family:var(--font-display);font-size:1.1rem;color:var(--text-primary);font-weight:700;margin-bottom:6px;">
                Définissez vos jours et plages horaires
            </h3>
            <p style="color:var(--text-muted);font-size:0.82rem;">
                Cochez les jours où vous acceptez les rendez-vous à domicile, puis ajustez vos heures.
            </p>
        </div>

        <form action="/coiffons/coiffeurs/sauvegarder_agenda.php" method="POST">
            <div class="table-responsive" style="border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--glass-border);">
                <table class="table table-dark table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr style="color:var(--text-muted);font-size:0.75rem;text-transform:uppercase;letter-spacing:.5px;">
                            <th class="text-center" style="width:70px;">Actif</th>
                            <th>Jour</th>
                            <th>Début</th>
                            <th>Fin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jours_semaine as $jour):
                            $cle_jour  = array_search($jour, array_column($horaires_existants, 'jour_semaine'));
                            $dispo_jour = ($cle_jour !== false) ? $horaires_existants[$cle_jour] : null;
                        ?>
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox"
                                           name="dispo[<?= $jour ?>]"
                                           value="1"
                                           class="form-check-input"
                                           style="width:20px;height:20px;cursor:pointer;background:var(--dark-3);border-color:var(--glass-border-md);"
                                           <?= $dispo_jour ? 'checked' : '' ?>>
                                </td>
                                <td style="font-weight:700;color:var(--text-primary);"><?= $jour ?></td>
                                <td>
                                    <input type="time"
                                           name="heure_debut[<?= $jour ?>]"
                                           class="fc-dark"
                                           style="max-width:120px;"
                                           value="<?= $dispo_jour['heure_debut'] ?? '08:00' ?>">
                                </td>
                                <td>
                                    <input type="time"
                                           name="heure_fin[<?= $jour ?>]"
                                           class="fc-dark"
                                           style="max-width:120px;"
                                           value="<?= $dispo_jour['heure_fin'] ?? '19:00' ?>">
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

<?php else: ?>
    <!-- ── Mode lecture — aperçu du planning ── -->
    <div class="glass-cct p-4">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3"
             style="border-bottom:1px solid var(--glass-border);">
            <div>
                <h4 style="font-family:var(--font-display);font-size:1rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
                    Votre planning hebdomadaire
                </h4>
                <p style="color:var(--text-muted);font-size:0.78rem;margin:0;">
                    Ces horaires sont visibles par les clients dans votre profil.
                </p>
            </div>
            <a href="/coiffons/coiffeurs/agenda_coiffeurs.php?action=edit"
               class="btn-outline-gold btn-sm"
               aria-label="Modifier l'agenda">
                <i class="bi bi-pencil me-1" aria-hidden="true"></i>Modifier
            </a>
        </div>

        <div class="row g-3">
            <?php foreach ($horaires_existants as $h): ?>
                <div class="col-md-4 col-sm-6">
                    <?php
                    // AvailabilityCard lecture seule
                    $av = [
                        'jour'   => htmlspecialchars($h['jour_semaine']),
                        'debut'  => date('H:i', strtotime($h['heure_debut'])),
                        'fin'    => date('H:i', strtotime($h['heure_fin'])),
                        'active' => true,
                    ];
                    ?>
                    <div class="availability-card availability-card--active" style="text-align:center;padding:1rem;">
                        <span class="badge-verified" style="display:inline-block;margin-bottom:8px;">
                            <?= $av['jour'] ?>
                        </span>
                        <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);font-family:monospace;">
                            <?= $av['debut'] ?> – <?= $av['fin'] ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
