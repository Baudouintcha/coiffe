<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclusion des fichiers de configuration
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// Variables d'alertes sécurisées
$alert_message = "";
$alert_type = "";
if (isset($_GET['success'])) {
    $alert_type = "success";
    if ($_GET['success'] === 'depense_ajoutee') $alert_message = "La dépense a été enregistrée avec succès.";
    if ($_GET['success'] === 'user_supprime') $alert_message = "L'utilisateur a été exclu définitivement de la plateforme.";
    if ($_GET['success'] === 'com_supprime') $alert_message = "Le commentaire inapproprié a été supprimé.";
    if ($_GET['success'] === 'log_nettoye') $alert_message = "L'historique de suppression a été mis à jour.";
}

// =================================================================
// INTERFACE #1 : L'ADMINISTRATEUR (VUE ANALYTIQUE ET BI)
// =================================================================
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    
    // 1. Calculs de l'activité globale et comptable
    $nb_clients = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client'")->fetchColumn();
    $nb_coiffeurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'coiffeur'")->fetchColumn();
    
    $total_entrees = $pdo->query("SELECT SUM(montant) FROM transactions_plateforme WHERE type_mouvement = 'entree'")->fetchColumn() ?? 0;
    $total_sorties = $pdo->query("SELECT SUM(montant) FROM transactions_plateforme WHERE type_mouvement = 'sortie'")->fetchColumn() ?? 0;
    $solde_net = $total_entrees - $total_sorties;

    // FONDS SÉQUESTRES : Argent dormant dans le système (Cumul des abonnements actifs ou portefeuilles virtuels si existants)
    // Ici calculé sur la base des entrées Kkiapay nettes de dépenses non encore consommées
    $fonds_sequestres = $solde_net * 0.4; // Ajustement business simulation

    // 2. Récupération des entités pour les tables d'affichage
    $utilisateurs = $pdo->query("SELECT * FROM utilisateurs ORDER BY id_user DESC")->fetchAll();
    $commentaires = $pdo->query("SELECT c.*, u.nom FROM commentaires c JOIN utilisateurs u ON c.id_client = u.id_user ORDER BY c.date_creation DESC")->fetchAll();
    $motifs_suppression = $pdo->query("SELECT * FROM suppressions_comptes ORDER BY date_demande DESC")->fetchAll();

    // 3. EXTRACTION DES DONNÉES POUR LES 4 GRAPHIQUES (ANALYSE TOTALE)
    // Graphique 1 : Sexe des utilisateurs
    $data_sexe = $pdo->query("SELECT sexe, COUNT(*) as total FROM utilisateurs WHERE role IN ('client', 'coiffeur') GROUP BY sexe")->fetchAll(PDO::FETCH_ASSOC);
    
    // Graphique 2 : Top Coiffeurs (Basé sur le nombre de prestations créées dans leur catalogue à défaut de table RDV complète)
    $top_coiffeurs = $pdo->query("SELECT u.nom, COUNT(p.id_prestation) as total_styles FROM prestations p JOIN utilisateurs u ON p.id_coiffeur = u.id_user GROUP BY p.id_coiffeur ORDER BY total_styles DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
    
    // Graphique 3 : Répartition Financement (Entrées vs Sorties)
    // Préparé dynamiquement pour Chart.js

    // Graphique 4 : Villes les plus actives
    $top_villes = $pdo->query("SELECT ville, COUNT(*) as total FROM utilisateurs WHERE ville IS NOT NULL AND ville != '' GROUP BY ville ORDER BY total DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

    // 4. 🚨 LOGIQUE DU CENTRE DE DIAGNOSTIC (SOUCIS ET RENDEMENTS)
    // Coiffeurs en baisse de rendement (Inactifs : Aucun style publié dans leur catalogue)
    $coiffeurs_inactifs = $pdo->query("SELECT nom, email FROM utilisateurs WHERE role = 'coiffeur' AND id_user NOT IN (SELECT DISTINCT id_coiffeur FROM prestations)")->fetchAll();
    
    // Soucis d'attrition : Nombre de personnes parties à cause d'un "bug" ou "mauvaise expérience" dans les motifs de suppression
    $bugs_remontes = $pdo->query("SELECT COUNT(*) FROM suppressions_comptes WHERE raison LIKE '%bug%' OR raison LIKE '%problème%' OR raison LIKE '%cher%'")->fetchColumn() ?? 0;
?>
    <!-- CDN requis insérés proprement pour le tableau de bord admin -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- 🗺️ NAVBAR DE PILOTAGE DE L'ADMINISTRATEUR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-danger sticky-top">
        <div class="container">
            <span class="navbar-brand fw-bold text-danger"><i class="bi bi-shield-lock-fill"></i> S.G. BACK-OFFICE</span>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav mx-auto text-uppercase small gap-3">
                    <li class="nav-item"><a class="nav-link text-white-50" href="#general"><i class="bi bi-speedometer2"></i> Général</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50" href="#graphiques"><i class="bi bi-graph-up-arrow"></i> Analyses & Flux</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50" href="#diagnostics"><i class="bi bi-exclamation-triangle"></i> Diagnostics</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50" href="#comptes"><i class="bi bi-people"></i> Gestion Comptes</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50" href="#moderation"><i class="bi bi-chat-dots"></i> Modération</a></li>
                </ul>
                <span class="navbar-text text-white small me-3">Mode Admin (Actif)</span>
            </div>
        </div>
    </nav>

    <section class="py-5" style="background-color: #000; min-height: 100vh; color: #fff;">
        <div class="container mt-2">
            
            <!-- Affichage des alertes sécurisées -->
            <?php if (!empty($alert_message)): ?>
                <div class="alert alert-success alert-dismissible fade show bg-success text-white border-0 mb-4" role="alert">
                    <i class="bi bi-check2-circle me-2"></i> <?php echo $alert_message; ?>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- ZONE GÉNÉRAL & COMPTEURS -->
            <div id="general" class="mb-5">
                <h1 class="text-danger fw-bold mb-4">BACK-OFFICE DE SUPÉRIEURE GÉNÉRALE</h1>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="p-4 rounded bg-dark border-start border-success border-4 shadow">
                            <h6 class="text-secondary uppercase small mb-1">Argent Entré (Kkiapay)</h6>
                            <span class="fs-4 fw-bold text-success">+ <?php echo number_format($total_entrees, 0, ',', ' '); ?> F</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-4 rounded bg-dark border-start border-danger border-4 shadow">
                            <h6 class="text-secondary uppercase small mb-1">Argent Sorti (Charges)</h6>
                            <span class="fs-4 fw-bold text-danger">- <?php echo number_format($total_sorties, 0, ',', ' '); ?> F</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-4 rounded bg-dark border-start border-info border-4 shadow">
                            <h6 class="text-secondary uppercase small mb-1">Bénéfice Net Actuel</h6>
                            <span class="fs-4 fw-bold text-info"><?php echo number_format($solde_net, 0, ',', ' '); ?> F</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-4 rounded bg-dark border-start border-warning border-4 shadow">
                            <h6 class="text-secondary uppercase small mb-1">Trésorerie Séquestre</h6>
                            <span class="fs-4 fw-bold text-warning"><?php echo number_format($fonds_sequestres, 0, ',', ' '); ?> F</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ENREGISTREMENT FLUX SORTANT -->
            <div class="card bg-dark border-secondary p-4 mb-5">
                <h5 class="text-white mb-3"><i class="bi bi-plus-circle text-danger me-2"></i> Enregistrer un flux sortant (Dépense)</h5>
                <form action="admin_action.php?action=ajouter_depense" method="POST" class="row g-3">
                    <div class="col-md-4">
                        <input type="number" name="montant" class="form-control bg-black text-white border-secondary" placeholder="Montant en FCFA" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="motif" class="form-control bg-black text-white border-secondary" placeholder="Motif de la dépense (ex: Serveur, Pub)" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-danger w-100 fw-bold">Valider</button>
                    </div>
                </form>
            </div>

            <!-- SECTION GRAPHES (BUSINESS INTELLIGENCE) -->
            <div id="graphiques" class="pt-4 mb-5">
                <h4 class="text-danger mb-4"><i class="bi bi-graph-up-arrow"></i> Analyse Graphique des Flux et de l'Activité</h4>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="card bg-dark border-secondary p-3 h-100 text-center">
                            <h6 class="text-secondary small mb-3">Communauté (Sexe)</h6>
                            <div class="d-flex justify-content-center align-items-center" style="height: 180px;">
                                <canvas id="chartSexe"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-dark border-secondary p-3 h-100 text-center">
                            <h6 class="text-secondary small mb-3">Dynamique Financière</h6>
                            <div class="d-flex justify-content-center align-items-center" style="height: 180px;">
                                <canvas id="chartFinance"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-dark border-secondary p-3 h-100 text-center">
                            <h6 class="text-secondary small mb-3">Top Coiffeurs (Catalogues)</h6>
                            <div class="d-flex justify-content-center align-items-center" style="height: 180px;">
                                <canvas id="chartCoiffeurs"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-dark border-secondary p-3 h-100 text-center">
                            <h6 class="text-secondary small mb-3">Zones géographiques (Villes)</h6>
                            <div class="d-flex justify-content-center align-items-center" style="height: 180px;">
                                <canvas id="chartVilles"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION DIAGNOSTIC ET ALERTES DE RENDEMENT -->
            <div id="diagnostics" class="pt-4 mb-5">
                <h4 class="text-warning mb-4"><i class="bi bi-exclamation-triangle-fill"></i> Centre de Diagnostic Opérationnel & Rendement</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card p-3 border border-danger bg-black h-100">
                            <h6 class="text-danger fw-bold"><i class="bi bi-person-x"></i> Alerte Rendement : Coiffeurs sans catalogue (Inactifs)</h6>
                            <?php if(empty($coiffeurs_inactifs)): ?>
                                <p class="text-muted small mt-2 mb-0">Tous vos partenaires coiffeurs ont publié des prestations.</p>
                            <?php else: ?>
                                <ul class="list-unstyled small mt-2 mb-0 text-white-50" style="max-height: 150px; overflow-y: auto;">
                                    <?php foreach($coiffeurs_inactifs as $ci): ?>
                                        <li class="border-bottom border-dark py-1 text-warning">⚠️ <?php echo htmlspecialchars($ci['nom']); ?> (<?php echo htmlspecialchars($ci['email']); ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-3 border border-warning bg-black h-100 text-center d-flex flex-column justify-content-center">
                            <h6 class="text-warning fw-bold mb-2"><i class="bi bi-shield-exclamation"></i> Alertes d'Attrition Critique (Bugs / Prix)</h6>
                            <h2 class="text-white fw-bold"><?php echo $bugs_remontes; ?></h2>
                            <span class="text-muted small">Comptes supprimés liés à une mauvaise expérience technique ou tarifaire</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COMPTES UTILISATEURS -->
            <div id="comptes" class="card bg-dark border-secondary p-4 mb-5 pt-4">
                <h4 class="text-white mb-4"><i class="bi bi-people me-2"></i> Comptes Utilisateurs (Total : <?php echo ($nb_clients + $nb_coiffeurs); ?>)</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle text-center">
                        <thead>
                            <tr class="text-secondary small">
                                <th>Profil</th><th>Nom</th><th>Email</th><th>Ville</th><th>Rôle</th><th>Abonnement (Coiffeur)</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $u): ?>
                                <tr>
                                    <td><img src="<?php echo htmlspecialchars($u['photo_profil'] ?? 'uploads/default.png'); ?>" class="rounded-circle" style="width:35px; height:35px; object-fit:cover;"></td>
                                    <td class="fw-bold text-white"><?php echo htmlspecialchars($u['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['ville'] ?? 'Non spécifiée'); ?></td>
                                    <td><span class="badge <?php echo $u['role'] === 'coiffeur' ? 'bg-warning text-dark' : ($u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'); ?>"><?php echo strtoupper($u['role']); ?></span></td>
                                    <td>
                                        <?php if ($u['role'] === 'coiffeur'): ?>
                                            <span class="badge <?php echo $u['abonnement_actif'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $u['abonnement_actif'] ? 'Actif jusqu\'au '.htmlspecialchars($u['date_expiration_abo']) : 'Inactif'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($u['role'] !== 'admin'): ?>
                                            <a href="admin_action.php?action=supprimer_user&id=<?php echo $u['id_user']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Exclure définitivement ce membre ?');"><i class="bi bi-trash"></i></a>
                                        <?php else: ?>
                                            <span class="text-muted small">Protégé</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- HISTORIQUE DES COMPTES SUPPRIMÉS -->
            <div class="card bg-dark border-secondary p-4 mb-5">
                <h4 class="text-white mb-4"><i class="bi bi-archive me-2"></i> Historique des Comptes Supprimés (Raisons de départ)</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr class="text-secondary small text-center">
                                <th>Utilisateur</th><th>Email</th><th>Ancien Rôle</th><th>Raison / Motif de sa décision</th><th>Date</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($motifs_suppression)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">Aucun compte supprimé enregistré pour le moment.</td></tr>
                            <?php Jess: ?>
                                <?php foreach ($motifs_suppression as $ms): ?>
                                    <tr>
                                        <td class="text-white fw-bold"><?php echo htmlspecialchars($ms['nom_utilisateur']); ?></td>
                                        <td><?php echo htmlspecialchars($ms['email_utilisateur']); ?></td>
                                        <td class="text-center"><span class="badge bg-secondary"><?php echo strtoupper($ms['role_utilisateur']); ?></span></td>
                                        <td class="text-secondary small"><?php echo nl2br(htmlspecialchars($ms['raison'])); ?></td>
                                        <td class="text-center text-muted small"><?php echo date('d/m/Y H:i', strtotime($ms['date_demande'])); ?></td>
                                        <td class="text-center">
                                            <a href="admin_action.php?action=supprimer_log_suppression&id=<?php echo $ms['id_suppression']; ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODÉRATION DES COMMENTAIRES -->
            <div id="moderation" class="card bg-dark border-secondary p-4">
                <h4 class="text-white mb-4"><i class="bi bi-chat-dots me-2"></i> Modération des Commentaires en ligne</h4>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr class="text-secondary small text-center">
                                <th>Client</th><th>Message / Avis</th><th>Note</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commentaires as $com): ?>
                                <tr>
                                    <td class="fw-bold text-warning"><?php echo htmlspecialchars($com['nom']); ?></td>
                                    <td class="text-light small"><?php echo htmlspecialchars($com['message']); ?></td>
                                    <td class="text-center text-warning"><?php echo str_repeat('⭐', $com['note']); ?></td>
                                    <td class="text-center">
                                        <a href="admin_action.php?action=supprimer_commentaire&id=<?php echo $com['id_commentaire']; ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3"></i> Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>

    <!-- CONFIGURATION DES SCRIPTS CHARTS JAVASCRIPT -->
    <script>
        // Chart 1 : Sexe
        new Chart(document.getElementById('chartSexe'), {
            type: 'doughnut',
            data: {
                labels: [<?php foreach($data_sexe as $ds) { echo '"' . strtoupper($ds['sexe']) . '",'; } ?>],
                datasets: [{
                    data: [<?php foreach($data_sexe as $ds) { echo $ds['total'] . ','; } ?>],
                    backgroundColor: ['#dc3545', '#ffc107', '#6c757d']
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Chart 2 : Finance
        new Chart(document.getElementById('chartFinance'), {
            type: 'pie',
            data: {
                labels: ['Entrées', 'Sorties'],
                datasets: [{
                    data: [<?php echo $total_entrees; ?>, <?php echo $total_sorties; ?>],
                    backgroundColor: ['#198754', '#dc3545']
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Chart 3 : Top Coiffeurs
        new Chart(document.getElementById('chartCoiffeurs'), {
            type: 'bar',
            data: {
                labels: [<?php foreach($top_coiffeurs as $tc) { echo '"' . htmlspecialchars($tc['nom']) . '",'; } ?>],
                datasets: [{
                    data: [<?php foreach($top_coiffeurs as $tc) { echo $tc['total_styles'] . ','; } ?>],
                    backgroundColor: '#ffc107'
                }]
            },
            options: { responsive: true, scales: { y: { display: false } }, plugins: { legend: { display: false } } }
        });

        // Chart 4 : Villes
        new Chart(document.getElementById('chartVilles'), {
            type: 'polarArea',
            data: {
                labels: [<?php foreach($top_villes as $tv) { echo '"' . htmlspecialchars($tv['ville']) . '",'; } ?>],
                datasets: [{
                    data: [<?php foreach($top_villes as $tv) { echo $tv['total'] . ','; } ?>],
                    backgroundColor: ['#0dcaf0', '#ffc107', '#fd7e14', '#20c997']
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
<?php
    include __DIR__ . '/../layout/footer.php';
    exit();
}

// =================================================================
// INTERFACE #2 : LE COIFFEUR (AVEC GESTION DE L'ABONNEMENT KKIAPAY)
// =================================================================
if (isset($_SESSION['role']) && $_SESSION['role'] === 'coiffeur') {
    $coiffeur_id = $_SESSION['id_user'];
    
    $chk = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ?");
    $chk->execute([$coiffeur_id]);
    $db_coiffeur = $chk->fetch();

    $stmt = $pdo->prepare("SELECT * FROM prestations WHERE id_coiffeur = ? ORDER BY id_prestation DESC");
    $stmt->execute([$coiffeur_id]);
    $mes_coiffures = $stmt->fetchAll();
?>
    <section class="py-5" style="background-color: #000; min-height: 100vh;">
        <div class="container mt-4">
            
            <?php if (!$db_coiffeur['abonnement_actif']): ?>
                <div class="row justify-content-center py-5">
                    <div class="col-md-6 text-center bg-dark p-5 rounded border border-warning shadow-lg">
                        <i class="bi bi-credit-card-2-back text-warning display-1 mb-4"></i>
                        <h2 class="text-white fw-bold mb-3">Activation de votre Vitrine Pro</h2>
                        <p class="text-secondary mb-4">Pour publier votre catalogue de coiffures et recevoir des réservations de clients de votre ville, veuillez vous acquitter de votre abonnement mensuel.</p>
                        <div class="bg-black p-3 rounded mb-4 border border-secondary">
                            <span class="text-muted d-block small">TARIF UNIQUE MENSUEL</span>
                            <span class="fs-2 fw-bold text-success">1 500 FCFA / mois</span>
                        </div>
                        
                        <script src="https://cdn.kkiapay.me/k.js"></script>
                        <kkiapay-widget 
                            amount="1500" 
                            key="VOTRE_CLE_PUBLIQUE_KKIAPAY_ICI" 
                            position="center" 
                            sandbox="true" 
                            data="<?php echo (int)$coiffeur_id; ?>"
                            callback="http://localhost/coiffe_chez_toi/kkiapay_webhook.php">
                        </kkiapay-widget>
                    </div>
                </div>
            <?php else: ?>
                <div class="row mb-5 align-items-center">
                    <div class="col-md-8">
                        <h1 class="text-warning fw-bold">SALON DE : <?php echo strtoupper(htmlspecialchars($db_coiffeur['nom'])); ?></h1>
                        <p class="text-success mb-0 small"><i class="bi bi-check-circle-fill"></i> Votre abonnement est actif (Expire le : <?php echo htmlspecialchars($db_coiffeur['date_expiration_abo']); ?>)</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="coiffons/coiffeurs/gestion_catalogue.php" class="btn btn-warning fw-bold px-4"><i class="bi bi-plus-circle-fill me-2"></i> AJOUTER UN STYLE</a>
                    </div>
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <a href="coiffons/coiffeurs/agenda_coiffeurs.php" class="card bg-dark text-white p-3 text-decoration-none border-secondary">
                            <h5 class="text-warning"><i class="bi bi-calendar3 me-2"></i> Configurer mon agenda</h5>
                            <span class="text-secondary small">Définissez vos horaires d'intervention chez les clients.</span>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="coiffons/coiffeurs/valider_rendezvous.php" class="card bg-dark text-white p-3 text-decoration-none border-secondary">
                            <h5 class="text-success"><i class="bi bi-check2-circle me-2"></i> Gérer mes Rendez-vous</h5>
                            <span class="text-secondary small">Consultez et validez les demandes clients.</span>
                        </a>
                    </div>
                </div>

                <h3 class="text-white mt-5 mb-3 border-bottom border-secondary pb-2">Mon Catalogue Actuel</h3>
                <div class="row g-4">
                    <?php foreach ($mes_coiffures as $c): ?>
                        <div class="col-md-4">
                            <div class="card bg-dark text-white h-100 border-secondary">
                                <div class="card-body">
                                    <h5 class="text-warning fw-bold"><?php echo htmlspecialchars($c['nom_style']); ?></h5>
                                    <p class="text-success fw-bold"><?php echo number_format($c['prix'], 0, ',', ' '); ?> FCFA</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php
    include __DIR__ . '/../layout/footer.php';
    exit();
}

// =================================================================
// INTERFACE #3 : L'INTERFACE PUBLIQUE ET CLIENTS
// =================================================================
$query_coms = $pdo->query("SELECT c.*, u.nom FROM commentaires c JOIN utilisateurs u ON c.id_client = u.id_user ORDER BY c.date_creation DESC LIMIT 6");
$all_comments = $query_coms->fetchAll();
?>
<section class="py-5 text-center bg-black border-bottom border-secondary" style="min-height: 55vh; display: flex; align-items: center;">
    <div class="container">
        <h1 class="text-warning fw-bold display-4 mb-3">VOTRE COIFFEUR IDÉAL, CHEZ VOUS</h1>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'client'): ?>
            <p class="text-light fs-5 mb-5 mx-auto" style="max-width: 650px;">Ravi de vous revoir, <strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong> ! Prêt à réserver une coiffure à <?php echo htmlspecialchars($_SESSION['ville'] ?? 'votre ville'); ?> ?</p>
            <a href="catalogue_ville.php" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow-lg">TROUVER UN COIFFEUR DANS MA VILLE</a>
        <?php else: ?>
            <p class="text-light fs-5 mb-5 mx-auto" style="max-width: 650px;">Découvrez les meilleurs talents de la coiffure à domicile près de chez vous.</p>
            <a href="coiffons/access/connexion.php" class="btn btn-outline-warning btn-lg px-5 py-3 fw-bold">SE CONNECTER POUR RÉSERVER</a>
        <?php endif; ?>
    </div>
</section>

<section class="py-5 bg-dark text-white">
    <div class="container text-center">
        <h2 class="text-warning mb-5 fw-bold">L'AVIS DE NOS CLIENTS</h2>
        <div id="carouselExample" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($all_comments as $index => $com): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?> py-3">
                        <p class="fs-4 italic">"<?php echo htmlspecialchars($com['message']); ?>"</p>
                        <h5 class="text-warning">- <?php echo htmlspecialchars($com['nom']); ?></h5>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../layout/footer.php'; ?>