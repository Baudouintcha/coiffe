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

include __DIR__ . '/../layout/header.php';
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">MON PORTEFEUILLE</h2>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-4 shadow-lg text-center h-100" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <i class="bi bi-wallet2 text-warning fs-1 mb-2"></i>
                    <h5 class="text-secondary small text-uppercase">Total de mes gains bruts</h5>
                    <h3 class="text-white fw-bold mt-2"><?php echo number_format($total_gains_bruts, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-4 shadow-lg text-center h-100" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <i class="bi bi-percent text-warning fs-1 mb-2"></i>
                    <h5 class="text-secondary small text-uppercase">Frais & Commissions (5%)</h5>
                    <h3 class="text-danger fw-bold mt-2"><?php echo number_format($total_commissions, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-4 shadow-lg text-center h-100" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                    <i class="bi bi-cash-stack text-success fs-1 mb-2"></i>
                    <h5 class="text-secondary small text-uppercase">Solde Réel Disponible</h5>
                    <h3 class="text-warning fw-bold mt-2"><?php echo number_format($solde_disponible, 0, ',', ' '); ?> FCFA</h3>
                </div>
            </div>
        </div>

        <?php if ($abonnement_expire): ?>
            <div class="card bg-black border border-danger shadow-lg mb-5" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-1 animate-pulse"></i>
                        <h4 class="text-white fw-bold mt-2 h5"><?php echo ($date_expiration === null) ? "Activation initiale requise" : "Votre abonnement mensuel est arrivé à terme"; ?></h4>
                        <p class="text-muted small">Date limite : <?php echo $date_expiration ? date('d/m/Y', strtotime($date_expiration)) : '--/--/----'; ?></p>
                    </div>

                    <div class="p-3 rounded mb-3" style="background-color: #1a0505; border: 1px solid #4a1414;">
                        <h6 class="text-danger fw-bold small mb-2"><i class="bi bi-shield-x me-1"></i> RESTRICTIONS DE VISIBILITÉ ACTIVES :</h6>
                        <ul class="text-secondary small mb-0 ps-3" style="font-size: 0.8rem; line-height: 1.6;">
                            <li><strong class="text-white">Perte de visibilité :</strong> Votre salon n'apparaît plus dans les recherches des clients.</li>
                            <li><strong class="text-white">Blocage des réservations :</strong> Les clients ne peuvent plus prendre rendez-vous.</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-center">
                        <form action="portefeuille.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="toggle_abo">
                            <input type="hidden" name="status" value="1">
                            <button type="submit" class="btn btn-success px-5 py-2 fw-bold text-uppercase" style="font-size: 0.85rem;">
                                <i class="bi bi-check-circle-fill me-1"></i> Réactiver & Rester Visible (1500 F)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php if ($jours_restants !== null && $jours_restants <= 5): ?>
                <div class="alert alert-danger bg-black border-danger text-danger mb-4 p-3 small d-flex align-items-center gap-2">
                    <i class="bi bi-bell-fill text-danger fs-5 animate-pulse"></i>
                    <div>
                        <strong>Attention Proche Échéance !</strong> Votre abonnement prend fin dans <strong><?php echo $jours_restants; ?> jours</strong> (le <?php echo date('d/m/Y', strtotime($date_expiration)); ?>).
                        <?php echo ($renouvellement_auto == 1) ? "Un montant de 1 500 FCFA sera automatiquement prélevé de votre solde." : "Le renouvellement automatique est désactivé. Votre profil sera masqué à cette date."; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning mb-5 p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3" style="background-color: #2c2512; border: 1px solid var(--gold); color: var(--gold);">
                <div>
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Statut de l'abonnement :</strong> Actif jusqu'au <strong><?php echo date('d/m/Y', strtotime($date_expiration)); ?></strong>. Les 1 500 FCFA mensuels maintiennent votre vitrine ouverte.
                </div>
                <div>
                    <form action="portefeuille.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="toggle_abo">
                        <?php if ($renouvellement_auto == 1): ?>
                            <input type="hidden" name="status" value="0">
                            <button type="submit" class="btn btn-outline-warning btn-sm fw-bold px-3">
                                <i class="bi bi-toggle-on me-1"></i> Désactiver le renouvellement
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="status" value="1">
                            <button type="submit" class="btn btn-warning btn-sm fw-bold px-3 text-black">
                                <i class="bi bi-toggle-off me-1"></i> Activer le renouvellement
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <h4 class="text-warning mb-3">Dernières opérations</h4>
        <div class="table-responsive">
            <table class="table table-dark table-bordered border-warning align-middle text-center small">
                <thead>
                    <tr class="text-warning">
                        <th>Date & Heure</th>
                        <th>Type d'opération</th>
                        <th>Description / Libellé</th>
                        <th>Brut (Client)</th>
                        <th>Frais Platform (5%)</th>
                        <th>Net Encaissé / Impact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historique_apercu)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">Aucun mouvement enregistré pour le moment.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historique_apercu as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($t['date_transaction']))); ?></td>
                                <td>
                                    <?php if ($t['type_transaction'] === 'gain'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1">Encaissment RDV</span>
                                    <?php elseif ($t['type_transaction'] === 'abonnement'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1">Frais Abonnement</span>
                                    <?php else: ?>
                                        <span class="badge bg-info-subtle text-info border border-info px-2 py-1">Retrait Compte</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-start"><?php echo htmlspecialchars($t['libelle'] ?? 'Opération de compte'); ?></td>
                                
                                <td><?php echo ($t['type_transaction'] === 'gain') ? number_format($t['montant_brut'], 0, ',', ' ') . ' F' : '-'; ?></td>
                                <td><?php echo ($t['type_transaction'] === 'gain') ? number_format($t['montant_brut'] * $commission_pourcentage, 0, ',', ' ') . ' F' : '-'; ?></td>
                                
                                <td class="fw-bold <?php echo ($t['montant'] >= 0) ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($t['montant'] >= 0 ? '+' : '') . number_format($t['montant'], 0, ',', ' ') ; ?> FCFA
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-column flex-sm-row justify-content-center align-items-center gap-3 mt-4">
            <button class="btn btn-outline-warning px-4 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#modalHistoriqueComplet">
                <i class="bi bi-clock-history me-1"></i> Voir tout mon historique
            </button>
            <button class="btn btn-gold px-5 py-2 fw-bold" onclick="alert('Demande de virement envoyée à l\'administration.')">
                <i class="bi bi-bank me-1"></i> Demander un virement
            </button>
        </div>
    </div>
</section>

<div class="modal fade" id="modalHistoriqueComplet" tabindex="-1" aria-labelledby="titleModal" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content bg-black border border-warning" style="border-radius: 15px;">
            <div class="modal-header border-bottom border-warning">
                <h5 class="modal-title text-warning fw-bold" id="titleModal"><i class="bi bi-journal-text me-2"></i>HISTORIQUE COMPTABILISÉ COMPLET</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-dark table-hover table-bordered border-secondary align-middle text-center small">
                        <thead>
                            <tr class="text-warning" style="background-color: #111;">
                                <th>Date / Heure</th>
                                <th>Type</th>
                                <th>Libellé / Description</th>
                                <th>Brut Client</th>
                                <th>Frais Site (5%)</th>
                                <th>Net Impacté</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historique_complet)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-secondary py-4">Aucune transaction archivée.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historique_complet as $t): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($t['date_transaction']))); ?></td>
                                        <td>
                                            <span class="badge <?php echo ($t['type_transaction'] === 'gain') ? 'bg-success' : (($t['type_transaction'] === 'abonnement') ? 'bg-danger' : 'bg-info'); ?>">
                                                <?php echo htmlspecialchars($t['type_transaction']); ?>
                                            </span>
                                        </td>
                                        <td class="text-start"><?php echo htmlspecialchars($t['libelle'] ?? 'Transaction'); ?></td>
                                        <td><?php echo ($t['type_transaction'] === 'gain') ? number_format($t['montant_brut'], 0, ',', ' ') . ' F' : '-'; ?></td>
                                        <td><?php echo ($t['type_transaction'] === 'gain') ? number_format($t['montant_brut'] * $commission_pourcentage, 0, ',', ' ') . ' F' : '-'; ?></td>
                                        <td class="fw-bold <?php echo ($t['montant'] >= 0) ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo ($t['montant'] >= 0 ? '+' : '') . number_format($t['montant'], 0, ',', ' '); ?> F
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top border-warning">
                <button type="button" class="btn btn-secondary px-4" data-bs-toggle="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-gold {
        background-color: var(--gold, #d4af37);
        color: black;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
    }
    .btn-gold:hover {
        background-color: #c99b2c;
        color: black;
    }
    .animate-pulse { 
        animation: pulse-danger 1.5s infinite; 
    }
    @keyframes pulse-danger { 
        0% { opacity: 0.6; transform: scale(1); } 
        50% { opacity: 1; transform: scale(1.03); } 
        100% { opacity: 0.6; transform: scale(1); } 
    }
    /* Personnalisation de la scrollbar de la Modal */
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    .modal-body::-webkit-scrollbar-thumb {
        background-color: var(--gold, #d4af37);
        border-radius: 4px;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>