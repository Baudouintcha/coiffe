<?php
// Fichier : coiffons/coiffeurs/portefeuille.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

// 1. SÉCURITÉ : Vérification de l'accès coiffeur
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'coiffeur') {
    header('Location: /coiffons/access/connexion.php');
    exit();
}

$id_coiffeur = $_SESSION['id_user'];

// 2. SÉCURITÉ : Génération du Token CSRF si inexistant
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 3. TRAITEMENT : Changement du statut de renouvellement auto (Sécurisé via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_abo') {
    // Vérification stricte du jeton CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Action non autorisée (Échec de la vérification de sécurité CSRF).');
    }

    if (isset($_POST['status'])) {
        $nouveau_statut = (int)$_POST['status'];
        
        // Mise à jour harmonisée dans la table 'users'
        $stmt_update = $pdo->prepare("UPDATE users SET renouvellement_auto = ? WHERE id = ?");
        $stmt_update->execute([$nouveau_statut, $id_coiffeur]);
        
        // Redirection PRG (Post-Redirect-Get) pour éviter le renvoi du formulaire au rafraîchissement
        header('Location: portefeuille.php');
        exit();
    }
}

// 4. RÉCUPÉRATION : Informations d'abonnement du coiffeur (Source : table users)
$stmt_coiffeur = $pdo->prepare("SELECT abonnement_status, date_expiration_abo, renouvellement_auto FROM users WHERE id = ?");
$stmt_coiffeur->execute([$id_coiffeur]);
$info_coiffeur = $stmt_coiffeur->fetch();

$date_expiration = $info_coiffeur['date_expiration_abo'] ?? null;
$renouvellement_auto = $info_coiffeur['renouvellement_auto'] ?? 1;

// 5. LOGIQUE DE L'ABONNEMENT & DES NOTIFICATIONS
$abonnement_expire = false;
$jours_restants = null;
$alerte_cloche_active = false;

if ($date_expiration) {
    $date_actuelle = new DateTime(date('Y-m-d'));
    $date_limite = new DateTime($date_expiration);
    
    if ($date_actuelle >= $date_limite) {
        $abonnement_expire = true;
    } else {
        // Calcul de la différence en jours pour la notification d'anticipation
        $interval = $date_actuelle->diff($date_limite);
        $jours_restants = (int)$interval->format('%r%a');
        
        // Si l'abonnement expire dans 5 jours ou moins, on prépare l'alerte pour la cloche
        if ($jours_restants <= 5 && $jours_restants >= 0) {
            $alerte_cloche_active = true;
            // Cette variable $alerte_cloche_active pourra être lue directement par votre header.php
            $_SESSION['alerte_abo_cloche'] = "Rappel : Votre abonnement expire dans $jours_restants jour(s). 1 500 FCFA seront déduits de votre solde.";
        } else {
            unset($_SESSION['alerte_abo_cloche']);
        }
    }
} else {
    // Si la date d'expiration est NULL, le coiffeur vient de s'inscrire et n'a pas d'abonnement actif
    $abonnement_expire = true;
}

// Constantes du modèle économique
$commission_pourcentage = 0.05;

// 6. CALCUL DU SOLDE ET DES COMPTES (Source : table transactions_portefeuille)
// Solde réel disponible (Somme algébrique de toutes les transactions réelles : gains, retraits, abonnements)
$stmt_solde = $pdo->prepare("SELECT SUM(montant) AS solde_reel FROM transactions_portefeuille WHERE user_id = ?");
$stmt_solde->execute([$id_coiffeur]);
$solde_data = $stmt_solde->fetch();
$solde_disponible = $solde_data['solde_reel'] ?? 0;

// Somme globale des gains bruts accumulés (Uniquement les lignes d'entrée de type 'gain' ou 'rdv')
$stmt_brut = $pdo->prepare("SELECT SUM(montant) AS total_brut FROM transactions_portefeuille WHERE user_id = ? AND type_transaction = 'gain'");
$stmt_brut->execute([$id_coiffeur]);
$brut_data = $stmt_brut->fetch();
$total_gains_bruts = $brut_data['total_brut'] ?? 0;

// Somme globale des commissions prélevées par le site (5%)
$total_commissions = $total_gains_bruts * $commission_pourcentage;

// 7. HISTORIQUE DES TRANSACTIONS (Complet pour la Modal + Limité pour l'aperçu)
$stmt_complet = $pdo->prepare("SELECT * FROM transactions_portefeuille WHERE user_id = ? ORDER BY date_creation DESC");
$stmt_complet->execute([$id_coiffeur]);
$historique_complet = $stmt_complet->fetchAll();

