<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// Sécurité : réservé aux coiffeurs
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: connexion.php');
    exit();
}

$id_coiffeur = $_SESSION['user_id'];

// 1. Définition des constantes de votre modèle économique
$abonnement_mensuel = 1500; // 1500 FCFA par mois
$commission_pourcentage = 0.05; // 5% sur chaque RDV

// Récupération des données du portefeuille (statut = 'termine' / payé)
$stmt = $pdo->prepare("SELECT 
                        COUNT(r.id_rdv) AS total_rdv, 
                        SUM(p.prix) AS total_gains 
                        FROM rendezvous r 
                        JOIN prestations p ON r.id_prestation = p.id_prestation 
                        WHERE r.id_coiffeur = ? AND r.statut = 'termine'");
$stmt->execute([$id_coiffeur]);
$stats = $stmt->fetch();

$total_gains_bruts = $stats['total_gains'] ?? 0;

// Calcul des commissions du site (5%)
$total_commissions = $total_gains_bruts * $commission_pourcentage;

// Soustraction de l'abonnement mensuel fixe (1500 FCFA) pour obtenir le revenu net
$net_a_percevoir = $total_gains_bruts - $total_commissions - $abonnement_mensuel;
if ($net_a_percevoir < 0) {
    $net_a_percevoir = 0; // Empêche un solde négatif au début
}

// Récupération des transactions
$rdv_stmt = $pdo->prepare("SELECT r.*, p.nom_style, p.prix, u.nom AS client_nom, u.prenom AS client_prenom 
                           FROM rendezvous r 
                           JOIN prestations p ON r.id_prestation = p.id_prestation 
                           JOIN users u ON r.id_client = u.id 
                           WHERE r.id_coiffeur = ? AND r.statut = 'termine' 
                           ORDER BY r.date_rdv DESC");
$rdv_stmt->execute([$id_coiffeur]);
$historique_gains = $rdv_stmt->fetchAll();
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">MON PORTEFEUILLE</h2>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-4 shadow-lg text-center h-100" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <i class="bi bi-wallet2 text-warning fs-1 mb-2"></i>
                    <h5 class="text-secondary small text-uppercase">Total de mes gains</h5>
                    <h3 class="text-success fw-bold mt-2"><?php echo number_format($total_gains_bruts, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-4 shadow-lg text-center h-100" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <i class="bi bi-percent text-warning fs-1 mb-2"></i>
                    <h5 class="text-secondary small text-uppercase">Frais & Commissions (5%)</h5>
                    <h3 class="text-danger fw-bold mt-2"><?php echo number_format($total_commissions, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-4 shadow-lg text-center h-100" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <i class="bi bi-cash-stack text-success fs-1 mb-2"></i>
                    <h5 class="text-secondary small text-uppercase">Gain net perçu</h5>
                    <h3 class="text-warning fw-bold mt-2"><?php echo number_format($net_a_percevoir, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>
        </div>

        <div class="alert alert-warning mb-5 p-4" style="background-color: #2c2512; border: 1px solid var(--gold); color: var(--gold);">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Statut de l'abonnement mensuel :</strong> Votre abonnement de <strong><?php echo $abonnement_mensuel; ?> FCFA</strong> est déduit automatiquement de vos gains nets mensuels afin de rester visible sur la plateforme.
        </div>

        <h4 class="text-warning mb-3">Historique des transactions</h4>
        <div class="table-responsive">
            <table class="table table-dark table-bordered border-warning align-middle">
                <thead>
                    <tr class="text-warning">
                        <th>Date du RDV</th>
                        <th>Client</th>
                        <th>Prestation</th>
                        <th>Montant Brut</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historique_gains)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">
                                Aucune transaction enregistrée pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historique_gains as $g): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($g['date_rdv']))); ?></td>
                                <td><?php echo htmlspecialchars(strtoupper($g['client_nom'] . ' ' . $g['client_prenom'])); ?></td>
                                <td><?php echo htmlspecialchars($g['nom_style']); ?></td>
                                <td class="text-success fw-bold"><?php echo number_format($g['prix'], 0, ',', ' '); ?> FCFA</td>
                                <td><span class="badge bg-success">Payé / Terminé</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-5">
            <button class="btn btn-gold px-5 py-2 fw-bold" onclick="alert('Demande de virement envoyée à l\'administration.')">
                <i class="bi bi-bank"></i> Demander un virement
            </button>
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

<?php include __DIR__ . '/../layout/footer.php'; ?>