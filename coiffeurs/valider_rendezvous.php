<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// SÉCURITÉ : Seul un coiffeur connecté peut gérer cette page
if ($role_utilisateur !== 'coiffeur') {
    header("Location: index.php");
    exit();
}

$coiffeur_id = $_SESSION['id_user'];
$message_action = "";

// =================================================================
// 1. LE ROBOT AUTOMATIQUE : TRAITEMENT DES EXPIRATIONS (TIMEOUT)
// =================================================================
// On récupère tous les RDV "en attente" pour vérifier s'ils ont expiré
$stmt_verif_expiration = $pdo->prepare("
    SELECT r.*, p.prix, p.id_coiffeur, u.ville 
    FROM rendez_vous r
    JOIN prestations p ON r.id = p.id_prestation
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
            $up_rdv = $pdo->prepare("UPDATE rendezvous SET statut = 'expire' WHERE id_rendezvous = ?");
            $up_rdv->execute([$rdv['id_rendezvous']]);

            // B. REMBOURSEMENT DU CLIENT (On dégèle son argent)
            if ($rdv['paiement_statut'] === 'gele') {
                // On remet l'argent dans son solde et on le retire de l'argent gelé
                $up_wallet = $pdo->prepare("UPDATE portefeuilles SET solde = solde + ?, argent_gele = argent_gele - ? WHERE user_id = ?");
                $up_wallet->execute([$rdv['prix'], $rdv['prix'], $rdv['id_client']]);

                // On écrit la ligne dans l'historique sécurisé
                $ins_transac = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'deblocage_rdv', ?, ?)");
                $ins_transac->execute([$rdv['id_client'], $rdv['prix'], "Remboursement automatique : Le coiffeur n'a pas répondu à temps (RDV #" . $rdv['id_rendezvous'] . ")"]);
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
    $verif = $pdo->prepare("SELECT r.*, p.prix FROM rendezvous r JOIN prestations p ON r.id_prestation = p.id_prestation WHERE r.id_rendezvous = ? AND p.id_coiffeur = ?");
    $verif->execute([$id_rdv, $coiffeur_id]);
    $rdv_valid = $verif->fetch();

    if ($rdv_valid && $rdv_valid['statut'] === 'en_attente') {
        $pdo->beginTransaction();
        try {
            if ($action === 'accepter') {
                // A. Le RDV est validé
                $stmt = $pdo->prepare("UPDATE rendezvous SET statut = 'confirme', paiement_statut = 'paye' WHERE id_rendezvous = ?");
                $stmt->execute([$id_rdv]);

                // B. L'argent gelé du client est détruit
                $up_client = $pdo->prepare("UPDATE portefeuilles SET argent_gele = argent_gele - ? WHERE user_id = ?");
                $up_client->execute([$rdv_valid['prix'], $rdv_valid['id_client']]);

                // C. Le portefeuille du coiffeur reçoit l'argent
                // Calcul de la commission de l'admin (Ex: 10%)
                $commission = $rdv_valid['prix'] * 0.10;
                $gain_coiffeur = $rdv_valid['prix'] - $commission;

                // Création/Mise à jour du portefeuille du coiffeur
                $up_coiffeur = $pdo->prepare("INSERT INTO portefeuilles (user_id, solde) VALUES (?, ?) ON DUPLICATE KEY UPDATE solde = solde + ?");
                $up_coiffeur->execute([$coiffeur_id, $gain_coiffeur, $gain_coiffeur]);

                // Historique pour le coiffeur
                $ins_t = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'gain_prestation', ?, ?)");
                $ins_t->execute([$coiffeur_id, $gain_coiffeur, "Gain prestation RDV #" . $id_rdv]);

                $message_action = "<div class='alert alert-success text-center fw-bold mb-4'><i class='bi bi-check-circle-fill'></i> Rendez-vous confirmé ! L'argent a été transféré dans votre portefeuille.</div>";

            } elseif ($action === 'refuser') {
                // A. On annule le RDV
                $stmt = $pdo->prepare("UPDATE rendezvous SET statut = 'annule' WHERE id_rendezvous = ?");
                $stmt->execute([$id_rdv]);

                // B. On rend immédiatement l'argent sur le solde du client
                if ($rdv_valid['paiement_statut'] === 'gele') {
                    $up_wallet = $pdo->prepare("UPDATE portefeuilles SET solde = solde + ?, argent_gele = argent_gele - ? WHERE user_id = ?");
                    $up_wallet->execute([$rdv_valid['prix'], $rdv_valid['prix'], $rdv_valid['id_client']]);

                    $ins_transac = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'deblocage_rdv', ?, ?)");
                    $ins_transac->execute([$rdv_valid['id_client'], $rdv_valid['prix'], "Rendez-vous refusé par le coiffeur (RDV #" . $id_rdv . ")"]);
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
          ORDER BY r.date_rdv DESC, r.heure_debut DESC , r.heure_fin DESC ";
$stmt = $pdo->prepare($query);
$stmt->execute([$coiffeur_id]);
$mes_rendezvous = $stmt->fetchAll();
?>

<div class="container py-5" style="min-height: 80vh;">
    <div class="row mb-4">
        <div class="col">
            <h2 class="text-warning fw-bold"><i class="bi bi-shield-shaded me-2"></i> TABLEAU DES DEMANDES ENTRANTES</h2>
            <p class="text-secondary small">Respectez vos chronos (15min pour le jour même, 1h pour le lendemain) pour éviter l'annulation automatique.</p>
        </div>
    </div>

    <?php echo $message_action; ?>

    <div class="card border-secondary p-4" style="background-color: var(--card-bg); border-radius: 12px;">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle text-center mb-0">
                <thead>
                    <tr class="text-secondary small border-bottom border-secondary">
                        <th>Client</th>
                        <th>Style & Tarif</th>
                        <th>Créneau Demandé</th>
                        <th>Statut Interne</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mes_rendezvous)): ?>
                        <tr>
                            <td colspan="5" class="text-muted py-5">Aucune activité enregistrée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mes_rendezvous as $rdv): ?>
                            <tr class="border-bottom border-dark">
                                <td>
                                    <strong class="text-white d-block"><?php echo htmlspecialchars(strtoupper($rdv['nom_client'])); ?></strong>
                                    <small class="text-secondary"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($rdv['ville_client']); ?></small>
                                </td>
                                <td class="text-warning">
                                    <span class="d-block text-white font-monospace"><?php echo htmlspecialchars($rdv['nom_style']); ?></span>
                                    <strong><?php echo number_format($rdv['prix'], 0, ',', ' '); ?> F</strong>
                                </td>
                                <td>
                                    <span class="badge bg-black border border-secondary text-white"><?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?></span>
                                    <span class="badge bg-warning text-black fw-bold"><?php echo $rdv['heure_rdv']; ?></span>
                                </td>
                                <td>
                                    <?php if ($rdv['statut'] === 'confirme'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-3">Encaissé</span>
                                    <?php elseif ($rdv['statut'] === 'expire'): ?>
                                        <span class="badge bg-secondary-subtle text-muted border border-secondary px-3">Expiré (Chrono dépassé)</span>
                                    <?php elseif ($rdv['statut'] === 'annule'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-3">Annulé</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning px-3 animate-pulse">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($rdv['statut'] === 'en_attente'): ?>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="valider_rendezvous.php?action=accepter&id_rdv=<?php echo $rdv['id_rendezvous']; ?>" class="btn btn-sm btn-success fw-bold px-3">Accepter</a>
                                            <a href="valider_rendezvous.php?action=refuser&id_rdv=<?php echo $rdv['id_rendezvous']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Refuser et rembourser le client ?');">Refuser</a>
                                        </div>
                                    <?php else: ?>
                                        <?php if ($rdv['statut'] === 'expire' || $rdv['statut'] === 'annule'): ?>
                                            <button class="btn btn-sm btn-outline-warning small" type="button" data-bs-toggle="collapse" data-bs-target="#secours_<?php echo $rdv['id_rendezvous']; ?>">
                                                <i class="bi bi-people-fill"></i> Voir alternatives
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">Terminé</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                                
                            <?php if ($rdv['statut'] === 'expire' || $rdv['statut'] === 'annule'): 
                                // On cherche 3 coiffeurs différents de la même ville qui sont actifs (liaison table users via id)
                                $stmt_secours = $pdo->prepare("SELECT id, nom, photo_profil FROM users WHERE role = 'coiffeur' AND ville = ? AND id != ? LIMIT 3");
                                $stmt_secours->execute([$rdv['ville_client'], $coiffeur_id]);
                                $coiffeurs_dispos = $stmt_secours->fetchAll();
                            ?>
                                <tr class="collapse bg-black" id="secours_<?php echo $rdv['id_rendezvous']; ?>">
                                    <td colspan="5" class="p-3 text-start">
                                        <h6 class="text-warning small fw-bold mb-2"><i class="bi bi-info-circle"></i> COIFFEURS DISPONIBLES DANS LA ZONE POUR LE CLIENT :</h6>
                                        <div class="d-flex gap-4">
                                            <?php if(empty($coiffeurs_dispos)): ?>
                                                <span class="text-muted small">Aucun autre coiffeur disponible dans cette localité.</span>
                                            <?php else: ?>
                                                <?php foreach($coiffeurs_dispos as $c): ?>
                                                    <div class="d-flex align-items-center gap-2 border border-secondary p-2 rounded bg-dark">
                                                        <img src="<?php echo $c['photo_profil'] ?? 'uploads/default.png'; ?>" class="rounded-circle" style="width:30px; height:30px; object-fit:cover;">
                                                        <span class="small fw-bold text-white"><?php echo htmlspecialchars($c['nom']); ?></span>
                                                        <span class="badge bg-success small">Disponible</span>
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

<style>
    .animate-pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; } 100% { opacity: 0.5; } }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>