// On extrait les 5 derniers mouvements pour l'aperçu de la page principale
$historique_apercu = array_slice($historique_complet, 0, 5);

include __DIR__ . '/../layout/header_coiffeur.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;color:var(--gold);margin-bottom:2px;">
            <i class="bi bi-wallet2 me-2"></i>Mon Portefeuille
        </h2>
        <p style="color:rgba(255,255,255,0.35);font-size:0.78rem;margin:0;">Gains, abonnement et historique financier</p>
    </div>
    <a href="/coiffons/index.php?page=dashboard_coiffeur"
       style="color:rgba(255,255,255,0.35);text-decoration:none;font-size:0.8rem;display:flex;align-items:center;gap:5px;">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- Stats financières -->
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="glass-cct p-3 text-center">
            <i class="bi bi-graph-up" style="color:var(--gold);font-size:1.3rem;display:block;margin-bottom:6px;"></i>
            <div style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Gains bruts</div>
            <div style="font-size:1rem;font-weight:700;color:#fff;"><?= number_format($total_gains_bruts, 0, ',', ' ') ?> <span style="font-size:0.65rem;color:rgba(255,255,255,0.4)">FCFA</span></div>
        </div>
    </div>
    <div class="col-4">
        <div class="glass-cct p-3 text-center">
            <i class="bi bi-percent" style="color:#ff6b6b;font-size:1.3rem;display:block;margin-bottom:6px;"></i>
            <div style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Commissions 5%</div>
            <div style="font-size:1rem;font-weight:700;color:#ff6b6b;"><?= number_format($total_commissions, 0, ',', ' ') ?> <span style="font-size:0.65rem;">FCFA</span></div>
        </div>
    </div>
    <div class="col-4">
        <div class="glass-cct p-3 text-center" style="border-color:rgba(212,175,55,0.25);">
            <i class="bi bi-cash-stack" style="color:var(--gold);font-size:1.3rem;display:block;margin-bottom:6px;"></i>
            <div style="font-size:0.65rem;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Solde disponible</div>
            <div style="font-size:1rem;font-weight:700;color:var(--gold);"><?= number_format($solde_disponible, 0, ',', ' ') ?> <span style="font-size:0.65rem;">FCFA</span></div>
        </div>
    </div>
</div>

<!-- Abonnement -->
<?php if ($abonnement_expire): ?>
    <div style="background:rgba(220,53,69,0.12);border:1px solid rgba(220,53,69,0.3);border-radius:14px;padding:20px 24px;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <i class="bi bi-exclamation-triangle-fill" style="color:#ff6b6b;font-size:1.4rem;flex-shrink:0;margin-top:2px;"></i>
            <div style="flex:1;">
                <div style="font-weight:700;color:#ffb3b3;margin-bottom:4px;">
                    <?= $date_expiration === null ? 'Activation requise' : 'Abonnement expiré' ?>
                </div>
                <div style="color:rgba(255,255,255,0.5);font-size:0.8rem;margin-bottom:12px;">
                    Votre profil n'est plus visible dans l'annuaire.
                    <?php if ($date_expiration): ?>
                        Expiré le <?= date('d/m/Y', strtotime($date_expiration)) ?>.
                    <?php endif; ?>
                </div>
                <form action="portefeuille.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="action" value="toggle_abo">
                    <input type="hidden" name="status" value="1">
                    <button type="submit" style="background:rgba(25,135,84,0.2);color:#6ee7b7;border:1px solid rgba(25,135,84,0.3);border-radius:20px;padding:7px 20px;font-size:0.78rem;font-weight:700;cursor:pointer;">
                        <i class="bi bi-check-circle me-1"></i>Réactiver l'abonnement (1 500 FCFA)
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <div style="background:rgba(25,135,84,0.08);border:1px solid rgba(25,135,84,0.2);border-radius:14px;padding:14px 20px;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <i class="bi bi-check-circle-fill" style="color:#6ee7b7;"></i>
            <span style="font-size:0.82rem;color:rgba(255,255,255,0.7);">
                Abonnement actif jusqu'au <strong style="color:#fff;"><?= date('d/m/Y', strtotime($date_expiration)) ?></strong>
                <?php if ($jours_restants !== null && $jours_restants <= 5): ?>
                    <span style="color:#ffd60a;margin-left:6px;">— <?= $jours_restants ?> jour(s) restant</span>
                <?php endif; ?>
            </span>
        </div>
        <form action="portefeuille.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="toggle_abo">
            <?php if ($renouvellement_auto == 1): ?>
                <input type="hidden" name="status" value="0">
                <button type="submit" style="background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.4);border:1px solid rgba(255,255,255,0.1);border-radius:20px;padding:5px 14px;font-size:0.72rem;cursor:pointer;">
                    Désactiver renouvellement auto
                </button>
            <?php else: ?>
                <input type="hidden" name="status" value="1">
                <button type="submit" style="background:var(--gold-dim);color:var(--gold);border:1px solid rgba(212,175,55,0.3);border-radius:20px;padding:5px 14px;font-size:0.72rem;cursor:pointer;">
                    Activer renouvellement auto
                </button>
            <?php endif; ?>
        </form>
    </div>
