<?php
include 'header.php';
require 'config.php';

// Sécurité : réservé aux clients connectés
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: connexion.php');
    exit();
}

$id_client = $_SESSION['user_id'];
$message = "";

// Récupération des informations du client connecté pour les pré-remplir
$stmt_client = $pdo->prepare("SELECT nom, prenom, ville, quartier FROM users WHERE id = ?");
$stmt_client->execute([$id_client]);
$client = $stmt_client->fetch();

// Récupération du coiffeur et de la prestation sélectionnés via l'URL (ex: reserver.php?coiffeur_id=X&presta_id=Y)
$id_coiffeur = isset($_GET['coiffeur_id']) ? intval($_GET['coiffeur_id']) : null;
$id_prestation = isset($_GET['presta_id']) ? intval($_GET['presta_id']) : null;

// S'il manque un paramètre, on redirige vers le catalogue pour choisir un style
if (!$id_coiffeur || !$id_prestation) {
    header('Location: catalogue.php');
    exit();
}

// Récupération des détails du coiffeur et de la prestation
$stmt_info = $pdo->prepare("SELECT u.nom, u.prenom, u.telephone, p.nom_style, p.prix 
                            FROM users u 
                            JOIN prestations p ON p.id_coiffeur = u.id 
                            WHERE u.id = ? AND p.id_prestation = ?");
$stmt_info->execute([$id_coiffeur, $id_prestation]);
$infos = $stmt_info->fetch();

if (!$infos) {
    header('Location: catalogue.php');
    exit();
}

// Traitement de la réservation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmer_rdv'])) {
    $date_rdv = htmlspecialchars($_POST['date_rdv']);
    $heure_rdv = htmlspecialchars($_POST['heure_rdv']);
    $lieu_rdv = htmlspecialchars($_POST['lieu']);

    // 1. Vérification si le jour est disponible dans l'agenda du coiffeur
    $jour_semaine = date('l', strtotime($date_rdv)); // En anglais selon la base, on peut adapter

    // Pour simplifier la correspondance des jours en français/anglais
    $jours = ['Sunday' => 'Dimanche', 'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi'];
    $jour_fr = $jours[date('l', strtotime($date_rdv))];

    $stmt_dispo = $pdo->prepare("SELECT * FROM coiffeur_disponibilites WHERE id_coiffeur = ? AND jour_semaine = ?");
    $stmt_dispo->execute([$id_coiffeur, $jour_fr]);
    $dispo = $stmt_dispo->fetch();

    if (!$dispo) {
        $message = "<div class='alert alert-danger'><strong>Erreur :</strong> Ce jour-là, le coiffeur ne travaille pas.</div>";
    } else {
        $heure_debut = strtotime($dispo['heure_debut']);
        $heure_fin = strtotime($dispo['heure_fin']);
        $heure_choisie = strtotime($heure_rdv);

        // 2. Vérification des heures de travail
        if ($heure_choisie < $heure_debut || ($heure_choisie + 7200) > $heure_fin) { // Ajout de 2h (durée de la prestation)
            $message = "<div class='alert alert-danger'><strong>Alerte :</strong> Le coiffeur ne sera pas disponible à cette heure (créneau requis : 2 heures).</div>";
        } else {
            // 3. Vérification si le créneau est déjà pris
            $stmt_rdv = $pdo->prepare("SELECT id_rdv FROM rendezvous WHERE id_coiffeur = ? AND date_rdv = ? AND heure_rdv = ?");
            $stmt_rdv->execute([$id_coiffeur, $date_rdv, $heure_rdv]);

            if ($stmt_rdv->rowCount() > 0) {
                $message = "<div class='alert alert-warning'><strong>Alerte :</strong> Ce créneau est déjà occupé par une autre réservation !</div>";
            } else {
                // 4. Enregistrement
                $stmt_insert = $pdo->prepare("INSERT INTO rendezvous (id_client, id_coiffeur, id_prestation, date_rdv, heure_rdv, lieu, statut) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')");
                if ($stmt_insert->execute([$id_client, $id_coiffeur, $id_prestation, $date_rdv, $heure_rdv, $lieu_rdv])) {
                    $message = "<div class='alert alert-success'>Votre rendez-vous a été enregistré avec succès. Le coiffeur recevra la notification !</div>";
                }
            }
        }
    }
}
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4" style="max-width: 600px;">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">FINALISER VOTRE RÉSERVATION</h2>

        <?php echo $message; ?>

        <div class="card p-4 shadow-lg" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
            <h5 class="text-warning mb-4">Résumé de votre choix</h5>
            <p class="text-white mb-1"><strong>Coiffeur :</strong> <?php echo htmlspecialchars(strtoupper($infos['nom'] . ' ' . $infos['prenom'])); ?></p>
            <p class="text-white mb-1"><strong>Style :</strong> <?php echo htmlspecialchars($infos['nom_style']); ?></p>
            <p class="text-success fw-bold fs-5 mb-4"><strong>Tarif :</strong> <?php echo number_format($infos['prix'], 0, ',', ' '); ?> FCFA</p>

            <hr class="border-warning my-3">

            <form method="POST">
                <div class="mb-3">
                    <label class="text-white small mb-1">Vos informations</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="text-white small mb-1">Lieu de résidence (lieu de la prestation)</label>
                    <input type="text" name="lieu" class="form-control" value="<?php echo htmlspecialchars($client['ville'] . ', ' . $client['quartier']); ?>" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="text-white small mb-1">Jour du rendez-vous</label>
                        <input type="date" name="date_rdv" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="text-white small mb-1">Heure de début</label>
                        <input type="time" name="heure_rdv" class="form-control" required>
                    </div>
                </div>

                <button type="submit" name="confirmer_rdv" class="btn btn-gold w-100 py-2 fw-bold mt-3">
                    VALIDER LA RÉSERVATION
                </button>
            </form>
        </div>
    </div>
</section>

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

<?php include 'footer.php'; ?>