<?php
/**
 * paiement/simuler_abonnement.php — Simulation d'activation d'abonnement coiffeur
 * Phase 6 — Portefeuille & Paiement simulé
 *
 * Cette page simule le flux Kkiapay sans appel API réel.
 * Architecture prête pour remplacement par Kkiapay SDK.
 * ⚠️ SQL / logique métier : via PaymentService uniquement
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/PaymentService.php';

// Sécurité
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

$id_coiffeur = (int)$_SESSION['id_user'];
$prix_abo    = 1500;
$duree_jours = 30;
$result      = null;

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Traitement confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_paiement'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erreur de sécurité CSRF.');
    }

    $payment = new PaymentService($pdo);
    $result  = $payment->activerAbonnement($id_coiffeur, $prix_abo, $duree_jours, 'simulation');

    if ($result['success']) {
        // Notification in-app au coiffeur
        require_once __DIR__ . '/../security/notifications.php';
        notifier($pdo, $id_coiffeur,
            '✅ Abonnement activé jusqu\'au ' . date('d/m/Y', strtotime($result['date_expiration'])) . '. '
            . 'Notre équipe vérifie votre diplôme et activera votre visibilité dans l\'annuaire dans les prochaines heures.',
            'success'
        );

        // Redirection dashboard avec succès
        $_SESSION['flash_success'] = $result['message'];
        header('Location: /coiffons/index.php?page=dashboard_coiffeur');
        exit();
    }
}

// Informations abonnement actuel
$stmt = $pdo->prepare("SELECT abonnement_status, date_expiration_abo, solde FROM users WHERE id = ?");
$stmt->execute([$id_coiffeur]);
$coiffeur = $stmt->fetch();
$solde_actuel       = floatval($coiffeur['solde'] ?? 0);
$abo_actif          = (bool)($coiffeur['abonnement_status'] ?? 0);
$date_exp_actuelle  = $coiffeur['date_expiration_abo'] ?? null;

include __DIR__ . '/../layout/header_coiffeur.php';
?>

<link rel="stylesheet" href="/coiffons/css/variables.css">
<link rel="stylesheet" href="/coiffons/css/animations.css">
<link rel="stylesheet" href="/coiffons/css/components.css">

<!-- ActionBar -->
<?php
$ab = [
    'icon'      => 'bi-credit-card',
    'title'     => 'Activer mon abonnement',
    'subtitle'  => 'Simulation de paiement — Mode démo',
    'back_href' => '/coiffons/coiffeurs/portefeuille.php',
    'back_label'=> 'Retour au portefeuille',
];
include __DIR__ . '/../views/components/action_bar.php';
?>

<!-- Erreur PaymentService -->
<?php if ($result !== null && !$result['success']): ?>
    <?php
    $toast_type    = 'danger';
    $toast_message = $result['message'];
    include __DIR__ . '/../views/components/toast.php';
    ?>
<?php endif; ?>

<!-- Bandeau simulation -->
<div class="glass-cct p-3 mb-4" style="background:rgba(255,193,7,0.06);border-color:rgba(255,193,7,0.2);">
    <i class="bi bi-info-circle" style="color:var(--warning-color);" aria-hidden="true"></i>
    <span style="color:var(--warning-color);font-size:.82rem;font-weight:600;">
        Mode Simulation
    </span>
    <span style="color:var(--text-muted);font-size:.78rem;">
        — Aucun paiement réel n'est effectué. Cette page simule le flux Kkiapay pour la démonstration.
    </span>
</div>

<!-- Récapitulatif abonnement -->
<div class="glass-cct p-4 mb-4">
    <h4 style="font-family:var(--font-display);font-size:1rem;color:var(--gold);margin-bottom:1rem;">
        Récapitulatif de votre commande
    </h4>

    <!-- Ligne abonnement -->
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--glass-border);">
        <div>
            <div style="font-weight:600;font-size:.88rem;color:var(--text-primary);">
                Abonnement mensuel Coiffe Chez Toi
            </div>
            <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">
                Durée : <?= $duree_jours ?> jours · Visibilité dans l'annuaire assurée
            </div>
        </div>
        <span style="font-family:var(--font-display);font-size:1.2rem;font-weight:800;color:var(--gold);">
            <?= number_format($prix_abo, 0, ',', ' ') ?> FCFA
        </span>
    </div>

    <!-- Statut actuel -->
    <div style="padding:12px 0;border-bottom:1px solid var(--glass-border);">
        <span style="font-size:.75rem;color:var(--text-muted);">Statut actuel :</span>
        <?php if ($abo_actif && $date_exp_actuelle): ?>
            <span class="badge-verified" style="margin-left:8px;">Actif jusqu'au <?= date('d/m/Y', strtotime($date_exp_actuelle)) ?></span>
        <?php else: ?>
            <span class="badge-danger" style="margin-left:8px;">Inactif</span>
        <?php endif; ?>
    </div>

    <!-- Total -->
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 0 0;">
        <span style="font-size:.82rem;color:var(--text-muted);">Total à payer</span>
        <span style="font-size:1.4rem;font-weight:800;color:var(--gold);">
            <?= number_format($prix_abo, 0, ',', ' ') ?> FCFA
        </span>
    </div>
</div>

<!-- Méthodes de paiement (simulation) -->
<div class="glass-cct p-4 mb-4">
    <h4 style="font-family:var(--font-display);font-size:.92rem;color:var(--text-secondary);margin-bottom:1rem;">
        Méthode de paiement
    </h4>
    <div style="background:var(--dark-3);border:2px solid var(--gold);border-radius:var(--radius-md);padding:14px 16px;display:flex;align-items:center;gap:12px;">
        <i class="bi bi-phone" style="color:var(--gold);font-size:1.4rem;" aria-hidden="true"></i>
        <div>
            <div style="font-weight:700;font-size:.88rem;color:var(--text-primary);">Mobile Money (MTN, Moov)</div>
            <div style="font-size:.72rem;color:var(--text-muted);">Paiement via Kkiapay — Mode simulation activé</div>
        </div>
        <span class="badge-gold" style="margin-left:auto;">Sélectionné</span>
    </div>
</div>

<!-- Formulaire de confirmation -->
<form method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <div class="glass-cct p-4 mb-4" style="background:rgba(25,135,84,0.06);border-color:rgba(25,135,84,0.2);">
        <div style="display:flex;align-items:flex-start;gap:12px;">
            <i class="bi bi-shield-check" style="color:var(--success-text);font-size:1.3rem;flex-shrink:0;margin-top:2px;" aria-hidden="true"></i>
            <div>
                <div style="font-weight:700;font-size:.85rem;color:var(--success-text);margin-bottom:4px;">
                    Paiement sécurisé
                </div>
                <p style="font-size:.78rem;color:var(--text-muted);margin:0;">
                    En mode simulation, aucune transaction réelle n'est effectuée.
                    Cliquez sur "Confirmer" pour activer votre abonnement et apparaître dans l'annuaire.
                </p>
            </div>
        </div>
    </div>

    <div class="d-flex gap-3">
        <a href="/coiffons/coiffeurs/portefeuille.php" class="btn-ghost" style="padding:13px;flex:1;text-align:center;">
            Annuler
        </a>
        <button type="submit"
                name="confirmer_paiement"
                class="btn-gold"
                style="padding:13px;flex:2;font-size:.92rem;">
            <i class="bi bi-check2-circle me-2" aria-hidden="true"></i>
            Confirmer le paiement — <?= number_format($prix_abo, 0, ',', ' ') ?> FCFA
        </button>
    </div>
</form>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
