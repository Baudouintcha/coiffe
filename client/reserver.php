<?php
/**
 * client/reserver.php — Page de réservation d'une prestation
 * Migration Design System v2.0 — Parcours Visiteur — Page 6/6
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — Seuls HTML/CSS/composants ont été modifiés.
 *     SQL, sessions, calculs prix, mode_test, FK cleanup : strictement inchangés.
 */

// 1. Sécurité et Session — inchangé
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    echo "<script>window.location.href='/coiffons/index.php?page=login';</script>";
    exit();
}

require_once __DIR__ . '/../security/config.php';

$id_client = $_SESSION['id_user'];
$message   = "";

// Paramètres URL — inchangé
$id_prestation = isset($_GET['presta_id'])  ? intval($_GET['presta_id'])  : 0;
$id_coiffeur   = isset($_GET['coiffeur_id'])? intval($_GET['coiffeur_id']): 0;

// Pré-remplissage depuis URL (depuis profil_public) — inchangé
$date_rdv_url    = isset($_GET['date_rdv'])    ? htmlspecialchars($_GET['date_rdv'])    : '';
$heure_debut_url = isset($_GET['heure_debut']) ? htmlspecialchars($_GET['heure_debut']) : '';

// Requête prestation — SQL inchangé
$presta_stmt = $pdo->prepare("SELECT * FROM prestations WHERE id_prestation = ?");
$presta_stmt->execute([$id_prestation]);
$prestation = $presta_stmt->fetch();

$duree_coiffeur = (isset($prestation['duree']) && $prestation['duree'] > 0) ? intval($prestation['duree']) : 60;

// Extraction options JSON — inchangé
$explode          = explode("|||", $prestation['description'] ?? '');
$description_pure = $explode[0] ?? '';
$json_options     = $explode[1] ?? '[]';
$options_catalogue = json_decode($json_options, true) ?: [];

// Simulation portefeuille — inchangé
$mode_test = true;
if ($mode_test) {
    $solde_actuel = 15000;
} else {
    $solde_stmt = $pdo->prepare("SELECT solde FROM users WHERE id = ?");
    $solde_stmt->execute([$id_client]);
    $client_info  = $solde_stmt->fetch();
    $solde_actuel = $client_info['solde'] ?? 0;
}

// Traitement formulaire — logique métier inchangée
$msg_type    = '';
$msg_content = '';

