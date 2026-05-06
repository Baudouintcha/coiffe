<?php
include 'header.php';
require 'config.php';

// Sécurité : réservé aux clients connectés
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: connexion.php');
    exit();
}

$id_client = $_SESSION['user_id'];

// Récupération de l'historique des réservations du client
$stmt = $pdo->prepare("SELECT r.*, p.nom_style, p.prix, u.nom AS coiffeur_nom, u.prenom AS coiffeur_prenom, u.telephone AS coiffeur_telephone 
                       FROM rendezvous r 
                       JOIN prestations p ON r.id_prestation = p.id_prestation 
                       JOIN users u ON r.id_coiffeur = u.id 
                       WHERE r.id_client = ? 
                       ORDER BY r.date_rdv DESC, r.heure_rdv DESC");
$stmt->execute([$id_client]);
$rendezvous = $stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">MES RENDEZ-VOUS</h2>

        <div class="table-responsive">
            <table class="table table-dark table-bordered border-warning align-middle">
                <thead>
                    <tr class="text-warning">
                        <th>Date & Heure</th>
                        <th>Coiffeur</th>
                        <th>Prestation</th>
                        <th>Montant</th>
                        <th>Lieu</th>
                        <th>Statut</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rendezvous)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-calendar-x fs-2"></i><br>
                                Vous n'avez aucune réservation pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rendezvous as $rdv): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($rdv['date_rdv']))); ?></strong><br>
                                    <small class="text-secondary"><?php echo htmlspecialchars(substr($rdv['heure_rdv'], 0, 5)); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(strtoupper($rdv['coiffeur_nom'] . ' ' . $rdv['coiffeur_prenom'])); ?></td>
                                <td><?php echo htmlspecialchars($rdv['nom_style']); ?></td>
                                <td class="text-success fw-bold"><?php echo number_format($rdv['prix'], 0, ',', ' '); ?> FCFA</td>
                                <td><?php echo htmlspecialchars($rdv['lieu']); ?></td>
                                <td>
                                    <?php if ($rdv['statut'] == 'en_attente'): ?>
                                        <span class="badge bg-warning text-dark">En attente de validation</span>
                                    <?php elseif ($rdv['statut'] == 'confirme'): ?>
                                        <span class="badge bg-primary">Confirmé</span>
                                    <?php elseif ($rdv['statut'] == 'termine'): ?>
                                        <span class="badge bg-success">Terminé</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($rdv['statut']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $rdv['coiffeur_telephone']); ?>" target="_blank" class="btn btn-success btn-sm">
                                        <i class="bi bi-whatsapp"></i> Contacter
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-5">
            <a href="catalogue.php" class="btn btn-gold px-5 py-2 fw-bold">
                <i class="bi bi-scissors"></i> Retour au catalogue
            </a>
        </div>
    </div>
</section>

<style>
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