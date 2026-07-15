<?php
// Fichier : coiffons/coiffeurs/agenda_coiffeurs.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusions avec les bons chemins relatifs
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header_coiffeur.php';

$coiffeur_id = $_SESSION['id_user'] ?? null;
$role_actuel = $_SESSION['role'] ?? 'invite';

// Protection de la page
if (!$coiffeur_id || $role_actuel !== 'coiffeur') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

// Récupération des horaires existants depuis la table 'disponibilites'
try {
    $stmt = $pdo->prepare("SELECT * FROM disponibilites WHERE coiffeur_id = ? ORDER BY FIELD(jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche')");
    $stmt->execute([$coiffeur_id]);
    $horaires_existants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $horaires_existants = [];
}

$mode_edition = isset($_GET['action']) && $_GET['action'] === 'edit';
$est_vide = empty($horaires_existants);

$jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
?>

<section class="py-0">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--gold);margin-bottom:2px;">
            <i class="bi bi-calendar3 me-2"></i>Mon Agenda
        </h2>
        <p style="color:rgba(255,255,255,0.35);font-size:0.78rem;margin:0;">Configurez vos disponibilités hebdomadaires</p>
    </div>
    <a href="/coiffons/index.php?page=dashboard_coiffeur"
       style="color:rgba(255,255,255,0.35);text-decoration:none;font-size:0.8rem;display:flex;align-items:center;gap:5px;">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
    <div class="msg-success mb-3">
        <i class="bi bi-check-circle me-2"></i> Votre agenda a été enregistré avec succès.
    </div>
<?php endif; ?>

        <?php if ($est_vide || $mode_edition): ?>
            <div class="glass-cct p-4">
                <div class="mb-3">
                    <span class="badge bg-warning text-dark fw-bold mb-2">Étape Directe</span>
                    <h3 class="text-white fw-bold mb-1">Définissez vos jours et plages horaires</h3>
                    <p class="text-muted small mb-0">Cochez simplement les jours où vous acceptez les rendez-vous clients à domicile, puis ajustez vos heures.</p>
                </div>

                <form action="sauvegarder_agenda.php" method="POST" class="mt-4">
                    <div class="table-responsive rounded border border-secondary">
                        <table class="table table-dark table-striped table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary small">
                                    <th style="width: 80px;" class="text-center">Actif</th>
                                    <th>Jour de la semaine</th>
                                    <th>Heure d'ouverture (Début)</th>
                                    <th>Heure de fermeture (Fin)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jours_semaine as $jour): 
                                    // Utilisation de 'jour_semaine' pour correspondre à la table disponibilites
                                    $cle_jour = array_search($jour, array_column($horaires_existants, 'jour_semaine'));
                                    $dispo_jour = ($cle_jour !== false) ? $horaires_existants[$cle_jour] : null;
                                ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="dispo[<?php echo $jour; ?>]" value="1" class="form-check-input bg-dark border-secondary fs-5" style="cursor: pointer;" <?php echo $dispo_jour ? 'checked' : ''; ?>>
                                        </td>
                                        <td class="text-white fw-bold fs-5"><?php echo $jour; ?></td>
                                        <td>
                                            <input type="time" name="heure_debut[<?php echo $jour; ?>]" class="form-control bg-dark text-white border-secondary py-2 font-monospace" value="<?php echo $dispo_jour['heure_debut'] ?? '08:00'; ?>">
                                        </td>
                                        <td>
                                            <input type="time" name="heure_fin[<?php echo $jour; ?>]" class="form-control bg-dark text-white border-secondary py-2 font-monospace" value="<?php echo $dispo_jour['heure_fin'] ?? '19:00'; ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary">
                        <span class="text-muted small"><i class="bi bi-info-circle text-warning me-1"></i> Les clients ne pourront réserver que sur les créneaux cochés ici.</span>
                    <button type="submit" class="btn btn-gold-cct fw-bold px-5 py-2">
                        ENREGISTRER MON AGENDA
                    </button>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <div class="glass-cct p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
                    <div>
                        <h4 class="text-white fw-bold mb-1">Votre planning hebdomadaire actuel</h4>
                        <p class="text-muted small mb-0">Voici les horaires visibles par les clients sur la plateforme.</p>
                    </div>
                    <a href="/coiffons/coiffeurs/agenda_coiffeurs.php?action=edit" class="btn btn-gold-cct btn-sm px-4">
                        <i class="bi bi-pencil-square me-2"></i>MODIFIER</a>
                </div>

                <div class="row g-3">
                    <?php foreach ($horaires_existants as $h): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="p-3 rounded border border-success text-center shadow-sm" style="background-color: #0b0c10;">
                                <!-- Utilisation de 'jour_semaine' pour l'affichage de l'aperçu -->
                                <span class="badge bg-success uppercase px-3 py-2 mb-2 fw-bold"><?php echo htmlspecialchars($h['jour_semaine']); ?></span>
                                <div class="text-white font-monospace fs-5 mt-1">
                                    <?php echo date('H:i', strtotime($h['heure_debut'])); ?> à <?php echo date('H:i', strtotime($h['heure_fin'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<style>
    .fc-dark { background:rgba(255,255,255,0.06)!important;border:1px solid rgba(255,255,255,0.12)!important;color:#fff!important;border-radius:8px!important; }
    .glass-cct { background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:16px; }
    .btn-gold-cct { background:var(--gold);color:#000;font-weight:700;border:none;border-radius:10px; }
    .btn-gold-cct:hover { background:#c9a227;color:#000; }
    .msg-success { background:rgba(25,135,84,0.15);border:1px solid rgba(25,135,84,0.3);color:#6ee7b7;border-radius:10px;padding:10px 16px;font-size:0.85rem; }
</style>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>