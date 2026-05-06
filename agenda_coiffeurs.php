<?php
include 'header.php';
require 'config.php';

// Sécurité : réservé aux coiffeurs
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: connexion.php');
    exit();
}

$id_coiffeur = $_SESSION['user_id'];
$message = "";

// Enregistrement des disponibilités
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['definir_horaires'])) {
    $jour_semaine = htmlspecialchars($_POST['jour_semaine']);
    $heure_debut = htmlspecialchars($_POST['heure_debut']);
    $heure_fin = htmlspecialchars($_POST['heure_fin']);

    // Vérification si le jour existe déjà
    $check = $pdo->prepare("SELECT id_dispo FROM coiffeur_disponibilites WHERE id_coiffeur = ? AND jour_semaine = ?");
    $check->execute([$id_coiffeur, $jour_semaine]);

    if ($check->rowCount() > 0) {
        $update = $pdo->prepare("UPDATE coiffeur_disponibilites SET heure_debut = ?, heure_fin = ? WHERE id_coiffeur = ? AND jour_semaine = ?");
        $update->execute([$heure_debut, $heure_fin, $id_coiffeur, $jour_semaine]);
    } else {
        $insert = $pdo->prepare("INSERT INTO coiffeur_disponibilites (id_coiffeur, jour_semaine, heure_debut, heure_fin) VALUES (?, ?, ?, ?)");
        $insert->execute([$id_coiffeur, $jour_semaine, $heure_debut, $heure_fin]);
    }
    $message = "<div class='alert alert-success'>Disponibilités mises à jour avec succès.</div>";
}

// Récupération des disponibilités
$dispo_stmt = $pdo->prepare("SELECT * FROM coiffeur_disponibilites WHERE id_coiffeur = ? ORDER BY id_dispo ASC");
$dispo_stmt->execute([$id_coiffeur]);
$disponibilites = $dispo_stmt->fetchAll();

// Récupération des rendez-vous des clients (à venir)
$rdv_stmt = $pdo->prepare("SELECT * FROM rendezvous WHERE id_coiffeur = ? ORDER BY date_rdv ASC");
// (Note : Si la table rendezvous est créée plus tard, cette requête fonctionne avec les clés appropriées)
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">GESTION DE MON AGENDA</h2>

        <?php echo $message; ?>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card p-4 shadow-lg h-100" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <h4 class="text-warning mb-3">Définir mes horaires</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="text-white small">Jour de la semaine</label>
                            <select name="jour_semaine" class="form-select" required>
                                <option value="Lundi">Lundi</option>
                                <option value="Mardi">Mardi</option>
                                <option value="Mercredi">Mercredi</option>
                                <option value="Jeudi">Jeudi</option>
                                <option value="Vendredi">Vendredi</option>
                                <option value="Samedi">Samedi</option>
                                <option value="Dimanche">Dimanche</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Heure de début</label>
                            <input type="time" name="heure_debut" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="text-white small">Heure de fin</label>
                            <input type="time" name="heure_fin" class="form-control" required>
                        </div>
                        <button type="submit" name="definir_horaires" class="btn btn-gold w-100 py-2 fw-bold mt-2">
                            ENREGISTRER
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <h4 class="text-warning mb-3">Mes horaires enregistrés</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-bordered border-warning align-middle">
                        <thead>
                            <tr>
                                <th>Jour</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($disponibilites)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-4">Aucun horaire enregistré.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($disponibilites as $d): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($d['jour_semaine']); ?></td>
                                        <td><?php echo htmlspecialchars($d['heure_debut']); ?></td>
                                        <td><?php echo htmlspecialchars($d['heure_fin']); ?></td>
                                        <td>
                                            <a href="agenda_coiffeur.php?supprimer_dispo=<?php echo $d['id_dispo']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette disponibilité ?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h4 class="text-warning mt-5 mb-3">Mes rendez-vous à venir</h4>
                <div class="p-4 rounded" style="background-color: #111; border: 1px solid #333;">
                    <p class="text-center text-secondary py-4">
                        <i class="bi bi-calendar-x" style="font-size: 2rem;"></i><br>
                        Aucun rendez-vous pour le moment.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .form-control,
    .form-select {
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