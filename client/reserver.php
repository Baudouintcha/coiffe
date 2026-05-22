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

// Récupération des paramètres de l'URL (Ex: reserver.php?prestation=5&coiffeur=2)
$id_prestation = isset($_GET['prestation']) ? intval($_GET['prestation']) : 0;
$id_coiffeur = isset($_GET['coiffeur']) ? intval($_GET['coiffeur']) : 0;

// 1. On récupère les infos de la coiffure demandée
$presta_stmt = $pdo->prepare("SELECT * FROM prestations WHERE id_prestation = ?");
$presta_stmt->execute([$id_prestation]);
$prestation = $presta_stmt->fetch();

// 2. IMPORTANT : On récupère le temps configuré par le coiffeur pour cette prestation
// Si le champ 'duree' est dans ta table 'prestations', il prend la valeur décidée par le coiffeur
$duree_coiffeur = (isset($prestation['duree']) && $prestation['duree'] > 0) ? intval($prestation['duree']) : 60; // 60 min par défaut si vide

if (!$prestation) {
    echo "<div class='container mt-5 alert alert-danger'>Prestation introuvable.</div>";
    include 'footer.php';
    exit();
}

// Traitement du formulaire de réservation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmer_rdv'])) {
    $date_rdv = htmlspecialchars($_POST['date_rdv']);
    $heure_debut = htmlspecialchars($_POST['heure_debut']);
    $heure_fin = htmlspecialchars($_POST['heure_fin']); // Ce champ aura été calculé par le JavaScript

    // Insertion du rendez-vous en base de données
    $insert = $pdo->prepare("INSERT INTO rendez_vous (id_client, coiffeur_id, id_prestation, date_rdv, heure_debut, heure_fin, statut) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')");
    
    if ($insert->execute([$id_client, $id_coiffeur, $id_prestation, $date_rdv, $heure_debut, $heure_fin])) {
        $message = "<div class='alert alert-success text-center'> Demande de rendez-vous envoyée au coiffeur !</div>";
    } else {
        $message = "<div class='alert alert-danger text-center'>Une erreur est survenue, veuillez réessayer.</div>";
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
                        <p class="text-success fw-bold mb-0 text-center"><?php echo number_format($prestation['prix'], 0, ',', ' '); ?> FCFA</p>
                        <hr class="border-secondary my-2">
                        <p class="text-secondary small text-center mb-0">
                            <i class="bi bi-hourglass-split text-warning"></i> Temps défini par le coiffeur : <strong><?php echo $duree_coiffeur; ?> minutes</strong>
                        </p>
                    </div>

                    <form method="POST">
                        <input type="hidden" id="temps_coiffeur" value="<?php echo $duree_coiffeur; ?>">

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

                        <button type="submit" name="confirmer_rdv" class="btn btn-gold w-100 py-2 fw-bold mt-3">
                            CONFIRMER LA RÉSERVATION
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('heure_debut').addEventListener('change', function() {
    let heureSelectionnee = this.value; // Récupère par ex: "10:30"
    let tempsNecessaire = parseInt(document.getElementById('temps_coiffeur').value); // Récupère le temps du coiffeur (ex: 90)
    
    if (heureSelectionnee && !isNaN(tempsNecessaire)) {
        // On sépare les heures et les minutes choisies
        let [heures, minutes] = heureSelectionnee.split(':').map(Number);
        
        // Calcul mathématique des nouvelles minutes
        let totalMinutes = minutes + tempsNecessaire;
        let nouvellesHeures = heures + Math.floor(totalMinutes / 60);
        let nouvellesMinutes = totalMinutes % 60;
        
        // Gestion du cas où le calcul dépasse 24h (sécurité de base)
        nouvellesHeures = nouvellesHeures % 24;
        
        // Formatage pour l'affichage (ex: transformer "9" en "09")
        let formatHeure = String(nouvellesHeures).padStart(2, '0');
        let formatMinute = String(nouvellesMinutes).padStart(2, '0');
        
        // On implémente directement le résultat dans l'input d'heure de fin
        document.getElementById('heure_fin').value = `${formatHeure}:${formatMinute}`;
    }
});
</script>

<style>
    .form-control {
        background-color: #fff;
        color: #000;
        border-radius: 8px;
    }
    .btn-gold {
        background-color: var(--gold);
        color: black;
        border: none;
        border-radius: 8px;
    }
    .btn-gold:hover {
        background-color: #c99b2c;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>