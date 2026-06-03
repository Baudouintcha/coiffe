<?php
// 1. Sécurité et Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seul un client connecté peut réserver
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    echo "<script>window.location.href='connexion.php';</script>";
    exit();
}

require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

$id_client = $_SESSION['id_user'];
$message = "";

// Récupération des paramètres de l'URL
$id_prestation = isset($_GET['presta_id']) ? intval($_GET['presta_id']) : 0;
$id_coiffeur = isset($_GET['coiffeur_id']) ? intval($_GET['coiffeur_id']) : 0;

// 1. On récupère les infos de la coiffure demandée depuis la table prestations
$presta_stmt = $pdo->prepare("SELECT * FROM prestations WHERE id_prestation = ?");
$presta_stmt->execute([$id_prestation]);
$prestation = $presta_stmt->fetch();

// 2. Récupération du temps configuré par le coiffeur (60 min par défaut si vide)
$duree_coiffeur = (isset($prestation['duree']) && $prestation['duree'] > 0) ? intval($prestation['duree']) : 60;

if (!$prestation) {
    echo "<div class='container mt-5 alert alert-danger text-center fw-bold'>Prestation introuvable.</div>";
    include __DIR__ . '/../layout/footer.php';
    exit();
}

// ==========================================
// ➕ MODIFICATION : EXTRACTION DES OPTIONS DU CATALOGUE (JSON)
// ==========================================
$explode = explode("|||", $prestation['description']);
$description_pure = $explode[0] ?? '';
$json_options = $explode[1] ?? '[]';
$options_catalogue = json_decode($json_options, true) ?: [];

// ==========================================
// ➕ MODIFICATION : RÉCUPÉRATION DU SOLDE CLIENT POUR SÉCURITÉ
// ==========================================
$solde_stmt = $pdo->prepare("SELECT solde FROM users WHERE id = ?");
$solde_stmt->execute([$id_client]);
$client_info = $solde_stmt->fetch();
$solde_actuel = $client_info['solde'] ?? 0;


// Traitement du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmer_rdv'])) {
    $date_rdv = htmlspecialchars($_POST['date_rdv']);
    $heure_debut = htmlspecialchars($_POST['heure_debut']);
    $heure_fin = htmlspecialchars($_POST['heure_fin']);

    // --- ZONE AUTOMATIQUE : FAIT PARTIE DE TON CODE DE BASE (CONSERVÉ INTACT) ---
    try {
        // On coupe temporairement la vérification pour agir
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        
        // On récupère la structure SQL interne de la table pour trouver la clé cachée
        $stmt = $pdo->query("SHOW CREATE TABLE rendez_vous");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $create_table_sql = $row['Create Table'];
        
        // Si PHP repère une contrainte liée à coiffure_id, il l'isole et l'extermine
        if (preg_match('/CONSTRAINT `([^`]+)` FOREIGN KEY \(`coiffure_id`\)/', $create_table_sql, $matches)) {
            $fk_name = $matches[1];
            $pdo->exec("ALTER TABLE rendez_vous DROP FOREIGN KEY `$fk_name`;");
        }
        
        // On nettoie aussi l'index associé pour que MariaDB soit totalement libre
        $pdo->exec("ALTER TABLE rendez_vous DROP INDEX IF EXISTS `coiffure_id`;");
        
        // On remet la sécurité globale en marche
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    } catch (Exception $e) {
        // Si le nettoyage a déjà réussi au clic précédent, on passe sans bloquer
    }
    // --- FIN DE LA ZONE DE SÉCURITÉ DE BASE ---


    // ==========================================
    // ➕ MODIFICATION : CALCUL DU COÛT REEL DU RDV (BASE + OPTIONS COCHÉES)
    // ==========================================
    $surcout_options = 0;
    if (isset($_POST['options_selectionnees']) && is_array($_POST['options_selectionnees'])) {
        foreach ($_POST['options_selectionnees'] as $prix_opt) {
            $surcout_options += floatval($prix_opt);
        }
    }
    $prix_total_calcule = floatval($prestation['prix']) + $surcout_options;


    // ==========================================
    // ➕ MODIFICATION : VÉRIFICATION DU PORTEFEUILLE AVANT L'INSERTION DE BASE
    // ==========================================
    if ($solde_actuel < $prix_total_calcule) {
        $message = "<div class='alert alert-danger text-center fw-bold'>❌ Solde insuffisant pour geler les fonds de ce RDV (" . number_format($prix_total_calcule, 0, ',', ' ') . " FCFA requis).</div>";
    } else {

        // 📝 TA REQUÊTE D'INSERTION D'ORIGINE (STRICTEMENT INTACTE)
        $insert = $pdo->prepare("INSERT INTO rendez_vous (client_id, coiffeur_id, coiffure_id, date_rdv, heure_debut, heure_fin, statut_rdv) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')");
        
        if ($insert->execute([$id_client, $id_coiffeur, $id_prestation, $date_rdv, $heure_debut, $heure_fin])) {
            $message = "<div class='alert alert-success text-center fw-bold'> Demande de rendez-vous envoyée au coiffeur !</div>";
        } else {
            $message = "<div class='alert alert-danger text-center fw-bold'>Une erreur est survenue, veuillez réessayer.</div>";
        }

    }
}
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <?php echo $message; ?>

                <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <h3 class="text-warning text-center mb-4" style="letter-spacing: 1px;">RÉSERVER MA SÉANCE</h3>
                    
                    <div class="p-3 mb-4 rounded bg-dark border border-secondary">
                        <h5 class="text-white mb-1 text-center"><?php echo htmlspecialchars($prestation['nom_style']); ?></h5>
                        <p class="text-muted small text-center mb-1"><?php echo htmlspecialchars($description_pure); ?></p>
                        <p class="text-success fw-bold mb-0 text-center"><?php echo number_format($prestation['prix'], 0, ',', ' '); ?> FCFA</p>
                        <hr class="border-secondary my-2">
                        <p class="text-secondary small text-center mb-0">
                            <i class="bi bi-hourglass-split text-warning"></i> Temps défini par le coiffeur : <strong><?php echo $duree_coiffeur; ?> minutes</strong>
                        </p>
                    </div>

                    <form method="POST">
                        <input type="hidden" id="temps_coiffeur" value="<?php echo $duree_coiffeur; ?>">
                        <input type="hidden" id="prix_de_base_js" value="<?php echo $prestation['prix']; ?>">

                        <?php if (!empty($options_catalogue)): ?>
                            <div class="mb-4 p-3 rounded" style="background-color: #161616; border: 1px dashed #333;">
                                <label class="text-warning small d-block mb-2 fw-bold">OPTIONS DISPONIBLES :</label>
                                <?php foreach ($options_catalogue as $index => $opt): ?>
                                    <div class="form-check my-2">
                                        <input class="form-check-input option-checkbox-js" type="checkbox" 
                                               name="options_selectionnees[]" 
                                               value="<?php echo $opt['prix']; ?>" 
                                               data-prix="<?php echo $opt['prix']; ?>"
                                               id="opt-<?php echo $index; ?>">
                                        <label class="form-check-label text-white small" for="opt-<?php echo $index; ?>">
                                            <?php echo htmlspecialchars($opt['nom']); ?> 
                                            <span class="text-muted">(+<?php echo number_format($opt['prix'], 0, ',', ' '); ?> F)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="text-white small mb-1">Date du rendez-vous</label>
                            <input type="date" name="date_rdv" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="text-white small mb-1">Heure de début</label>
                                <input type="time" id="heure_debut" name="heure_debut" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="text-white small mb-1">Heure de fin </label>
                                <input type="time" id="heure_fin" name="heure_fin" class="form-control bg-dark border-secondary text-warning fw-bold" readonly required>
                            </div>
                        </div>

                        <div class="p-2 mb-3 rounded text-center" style="background-color: #1a1a1a; border: 1px solid #222;">
                            <span class="text-secondary small">Montant total estimé :</span>
                            <span class="text-warning fw-bold id" id="total_dynamique_rdv"><?php echo number_format($prestation['prix'], 0, ',', ' '); ?></span> <span class="text-warning fw-bold">FCFA</span>
                        </div>

                        <button type="submit" name="confirmer_rdv" class="btn btn-gold w-100 py-2 fw-bold mt-2">
                            CONFIRMER LA RÉSERVATION
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Captures des nouveaux éléments pour le calcul combiné
const heureDebutInput = document.getElementById('heure_debut');
const checkboxesOptions = document.querySelectorAll('.option-checkbox-js');

