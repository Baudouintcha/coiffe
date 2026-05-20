<?php
require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';

// SÉCURITÉ : Seul un coiffeur connecté peut gérer son agenda
if ($role_utilisateur !== 'coiffeur') {
    header("Location: index.php");
    exit();
}

$coiffeur_id = $_SESSION['id_user'];
$message_action = "";

// Les 7 jours de la semaine à gérer
$jours_semaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

// =================================================================
// 1. TRAITEMENT DU FORMULAIRE (SAUVEGARDE EN BDD)
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_agenda'])) {
    $pdo->beginTransaction();
    try {
        foreach ($jours_semaine as $jour) {
            // On vérifie si la case du jour est cochée (1) ou non (0)
            $est_dispo = isset($_POST['dispo'][$jour]) ? 1 : 0;
            $heure_debut = !empty($_POST['heure_debut'][$jour]) ? $_POST['heure_debut'][$jour] : '08:00';
            $heure_fin = !empty($_POST['heure_fin'][$jour]) ? $_POST['heure_fin'][$jour] : '18:00';

            // Le "ON DUPLICATE KEY UPDATE" permet de créer si ça n'existe pas, ou de modifier si ça existe déjà
            $stmt = $pdo->prepare("
                INSERT INTO disponibilites_coiffeur (user_id, jour_semaine, est_dispo, heure_debut, heure_fin)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE est_dispo = ?, heure_debut = ?, heure_fin = ?
            ");
            $stmt->execute([
                $coiffeur_id, $jour, $est_dispo, $heure_debut, $heure_fin,
                $est_dispo, $heure_debut, $heure_fin
            ]);
        }
        $pdo->commit();
        $message_action = "<div class='alert alert-success text-center fw-bold mb-4'><i class='bi bi-check-circle-fill'></i> Votre agenda a été mis à jour avec succès !</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message_action = "<div class='alert alert-danger text-center fw-bold mb-4'>Une erreur est survenue lors de l'enregistrement de l'agenda.</div>";
    }
}

// =================================================================
// 2. RÉCUPÉRATION DES DONNÉES EXISTANTES POUR L'AFFICHAGE
// =================================================================
$stmt_get = $pdo->prepare("SELECT * FROM disponibilites_coiffeur WHERE user_id = ?");
$stmt_get->execute([$coiffeur_id]);
$config_existante = $stmt_get->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
// Astuce PHP : $config_existante['Lundi'] contiendra directement les infos du lundi !
?>

<div class="container py-5" style="min-height: 80vh;">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-warning fw-bold"><i class="bi bi-calendar3-event me-2"></i> CONFIGURER MON AGENDA</h2>
            <p class="text-secondary small">Cochez vos jours de disponibilité et définissez vos tranches horaires de travail de manière indépendante.</p>
        </div>
    </div>

    <?php echo $message_action; ?>

    <form method="POST" action="agenda_coiffeur.php">
        <div class="card border-secondary p-4" style="background-color: var(--card-bg); border-radius: 12px;">
            
            <div class="d-flex flex-column gap-3">
                <?php foreach ($jours_semaine as $jour): 
                    // On récupère les valeurs en BDD si elles existent, sinon on met des valeurs par défaut
                    $checked = (isset($config_existante[$jour]) && $config_existante[$jour]['est_dispo'] == 1) ? 'checked' : '';
                    $h_debut = isset($config_existante[$jour]) ? substr($config_existante[$jour]['heure_debut'], 0, 5) : '08:00';
                    $h_fin = isset($config_existante[$jour]) ? substr($config_existante[$jour]['heure_fin'], 0, 5) : '18:00';
                ?>
                    <div class="row align-items-center bg-dark p-3 rounded border border-secondary inner-row-dispo">
                        
                        <div class="col-md-3 col-12 mb-2 mb-md-0">
                            <div class="form-check form-switch">
                                <input class="form-check-input syntax-checkbox-warning" type="checkbox" name="dispo[<?php echo $jour; ?>]" id="check_<?php echo $jour; ?>" value="1" <?php echo $checked; ?> onchange="toggleHeures('<?php echo $jour; ?>')">
                                <label class="form-check-label fw-bold text-white text-uppercase ms-2" for="check_<?php echo $jour; ?>">
                                    <?php echo $jour; ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-4 col-6 input-group-time-box">
                            <div class="input-group">
                                <span class="input-group-text bg-black text-secondary border-secondary small">De</span>
                                <input type="time" name="heure_debut[<?php echo $jour; ?>]" id="debut_<?php echo $jour; ?>" class="form-control bg-black text-white border-secondary text-center" value="<?php echo $h_debut; ?>" <?php echo empty($checked) ? 'disabled' : ''; ?>>
                            </div>
                        </div>

                        <div class="col-md-4 col-6 input-group-time-box">
                            <div class="input-group">
                                <span class="input-group-text bg-black text-secondary border-secondary small">À</span>
                                <input type="time" name="heure_fin[<?php echo $jour; ?>]" id="fin_<?php echo $jour; ?>" class="form-control bg-black text-white border-secondary text-center" value="<?php echo $h_fin; ?>" <?php echo empty($checked) ? 'disabled' : ''; ?>>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row mt-4">
                <div class="col text-end">
                    <button type="submit" name="enregistrer_agenda" class="btn btn-warning fw-bold px-5 py-2">
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i> Enregistrer mon agenda
                    </button>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
function toggleHeures(jour) {
    var checkBox = document.getElementById("check_" + jour);
    var inputDebut = document.getElementById("debut_" + jour);
    var inputFin = document.getElementById("fin_" + jour);
    
    if (checkBox.checked == true) {
        inputDebut.disabled = false;
        inputFin.disabled = false;
    } else {
        inputDebut.disabled = true;
        inputFin.disabled = true;
    }
}
</script>

<style>
    .syntax-checkbox-warning:checked {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
    }
    .inner-row-dispo {
        transition: all 0.3s ease;
    }
    .inner-row-dispo:hover {
        border-color: #ffc107 !important;
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>