<?php endif; ?>

<!-- Historique -->
<div class="glass-cct" style="overflow:hidden;">
    <div style="padding:14px 20px;border-bottom:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:0.88rem;font-weight:700;color:#fff;">Dernières opérations</span>
    </div>
    <?php if (empty($historique_apercu)): ?>
        <div style="padding:2rem;text-align:center;color:rgba(255,255,255,0.25);font-size:0.82rem;">
            Aucune transaction pour le moment
        </div>
    <?php else: ?>
        <?php foreach ($historique_apercu as $t):
            $is_gain = $t['type_transaction'] === 'gain';
            $montant_color = $is_gain ? '#6ee7b7' : '#ff6b6b';
            $montant_sign  = $is_gain ? '+' : '-';
        ?>
            <div style="padding:12px 20px;border-bottom:1px solid rgba(255,255,255,0.04);display:flex;align-items:center;gap:12px;">
                <div style="width:32px;height:32px;border-radius:50%;background:<?= $is_gain ? 'rgba(25,135,84,0.15)' : 'rgba(220,53,69,0.15)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-<?= $is_gain ? 'arrow-down-circle' : 'arrow-up-circle' ?>" style="color:<?= $montant_color ?>;font-size:0.9rem;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.82rem;color:#fff;font-weight:500;"><?= htmlspecialchars($t['motif'] ?? $t['type_transaction']) ?></div>
                    <div style="font-size:0.7rem;color:rgba(255,255,255,0.3);"><?= date('d/m/Y H:i', strtotime($t['date_creation'])) ?></div>
                </div>
                <div style="font-size:0.9rem;font-weight:700;color:<?= $montant_color ?>;">
                    <?= $montant_sign ?><?= number_format(abs($t['montant']), 0, ',', ' ') ?> FCFA
                </div>
            </div>
        <?php endforeach; ?>
        <div style="padding:12px 20px;text-align:center;">
            <button onclick="document.getElementById('modalHistorique').style.display='flex'"
                    style="background:var(--gold-dim);color:var(--gold);border:1px solid rgba(212,175,55,0.25);border-radius:20px;padding:6px 20px;font-size:0.75rem;font-weight:700;cursor:pointer;">
                Voir tout l'historique
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL HISTORIQUE -->
<div id="modalHistorique" style="display:none;position:fixed;inset:0;z-index:500;align-items:center;justify-content:center;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);" onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#111;border:1px solid rgba(212,175,55,0.2);border-radius:20px;width:90%;max-width:700px;max-height:80vh;overflow-y:auto;padding:24px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--gold);">Historique complet</h3>
            <button onclick="document.getElementById('modalHistorique').style.display='none'" style="background:none;border:none;color:rgba(255,255,255,0.4);font-size:1.4rem;cursor:pointer;">&times;</button>
        </div>
        <?php foreach ($historique_complet as $t):
            $is_gain = $t['type_transaction'] === 'gain';
            $montant_color = $is_gain ? '#6ee7b7' : '#ff6b6b';
        ?>
            <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);display:flex;align-items:center;gap:10px;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.82rem;color:#fff;"><?= htmlspecialchars($t['motif'] ?? $t['type_transaction']) ?></div>
                    <div style="font-size:0.68rem;color:rgba(255,255,255,0.3);"><?= date('d/m/Y H:i', strtotime($t['date_creation'])) ?></div>
                </div>
                <div style="font-size:0.88rem;font-weight:700;color:<?= $montant_color ?>;white-space:nowrap;">
                    <?= $is_gain ? '+' : '-' ?><?= number_format(abs($t['montant']), 0, ',', ' ') ?> FCFA
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .glass-cct { background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:16px; }
</style>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
