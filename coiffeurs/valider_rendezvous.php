<?php
// Fichier : coiffons/coiffeurs/valider_rendezvous.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

// SÉCURITÉ : Seul un coiffeur connecté peut gérer cette page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coiffeur') {
    header("Location: /coiffons/index.php");
    exit();
}

$coiffeur_id = $_SESSION['id_user'];
$message_action = "";

// =================================================================
// 1. LE ROBOT AUTOMATIQUE : TRAITEMENT DES EXPIRATIONS (TIMEOUT)
// =================================================================
$stmt_verif_expiration = $pdo->prepare("
    SELECT r.*, p.prix, p.id_coiffeur, u.ville 
    FROM rendez_vous r
    JOIN prestations p ON r.coiffure_id = p.id_prestation
    JOIN users u ON p.id_coiffeur = u.id
    WHERE r.statut_rdv = 'en_attente'
");
$stmt_verif_expiration->execute();
$rdvs_en_attente = $stmt_verif_expiration->fetchAll();

foreach ($rdvs_en_attente as $rdv) {
    $date_demande = strtotime($rdv['date_demande']);
    $date_rdv = $rdv['date_rdv'];
    $heure_actuelle = time();
    
    // Détermination du délai limite : jour même = 15 min (900s), lendemain ou plus = 1h (3600s)
    $delai_limite = ($date_rdv === date('Y-m-d')) ? 900 : 3600;

    // Si le temps est écoulé !
    if (($heure_actuelle - $date_demande) > $delai_limite) {
        $pdo->beginTransaction();
        try {
            // A. On passe le rendez-vous en statut 'expire'
            $up_rdv = $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'expire' WHERE id = ?");
            $up_rdv->execute([$rdv['id']]);

            // B. REMBOURSEMENT DU CLIENT (On dégèle son argent)
            if ($rdv['statut_paiement'] === 'gele') {
                $up_wallet = $pdo->prepare("UPDATE portefeuilles SET solde = solde + ?, argent_gele = argent_gele - ? WHERE user_id = ?");
                $up_wallet->execute([$rdv['prix'], $rdv['prix'], $rdv['client_id']]);

                // On écrit la ligne dans l'historique sécurisé
                $ins_transac = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'deblocage_rdv', ?, ?)");
                $ins_transac->execute([$rdv['client_id'], $rdv['prix'], "Remboursement automatique : Le coiffeur n'a pas répondu à temps (RDV #" . $rdv['id'] . ")"]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

// =================================================================
// 2. TRAITEMENT DES CLICS MANUELS (ACCEPTER / REFUSER)
// =================================================================
if (isset($_GET['action']) && isset($_GET['id_rdv'])) {
    $action = htmlspecialchars($_GET['action']);
    $id_rdv = intval($_GET['id_rdv']);

    // Vérification que ce RDV appartient bien à ce coiffeur
    $verif = $pdo->prepare("SELECT r.*, p.prix FROM rendez_vous r JOIN prestations p ON r.coiffure_id = p.id_prestation WHERE r.id = ? AND p.id_coiffeur = ?");
    $verif->execute([$id_rdv, $coiffeur_id]);
    $rdv_valid = $verif->fetch();

    if ($rdv_valid && $rdv_valid['statut_rdv'] === 'en_attente') {
        $pdo->beginTransaction();
        try {
            if ($action === 'accepter') {
                // A. Le RDV est validé
                $stmt = $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'confirme', statut_paiement = 'paye' WHERE id = ?");
                $stmt->execute([$id_rdv]);

                // B. L'argent gelé du client est retiré
                $up_client = $pdo->prepare("UPDATE portefeuilles SET argent_gele = argent_gele - ? WHERE user_id = ?");
                $up_client->execute([$rdv_valid['prix'], $rdv_valid['client_id']]);

                // C. Le portefeuille du coiffeur reçoit l'argent (-5% commission)
                $commission = $rdv_valid['prix'] * 0.05; 
                $gain_coiffeur = $rdv_valid['prix'] - $commission;

                $up_coiffeur = $pdo->prepare("INSERT INTO portefeuilles (user_id, solde) VALUES (?, ?) ON DUPLICATE KEY UPDATE solde = solde + ?");
                $up_coiffeur->execute([$coiffeur_id, $gain_coiffeur, $gain_coiffeur]);

                // Historique pour le coiffeur
                $ins_t = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'gain_prestation', ?, ?)");
                $ins_t->execute([$coiffeur_id, $gain_coiffeur, "Gain prestation RDV #" . $id_rdv]);

                $message_action = "<div class='alert alert-success text-center fw-bold mb-4'><i class='bi bi-check-circle-fill'></i> Rendez-vous confirmé ! L'argent a été transféré dans votre portefeuille.</div>";

            } elseif ($action === 'refuser') {
                // A. On annule le RDV
                $stmt = $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'annule' WHERE id = ?");
                $stmt->execute([$id_rdv]);

                // B. On rend immédiatement l'argent sur le solde du client
                if ($rdv_valid['statut_paiement'] === 'gele') {
                    $up_wallet = $pdo->prepare("UPDATE portefeuilles SET solde = solde + ?, argent_gele = argent_gele - ? WHERE user_id = ?");
                    $up_wallet->execute([$rdv_valid['prix'], $rdv_valid['prix'], $rdv_valid['client_id']]);

                    $ins_transac = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'deblocage_rdv', ?, ?)");
                    $ins_transac->execute([$rdv_valid['client_id'], $rdv_valid['prix'], "Rendez-vous refusé par le coiffeur (RDV #" . $id_rdv . ")"]);
                }

                $message_action = "<div class='alert alert-danger text-center fw-bold mb-4'><i class='bi bi-exclamation-triangle-fill'></i> Vous avez refusé le rendez-vous. Le client a été remboursé.</div>";
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message_action = "<div class='alert alert-warning text-center fw-bold mb-4'>Une erreur est survenue lors du traitement.</div>";
        }
    }
}

// =================================================================
// 3. RÉCUPÉRATION DES RENDEZ-VOUS DU COIFFEUR POUR AFFICHAGE
// =================================================================
$query = "SELECT r.*, u.nom AS nom_client, u.telephone, u.ville AS ville_client, p.nom_style, p.prix 
          FROM rendez_vous r
          JOIN users u ON r.client_id = u.id
          JOIN prestations p ON r.coiffure_id = p.id_prestation
          WHERE p.id_coiffeur = ? 
          ORDER BY r.date_rdv DESC, r.heure_debut DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$coiffeur_id]);
$mes_rendezvous = $stmt->fetchAll();

include __DIR__ . '/../layout/header.php';
?>

<section class="py-2 py-sm-5 style-smartwatch-container" style="background-color: #000; min-height: 100vh; color: #fff;">
    <div class="container-fluid px-2 px-sm-4 mt-2">
        
        <div class="row mb-3">
            <div class="col text-center text-md-start">
                <h2 class="text-warning fw-bold h4 mb-1" style="letter-spacing: 0.5px;">
                    <i class="bi bi-shield-shaded me-1"></i> DEMANDES
                </h2>
                <p class="text-secondary small d-none d-sm-block" style="font-size: 0.8rem;">Respectez vos chronos (15min pour le jour même, 1h pour le lendemain) pour éviter l'annulation automatique.</p>
            </div>
        </div>

        <?php echo $message_action; ?>

        <div class="card p-2 p-sm-4 shadow-lg border-0 border-sm animate-fade-in" style="background-color: #111; border-radius: 8px;">
            <div class="table-responsive style-custom-scrollbar">
                <table class="table table-dark table-hover align-middle text-center mb-0 border-secondary style-table-compact">
                    <thead>
                        <tr class="text-secondary border-bottom border-secondary" style="font-size: 0.75rem;">
                            <th class="py-2 px-1">Client</th>
                            <th class="py-2 px-1">Style</th>
                            <th class="py-2 px-1">Créneau</th>
                            <th class="py-3 px-1 d-none d-xs-table-cell">Statut</th>
                            <th class="py-2 px-1">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mes_rendezvous)): ?>
                            <tr>
                                <td colspan="5" class="text-muted py-4 small">
                                    <i class="bi bi-calendar-x d-block fs-4 mb-1 text-secondary"></i>
                                    Aucune activité.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mes_rendezvous as $rdv): ?>
                                <tr class="border-bottom border-dark" style="font-size: 0.8rem;">
                                    <td class="py-2 px-1 text-start text-xs-center">
                                        <strong class="text-white d-block text-truncate style-max-width-name"><?php echo htmlspecialchars(strtoupper($rdv['nom_client'])); ?></strong>
                                        <small class="text-secondary text-truncate d-block" style="font-size: 0.7rem;"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($rdv['ville_client']); ?></small>
                                    </td>
                                    
                                    <td class="text-warning py-2 px-1">
                                        <span class="d-block text-white text-truncate style-max-width-style" style="font-size: 0.75rem;"><?php echo htmlspecialchars($rdv['nom_style']); ?></span>
                                        <strong class="text-success" style="font-size: 0.75rem;"><?php echo number_format($rdv['prix'], 0, ',', ' '); ?> F</strong>
                                    </td>
                                    
                                    <td class="py-2 px-1">
                                        <span class="badge bg-black border border-secondary text-white p-1 mb-1 d-block" style="font-size: 0.65rem;"><?php echo date('d/m', strtotime($rdv['date_rdv'])); ?></span>
                                        <span class="badge bg-warning text-black fw-bold p-1 d-block" style="font-size: 0.65rem;"><?php echo substr($rdv['heure_debut'], 0, 5); ?></span>
                                    </td>
                                    
                                    <td class="py-2 px-1 d-none d-xs-table-cell">
                                        <?php if ($rdv['statut_rdv'] === 'confirme'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success style-badge-mini">Encaissé</span>
                                        <?php elseif ($rdv['statut_rdv'] === 'expire'): ?>
                                            <span class="badge bg-secondary-subtle text-muted border border-secondary style-badge-mini">Expiré</span>
                                        <?php elseif ($rdv['statut_rdv'] === 'annule'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger style-badge-mini">Annulé</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning style-badge-mini animate-pulse">Attente</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="py-2 px-1">
                                        <?php if ($rdv['statut_rdv'] === 'en_attente'): ?>
                                            <div class="d-flex flex-column flex-xs-row gap-1 justify-content-center align-items-center">
                                                <a href="valider_rendezvous.php?action=accepter&id_rdv=<?php echo $rdv['id']; ?>" class="btn btn-xs btn-success fw-bold style-btn-responsive" style="padding: 2px 6px; font-size: 0.65rem;">Oui</a>
                                                <a href="valider_rendezvous.php?action=refuser&id_rdv=<?php echo $rdv['id']; ?>" class="btn btn-xs btn-outline-danger style-btn-responsive" style="padding: 2px 6px; font-size: 0.65rem;" onclick="return confirm('Refuser ?');">Non</a>
                                            </div>
                                        <?php else: ?>
                                            <?php if ($rdv['statut_rdv'] === 'expire' || $rdv['statut_rdv'] === 'annule'): ?>
                                                <button class="btn btn-xs btn-outline-warning fw-bold style-btn-responsive" style="padding: 2px 4px; font-size: 0.6rem;" type="button" data-bs-toggle="collapse" data-bs-target="#secours_<?php echo $rdv['id']; ?>">
                                                    Alts
                                                </button>
                                            <?php else: ?>
                                                <span class="badge border border-warning text-warning style-badge-mini" style="background-color: rgba(255, 193, 7, 0.12); font-size: 0.65rem;">
                                                    <i class="bi bi-check2-all"></i> Fini
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                    
                                <?php if ($rdv['statut_rdv'] === 'expire' || $rdv['statut_rdv'] === 'annule'): 
                                    $stmt_secours = $pdo->prepare("SELECT id, nom, photo_profil FROM users WHERE role = 'coiffeur' AND ville = ? AND id != ? LIMIT 3");
                                    $stmt_secours->execute([$rdv['ville_client'], $coiffeur_id]);
                                    $coiffeurs_dispos = $stmt_secours->fetchAll();
                                ?>
                                    <tr class="collapse bg-black" id="secours_<?php echo $rdv['id']; ?>">
                                        <td colspan="5" class="p-2 text-start border-bottom border-warning" style="background-color: #0d0d0d;">
                                            <h6 class="text-warning fw-bold mb-2" style="font-size: 0.7rem;"><i class="bi bi-info-circle me-1"></i> DISPOS ZONE :</h6>
                                            <div class="d-flex flex-wrap gap-1 style-flex-smartwatch">
                                                <?php if(empty($coiffeurs_dispos)): ?>
                                                    <span class="text-muted small italic" style="font-size: 0.65rem;">Aucun autre coiffeur.</span>
                                                <?php else: ?>
                                                    <?php foreach($coiffeurs_dispos as $c): ?>
                                                        <div class="d-flex align-items-center gap-1 border border-secondary p-1 rounded" style="background-color: #1a1a1a; max-width: 100%;">
                                                            <img src="/coiffons/<?php echo $c['photo_profil'] ?? 'uploads/default.png'; ?>" class="rounded-circle" style="width:18px; height:18px; object-fit:cover;">
                                                            <span class="text-white text-truncate" style="font-size: 0.65rem; max-width: 50px;"><?php echo htmlspecialchars($c['nom']); ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<style>
    /* Support écran minuscule & Apple Watch (Moins de 320px) */
    @media (max-width: 320px) {
        .style-smartwatch-container { padding-left: 4px !important; padding-right: 4px !important; }
        .style-table-compact th, .style-table-compact td { padding: 4px 2px !important; }
        .style-max-width-name { max-width: 45px !important; }
        .style-max-width-style { max-width: 40px !important; }
        .style-btn-responsive { width: 100% !important; display: block !important; text-align: center; }
        .style-flex-smartwatch { flex-direction: column !important; }
    }
    
    /* Adaptabilité générale */
    .style-max-width-name { max-width: 80px; display: inline-block; }
    .style-max-width-style { max-width: 75px; display: inline-block; }
    .style-badge-mini { padding: 3px 6px; font-size: 0.65rem; display: inline-block; border-radius: 4px; }
    
    /* Barre de défilement discrète et fine pour l'affichage tactile condensé */
    .style-custom-scrollbar::-webkit-scrollbar { height: 3px; }
    .style-custom-scrollbar::-webkit-scrollbar-thumb { background: #333; border-radius: 10px; }
    
    /* Classes de rendu */
    .animate-pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }
    .table-dark { --bs-table-bg: #111; }
    .table-hover tr:hover { --bs-table-hover-bg: #161616 !important; }
    
    /* Support pour affichages intermédiaires de grilles compactes */
    @media (max-width: 575.98px) {
        .d-xs-table-cell { display: table-cell !important; }
        .flex-xs-row { flex-direction: row !important; }
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>