if ($prestation && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmer_rdv'])) {
    $date_rdv    = htmlspecialchars($_POST['date_rdv']);
    $heure_debut = htmlspecialchars($_POST['heure_debut']);
    $heure_fin   = htmlspecialchars($_POST['heure_fin']);

    // Nettoyage FK — SQL inchangé
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $stmt_fk = $pdo->query("SHOW CREATE TABLE rendez_vous");
        $row_fk  = $stmt_fk->fetch(PDO::FETCH_ASSOC);
        if (preg_match('/CONSTRAINT `([^`]+)` FOREIGN KEY \(`coiffure_id`\)/', $row_fk['Create Table'], $matches)) {
            $pdo->exec("ALTER TABLE rendez_vous DROP FOREIGN KEY `{$matches[1]}`;");
        }
        $pdo->exec("ALTER TABLE rendez_vous DROP INDEX IF EXISTS `coiffure_id`;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    } catch (Exception $e) { /* déjà nettoyé */ }

    // Calcul prix — inchangé
    $surcout_options = 0;
    if (isset($_POST['options_selectionnees']) && is_array($_POST['options_selectionnees'])) {
        foreach ($_POST['options_selectionnees'] as $prix_opt) {
            $surcout_options += floatval($prix_opt);
        }
    }
    $prix_total_calcule = floatval($prestation['prix']) + $surcout_options;

    if ($solde_actuel < $prix_total_calcule) {
        $msg_type    = 'msg-danger';
        $msg_content = '❌ Solde insuffisant pour geler les fonds de ce RDV ('
                     . number_format($prix_total_calcule, 0, ',', ' ')
                     . ' FCFA requis). Votre solde est de '
                     . number_format($solde_actuel, 0, ',', ' ') . ' FCFA.';
    } else {
        $insert = $pdo->prepare("INSERT INTO rendez_vous (client_id, coiffeur_id, coiffure_id, date_rdv, heure_debut, heure_fin, statut_rdv) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')");
        if ($insert->execute([$id_client, $id_coiffeur, $id_prestation, $date_rdv, $heure_debut, $heure_fin])) {
            if (!$mode_test) {
                $pdo->prepare("UPDATE users SET solde = ? WHERE id = ?")->execute([$solde_actuel - $prix_total_calcule, $id_client]);
            }
            $solde_actuel  -= $prix_total_calcule;
            $msg_type       = 'msg-success';
            $msg_content    = '🎉 Demande de rendez-vous envoyée au coiffeur ! '
                            . number_format($prix_total_calcule, 0, ',', ' ')
                            . ' FCFA ont été gelés sur votre compte. Solde restant : '
                            . number_format($solde_actuel, 0, ',', ' ') . ' FCFA'
                            . ($mode_test ? ' (Simulé)' : '') . '.';
        } else {
            $msg_type    = 'msg-danger';
            $msg_content = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver ma séance — Coiffe Chez Toi</title>

    <!-- Design System v2.0 — Ordre de chargement officiel -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">

    <!-- Bootstrap 5.3.3 (grille uniquement) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Styles spécifiques à reserver.php, tous via var(--*) DS ── */

    /* Layout page */
    .reservation-page   { padding-top:calc(var(--navbar-height) + 2rem); padding-bottom:calc(var(--bottomnav-height) + 2rem); min-height:100vh; background:var(--dark); }

    /* Card recap prestation */
    .recap-card         { background:var(--glass-bg); border:1px solid var(--glass-border); border-radius:var(--radius-lg); padding:1.25rem 1.5rem; margin-bottom:1.5rem; }
    .recap-style-name   { font-family:var(--font-display); font-size:1rem; font-weight:700; color:var(--text-primary); text-align:center; margin-bottom:4px; }
    .recap-desc         { color:var(--text-muted); font-size:.8rem; text-align:center; margin-bottom:8px; }
    .recap-prix         { font-size:1.1rem; font-weight:700; color:var(--success-text); text-align:center; }
    .recap-solde        { font-size:.78rem; color:var(--text-muted); text-align:center; margin-top:4px; }
    .recap-duree        { font-size:.78rem; color:var(--text-muted); text-align:center; margin-top:4px; }

    /* Options block */
    .options-block      { background:var(--glass-bg); border:1px dashed var(--glass-border-md); border-radius:var(--radius-md); padding:14px 16px; margin-bottom:1.25rem; }
    .options-title      { font-size:.72rem; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--gold); margin-bottom:10px; }

    /* Checkbox options */
    .option-check-label { display:flex; align-items:center; gap:10px; cursor:pointer; padding:6px 0; color:var(--text-primary); font-size:.85rem; }
    .option-check-label input[type="checkbox"] { width:16px; height:16px; accent-color:var(--gold); cursor:pointer; flex-shrink:0; }
    .option-price       { color:var(--text-muted); font-size:.78rem; }

    /* Bloc total dynamique */
    .total-block        { background:var(--dark-2); border:1px solid var(--glass-border); border-radius:var(--radius-md); padding:12px 16px; text-align:center; margin-bottom:1.25rem; }
    .total-label        { font-size:.72rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; display:block; }
    .total-value        { font-size:1.5rem; font-weight:800; color:var(--gold); display:block; }

    /* Divider sections du formulaire */
    .form-section-sep   { font-size:.72rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--gold); border-bottom:1px solid var(--glass-border); padding-bottom:6px; margin-bottom:14px; margin-top:4px; }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════
     NAVBAR CLIENT — Composant CL §21
     ══════════════════════════════════════════ -->
<?php
$prenom_client = htmlspecialchars($_SESSION['prenom'] ?? '');
$photo_profil  = $_SESSION['photo_profil'] ?? null;
$nb_notifs     = 0;
$page_root     = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<!-- ══════════════════════════════════════════
     TOAST — Message flash (succès / erreur)
     ══════════════════════════════════════════ -->
<?php if (!empty($msg_type) && !empty($msg_content)):
    $toast_type    = $msg_type === 'msg-success' ? 'success' : 'danger';
    $toast_message = htmlspecialchars($msg_content);
    $toast_duration = 6000;
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<!-- ══════════════════════════════════════════
     CONTENU PRINCIPAL
     ══════════════════════════════════════════ -->
<main class="reservation-page page-transition" id="main-content">
    <div class="container" style="max-width:640px;">

        <!-- Lien retour -->
        <div class="mb-4">
            <a href="javascript:history.back()"
               class="btn-ghost btn-sm d-inline-flex align-items-center gap-1"
               aria-label="Retour à la page précédente">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Retour
            </a>
        </div>

        <?php if (!$prestation): ?>
            <!-- Empty state si prestation introuvable -->
            <?php
            $es = ['icon'=>'bi-exclamation-circle', 'message'=>'Prestation introuvable.', 'cta_label'=>'Retour à l\'accueil', 'cta_href'=>'/coiffons/index.php'];
            include __DIR__ . '/../views/components/empty_state.php';
            ?>
        <?php else: ?>

        <!-- ── Titre de la page ── -->
        <h1 style="font-family:var(--font-display);font-size:1.5rem;font-weight:700;color:var(--gold);text-align:center;letter-spacing:1px;margin-bottom:2rem;">
            RÉSERVER MA SÉANCE
        </h1>

        <!-- ── Récapitulatif prestation — FloatingSection CL §3 ── -->
        <div class="recap-card">
            <div class="recap-style-name">
                <?= htmlspecialchars($prestation['nom_style']) ?>
            </div>
            <?php if (!empty($description_pure)): ?>
                <p class="recap-desc"><?= htmlspecialchars($description_pure) ?></p>
            <?php endif; ?>
            <p class="recap-prix">
                <?= number_format($prestation['prix'], 0, ',', ' ') ?> FCFA
            </p>

            <hr style="border-color:var(--glass-border);margin:10px 0;">

            <p class="recap-solde">
                <i class="bi bi-wallet2 me-1" aria-hidden="true"></i>
                Mon solde disponible :
                <strong style="color:var(--gold);">
                    <?= number_format($solde_actuel, 0, ',', ' ') ?> FCFA
                </strong>
                <?php if ($mode_test): ?>
                    <span style="color:var(--text-disabled);font-size:.7rem;">(simulé)</span>
                <?php endif; ?>
            </p>
            <p class="recap-duree">
                <i class="bi bi-hourglass-split me-1" style="color:var(--gold);" aria-hidden="true"></i>
                Durée : <strong style="color:var(--text-primary);"><?= $duree_coiffeur ?> minutes</strong>
            </p>
        </div>

        <!-- ── FORMULAIRE DE RÉSERVATION ── -->
        <form method="POST" id="reservationForm" novalidate>
            <input type="hidden" id="temps_coiffeur"   value="<?= $duree_coiffeur ?>">
            <input type="hidden" id="prix_de_base_js"  value="<?= htmlspecialchars($prestation['prix']) ?>">

            <!-- Options catalogue -->
            <?php if (!empty($options_catalogue)): ?>
            <div class="options-block">
                <div class="options-title">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>
                    Options disponibles
                </div>
                <?php foreach ($options_catalogue as $index => $opt): ?>
                    <label class="option-check-label" for="opt-<?= $index ?>">
                        <input type="checkbox"
                               class="option-checkbox-js"
                               name="options_selectionnees[]"
                               id="opt-<?= $index ?>"
                               value="<?= htmlspecialchars($opt['prix']) ?>"
                               data-prix="<?= htmlspecialchars($opt['prix']) ?>">
                        <span><?= htmlspecialchars($opt['nom']) ?></span>
                        <span class="option-price ms-auto">
                            +<?= number_format($opt['prix'], 0, ',', ' ') ?> FCFA
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Section date et heure -->
            <div class="form-section-sep">Date et heure</div>

            <!-- Date -->
            <div class="mb-3">
                <label for="date_rdv" class="form-label-cct">
                    Date du rendez-vous
                </label>
                <input type="date"
                       id="date_rdv"
                       name="date_rdv"
                       class="fc-dark"
                       min="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars($date_rdv_url) ?>"
                       required
                       aria-required="true">
            </div>

            <!-- Heures -->
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label for="heure_debut" class="form-label-cct">Heure de début</label>
                    <input type="time"
                           id="heure_debut"
                           name="heure_debut"
                           class="fc-dark"
                           value="<?= htmlspecialchars($heure_debut_url) ?>"
                           required
                           aria-required="true">
                </div>
                <div class="col-6">
                    <label for="heure_fin" class="form-label-cct">
                        Heure de fin
                        <span style="color:var(--text-muted);font-weight:400;">(calculée)</span>
                    </label>
                    <input type="time"
                           id="heure_fin"
                           name="heure_fin"
                           class="fc-dark"
                           style="color:var(--gold) !important;font-weight:700;"
                           readonly
                           required
                           aria-label="Heure de fin calculée automatiquement"
                           aria-readonly="true">
                </div>
            </div>

            <!-- Bloc total dynamique -->
            <div class="total-block mb-4" aria-live="polite" aria-label="Montant total estimé">
                <span class="total-label">Montant total estimé</span>
                <span class="total-value">
                    <span id="total_dynamique_rdv">
                        <?= number_format($prestation['prix'], 0, ',', ' ') ?>
                    </span>
                    FCFA
                </span>
            </div>

            <!-- Bouton submit — PrimaryButton CL §12 -->
            <button type="submit"
                    name="confirmer_rdv"
                    class="btn-gold w-100"
                    style="padding:14px;font-size:.95rem;">
                <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>
                CONFIRMER LA RÉSERVATION
            </button>

        </form>

        <?php endif; ?>

    </div>
</main>

<!-- ══════════════════════════════════════════
     FOOTER GLOBAL + BOTTOM NAV
     ══════════════════════════════════════════ -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../views/components/footer_global.php';

$current_page = 'reserver.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ══ JAVASCRIPT UI — logique de calcul inchangée ══ -->
<script>
const heureDebutInput   = document.getElementById('heure_debut');
const checkboxesOptions = document.querySelectorAll('.option-checkbox-js');

function actualiserDureeEtPrix() {
    // Calcul heure de fin — logique inchangée
    const heureSelectionnee = heureDebutInput.value;
    const tempsNecessaire   = parseInt(document.getElementById('temps_coiffeur').value);

    if (heureSelectionnee && !isNaN(tempsNecessaire)) {
        let [heures, minutes] = heureSelectionnee.split(':').map(Number);
        let totalMinutes   = minutes + tempsNecessaire;
        let nouvellesHeures = heures + Math.floor(totalMinutes / 60);
        let nouvellesMinutes = totalMinutes % 60;
        nouvellesHeures     = nouvellesHeures % 24;
        document.getElementById('heure_fin').value =
            String(nouvellesHeures).padStart(2,'0') + ':' + String(nouvellesMinutes).padStart(2,'0');
    }

    // Calcul prix total dynamique — logique inchangée
    let prixDeBase     = parseFloat(document.getElementById('prix_de_base_js').value) || 0;
    let cumulatOptions = 0;
    checkboxesOptions.forEach(cb => {
        if (cb.checked) cumulatOptions += parseFloat(cb.getAttribute('data-prix')) || 0;
    });
    document.getElementById('total_dynamique_rdv').innerText =
        (prixDeBase + cumulatOptions).toLocaleString('fr-FR');
}

// Préremplissage si heure passée en URL (depuis profil_public)
if (heureDebutInput.value) actualiserDureeEtPrix();

heureDebutInput.addEventListener('change', actualiserDureeEtPrix);
checkboxesOptions.forEach(cb => cb.addEventListener('change', actualiserDureeEtPrix));
</script>

</body>
</html>
