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

// Traitement du changement de statut du rendez-vous
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['changer_statut'])) {
    $id_rdv = intval($_POST['id_rdv']);
    $nouveau_statut = htmlspecialchars($_POST['statut']);

    // On s'assure que le coiffeur est bien concerné par ce RDV
    $stmt_check = $pdo->prepare("SELECT id_rdv FROM rendezvous WHERE id_rdv = ? AND id_coiffeur = ?");
    $stmt_check->execute([$id_rdv, $id_coiffeur]);

    if ($stmt_check->rowCount() > 0) {
        $stmt_update = $pdo->prepare("UPDATE rendezvous SET statut = ? WHERE id_rdv = ?");
        if ($stmt_update->execute([$nouveau_statut, $id_rdv])) {
            $message = "<div class='alert alert-success'>Statut du rendez-vous mis à jour avec succès.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Une erreur est survenue.</div>";
        }
    }
}

// Récupération de tous les rendez-vous liés à ce coiffeur
$stmt_rdv = $pdo->prepare("SELECT r.*, p.nom_style, p.prix, u.nom AS client_nom, u.prenom AS client_prenom, u.telephone AS client_telephone 
                           FROM rendezvous r 
                           JOIN prestations p ON r.id_prestation = p.id_prestation 
                           JOIN users u ON r.id_client = u.id 
                           WHERE r.id_coiffeur = ? 
                           ORDER BY r.date_rdv DESC, r.heure_rdv DESC");
$stmt_rdv->execute([$id_coiffeur]);
$rendezvous_liste = $stmt_rdv->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">GESTION DES RENDEZ-VOUS</h2>

        <?php echo $message; ?>

        <div class="table-responsive">
            <table class="table table-dark table-bordered border-warning align-middle">
                <thead>
                    <tr class="text-warning">
                        <th>Date & Heure</th>
                        <th>Client</th>
                        <th>Prestation</th>
                        <th>Montant</th>
                        <th>Lieu</th>
                        <th>Contact</th>
                        <th>Statut actuel</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rendezvous_liste)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-5">
                                <i class="bi bi-calendar-x fs-2"></i><br>
                                Aucune demande de rendez-vous pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rendezvous_liste as $rdv): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($rdv['date_rdv']))); ?></strong><br>
                                    <small class="text-secondary"><?php echo htmlspecialchars(substr($rdv['heure_rdv'], 0, 5)); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(strtoupper($rdv['client_nom'] . ' ' . $rdv['client_prenom'])); ?></td>
                                <td><?php echo htmlspecialchars($rdv['nom_style']); ?></td>
                                <td class="text-success fw-bold"><?php echo number_format($rdv['prix'], 0, ',', ' '); ?> FCFA</td>
                                <td><?php echo htmlspecialchars($rdv['lieu']); ?></td>
                                <td>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $rdv['client_telephone']); ?>" target="_blank" class="btn btn-success btn-sm">
                                        <i class="bi bi-whatsapp"></i> Contacter
                                    </a>
                                </td>
                                <td>
                                    <?php if ($rdv['statut'] == 'en_attente'): ?>
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    <?php elseif ($rdv['statut'] == 'confirme'): ?>
                                        <span class="badge bg-primary">Confirmé</span>
                                    <?php elseif ($rdv['statut'] == 'termine'): ?>
                                        <span class="badge bg-success">Terminé</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($rdv['statut']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="id_rdv" value="<?php echo $rdv['id_rdv']; ?>">
                                        <select name="statut" class="form-select form-select-sm" style="width: auto;">
                                            <option value="confirme" <?php echo ($rdv['statut'] == 'confirme') ? 'selected' : ''; ?>>Confirmer</option>
                                            <option value="termine" <?php echo ($rdv['statut'] == 'termine') ? 'selected' : ''; ?>>Terminé</option>
                                            <option value="annule" <?php echo ($rdv['statut'] == 'annule') ? 'selected' : ''; ?>>Annuler</option>
                                        </select>
                                        <button type="submit" name="changer_statut" class="btn btn-warning btn-sm text-dark fw-bold">
                                            Mettre à jour
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-5">
            <a href="agenda.php" class="btn btn-gold px-5 py-2 fw-bold">
                <i class="bi bi-calendar3"></i> Voir mon agenda
            </a>
        </div>
    </div>
</section>

<style>
    .form-select {
        background-color: #333;
        color: #fff;
        border: 1px solid var(--gold);
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