function actualiserDureeEtPrix() {
    // 1. Ton calcul initial de l'heure de fin (INTACT COPIÉ-COLLÉ)
    let heureSelectionnee = heureDebutInput.value; 
    let tempsNecessaire = parseInt(document.getElementById('temps_coiffeur').value); 
    
    if (heureSelectionnee && !isNaN(tempsNecessaire)) {
        let [heures, minutes] = heureSelectionnee.split(':').map(Number);
        
        let totalMinutes = minutes + tempsNecessaire;
        let nouvellesHeures = heures + Math.floor(totalMinutes / 60);
        let nouvellesMinutes = totalMinutes % 60;
        
        nouvellesHeures = nouvellesHeures % 24;
        
        let formatHeure = String(nouvellesHeures).padStart(2, '0');
        let formatMinute = String(nouvellesMinutes).padStart(2, '0');
        
        document.getElementById('heure_fin').value = `${formatHeure}:${formatMinute}`;
    }

    // 2. ➕ AJOUT : Calcul du prix total avec surcoûts en direct
    let prixDeBase = parseFloat(document.getElementById('prix_de_base_js').value) || 0;
    let cumulatOptions = 0;

    checkboxesOptions.forEach(cb => {
        if (cb.checked) {
            cumulatOptions += parseFloat(cb.getAttribute('data-prix')) || 0;
        }
    });

    let prixGlobal = prixDeBase + cumulatOptions;
    document.getElementById('total_dynamique_rdv').innerText = prixGlobal.toLocaleString('fr-FR');
}

// Écouteurs d'événements croisés
heureDebutInput.addEventListener('change', actualiserDureeEtPrix);
checkboxesOptions.forEach(cb => cb.addEventListener('change', actualiserDureeEtPrix));
</script>

<style>
    .form-control {
        background-color: #fff;
        color: #000;
        border-radius: 8px;
    }
    .btn-gold {
        background-color: #ffc107;
        color: black;
        border: none;
        border-radius: 8px;
    }
    .btn-gold:hover {
        background-color: #c99b2c;
        color: black;
    }
    /* ➕ Alignement propre des checkboxes */
    .form-check-input:checked {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>