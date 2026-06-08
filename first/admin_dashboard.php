<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
// Note : Vérifie si ta table est au singulier (transaction_site) ou pluriel (transactions_site)
$stat_commission = $bdd->query("SELECT SUM(montant_commission) FROM transactions_site WHERE type_transaction = 'gain'")->fetchColumn() ?? 0;
$stat_abonnements = $bdd->query("SELECT SUM(montant) FROM abonnements_paiements WHERE statut = 'actif'")->fetchColumn() ?? 0;
$nb_coiffeurs = $bdd->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur'")->fetchColumn();
$tresorerie_sequestre = $bdd->query("SELECT SUM(solde) FROM users")->fetchColumn() ?? 0;

// 4. DATA POUR LES 3 GRAPHIQUES
// Graphique 1 : Sexe des utilisateurs
$data_sexe = $bdd->query("SELECT sexe, COUNT(*) as total FROM users WHERE role IN ('client', 'coiffeur') GROUP BY sexe")->fetchAll(PDO::FETCH_ASSOC);
// Graphique 2 : Top Coiffeurs
$top_coiffeurs = $bdd->query("SELECT u.nom, u.prenom, COUNT(r.id) as total_rdv FROM rendez_vous r JOIN users u ON r.coiffeur_id = u.id GROUP BY r.coiffeur_id ORDER BY total_rdv DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
// Graphique 3 : Prestations les plus demandées (Correction : On extrait bien nom_style)
$top_prestations = $bdd->query("SELECT p.nom_style, COUNT(r.id) as nb_demandes FROM rendez_vous r JOIN prestations p ON r.coiffure_id = p.id_prestation GROUP BY r.coiffure_id ORDER BY nb_demandes DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

// 5. LOGIQUE DU CENTRE DE DIAGNOSTIC
$coiffeurs_inactifs = $bdd->query("SELECT nom, prenom, telephone FROM users WHERE role = 'coiffeur' AND id NOT IN (SELECT DISTINCT coiffeur_id FROM rendez_vous)")->fetchAll();
$bugs_transactions = $bdd->query("SELECT COUNT(*) FROM transactions_site WHERE type_transaction = 'echoue' OR type_transaction = 'annule'")->fetchColumn() ?? 0;
$clients_sans_rdv = $bdd->query("SELECT COUNT(*) FROM users WHERE role = 'client' AND id NOT IN (SELECT DISTINCT client_id FROM rendez_vous)")->fetchColumn() ?? 0;

// 6. RÉCUPÉRATION DES DEMANDES EN ATTENTE
$abonnements_attente = $bdd->query("SELECT a.*, u.nom, u.prenom, u.telephone FROM abonnements_paiements a JOIN users u ON a.id = u.id WHERE a.statut = 'en_attente'")->fetchAll();
$retraits_attente = $bdd->query("SELECT t.*, u.nom, u.prenom, u.telephone FROM transactions_portefeuille t JOIN users u ON t.user_id = u.id WHERE t.type_transaction = 'retrait'")->fetchAll();
?>

<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Super Admin Dashboard - Coiffe Chez Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --gold: #D4AF37; --dark-bg: #000000; --card-bg: #111111; }
        body { background-color: var(--dark-bg); color: white; font-family: 'Segoe UI', sans-serif; scroll-behavior: smooth; }
        .card-custom { background-color: var(--card-bg); border: 1px solid #222; border-top: 3px solid var(--gold); border-radius: 12px; }
        .text-gold { color: var(--gold); }
        .nav-link { color: #aaa !important; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { color: var(--gold) !important; font-weight: bold; }
        /* Style personnalisé pour les scrollbars des alertes */
        ul::-webkit-scrollbar { width: 6px; }
        ul::-webkit-scrollbar-track { background: #111; }
        ul::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-warning sticky-top">
        <div class="container">
            <span class="navbar-brand fw-bold text-warning"><i class="bi bi-shield-lock-fill"></i> COIFFE_CHEZ_TOI ADMIN</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto text-uppercase small gap-3">
                    <li class="nav-item"><a class="nav-link" href="#general"><i class="bi bi-speedometer2"></i> Général</a></li>
                    <li class="nav-item"><a class="nav-link" href="#graphiques"><i class="bi bi-graph-up-arrow"></i> Flux & Graphes</a></li>
                    <li class="nav-item"><a class="nav-link" href="#diagnostics"><i class="bi bi-exclamation-triangle"></i> Diagnostics</a></li>
                    <li class="nav-item"><a class="nav-link" href="#validations"><i class="bi bi-cash-stack"></i> Validations</a></li>
                </ul>
                <a href="../deconnexion.php" class="btn btn-outline-danger btn-sm fw-bold"><i class="bi bi-power"></i> Quitter</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show bg-success text-white border-0 shadow-lg" role="alert">
                <strong><i class="bi bi-check2-circle"></i> Système mis à jour :</strong> 
                <?php 
                    if ($_GET['success'] === 'abonnement_valide') echo "L'abonnement du partenaire coiffeur a été activé avec succès.";
                    if ($_GET['success'] === 'retrait_valide') echo "Le transfert de fonds Mobile Money a été enregistré comme effectué.";
                ?>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div id="general" class="pt-2">
            <h2 class="mb-4 fw-bold text-uppercase" style="letter-spacing: 1px;">Tableau de Bord Stratégique</h2>
            <div class="row g-3 mb-5">
                <div class="col-md-3">
                    <div class="card card-custom p-3 text-center">
                        <h6 class="text-secondary text-uppercase small fw-bold">Commissions (5%)</h6>
                        <h4 class="text-gold fw-bold m-0"><?php echo number_format($stat_commission, 0, ',', ' '); ?> F</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 text-center">
                        <h6 class="text-secondary text-uppercase small fw-bold">Revenus Abonnements</h6>
                        <h4 class="text-gold fw-bold m-0"><?php echo number_format($stat_abonnements, 0, ',', ' '); ?> F</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 text-center" style="border-top-color: #2ecc71;">
                        <h6 class="text-secondary text-uppercase small fw-bold">Fonds Séquestres (Site)</h6>
                        <h4 class="text-success fw-bold m-0"><?php echo number_format($tresorerie_sequestre, 0, ',', ' '); ?> F</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 text-center">
                        <h6 class="text-secondary text-uppercase small fw-bold">Coiffeurs Connectés</h6>
                        <h4 class="text-white fw-bold m-0"><?php echo $nb_coiffeurs; ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div id="graphiques" class="pt-5 mb-5">
            <h4 class="text-warning mb-4 fw-bold text-uppercase" style="letter-spacing: 1px;"><i class="bi bi-graph-up-arrow"></i> Centre d'Analyse Globale</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-custom p-3 h-100">
                        <h6 class="text-center text-secondary small fw-bold mb-3">Communauté selon le Sexe</h6>
                        <div style="max-height: 220px;" class="d-flex justify-content-center">
                            <canvas id="chartSexe"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-custom p-3 h-100">
                        <h6 class="text-center text-secondary small fw-bold mb-3">Classement Performance Coiffeurs</h6>
                        <canvas id="chartCoiffeurs"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-custom p-3 h-100">
                        <h6 class="text-center text-secondary small fw-bold mb-3">Prestations les plus Demandées</h6>
                        <canvas id="chartPrestations"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div id="diagnostics" class="pt-5 mb-5">
            <h4 class="text-danger mb-4 fw-bold text-uppercase" style="letter-spacing: 1px;"><i class="bi bi-exclamation-triangle"></i> Diagnostic d'Activité & Alertes</h4>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card p-3 border border-secondary h-100" style="background-color: #111; border-radius: 12px;">
                        <h6 class="text-danger fw-bold mb-3"><i class="bi bi-person-x-fill"></i> Alerte Rendement (Coiffeurs Inactifs)</h6>
                        <?php if(empty($coiffeurs_inactifs)): ?>
                            <p class="text-muted small mt-2 mb-0">Tous vos coiffeurs ont une activité enregistrée. Excellent !</p>
                        <?php else: ?>
                            <ul class="list-unstyled small mb-0 text-white-50" style="max-height: 140px; overflow-y: auto;">
                                <?php foreach($coiffeurs_inactifs as $ci): ?>
                                    <li class="border-bottom border-dark py-2">⚠️ <?php echo htmlspecialchars($ci['nom'].' '.$ci['prenom']); ?> (<span class="text-warning"><?php echo htmlspecialchars($ci['telephone']); ?></span>)</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 border border-secondary h-100 text-center d-flex flex-column justify-content-center" style="background-color: #111; border-radius: 12px;">
                        <h6 class="text-warning fw-bold mb-2"><i class="bi bi-bug"></i> Soucis Techniques de Paiement</h6>
                        <h2 class="text-white fw-bold my-2"><?php echo $bugs_transactions; ?></h2>
                        <span class="text-secondary small">Échecs / Annulations constatés</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-3 border border-secondary h-100 text-center d-flex flex-column justify-content-center" style="background-color: #111; border-radius: 12px;">
                        <h6 class="text-info fw-bold mb-2"><i class="bi bi-cart-x"></i> Taux d'Abandon à l'Inscription</h6>
                        <h2 class="text-white fw-bold my-2"><?php echo $clients_sans_rdv; ?></h2>
                        <span class="text-secondary small">Clients inscrits sans aucun rendez-vous</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="validations" class="pt-5">
            <div class="card card-custom p-4 mb-5" style="border-top-color: #2ecc71;">
                <h4 class="text-success mb-3 fw-bold small text-uppercase" style="letter-spacing: 0.5px;"><i class="bi bi-card-checklist"></i> Abonnements en attente de validation (1 500 FCFA)</h4>
                <?php if (empty($abonnements_attente)): ?>
                    <p class="text-muted mb-0 small">Aucun paiement d'abonnement en attente de vérification.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover border-secondary align-middle text-center m-0">
                            <thead>
                                <tr class="border-secondary text-secondary small">
                                    <th>COIFFEUR</th>
                                    <th>NUMÉRO DE TÉLÉPHONE</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($abonnements_attente as $ab): ?>
                                    <tr class="border-secondary">
                                        <td class="fw-bold text-white"><?php echo htmlspecialchars($ab['nom'] . ' ' . $ab['prenom']); ?></td>
                                        <td class="text-warning"><?php echo htmlspecialchars($ab['telephone']); ?></td>
                                        <td>
                                            <a href="admin_actions.php?action=valider_abonnement&id=<?php echo $ab['id_abonnement']; ?>" class="btn btn-success btn-sm fw-bold px-3">
                                                <i class="bi bi-check-circle-fill me-1"></i> Confirmer la réception
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card card-custom p-4" style="border-top-color: #D4AF37;">
                <h4 class="text-warning mb-3 fw-bold small text-uppercase" style="letter-spacing: 0.5px;"><i class="bi bi-cash-coin"></i> Demandes de retrait coiffeurs (Mobile Money)</h4>
                <?php if (empty($retraits_attente)): ?>
                    <p class="text-muted mb-0 small">Aucune demande de retrait de fonds en attente.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover border-secondary align-middle text-center m-0">
                            <thead>
                                <tr class="border-secondary text-secondary small">
                                    <th>COIFFEUR</th>
                                    <th>TÉLÉPHONE DESTINATION</th>
                                    <th>MONTANT NET À ENVOYER</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($retraits_attente as $ret): ?>
                                    <tr class="border-secondary">
                                        <td class="fw-bold text-white"><?php echo htmlspecialchars($ret['nom'] . ' ' . $ret['prenom']); ?></td>
                                        <td class="text-info"><?php echo htmlspecialchars($ret['telephone']); ?></td>
                                        <td class="text-gold fw-bold"><?php echo number_format(abs($ret['montant_net']), 0, ',', ' '); ?> FCFA</td>
                                        <td>
                                            <a href="admin_actions.php?action=valider_retrait&id=<?php echo $ret['id_transaction']; ?>" class="btn btn-warning btn-sm fw-bold text-dark px-3">
                                                <i class="bi bi-send-check-fill me-1"></i> Transfert Effectué
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

    </div>

    <script>
        // Graphique 1 : Sexe
        const ctxSexe = document.getElementById('chartSexe').getContext('2d');
        new Chart(ctxSexe, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach($data_sexe as $d) { echo '"' . strtoupper($d['sexe']) . '",'; } ?>],
                datasets: [{
                    data: [<?php foreach($data_sexe as $d) { echo $d['total'] . ','; } ?>],
                    backgroundColor: ['#D4AF37', '#ffffff', '#444444'],
                    borderWidth: 0
                }]
            },
            options: { plugins: { legend: { labels: { color: 'white' } } } }
        });

        // Graphique 2 : Performance Coiffeurs
        const ctxCoif = document.getElementById('chartCoiffeurs').getContext('2d');
        new Chart(ctxCoif, {
            type: 'bar',
            data: {
                labels: [<?php foreach($top_coiffeurs as $tc) { echo '"' . htmlspecialchars($tc['prenom']) . '",'; } ?>],
                datasets: [{
                    label: 'Rendez-vous',
                    data: [<?php foreach($top_coiffeurs as $tc) { echo $tc['total_rdv'] . ','; } ?>],
                    backgroundColor: '#D4AF37'
                }]
            },
            options: { 
                scales: { 
                    y: { grid: { color: '#222' }, ticks: { color: 'white' } }, 
                    x: { grid: { display: false }, ticks: { color: 'white' } } 
                }, 
                plugins: { legend: { display: false } } 
            }
        });

        // Graphique 3 : Demandes Prestations (Correction de la clé de tableau en nom_style)
        const ctxPrest = document.getElementById('chartPrestations').getContext('2d');
        new Chart(ctxPrest, {
            type: 'line',
            data: {
                labels: [<?php foreach($top_prestations as $tp) { echo '"' . htmlspecialchars($tp['nom_style']) . '",'; } ?>],
                datasets: [{
                    label: 'Volume',
                    data: [<?php foreach($top_prestations as $tp) { echo $tp['nb_demandes'] . ','; } ?>],
                    borderColor: '#2ecc71',
                    backgroundColor: '#2ecc71',
                    tension: 0.3,
                    fill: false
                }]
            },
            options: { 
                scales: { 
                    y: { grid: { color: '#222' }, ticks: { color: 'white' } }, 
                    x: { grid: { display: false }, ticks: { color: 'white' } } 
                }, 
                plugins: { legend: { display: false } } 
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>