<?php
/**
 * payment/simuler_abonnement.php — Page de souscription abonnement (simulation)
 * Accessible depuis coiffeurs/portefeuille.php
 * Architecture : simulation maintenant, Kkiapay après soutenance
 *
 * ⚠️ LOGIQUE MÉTIER RÉELLE — SQL via PaymentService : inchangeable
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/PaymentService.php';

// Sécurité : coiffeur uniquement
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

$id_coiffeur  = $_SESSION['id_user'];
$prenom       = htmlspecialchars($_SESSION['prenom'] ?? 'Coiffeur');
$montant_abo  = 1500;

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Traitement POST ──
$resultat = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Action non autorisée (Échec CSRF).');
    }

    $paymentService = new PaymentService($pdo);
    $resultat = $paymentService->activerAbonnement($id_coiffeur, $montant_abo);

    if ($resultat['success']) {
        $_SESSION['flash_success'] = 'Abonnement activé jusqu\'au ' . date('d/m/Y', strtotime($resultat['expiration'])) . ' !';
        header('Location: /coiffons/index.php?page=dashboard_coiffeur');
        exit();
    }
}

// ── Données coiffeur ──
try {
    $stmt = $pdo->prepare("SELECT solde, abonnement_status, date_expiration_abo FROM users WHERE id = ?");
    $stmt->execute([$id_coiffeur]);
    $coiffeur_data = $stmt->fetch();
    $solde         = floatval($coiffeur_data['solde'] ?? 0);
    $abo_actif     = ((int)($coiffeur_data['abonnement_status'] ?? 0) === 1);
    $date_exp      = $coiffeur_data['date_expiration_abo'] ?? null;
} catch (Exception $e) {
    $solde     = 0;
    $abo_actif = false;
    $date_exp  = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Souscrire à l'abonnement — Coiffe Chez Toi</title>

    <!-- Design System v2.0 -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    body {
        background: var(--dark);
        color: var(--text-primary);
        font-family: var(--font-body);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* ── Topbar minimal coiffeur ── */
    .abo-topbar {
        position: sticky; top: 0; z-index: 100;
        display: flex; align-items: center; justify-content: space-between;
        padding: 0 2rem; height: 60px;
        background: rgba(0,0,0,0.92);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(212,175,55,0.12);
    }
    .abo-brand {
        font-family: var(--font-display);
        font-size: 1rem; font-weight: 700;
        color: var(--gold); text-decoration: none;
        letter-spacing: 1px;
    }

    /* ── Carte principale abonnement ── */
    .abo-card {
        background: var(--dark-2);
        border: 1px solid rgba(212,175,55,0.25);
        border-radius: var(--radius-xl);
        overflow: hidden;
        max-width: 480px;
        width: 100%;
        margin: 0 auto;
    }
    .abo-card-header {
        background: linear-gradient(135deg, rgba(212,175,55,0.15) 0%, rgba(212,175,55,0.05) 100%);
        padding: 2rem;
        text-align: center;
        border-bottom: 1px solid rgba(212,175,55,0.15);
    }
    .abo-price {
        font-family: var(--font-display);
        font-size: 3rem;
        font-weight: 900;
        color: var(--gold);
        line-height: 1;
    }
    .abo-price span {
        font-size: 1rem;
        font-weight: 400;
        color: var(--text-muted);
    }
    .abo-card-body { padding: 1.5rem 2rem; }

    /* ── Ligne solde ── */
    .solde-row {
        display: flex; align-items: center; justify-content: space-between;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-bottom: 1.5rem;
    }
    .solde-label { font-size: .78rem; color: var(--text-muted); }
    .solde-value {
        font-size: 1.1rem; font-weight: 800;
        color: <?= $solde >= $montant_abo ? 'var(--gold)' : '#ff6b6b' ?>;
    }

    /* ── Feature list ── */
    .feature-list { list-style: none; padding: 0; margin: 0 0 1.5rem; }
    .feature-list li {
        display: flex; align-items: center; gap: 10px;
        font-size: .84rem; color: var(--text-secondary);
        padding: 7px 0;
        border-bottom: 1px solid rgba(255,255,255,0.04);
    }
    .feature-list li:last-child { border-bottom: none; }
    .feature-list li i { color: var(--gold); font-size: .9rem; }

    /* ── Simulation badge ── */
    .sim-badge {
        background: rgba(255,193,7,0.12);
        border: 1px solid rgba(255,193,7,0.25);
        color: #ffd60a;
        border-radius: var(--radius-md);
        padding: 10px 14px;
        font-size: .75rem;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    </style>
</head>
<body>

<!-- ── Topbar ── -->
<nav class="abo-topbar">
    <a href="/coiffons/index.php?page=dashboard_coiffeur" class="abo-brand">CCT</a>
    <a href="/coiffons/coiffeurs/portefeuille.php"
       style="color:var(--text-muted);text-decoration:none;font-size:.82rem;display:flex;align-items:center;gap:6px;">
        <i class="bi bi-arrow-left"></i> Retour au portefeuille
    </a>
</nav>

<!-- ── Contenu principal ── -->
<main style="flex:1;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;" class="page-transition">
    <div style="width:100%;max-width:480px;">

        <!-- Flash erreur -->
        <?php if ($resultat && !$resultat['success']): ?>
            <div style="background:rgba(220,53,69,0.15);border:1px solid rgba(220,53,69,0.3);color:#ffb3b3;border-radius:var(--radius-md);padding:12px 16px;margin-bottom:1.25rem;font-size:.84rem;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?= htmlspecialchars($resultat['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Carte abonnement -->
        <div class="abo-card">
            <div class="abo-card-header">
                <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:.5rem;">
                    <i class="bi bi-stars" style="color:var(--gold);" aria-hidden="true"></i>
                    Coiffe Chez Toi
                </div>
                <h1 style="font-family:var(--font-display);font-size:1.3rem;font-weight:700;color:var(--text-primary);margin-bottom:1rem;">
                    Abonnement mensuel
                </h1>
                <div class="abo-price">
                    <?= number_format($montant_abo, 0, ',', ' ') ?>
                    <span>FCFA / mois</span>
                </div>
                <?php if ($abo_actif && $date_exp): ?>
                    <div style="margin-top:.75rem;font-size:.75rem;color:var(--gold);">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Actuellement actif jusqu'au <?= date('d/m/Y', strtotime($date_exp)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="abo-card-body">

                <!-- Solde actuel -->
                <div class="solde-row">
                    <span class="solde-label"><i class="bi bi-wallet2 me-1"></i> Votre solde actuel</span>
                    <span class="solde-value"><?= number_format($solde, 0, ',', ' ') ?> FCFA</span>
                </div>

                <!-- Avantages inclus -->
                <ul class="feature-list">
                    <li><i class="bi bi-check-circle-fill"></i> Profil visible dans l'annuaire</li>
                    <li><i class="bi bi-check-circle-fill"></i> Réception de demandes de RDV</li>
                    <li><i class="bi bi-check-circle-fill"></i> Badge "Coiffeur certifié"</li>
                    <li><i class="bi bi-check-circle-fill"></i> Accès aux outils de gestion</li>
                    <li><i class="bi bi-check-circle-fill"></i> Support prioritaire</li>
                </ul>

                <!-- Avertissement mode simulation -->
                <div class="sim-badge">
                    <i class="bi bi-info-circle-fill" style="margin-top:1px;flex-shrink:0;"></i>
                    <div>
                        <strong>Mode simulation</strong> — Aucun paiement réel n'est effectué.
                        L'abonnement sera activé directement pour la démonstration.
                        Le paiement Mobile Money (Kkiapay) sera intégré après la soutenance.
                    </div>
                </div>

                <!-- Formulaire de confirmation -->
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <button type="submit"
                            class="btn-gold w-100"
                            style="font-size:.95rem;padding:14px;"
                            aria-label="Confirmer la souscription à l'abonnement mensuel de <?= number_format($montant_abo, 0, ',', ' ') ?> FCFA">
                        <i class="bi bi-lightning-charge-fill me-2" aria-hidden="true"></i>
                        Confirmer le paiement
                    </button>
                </form>

                <p style="text-align:center;font-size:.72rem;color:var(--text-muted);margin-top:12px;">
                    Renouvellement automatique · Annulable à tout moment dans
                    <a href="/coiffons/coiffeurs/portefeuille.php" style="color:var(--gold);text-decoration:none;">votre portefeuille</a>
                </p>

            </div>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toast de retour session si rechargement -->
<?php if (!empty($_SESSION['flash_success'])): ?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    if (typeof showToast === 'function') {
        showToast(<?= json_encode($_SESSION['flash_success']) ?>, 'success');
    }
});
</script>
<?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

</body>
</html>
