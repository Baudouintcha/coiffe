<?php
/**
 * coiffeurs/mes_zones.php — Sélection des zones géographiques d'intervention
 * Migration Design System v2.0 — Parcours Coiffeur — Page 5
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, quartiers, zones_coiffeur : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    header("Location: /coiffons/access/connexion.php");
    exit();
}

require_once __DIR__ . '/../security/config.php';

$id_coiffeur = $_SESSION['id_user'];
$message_type = '';
$message_text = '';

// IDs villes — inchangés
$id_cotonou = 1;
$id_calavi  = 2;

// TRAITEMENT — SQL inchangé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_zones'])) {
    try {
        $pdo->beginTransaction();

        $delete_stmt = $pdo->prepare("DELETE FROM zones_coiffeur WHERE id_coiffeur = ?");
        $delete_stmt->execute([$id_coiffeur]);

        if (isset($_POST['quartiers_choisis']) && is_array($_POST['quartiers_choisis'])) {
            $insert_stmt = $pdo->prepare("INSERT INTO zones_coiffeur (id_coiffeur, id_quartier) VALUES (?, ?)");
            foreach ($_POST['quartiers_choisis'] as $id_quartier) {
                $insert_stmt->execute([$id_coiffeur, intval($id_quartier)]);
            }
        }

        $pdo->commit();
        $message_type = 'success';
        $message_text = 'Vos zones d\'intervention ont été mises à jour avec succès !';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message_type = 'danger';
        $message_text = 'Une erreur est survenue : ' . $e->getMessage();
    }
}

// Récupération ville coiffeur — SQL CORRIGÉ : utiliser id_ville au lieu de ville
$profile_stmt = $pdo->prepare("SELECT id_ville FROM users WHERE id = ?");
$profile_stmt->execute([$id_coiffeur]);
$id_ville_coiffeur = intval($profile_stmt->fetchColumn());

if ($id_ville_coiffeur === $id_cotonou || $id_ville_coiffeur === $id_calavi) {
    $all_quartiers_stmt = $pdo->prepare("
        SELECT q.*, v.nom_ville
        FROM quartiers q
        JOIN villes v ON q.id_ville = v.id
        WHERE q.id_ville IN (?, ?)
        ORDER BY v.nom_ville ASC, q.nom_quartier ASC
    ");
    $all_quartiers_stmt->execute([$id_cotonou, $id_calavi]);
} else {
    $all_quartiers_stmt = $pdo->prepare("
        SELECT q.*, v.nom_ville
        FROM quartiers q
        JOIN villes v ON q.id_ville = v.id
        WHERE q.id_ville = ?
        ORDER BY q.nom_quartier ASC
    ");
    $all_quartiers_stmt->execute([$id_ville_coiffeur]);
}
$tous_les_quartiers = $all_quartiers_stmt->fetchAll();

$mes_zones_stmt = $pdo->prepare("SELECT id_quartier FROM zones_coiffeur WHERE id_coiffeur = ?");
$mes_zones_stmt->execute([$id_coiffeur]);
$raw_zones        = $mes_zones_stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
$mes_zones_actives = array_map('intval', $raw_zones);

include __DIR__ . '/../layout/header_coiffeur.php';
?>

<!-- ActionBar -->
<?php
$ab = [
    'icon'     => 'bi-geo-alt',
    'title'    => 'Mes Zones d\'Intervention',
    'subtitle' => 'Cochez les quartiers où vous acceptez de vous déplacer',
];
include __DIR__ . '/../views/components/action_bar.php';
?>

<!-- Toast message -->
<?php if (!empty($message_text)):
    $toast_type    = $message_type;
    $toast_message = $message_text;
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<!-- Conseil -->
<div class="glass-cct p-3 mb-4"
     style="background:var(--gold-dim);border:1px dashed rgba(212,175,55,0.3);">
    <i class="bi bi-lightbulb" style="color:var(--gold);margin-right:8px;" aria-hidden="true"></i>
    <span style="color:var(--text-secondary);font-size:0.82rem;">
        Plus vous couvrez de zones, plus vous apparaissez dans les résultats de recherche des clients.
    </span>
</div>

<!-- Formulaire zones -->
<form method="POST" novalidate>
    <?php if (!empty($tous_les_quartiers)): ?>
        <div class="row g-3 mb-4">
            <?php foreach ($tous_les_quartiers as $q): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <label class="zone-label" for="quartier-<?= $q['id'] ?>">
                        <input type="checkbox"
                               name="quartiers_choisis[]"
                               value="<?= $q['id'] ?>"
                               id="quartier-<?= $q['id'] ?>"
                               <?= in_array(intval($q['id']), $mes_zones_actives, true) ? 'checked' : '' ?>>
                        <div>
                            <div style="font-size:0.82rem;font-weight:600;color:var(--text-primary);">
                                <?= htmlspecialchars($q['nom_quartier']) ?>
                            </div>
                            <div style="font-size:0.68rem;color:var(--text-muted);">
                                <?= htmlspecialchars($q['nom_ville']) ?>
                            </div>
                        </div>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <?php
        $es = ['icon'=>'bi-geo', 'message'=>'Aucun quartier disponible pour votre zone géographique.'];
        include __DIR__ . '/../views/components/empty_state.php';
        ?>
    <?php endif; ?>

    <div class="text-center">
        <button type="submit"
                name="enregistrer_zones"
                class="btn-gold"
                style="padding:12px 40px;border-radius:var(--radius-pill);">
            <i class="bi bi-shield-check me-2" aria-hidden="true"></i>
            Enregistrer mes zones
        </button>
    </div>
</form>

<style>
/* Zone label — checkboxes stylisées */
.zone-label {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: var(--transition-fast);
}
.zone-label:hover,
.zone-label:has(input:checked) {
    border-color: rgba(212,175,55,0.3);
    background: var(--gold-dim);
}
.zone-label input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: var(--gold); cursor: pointer;
    flex-shrink: 0;
}
</style>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
