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
    SELECT r.*, p.prix, p.id_coiffeur
    FROM rendez_vous r
    JOIN prestations p ON r.coiffure_id = p.id_prestation
    WHERE r.statut_rdv = 'en_attente' AND p.id_coiffeur = ?
");
$stmt_verif_expiration->execute([$coiffeur_id]);
$rdvs_en_attente = $stmt_verif_expiration->fetchAll();

foreach ($rdvs_en_attente as $rdv) {
    $date_demande = strtotime($rdv['date_demande']);
    $date_rdv = $rdv['date_rdv'];
    $heure_actuelle = time();
    
    $delai_limite = ($date_rdv === date('Y-m-d')) ? 900 : 3600;

    if (($heure_actuelle - $date_demande) > $delai_limite) {
        $pdo->beginTransaction();
        try {
            $up_rdv = $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'expire' WHERE id = ?");
            $up_rdv->execute([$rdv['id']]);

            if ($rdv['statut_paiement'] === 'gele') {
                $up_wallet = $pdo->prepare("UPDATE portefeuilles SET solde = solde + ?, argent_gele = argent_gele - ? WHERE user_id = ?");
                $up_wallet->execute([$rdv['prix'], $rdv['prix'], $rdv['client_id']]);

                $ins_transac = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'deblocage_rdv', ?, ?)");
                $ins_transac->execute([$rdv['client_id'], $rdv['prix'], "Expiration : RDV #" . $rdv['id']]);
            }

            // Notification Client Expiration
            $msg_notif_client = "La demande de rendez-vous a expiré (sans réponse du coiffeur). Votre solde a été dégelé.";
            $ins_notif_client = $pdo->prepare("INSERT INTO notifications (user_id, message, type, statut) VALUES (?, ?, 'danger', 'non_lu')");
            $ins_notif_client->execute([$rdv['client_id'], $msg_notif_client]);

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

    $verif = $pdo->prepare("SELECT r.*, p.prix FROM rendez_vous r JOIN prestations p ON r.coiffure_id = p.id_prestation WHERE r.id = ? AND p.id_coiffeur = ?");
    $verif->execute([$id_rdv, $coiffeur_id]);
    $rdv_valid = $verif->fetch();

    if ($rdv_valid && $rdv_valid['statut_rdv'] === 'en_attente') {
        $pdo->beginTransaction();
        try {
            if ($action === 'accepter') {
                $stmt = $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'confirme', statut_paiement = 'paye' WHERE id = ?");
                $stmt->execute([$id_rdv]);

                $up_client = $pdo->prepare("UPDATE portefeuilles SET argent_gele = argent_gele - ? WHERE user_id = ?");
                $up_client->execute([$rdv_valid['prix'], $rdv_valid['client_id']]);

                $ins_t_client = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'paiement_confirme', ?, ?)");
                $ins_t_client->execute([$rdv_valid['client_id'], $rdv_valid['prix'], "Paiement validé pour le RDV #" . $id_rdv]);

                $commission = $rdv_valid['prix'] * 0.05; 
                $gain_coiffeur = $rdv_valid['prix'] - $commission;

                $up_coiffeur = $pdo->prepare("INSERT INTO portefeuilles (user_id, solde) VALUES (?, ?) ON DUPLICATE KEY UPDATE solde = solde + ?");
                $up_coiffeur->execute([$coiffeur_id, $gain_coiffeur, $gain_coiffeur]);

                $ins_t = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'gain_prestation', ?, ?)");
                $ins_t->execute([$coiffeur_id, $gain_coiffeur, "Gain prestation RDV #" . $id_rdv]);

                // Notification Client Acceptation
                $msg_notif = "Votre coiffeur a accepté votre rendez-vous !";
                $ins_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, statut) VALUES (?, ?, 'succes', 'non_lu')");
                $ins_notif->execute([$rdv_valid['client_id'], $msg_notif]);

                $message_action = "<div class='alert alert-success text-center fw-bold mb-4'>Rendez-vous validé avec succès !</div>";

            } elseif ($action === 'refuser') {
                $stmt = $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'annule' WHERE id = ?");
                $stmt->execute([$id_rdv]);

                if ($rdv_valid['statut_paiement'] === 'gele') {
                    $up_wallet = $pdo->prepare("UPDATE portefeuilles SET solde = solde + ?, argent_gele = argent_gele - ? WHERE user_id = ?");
                    $up_wallet->execute([$rdv_valid['prix'], $rdv_valid['prix'], $rdv_valid['client_id']]);

                    $ins_transac = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'deblocage_rdv', ?, ?)");
                    $ins_transac->execute([$rdv_valid['client_id'], $rdv_valid['prix'], "Rendez-vous refusé par le coiffeur (RDV #" . $id_rdv . ")"]);
                }

                // Notification Client Refus
                $msg_notif = "Votre coiffeur a refusé votre demande de rendez-vous. Votre solde a été recrédité.";
                $ins_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, statut) VALUES (?, ?, 'danger', 'non_lu')");
                $ins_notif->execute([$rdv_valid['client_id'], $msg_notif]);

                $message_action = "<div class='alert alert-danger text-center fw-bold mb-4'>Rendez-vous refusé. Le client a été remboursé.</div>";
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message_action = "<div class='alert alert-warning text-center fw-bold mb-4'>Une erreur est survenue.</div>";
        }
    }
}

