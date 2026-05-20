<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';
include 'header.php';

// =================================================================
// INTERFACE #1 : L'ADMINISTRATEUR
// =================================================================
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    // Calculs de l'activité globale
    $nb_clients = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client'")->fetchColumn();
    $nb_coiffeurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'coiffeur'")->fetchColumn();
    
    // Calculs comptables (Entrées / Sorties)
    $total_entrees = $pdo->query("SELECT SUM(montant) FROM transactions_plateforme WHERE type_mouvement = 'entree'")->fetchColumn() ?? 0;
    $total_sorties = $pdo->query("SELECT SUM(montant) FROM transactions_plateforme WHERE type_mouvement = 'sortie'")->fetchColumn() ?? 0;
    $solde_net = $total_entrees - $total_sorties;

    // Récupération des entités pour affichage
    $utilisateurs = $pdo->query("SELECT * FROM utilisateurs ORDER BY id_user DESC")->fetchAll();
    $commentaires = $pdo->query("SELECT c.*, u.nom FROM commentaires c JOIN utilisateurs u ON c.id_client = u.id_user ORDER BY c.date_creation DESC")->fetchAll();
    $motifs_suppression = $pdo->query("SELECT * FROM suppressions_comptes ORDER BY date_demande DESC")->fetchAll();
?>
    <section class="py-5" style="background-color: #000; min-height: 100vh;">
        <div class="container mt-4">
            <h1 class="text-danger fw-bold mb-4"><i class="bi bi-shield-lock-fill"></i> BACK-OFFICE DE SUPÉRIEURE GÉNÉRALE</h1>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="p-4 rounded bg-dark border-start border-success border-4 shadow">
                        <h6 class="text-secondary uppercase small mb-1">Argent Entré (Abonnements Kkiapay)</h6>
                        <span class="fs-3 fw-bold text-success">+ <?php echo number_format($total_entrees, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded bg-dark border-start border-danger border-4 shadow">
                        <h6 class="text-secondary uppercase small mb-1">Argent Sorti (Charges enregistrées)</h6>
                        <span class="fs-3 fw-bold text-danger">- <?php echo number_format($total_sorties, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 rounded bg-dark border-start border-info border-4 shadow">
                        <h6 class="text-secondary uppercase small mb-1">Bénéfice Net Actuel</h6>
                        <span class="fs-3 fw-bold text-info"><?php echo number_format($solde_net, 0, ',', ' '); ?> FCFA</span>
                    </div>
                </div>
            </div>

            <div class="card bg-dark border-secondary p-4 mb-5">
                <h5 class="text-white mb-3"><i class="bi bi-plus-circle text-danger me-2"></i> Enregistrer un flux sortant (Dépense)</h5>
                <form action="admin_action.php?action=ajouter_depense&id=1" method="POST" class="row g-3">
                    <div class="col-md-4">
                        <input type="number" name="montant" class="form-control bg-black text-white border-secondary" placeholder="Montant en FCFA" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="motif" class="form-control bg-black text-white border-secondary" placeholder="Motif de la dépense (ex: Hébergement serveur, Publicité)" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-danger w-100 fw-bold">Valider</button>
                    </div>
                </form>
            </div>

            <div class="card bg-dark border-secondary p-4 mb-5">
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
                                    <td><img src="<?php echo htmlspecialchars($u['photo_profil']); ?>" class="rounded-circle" style="width:35px; height:35px; object-fit:cover;"></td>
                                    <td class="fw-bold text-white"><?php echo htmlspecialchars($u['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['ville'] ?? 'Non spécifiée'); ?></td>
                                    <td><span class="badge <?php echo $u['role'] === 'coiffeur' ? 'bg-warning text-dark' : ($u['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'); ?>"><?php echo strtoupper($u['role']); ?></span></td>
                                    <td>
                                        <?php if ($u['role'] === 'coiffeur'): ?>
                                            <span class="badge <?php echo $u['abonnement_actif'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $u['abonnement_actif'] ? 'Actif jusqu\'au '.$u['date_expiration_abo'] : 'Inactif'; ?>
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
                            <?php else: ?>
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

            <div class="card bg-dark border-secondary p-4">
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
<?php
    include 'footer.php';
    exit();
}

// =================================================================
// INTERFACE #2 : LE COIFFEUR (AVEC GESTION DE L'ABONNEMENT KKIAPAY)
// =================================================================
if (isset($_SESSION['role']) && $_SESSION['role'] === 'coiffeur') {
    $coiffeur_id = $_SESSION['id_user'];
    
    // Récupération des informations fraîches du coiffeur depuis la base de données
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
                            <span class="fs-2 fw-bold text-success">5 000 FCFA / mois</span>
                        </div>
                        
                        <script src="https://cdn.kkiapay.me/k.js"></script>
                        <kkiapay-widget 
                            amount="5000" 
                            key="VOTRE_CLE_PUBLIQUE_KKIAPAY_ICI" 
                            position="center" 
                            sandbox="true" 
                            data="<?php echo $coiffeur_id; ?>"
                            callback="http://localhost/coiffe_chez_toi/kkiapay_webhook.php">
                        </kkiapay-widget>
                    </div>
                </div>
            <?php else: ?>
                <div class="row mb-5 align-items-center">
                    <div class="col-md-8">
                        <h1 class="text-warning fw-bold">SALON DE : <?php echo strtoupper(htmlspecialchars($db_coiffeur['nom'])); ?></h1>
                        <p class="text-success mb-0 small"><i class="bi bi-check-circle-fill"></i> Votre abonnement est actif (Expire le : <?php echo $db_coiffeur['date_expiration_abo']; ?>)</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <a href="gestion_catalogue.php" class="btn btn-warning fw-bold px-4"><i class="bi bi-plus-circle-fill me-2"></i> AJOUTER UN STYLE</a>
                    </div>
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <a href="agenda_coiffeurs.php" class="card bg-dark text-white p-3 text-decoration-none border-secondary">
                            <h5 class="text-warning"><i class="bi bi-calendar3 me-2"></i> Configurer mon agenda</h5>
                            <span class="text-secondary small">Définissez vos horaires d'intervention chez les clients.</span>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="valider_rendezvous.php" class="card bg-dark text-white p-3 text-decoration-none border-secondary">
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
    include 'footer.php';
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
            <p class="text-light fs-5 mb-5 mx-auto" style="max-width: 650px;">Ravi de vous revoir, <strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong> ! Prêt à réserver une coiffure à <?php echo htmlspecialchars($_SESSION['ville']); ?> ?</p>
            <a href="catalogue_ville.php" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow-lg">TROUVER UN COIFFEUR DANS MA VILLE</a>
        <?php else: ?>
            <p class="text-light fs-5 mb-5 mx-auto" style="max-width: 650px;">Découvrez les meilleurs talents de la coiffure à domicile près de chez vous.</p>
            <a href="connexion.php" class="btn btn-outline-warning btn-lg px-5 py-3 fw-bold">SE CONNECTER POUR RÉSERVER</a>
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
<?php include 'footer.php'; ?>