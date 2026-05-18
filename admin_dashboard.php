<?php
session_start();

// 1. SÉCURITÉ STRICTE : Seul l'admin entre ici
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php");
    exit();
}

// 2. CONNEXION À LA BASE DE DONNÉES
try {
    $bdd = new PDO('mysql:host=localhost;dbname=cft;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// 3. RÉCUPÉRATION DES STATISTIQUES FINANCIÈRES
$stat_commission = $bdd->query("SELECT SUM(commission_prelevee) FROM transactions WHERE type_transaction = 'gain'")->fetchColumn() ?? 0;
$stat_abonnements = $bdd->query("SELECT SUM(montant_paye) FROM abonnements WHERE statut_abonnement = 'actif'")->fetchColumn() ?? 0;
$nb_coiffeurs = $bdd->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur'")->fetchColumn();

// 4. RÉCUPÉRATION DES DEMANDES EN ATTENTE
$abonnements_attente = $bdd->query("SELECT a.*, u.nom, u.prenom, u.telephone FROM abonnements a JOIN users u ON a.id_coiffeur = u.id WHERE a.statut_abonnement = 'en_attente'")->fetchAll();
$retraits_attente = $bdd->query("SELECT t.*, u.nom, u.prenom, u.telephone FROM transactions t JOIN portefeuille p ON t.id_portefeuille = p.id_portefeuille JOIN users u ON p.id_coiffeur = u.id WHERE t.type_transaction = 'retrait' AND t.statut_paiement = 'en_attente'")->fetchAll();
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Espace Admin - Coiffe_Chez_Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --gold: #D4AF37; --dark-bg: #121212; --card-bg: #1e1e1e; }
        body { background-color: var(--dark-bg); color: white; font-family: 'Segoe UI', sans-serif; }
        .card-custom { background-color: var(--card-bg); border: 1px solid #333; border-top: 3px solid var(--gold); }
        .text-gold { color: var(--gold); }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark bg-black border-bottom border-warning">
        <div class="container">
            <span class="navbar-brand fw-bold text-warning"><i class="bi bi-shield-lock"></i> PANNEAU D'ADMINISTRATION</span>
            <a href="deconnexion.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-power"></i> Déconnexion</a>
        </div>
    </nav>

    <div class="container my-5">
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show bg-success text-white border-0" role="alert">
                <strong>Succès !</strong> 
                <?php 
                    if($_GET['success'] == 'abonnement_valide') echo "L'abonnement du coiffeur a été activé avec succès.";
                    if($_GET['success'] == 'retrait_valide') echo "Le retrait a été marqué comme transféré et le solde mis à jour.";
                ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h2 class="mb-4">Vue d'ensemble de l'activité</h2>

        <div class="row g-3 mb-5">
            <div class="col-md-4">
                <div class="card card-custom p-4 text-center">
                    <h6 class="text-secondary text-uppercase">Commissions perçues (5%)</h6>
                    <h3 class="text-gold fw-bold"><?php echo number_format($stat_commission, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-4 text-center">
                    <h6 class="text-secondary text-uppercase">Revenus Abonnements</h6>
                    <h3 class="text-gold fw-bold"><?php echo number_format($stat_abonnements, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-custom p-4 text-center">
                    <h6 class="text-secondary text-uppercase">Coiffeurs Partenaires</h6>
                    <h3 class="text-white fw-bold"><?php echo $nb_coiffeurs; ?></h3>
                </div>
            </div>
        </div>

        <div class="card card-custom p-4 mb-5">
            <h4 class="text-warning mb-3"><i class="bi bi-card-checklist"></i> Abonnements en attente de validation (1 500 FCFA)</h4>
            <?php if (empty($abonnements_attente)): ?>
                <p class="text-muted mb-0">Aucun paiement d'abonnement en attente de vérification.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-striped align-middle">
                        <thead>
                            <tr>:
                                <th>Coiffeur</th>
                                <th>Numéro de Téléphone</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($abonnements_attente as $ab): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ab['nom'] . ' ' . $ab['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($ab['telephone']); ?></td>
                                    <td>
                                        <a href="admin_actions.php?action=valider_abonnement&id=<?php echo $ab['id_abonnement']; ?>" class="btn btn-success btn-sm fw-bold">
                                            <i class="bi bi-check-circle"></i> Confirmer la réception des 1500F
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card card-custom p-4">
            <h4 class="text-warning mb-3"><i class="bi bi-cash-coin"></i> Demandes de retrait coiffeurs (Mobile Money)</h4>
            <?php if (empty($retraits_attente)): ?>
                <p class="text-muted mb-0">Aucune demande de retrait de fonds en attente.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Coiffeur</th>
                                <th>Téléphone de destination</th>
                                <th>Montant Net à envoyer</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($retraits_attente as $ret): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ret['nom'] . ' ' . $ret['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($ret['telephone']); ?></td>
                                    <td class="text-gold fw-bold"><?php echo number_format(abs($ret['montant_net']), 0, ',', ' '); ?> FCFA</td>
                                    <td>
                                        <a href="admin_actions.php?action=valider_retrait&id=<?php echo $ret['id_transaction']; ?>" class="btn btn-warning btn-sm fw-bold text-dark">
                                            <i class="bi bi-send-check"></i> J'ai fait le transfert (Valider)
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>