// =================================================================
// 3. RÉCUPÉRATION AVEC JOINTURE POUR LE NOM DE LA VILLE
// =================================================================
$query = "SELECT r.*, u.nom AS nom_client, v.nom_ville, p.nom_style, p.prix 
          FROM rendez_vous r
          JOIN users u ON r.client_id = u.id
          JOIN villes v ON u.ville = v.id
          JOIN prestations p ON r.coiffure_id = p.id_prestation
          WHERE p.id_coiffeur = ? 
          ORDER BY (r.statut_rdv = 'en_attente') DESC, r.date_rdv DESC, r.heure_debut DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$coiffeur_id]);
$mes_rendezvous = $stmt->fetchAll();

include __DIR__ . '/../layout/header.php';
?>

<section class="py-5" style="background-color: #000; min-height: 100vh; color: #fff;">
    <div class="container py-2">
        
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="text-warning fw-bold h3 text-uppercase" style="letter-spacing: 1.5px;">
                    <i class="bi bi-calendar-check me-2"></i>Gestion des Rendez-vous
                </h1>
                <p class="text-muted small mb-0">Visualisez, acceptez ou refusez vos demandes de prestations</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-10 mx-auto">
                
                <?php echo $message_action; ?>

                <div class="card p-3 border-0 shadow-lg" style="background-color: #0c0c0c; border-radius: 12px;">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0" style="--bs-table-bg: #0c0c0c;">
                            <thead>
                                <tr class="text-secondary border-bottom border-secondary text-uppercase small" style="font-size: 0.75rem; letter-spacing: 1px;">
                                    <th class="ps-3 py-3">Client</th>
                                    <th class="py-3">Ville</th>
                                    <th class="py-3">Prestation</th>
                                    <th class="py-3 text-center">Date & Heure</th>
                                    <th class="py-3 text-center">Statut</th>
                                    <th class="pe-3 py-3 text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mes_rendezvous)): ?>
                                    <tr>
                                        <td colspan="6" class="text-muted text-center py-5">
                                            <i class="bi bi-calendar-x d-block fs-3 mb-2 text-secondary"></i>
                                            Aucun rendez-vous enregistré pour le moment.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($mes_rendezvous as $rdv): ?>
                                        <tr class="border-bottom border-dark" style="font-size: 0.85rem;">
                                            
                                            <td class="ps-3 py-3">
                                                <span class="text-white fw-bold"><?php echo htmlspecialchars(strtoupper($rdv['nom_client'])); ?></span>
                                            </td>
                                            
                                            <td class="py-3 text-secondary">
                                                <i class="bi bi-geo-alt text-warning me-1"></i>
                                                <?php echo htmlspecialchars($rdv['nom_ville']); ?>
                                            </td>
                                            
                                            <td class="py-3">
                                                <span class="d-block text-light"><?php echo htmlspecialchars($rdv['nom_style']); ?></span>
                                                <small class="text-success fw-bold"><?php echo number_format($rdv['prix'], 0, ',', ' '); ?> F</small>
                                            </td>
                                            
                                            <td class="py-3 text-center">
                                                <span class="d-block text-white"><?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?></span>
                                                <span class="badge bg-warning text-black fw-bold mt-1" style="font-size: 0.7rem;"><?php echo substr($rdv['heure_debut'], 0, 5); ?></span>
                                            </td>
                                            
                                            <td class="py-3 text-center">
                                                <?php if ($rdv['statut_rdv'] === 'confirme'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">Confirmé</span>
                                                <?php elseif ($rdv['statut_rdv'] === 'expire'): ?>
                                                    <span class="badge bg-secondary-subtle text-muted border border-secondary px-2 py-1">Expiré</span>
                                                <?php elseif ($rdv['statut_rdv'] === 'annule'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">Annulé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1 text-uppercase animate-pulse" style="font-size: 0.7rem;">En attente</span>
                                                <?php endif; ?>
                                            </td>
                                            
                                            <td class="pe-3 py-3 text-end">
                                                <?php if ($rdv['statut_rdv'] === 'en_attente'): ?>
                                                    <div class="d-inline-flex gap-2">
                                                        <a href="valider_rendezvous.php?action=accepter&id_rdv=<?php echo $rdv['id']; ?>" class="btn btn-sm btn-success fw-bold px-3">
                                                            <i class="bi bi-check-lg me-1"></i> Accepter
                                                        </a>
                                                        <a href="valider_rendezvous.php?action=refuser&id_rdv=<?php echo $rdv['id']; ?>" class="btn btn-sm btn-outline-danger px-3" onclick="return confirm('Refuser ce rendez-vous ?');">
                                                            <i class="bi bi-x-lg"></i>
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small"><i class="bi bi-check2-all"></i> Traité</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<style>
    /* Effet d'animation discret pour le badge en attente */
    .animate-pulse { animation: pulse 1.8s infinite; }
    @keyframes pulse { 0% { opacity: 0.7; } 50% { opacity: 1; } 100% { opacity: 0.7; } }
    
    /* Table hover subtile */
    .table-hover tr:hover { --bs-table-hover-bg: #121212 !important; }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>

