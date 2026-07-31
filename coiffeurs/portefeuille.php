<?php
/**
 * coiffeurs/portefeuille.php — Finance : gains, abonnement, historique
 * Migration Design System v2.0 — Parcours Coiffeur — Page 4
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, CSRF, calculs portefeuille : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

// 1. SÉCURITÉ
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

$id_coiffeur = $_SESSION['id_user'];

// 2. Token CSRF — inchangé
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. TRAITEMENT renouvellement auto — inchangé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_abo') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Action non autorisée (Échec de la vérification de sécurité CSRF).');
    }
    if (isset($_POST['status'])) {
        $nouveau_statut = (int)$_POST['status'];
        $stmt_update = $pdo->prepare("UPDATE users SET renouvellement_auto = ? WHERE id = ?");
        $stmt_update->execute([$nouveau_statut, $id_coiffeur]);
        header('Location: portefeuille.php');
        exit();
    }
}

// 4. RÉCUPÉRATION abonnement — inchangé
$stmt_coiffeur = $pdo->prepare("SELECT abonnement_status, date_expiration_abo, renouvellement_auto FROM users WHERE id = ?");
$stmt_coiffeur->execute([$id_coiffeur]);
$info_coiffeur = $stmt_coiffeur->fetch();

$date_expiration    = $info_coiffeur['date_expiration_abo'] ?? null;
$renouvellement_auto = $info_coiffeur['renouvellement_auto'] ?? 1;

// 5. LOGIQUE ABONNEMENT — inchangée
$abonnement_expire    = false;
$jours_restants       = null;
$alerte_cloche_active = false;

if ($date_expiration) {
    $date_actuelle = new DateTime(date('Y-m-d'));
    $date_limite   = new DateTime($date_expiration);

    if ($date_actuelle >= $date_limite) {
        $abonnement_expire = true;
    } else {
        $interval       = $date_actuelle->diff($date_limite);
        $jours_restants = (int)$interval->format('%r%a');

        if ($jours_restants <= 5 && $jours_restants >= 0) {
            $alerte_cloche_active = true;
            $_SESSION['alerte_abo_cloche'] = "Rappel : Votre abonnement expire dans $jours_restants jour(s). 1 500 FCFA seront déduits de votre solde.";
        } else {
            unset($_SESSION['alerte_abo_cloche']);
        }
    }
} else {
    $abonnement_expire = true;
}

$commission_pourcentage = 0.05;

// 6. CALCUL SOLDE — inchangé
$stmt_solde = $pdo->prepare("SELECT SUM(montant) AS solde_reel FROM transactions_portefeuille WHERE user_id = ?");
$stmt_solde->execute([$id_coiffeur]);
$solde_data       = $stmt_solde->fetch();
$solde_disponible = $solde_data['solde_reel'] ?? 0;

$stmt_brut = $pdo->prepare("SELECT SUM(montant) AS total_brut FROM transactions_portefeuille WHERE user_id = ? AND type_transaction = 'gain'");
$stmt_brut->execute([$id_coiffeur]);
$brut_data         = $stmt_brut->fetch();
$total_gains_bruts = $brut_data['total_brut'] ?? 0;
$total_commissions = $total_gains_bruts * $commission_pourcentage;

// 7. HISTORIQUE — inchangé
$stmt_complet = $pdo->prepare("SELECT * FROM transactions_portefeuille WHERE user_id = ? ORDER BY date_creation DESC");
$stmt_complet->execute([$id_coiffeur]);
$historique_complet = $stmt_complet->fetchAll();
$historique_apercu  = array_slice($historique_complet, 0, 5);

include __DIR__ . '/../layout/header_coiffeur.php';
?>

<!-- Design System v2.0 -->
<link rel="stylesheet" href="/coiffons/css/variables.css">
<link rel="stylesheet" href="/coiffons/css/animations.css">
<link rel="stylesheet" href="/coiffons/css/components.css">

<!-- ActionBar -->
<?php
$ab = [
    'icon'     => 'bi-wallet2',
    'title'    => 'Mon Portefeuille',
    'subtitle' => 'Gains, abonnement et historique financier',
];
include __DIR__ . '/../views/components/action_bar.php';
?>

<!-- ── Stats financières ── -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <?php
        $stat = [
            'icon'    => 'bi-graph-up',
            'label'   => 'Gains bruts',
            'value'   => number_format($total_gains_bruts, 0, ',', ' '),
            'unit'    => 'FCFA',
            'variant' => 'gold',
        ];
        include __DIR__ . '/../views/components/statistic_card.php';
        ?>
    </div>
    <div class="col-4">
        <?php
        $stat = [
            'icon'    => 'bi-percent',
            'label'   => 'Commissions 5%',
            'value'   => number_format($total_commissions, 0, ',', ' '),
            'unit'    => 'FCFA',
            'variant' => 'danger',
        ];
        include __DIR__ . '/../views/components/statistic_card.php';
        ?>
    </div>
    <div class="col-4">
        <?php
        $stat = [
            'icon'    => 'bi-cash-stack',
            'label'   => 'Solde disponible',
            'value'   => number_format($solde_disponible, 0, ',', ' '),
            'unit'    => 'FCFA',
            'variant' => 'gold',
        ];
        include __DIR__ . '/../views/components/statistic_card.php';
        ?>
    </div>
</div>

<!-- ── Bloc abonnement ── -->
<?php if ($abonnement_expire):
    // Abonnement expiré ou absent
    $titre_abo = $date_expiration === null ? 'Activation requise' : 'Abonnement expiré';
    $texte_abo = 'Votre profil n\'est plus visible dans l\'annuaire.' .
                 ($date_expiration ? ' Expiré le ' . date('d/m/Y', strtotime($date_expiration)) . '.' : '');
    $ib = [
        'variant' => 'danger',
        'icon'    => 'bi-exclamation-triangle-fill',
        'title'   => $titre_abo,
        'text'    => $texte_abo,
        'cta'     => [
            'type'        => 'submit',
            'label'       => 'Réactiver l\'abonnement (1 500 FCFA)',
            'action'      => 'portefeuille.php',
            'form_fields' => '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">'
                           . '<input type="hidden" name="action" value="toggle_abo">'
                           . '<input type="hidden" name="status" value="1">',
        ],
    ];
    include __DIR__ . '/../views/components/information_block.php';
else:
    // Abonnement actif
    $texte_actif = 'Abonnement actif jusqu\'au ' . date('d/m/Y', strtotime($date_expiration));
    if ($jours_restants !== null && $jours_restants <= 5) {
        $texte_actif .= ' — ' . $jours_restants . ' jour(s) restant';
    }

    if ($renouvellement_auto == 1) {
        $btn_label  = 'Désactiver renouvellement auto';
        $btn_status = 0;
    } else {
        $btn_label  = 'Activer renouvellement auto';
        $btn_status = 1;
    }

    $ib = [
        'variant' => 'success',
        'icon'    => 'bi-check-circle-fill',
        'title'   => '',
        'text'    => $texte_actif,
        'cta'     => [
            'type'        => 'submit',
            'label'       => $btn_label,
            'action'      => 'portefeuille.php',
            'form_fields' => '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">'
                           . '<input type="hidden" name="action" value="toggle_abo">'
                           . '<input type="hidden" name="status" value="' . $btn_status . '">',
        ],
    ];
    include __DIR__ . '/../views/components/information_block.php';
endif; ?>

<!-- ── Historique des transactions ── -->
<div class="glass-cct" style="overflow:hidden;margin-top:1.5rem;">
    <div class="d-flex align-items-center justify-content-between"
         style="padding:14px 20px;border-bottom:1px solid var(--glass-border);">
        <span style="font-size:0.88rem;font-weight:700;color:var(--text-primary);">
            Dernières opérations
        </span>
    </div>

    <?php if (empty($historique_apercu)): ?>
        <div style="padding:2rem;text-align:center;color:var(--text-muted);font-size:0.82rem;">
            Aucune transaction pour le moment
        </div>
    <?php else: ?>
        <?php foreach ($historique_apercu as $t):
            $tl = [
                'type'    => $t['type_transaction'] === 'gain' ? 'gain' : 'debit',
                'label'   => $t['motif'] ?? $t['type_transaction'],
                'date'    => date('d/m/Y H:i', strtotime($t['date_creation'])),
                'montant' => number_format(abs($t['montant']), 0, ',', ' '),
                'unite'   => 'FCFA',
            ];
            include __DIR__ . '/../views/components/timeline_item.php';
        endforeach; ?>

        <div style="padding:12px 20px;text-align:center;">
            <button onclick="openModal('modal-historique')"
                    class="btn-outline-gold btn-sm"
                    aria-label="Voir tout l'historique">
                Voir tout l'historique
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal historique complet -->
<?php
ob_start();
foreach ($historique_complet as $t):
    $tl = [
        'type'    => $t['type_transaction'] === 'gain' ? 'gain' : 'debit',
        'label'   => $t['motif'] ?? $t['type_transaction'],
        'date'    => date('d/m/Y H:i', strtotime($t['date_creation'])),
        'montant' => number_format(abs($t['montant']), 0, ',', ' '),
        'unite'   => 'FCFA',
    ];
    include __DIR__ . '/../views/components/timeline_item.php';
endforeach;
$modal_content = ob_get_clean();

$modal_id    = 'modal-historique';
$modal_title = 'Historique complet';
$modal_width = '700px';
include __DIR__ . '/../views/components/modal.php';
unset($modal_content, $modal_id, $modal_title, $modal_width);
?>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
