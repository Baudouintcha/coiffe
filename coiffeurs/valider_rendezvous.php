<?php
/**
 * coiffeurs/valider_rendezvous.php — Gestion des demandes RDV (accepter/refuser)
 * Migration Design System v2.0 — Parcours Coiffeur — Page 3
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, transactions portefeuille, notifications : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coiffeur') {
    header("Location: /coiffons/index.php?page=login");
    exit();
}

$coiffeur_id    = $_SESSION['id_user'];
$message_type   = '';
$message_text   = '';

// =================================================================
// 1. ROBOT AUTOMATIQUE : TRAITEMENT DES EXPIRATIONS (SQL inchangé)
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
    $date_demande   = strtotime($rdv['date_demande']);
    $date_rdv       = $rdv['date_rdv'];
    $heure_actuelle = time();
    $delai_limite   = ($date_rdv === date('Y-m-d')) ? 900 : 3600;

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
// 2. TRAITEMENT DES CLICS MANUELS (SQL inchangé)
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

                $commission    = $rdv_valid['prix'] * 0.05;
                $gain_coiffeur = $rdv_valid['prix'] - $commission;

                $up_coiffeur = $pdo->prepare("INSERT INTO portefeuilles (user_id, solde) VALUES (?, ?) ON DUPLICATE KEY UPDATE solde = solde + ?");
                $up_coiffeur->execute([$coiffeur_id, $gain_coiffeur, $gain_coiffeur]);

                $ins_t = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'gain_prestation', ?, ?)");
                $ins_t->execute([$coiffeur_id, $gain_coiffeur, "Gain prestation RDV #" . $id_rdv]);

                $msg_notif = "Votre coiffeur a accepté votre rendez-vous !";
                $ins_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, statut) VALUES (?, ?, 'succes', 'non_lu')");
                $ins_notif->execute([$rdv_valid['client_id'], $msg_notif]);

                $message_type = 'success';
                $message_text = 'Rendez-vous validé avec succès !';

            } elseif ($action === 'refuser') {
                $stmt = $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'annule' WHERE id = ?");
                $stmt->execute([$id_rdv]);

                if ($rdv_valid['statut_paiement'] === 'gele') {
                    $up_wallet = $pdo->prepare("UPDATE portefeuilles SET solde = solde + ?, argent_gele = argent_gele - ? WHERE user_id = ?");
                    $up_wallet->execute([$rdv_valid['prix'], $rdv_valid['prix'], $rdv_valid['client_id']]);

                    $ins_transac = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif) VALUES (?, 'deblocage_rdv', ?, ?)");
                    $ins_transac->execute([$rdv_valid['client_id'], $rdv_valid['prix'], "Rendez-vous refusé par le coiffeur (RDV #" . $id_rdv . ")"]);
                }

                $msg_notif = "Votre coiffeur a refusé votre demande de rendez-vous. Votre solde a été recrédité.";
                $ins_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, statut) VALUES (?, ?, 'danger', 'non_lu')");
                $ins_notif->execute([$rdv_valid['client_id'], $msg_notif]);

                $message_type = 'danger';
                $message_text = 'Rendez-vous refusé. Le client a été remboursé.';
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message_type = 'danger';
            $message_text = 'Une erreur est survenue.';
        }
    }
}

// =================================================================
// 3. RÉCUPÉRATION DES RENDEZ-VOUS (SQL inchangé)
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

<!-- Design System v2.0 -->
<link rel="stylesheet" href="/coiffons/css/variables.css">
<link rel="stylesheet" href="/coiffons/css/animations.css">
<link rel="stylesheet" href="/coiffons/css/components.css">

<!-- ActionBar -->
<?php
$ab = [
    'icon'     => 'bi-calendar-check',
    'title'    => 'Mes Rendez-vous',
    'subtitle' => 'Gérez vos demandes et confirmations',
];
include __DIR__ . '/../views/components/action_bar.php';
?>

<!-- Toast message action -->
<?php if (!empty($message_text)):
    $toast_type    = $message_type;
    $toast_message = $message_text;
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<!-- Tableau des rendez-vous -->
<?php if (empty($mes_rendezvous)): ?>
    <?php
    $es = [
        'icon'    => 'bi-calendar-x',
        'message' => 'Aucun rendez-vous enregistré pour le moment.',
    ];
    include __DIR__ . '/../views/components/empty_state.php';
    ?>
<?php else: ?>
    <!-- Desktop : tableau -->
    <div class="glass-cct d-none d-md-block" style="overflow:hidden;">
        <div class="table-responsive">
            <table class="table-dark-cct">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Prestation</th>
                        <th class="text-center">Date &amp; Heure</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mes_rendezvous as $rdv):
                        // Statut badge DS
                        $badge_class = match($rdv['statut_rdv']) {
                            'confirme','accepte' => 'badge-verified',
                            'annule','expire'    => 'badge-danger',
                            'termine'            => 'badge-muted',
                            default              => 'badge-pending',
                        };
                        $badge_label = match($rdv['statut_rdv']) {
                            'confirme','accepte' => 'Confirmé',
                            'annule'             => 'Annulé',
                            'expire'             => 'Expiré',
                            'termine'            => 'Terminé',
                            default              => 'En attente',
                        };
                    ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;font-size:0.85rem;color:var(--text-primary);">
                                    <?= htmlspecialchars(strtoupper($rdv['nom_client'])) ?>
                                </div>
                                <div style="color:var(--text-muted);font-size:0.72rem;">
                                    <i class="bi bi-geo-alt" style="color:var(--gold);" aria-hidden="true"></i>
                                    <?= htmlspecialchars($rdv['nom_ville']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:0.85rem;color:var(--text-secondary);">
                                    <?= htmlspecialchars($rdv['nom_style']) ?>
                                </div>
                                <div style="color:var(--gold);font-weight:700;font-size:0.78rem;">
                                    <?= number_format($rdv['prix'], 0, ',', ' ') ?> FCFA
                                </div>
                            </td>
                            <td class="text-center">
                                <div style="font-size:0.82rem;color:var(--text-primary);">
                                    <?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?>
                                </div>
                                <span class="badge-gold" style="font-size:0.7rem;">
                                    <?= substr($rdv['heure_debut'], 0, 5) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="<?= $badge_class ?>"><?= $badge_label ?></span>
                            </td>
                            <td class="text-end">
                                <?php if ($rdv['statut_rdv'] === 'en_attente'): ?>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="valider_rendezvous.php?action=accepter&id_rdv=<?= $rdv['id'] ?>"
                                           class="btn-outline-gold btn-sm"
                                           aria-label="Accepter le RDV #<?= $rdv['id'] ?>">
                                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Accepter
                                        </a>
                                        <a href="valider_rendezvous.php?action=refuser&id_rdv=<?= $rdv['id'] ?>"
                                           class="btn-danger-cct btn-sm"
                                           onclick="return confirm('Refuser ce rendez-vous ?')"
                                           aria-label="Refuser le RDV #<?= $rdv['id'] ?>">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-disabled);font-size:0.75rem;">
                                        <i class="bi bi-check2-all" aria-hidden="true"></i> Traité
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile : cartes -->
    <div class="d-md-none">
        <?php foreach ($mes_rendezvous as $rdv):
            $badge_class = match($rdv['statut_rdv']) {
                'confirme','accepte' => 'badge-verified',
                'annule','expire'    => 'badge-danger',
                'termine'            => 'badge-muted',
                default              => 'badge-pending',
            };
            $badge_label = match($rdv['statut_rdv']) {
                'confirme','accepte' => 'Confirmé',
                'annule'             => 'Annulé',
                'expire'             => 'Expiré',
                'termine'            => 'Terminé',
                default              => 'En attente',
            };
        ?>
            <div class="glass-cct p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div style="font-weight:700;font-size:0.88rem;color:var(--text-primary);">
                            <?= htmlspecialchars(strtoupper($rdv['nom_client'])) ?>
                        </div>
                        <div style="color:var(--text-muted);font-size:0.72rem;">
                            <i class="bi bi-geo-alt" style="color:var(--gold);" aria-hidden="true"></i>
                            <?= htmlspecialchars($rdv['nom_ville']) ?>
                        </div>
                    </div>
                    <span class="<?= $badge_class ?>"><?= $badge_label ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div style="font-size:0.82rem;color:var(--text-secondary);">
                            <?= htmlspecialchars($rdv['nom_style']) ?>
                        </div>
                        <div style="color:var(--gold);font-weight:700;font-size:0.8rem;">
                            <?= number_format($rdv['prix'], 0, ',', ' ') ?> FCFA
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:0.8rem;color:var(--text-primary);">
                            <?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?>
                        </div>
                        <span class="badge-gold" style="font-size:0.7rem;">
                            <?= substr($rdv['heure_debut'], 0, 5) ?>
                        </span>
                    </div>
                </div>
                <?php if ($rdv['statut_rdv'] === 'en_attente'): ?>
                    <div class="d-flex gap-2">
                        <a href="valider_rendezvous.php?action=accepter&id_rdv=<?= $rdv['id'] ?>"
                           class="btn-outline-gold btn-sm flex-grow-1 text-center"
                           aria-label="Accepter">
                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i>Accepter
                        </a>
                        <a href="valider_rendezvous.php?action=refuser&id_rdv=<?= $rdv['id'] ?>"
                           class="btn-danger-cct btn-sm"
                           onclick="return confirm('Refuser ce rendez-vous ?')"
                           aria-label="Refuser">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
