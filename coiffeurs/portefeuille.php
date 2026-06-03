<?php
// Fichier : coiffons/coiffeurs/portefeuille.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

// Sécurité : Vérification avec la bonne variable de session 'id_user'
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

$id_coiffeur = $_SESSION['id_user']; // Clé harmonisée avec ton CODE

// Traitement de l'action de changement de statut du renouvellement automatique
if (isset($_GET['action']) && $_GET['action'] === 'toggle_abo' && isset($_GET['status'])) {
    $nouveau_statut = (int)$_GET['status'];
    
    // Mise à jour du choix du coiffeur dans la base de données
    $stmt_update = $pdo->prepare("UPDATE coiffeurs SET renouvellement_auto = ? WHERE id_coiffeur = ?");
    $stmt_update->execute([$nouveau_statut, $id_coiffeur]);
    
    header('Location: portefeuille.php');
    exit();
}

// Récupération des informations spécifiques du coiffeur (statut abonnement et choix renouvellement)
$stmt_coiffeur = $pdo->prepare("SELECT abonnement_status, date_expiration_abo, renouvellement_auto FROM users WHERE id = ?");
$stmt_coiffeur->execute([$id_coiffeur]);
$info_coiffeur = $stmt_coiffeur->fetch();

$date_expiration = $info_coiffeur['date_expiration_abo'] ?? null;
$renouvellement_auto = $info_coiffeur['renouvellement_auto'] ?? 1;

// Vérification si l'abonnement est arrivé à terme (date dépassée ou égale à aujourd'hui)
$abonnement_expire = false;
if ($date_expiration) {
    $abonnement_expire = (strtotime($date_expiration) <= strtotime(date('Y-m-d')));
}

include __DIR__ . '/../layout/header.php';

// 1. Définition des constantes du modèle économique
$abonnement_mensuel = 1500; // 1500 FCFA par mois
$commission_pourcentage = 0.05; // 5% sur chaque RDV

// 2. Récupération des données du portefeuille (Requête adaptée à ta table rendez_vous)
$stmt = $pdo->prepare("SELECT 
                        COUNT(r.id) AS total_rdv, 
                        SUM(p.prix) AS total_gains 
                        FROM rendez_vous r 
                        JOIN prestations p ON r.coiffure_id = p.id_prestation 
                        WHERE r.coiffeur_id = ? AND r.statut_rdv = 'termine'");
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

// 3. Récupération des transactions (Requête historique adaptée également)
$rdv_stmt = $pdo->prepare("SELECT r.*, p.nom_style, p.prix, u.nom AS client_nom, u.prenom AS client_prenom 
                           FROM rendez_vous r 
                           JOIN prestations p ON r.coiffure_id = p.id_prestation 
                           JOIN users u ON r.client_id = u.id 
                           WHERE r.coiffeur_id = ? AND r.statut_rdv = 'termine' 
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

        <?php if ($abonnement_expire): ?>
            <div class="card bg-black border border-danger shadow-lg mb-5" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-1 animate-pulse"></i>
                        <h4 class="text-white fw-bold mt-2 h5">Votre abonnement mensuel est arrivé à terme</h4>
                        <p class="text-muted small">Date limite dépassée : <?php echo $date_expiration ? date('d/m/Y', strtotime($date_expiration)) : '--/--/----'; ?></p>
                    </div>

                    <div class="p-3 rounded mb-3" style="background-color: #1a0505; border: 1px solid #4a1414;">
                        <h6 class="text-danger fw-bold small mb-2"><i class="bi bi-shield-x me-1"></i> CONTRAINTES EN CAS DE NON-RECONDUCTION :</h6>
                        <ul class="text-secondary small mb-0 ps-3" style="font-size: 0.8rem; line-height: 1.6;">
                            <li><strong class="text-white">Perte immédiate de visibilité :</strong> Votre salon n'apparaîtra plus dans les recherches des clients de votre ville.</li>
                            <li><strong class="text-white">Blocage des réservations :</strong> Les clients ne pourront plus planifier de rendez-vous avec vous.</li>
                            <li><strong class="text-white">Catalogue masqué :</strong> Vos coiffures et tarifs ne seront plus accessibles au public.</li>
                        </ul>
                    </div>

                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-sm-6">
                            <a href="portefeuille.php?action=toggle_abo&status=1" class="btn btn-success w-100 py-2 fw-bold text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                <i class="bi bi-check-circle-fill me-1"></i> Recharger & Rester Visible (1500 F)
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 text-center text-sm-end">
                            <a href="portefeuille.php?action=toggle_abo&status=0" class="text-muted small text-decoration-underline d-inline-block py-1">
                                Non merci, désactiver mon profil et assumer les contraintes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mb-5 p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3" style="background-color: #2c2512; border: 1px solid var(--gold); color: var(--gold);">
                <div>
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Statut de l'abonnement mensuel :</strong> Votre abonnement de <strong><?php echo $abonnement_mensuel; ?> FCFA</strong> est déduit automatiquement de vos gains nets mensuels afin de rester visible sur la plateforme.
                </div>
                <div>
                    <?php if ($renouvellement_auto == 1): ?>
                        <a href="portefeuille.php?action=toggle_abo&status=0" class="btn btn-outline-warning btn-sm fw-bold px-3">
                            <i class="bi bi-toggle-on me-1"></i> Désactiver le renouvellement
                        </a>
                    <?php else: ?>
                        <a href="portefeuille.php?action=toggle_abo&status=1" class="btn btn-warning btn-sm fw-bold px-3 text-black">
                            <i class="bi bi-toggle-off me-1"></i> Activer le renouvellement
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

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
    
    .animate-pulse { 
        animation: pulse-danger 1.5s infinite; 
    }
    @keyframes pulse-danger { 
        0% { opacity: 0.6; transform: scale(1); } 
        50% { opacity: 1; transform: scale(1.05); } 
        100% { opacity: 0.6; transform: scale(1); } 
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>