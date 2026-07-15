<?php
// Fichier : coiffons/coiffeurs/valider_rendezvous.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

// SÉCURITÉ : Seul un coiffeur connecté peut gérer cette page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coiffeur') {
    header("Location: /coiffons/index.php?page=login");
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

include __DIR__ . '/../layout/header_coiffeur.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--gold);margin-bottom:2px;">
            <i class="bi bi-calendar-check me-2"></i>Mes Rendez-vous
        </h2>
        <p style="color:rgba(255,255,255,0.35);font-size:0.78rem;margin:0;">Gérez vos demandes et confirmations</p>
    </div>
    <a href="/coiffons/index.php?page=dashboard_coiffeur"
       style="color:rgba(255,255,255,0.35);text-decoration:none;font-size:0.8rem;display:flex;align-items:center;gap:5px;">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (!empty($message_action)):
    $is_success = str_contains($message_action, 'success') || str_contains($message_action, 'validé');
?>
    <div class="<?= $is_success ? 'msg-success' : 'msg-danger' ?> mb-4">
        <?= strip_tags($message_action, '<strong>') ?>
    </div>
<?php endif; ?>

<div class="glass-cct" style="overflow:hidden;">
    <div class="table-responsive">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.06);">
                    <th style="padding:12px 16px;color:rgba(255,255,255,0.35);font-size:0.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Client</th>
                    <th style="padding:12px 16px;color:rgba(255,255,255,0.35);font-size:0.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;">Prestation</th>
                    <th style="padding:12px 16px;color:rgba(255,255,255,0.35);font-size:0.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;text-align:center;">Date & Heure</th>
                    <th style="padding:12px 16px;color:rgba(255,255,255,0.35);font-size:0.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;text-align:center;">Statut</th>
                    <th style="padding:12px 16px;color:rgba(255,255,255,0.35);font-size:0.7rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mes_rendezvous)): ?>
                    <tr>
                        <td colspan="5" style="padding:3rem;text-align:center;color:rgba(255,255,255,0.2);font-size:0.85rem;">
                            <i class="bi bi-calendar-x" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                            Aucun rendez-vous enregistré
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mes_rendezvous as $rdv):
                        $badge_style = match($rdv['statut_rdv']) {
                            'confirme','accepte' => 'background:rgba(25,135,84,0.2);color:#6ee7b7;border:1px solid rgba(25,135,84,0.3)',
                            'annule','expire'    => 'background:rgba(220,53,69,0.15);color:#ffb3b3;border:1px solid rgba(220,53,69,0.25)',
                            'termine'            => 'background:rgba(99,102,241,0.15);color:#a5b4fc;border:1px solid rgba(99,102,241,0.25)',
                            default              => 'background:rgba(255,193,7,0.15);color:#ffd60a;border:1px solid rgba(255,193,7,0.25)',
                        };
                        $badge_label = match($rdv['statut_rdv']) {
                            'confirme','accepte' => 'Confirmé',
                            'annule'             => 'Annulé',
                            'expire'             => 'Expiré',
                            'termine'            => 'Terminé',
                            default              => 'En attente',
                        };
                    ?>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.04);transition:background 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <td style="padding:14px 16px;">
                            <div style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars(strtoupper($rdv['nom_client'])) ?></div>
                            <div style="color:rgba(255,255,255,0.35);font-size:0.72rem;">
                                <i class="bi bi-geo-alt" style="color:var(--gold)"></i>
                                <?= htmlspecialchars($rdv['nom_ville']) ?>
                            </div>
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="font-size:0.85rem;color:rgba(255,255,255,0.8);"><?= htmlspecialchars($rdv['nom_style']) ?></div>
                            <div style="color:var(--gold);font-weight:700;font-size:0.78rem;"><?= number_format($rdv['prix'], 0, ',', ' ') ?> FCFA</div>
                        </td>
                        <td style="padding:14px 16px;text-align:center;">
                            <div style="font-size:0.82rem;color:#fff;"><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></div>
                            <span style="background:var(--gold-dim);color:var(--gold);font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:20px;"><?= substr($rdv['heure_debut'], 0, 5) ?></span>
                        </td>
                        <td style="padding:14px 16px;text-align:center;">
                            <span style="<?= $badge_style ?>;font-size:0.68rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                                <?= $badge_label ?>
                            </span>
                        </td>
                        <td style="padding:14px 16px;text-align:right;">
                            <?php if ($rdv['statut_rdv'] === 'en_attente'): ?>
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="valider_rendezvous.php?action=accepter&id_rdv=<?= $rdv['id'] ?>"
                                       style="background:rgba(25,135,84,0.2);color:#6ee7b7;border:1px solid rgba(25,135,84,0.3);font-size:0.72rem;font-weight:700;padding:5px 12px;border-radius:20px;text-decoration:none;">
                                        <i class="bi bi-check-lg me-1"></i>Accepter
                                    </a>
                                    <a href="valider_rendezvous.php?action=refuser&id_rdv=<?= $rdv['id'] ?>"
                                       style="background:rgba(220,53,69,0.15);color:#ffb3b3;border:1px solid rgba(220,53,69,0.25);font-size:0.72rem;font-weight:700;padding:5px 12px;border-radius:20px;text-decoration:none;"
                                       onclick="return confirm('Refuser ce rendez-vous ?')">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <span style="color:rgba(255,255,255,0.2);font-size:0.75rem;"><i class="bi bi-check2-all"></i> Traité</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .glass-cct { background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:16px; }
    .msg-success { background:rgba(25,135,84,0.15);border:1px solid rgba(25,135,84,0.3);color:#6ee7b7;border-radius:10px;padding:10px 16px;font-size:0.85rem; }
    .msg-danger  { background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ffb3b3;border-radius:10px;padding:10px 16px;font-size:0.85rem; }
</style>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>

