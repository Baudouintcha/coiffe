<?php
/**
 * client/reserver.php — Page de validation de réservation
 * Refonte UX/UI — Design System v2.0
 *
 * MODES :
 *   - Mode récapitulatif (normal) : date_rdv + heure_debut présents en GET
 *     → affiche le récap complet + options + confirmation
 *   - Mode secours : date_rdv ou heure_debut absents
 *     → affiche les sélecteurs de date/heure
 *
 * LOGIQUE MÉTIER INTACTE — SQL, sessions, notifications : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    echo "<script>window.location.href='/coiffons/index.php?page=login';</script>";
    exit();
}

require_once __DIR__ . '/../security/config.php';

$client_id = $_SESSION['id_user'];

// Paramètres GET
$id_service   = isset($_GET['presta_id'])   ? intval($_GET['presta_id'])   : 0;
$prestataire_id     = isset($_GET['prestataire_id']) ? intval($_GET['prestataire_id']) : 0;
$date_rdv_url    = isset($_GET['date_rdv'])    ? trim($_GET['date_rdv'])      : '';
$heure_debut_url = isset($_GET['heure_debut']) ? trim($_GET['heure_debut'])   : '';

// Mode : récapitulatif ou secours ?
$mode_recap = ($date_rdv_url !== '' && $heure_debut_url !== '');

// Chargement prestation — SQL inchangé
$presta_stmt = $pdo->prepare("SELECT * FROM prestations WHERE id_service = ?");
$presta_stmt->execute([$id_service]);
$prestation = $presta_stmt->fetch();

$duree_coiffeur = (isset($prestation['duree']) && $prestation['duree'] > 0) ? intval($prestation['duree']) : 60;

// Extraction options JSON — inchangé
$explode           = explode("|||", $prestation['description'] ?? '');
$description_pure  = $explode[0] ?? '';
$json_options      = $explode[1] ?? '[]';
$options_catalogue = json_decode($json_options, true) ?: [];

// Chargement coiffeur (pour l'afficher dans le récap)
$coiffeur_recap = null;
if ($prestataire_id > 0) {
    try {
        $stmt_c = $pdo->prepare("SELECT u.nom, u.prenom, u.photo_profil,
            (SELECT ROUND(AVG(note),1) FROM commentaires WHERE prestataire_id=u.id AND type='public') AS note_moy
            FROM users u WHERE u.id = ?");
        $stmt_c->execute([$prestataire_id]);
        $coiffeur_recap = $stmt_c->fetch();
    } catch (Exception $e) {}
}

// Photo coiffeur
$photo_coiffeur_url = null;
if ($coiffeur_recap && !empty($coiffeur_recap['photo_profil'])) {
    $cp = __DIR__ . '/../access/' . $coiffeur_recap['photo_profil'];
    if (file_exists($cp)) $photo_coiffeur_url = '/coiffons/access/' . $coiffeur_recap['photo_profil'];
}

// Simulation portefeuille — inchangé
$mode_test    = true;
$solde_actuel = $mode_test ? 15000 : 0;
if (!$mode_test) {
    $s = $pdo->prepare("SELECT solde FROM users WHERE id = ?");
    $s->execute([$client_id]);
    $solde_actuel = floatval($s->fetchColumn() ?? 0);
}

// Calcul heure de fin (mode récap)
$heure_fin_calculee = '';
if ($mode_recap && $heure_debut_url) {
    [$hh, $mm]          = explode(':', $heure_debut_url);
    $total_min          = (int)$mm + $duree_coiffeur;
    $new_h              = ((int)$hh + intdiv($total_min, 60)) % 24;
    $new_m              = $total_min % 60;
    $heure_fin_calculee = str_pad($new_h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($new_m, 2, '0', STR_PAD_LEFT);
}

// Formatage date lisible
$date_lisible = '';
if ($date_rdv_url) {
    $ts_date    = strtotime($date_rdv_url);
    $jours_fr   = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
    $mois_fr    = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    $date_lisible = $jours_fr[date('w', $ts_date)] . ' ' . date('j', $ts_date) . ' ' . $mois_fr[(int)date('n', $ts_date)];
}

// ── TRAITEMENT POST — logique métier inchangée ──
$msg_type    = '';
$msg_content = '';
$reservation_ok = false;

if ($prestation && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_rdv'])) {
    $date_rdv    = htmlspecialchars($_POST['date_rdv']);
    $heure_debut = htmlspecialchars($_POST['heure_debut']);
    $heure_fin   = htmlspecialchars($_POST['heure_fin']);

    $surcout_options = 0;
    if (isset($_POST['options_selectionnees']) && is_array($_POST['options_selectionnees'])) {
        foreach ($_POST['options_selectionnees'] as $prix_opt) {
            $surcout_options += floatval($prix_opt);
        }
    }
    $prix_total_calcule = floatval($prestation['prix']) + $surcout_options;

    if ($solde_actuel < $prix_total_calcule) {
        $msg_type    = 'danger';
        $msg_content = 'Solde insuffisant (' . number_format($prix_total_calcule,0,',','') . ' FCFA requis, solde : ' . number_format($solde_actuel,0,',','') . ' FCFA).';
    } else {
        $insert = $pdo->prepare("INSERT INTO rendez_vous (client_id, prestataire_id, service_id, date_rdv, heure_debut, heure_fin, statut_rdv) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')");
        if ($insert->execute([$client_id, $prestataire_id, $id_service, $date_rdv, $heure_debut, $heure_fin])) {
            if (!$mode_test) {
                $pdo->prepare("UPDATE users SET solde = ? WHERE id = ?")->execute([$solde_actuel - $prix_total_calcule, $client_id]);
            }
            $solde_actuel -= $prix_total_calcule;

            // Notification coiffeur — inchangée
            $msg_notif = "Nouvelle demande de RDV de " . ($_SESSION['prenom'] ?? 'Un client') . " " . ($_SESSION['nom'] ?? '')
                       . " pour le " . $date_rdv . " à " . $heure_debut . ".";
            try {
                $pdo->prepare("INSERT INTO notifications (id_user, message, type, lu, date_notification) VALUES (?, ?, 'info', 0, NOW())")
                    ->execute([$prestataire_id, $msg_notif]);
            } catch (Exception $e) {}

            $reservation_ok  = true;
            $msg_type        = 'success';
            $msg_content     = number_format($prix_total_calcule, 0, ',', ' ') . ' FCFA gelés sur votre portefeuille.';
        } else {
            $msg_type    = 'danger';
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
    <title>Confirmer la réservation — Coiffe Chez Toi</title>
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    .resa-page { padding-top:calc(var(--navbar-height) + 1.5rem); padding-bottom:calc(var(--bottomnav-height) + 2rem); min-height:100vh; background:var(--dark); }

    /* ── Card recap ── */
    .resa-card {
        background: var(--dark-2);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .resa-card-header {
        padding: 14px 20px;
        border-bottom: 1px solid var(--glass-border);
        display: flex; align-items: center; gap: 10px;
    }
    .resa-card-header-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--gold); }
    .resa-row {
        display: flex; align-items: flex-start; justify-content: space-between;
        padding: 12px 20px;
        border-bottom: 1px solid rgba(255,255,255,.04);
        gap: 12px;
    }
    .resa-row:last-child { border-bottom: none; }
    .resa-label { font-size:.72rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.4px; min-width:80px; flex-shrink:0; margin-top:2px; }
    .resa-value { font-size:.88rem; font-weight:600; color:var(--text-primary); text-align:right; }
    .resa-value--gold { color:var(--gold); font-weight:800; font-size:1rem; }
    .resa-value--success { color:var(--success-text); }

    /* Coiffeur header */
    .resa-coiffeur-block {
        background: linear-gradient(135deg, rgba(212,175,55,.06) 0%, rgba(255,255,255,.03) 100%);
        border: 1px solid rgba(212,175,55,.20);
        border-radius: var(--radius-xl);
        padding: 18px 20px;
        display: flex; align-items: center; gap: 14px;
        margin-bottom: 1rem;
    }
    .resa-coif-avatar { width:52px; height:52px; border-radius:50%; object-fit:cover; border:2px solid var(--gold); flex-shrink:0; }
    .resa-coif-avatar-ph { width:52px; height:52px; border-radius:50%; background:rgba(212,175,55,.12); border:2px solid var(--gold); display:flex; align-items:center; justify-content:center; font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:var(--gold); flex-shrink:0; }

    /* Options */
    .opt-label { display:flex; align-items:center; gap:10px; cursor:pointer; padding:10px 0; color:var(--text-primary); font-size:.88rem; border-bottom:1px solid rgba(255,255,255,.04); }
    .opt-label:last-child { border-bottom:none; }
    .opt-label input[type="checkbox"] { width:18px; height:18px; accent-color:var(--gold); flex-shrink:0; cursor:pointer; }
    .opt-price { color:var(--text-muted); font-size:.78rem; margin-left:auto; }

    /* Total flottant */
    .total-block {
        background: linear-gradient(135deg, rgba(212,175,55,.10), rgba(212,175,55,.05));
        border: 1.5px solid rgba(212,175,55,.35);
        border-radius: var(--radius-lg);
        padding: 14px 20px;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 1rem;
    }
    .total-label-txt { font-size:.72rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.5px; display:block; }
    .total-val-txt   { font-size:1.6rem; font-weight:900; color:var(--gold); font-family:var(--font-display); }

    /* Bloc succès */
    .success-screen { text-align:center; padding:3rem 2rem; }
    .success-icon { width:72px; height:72px; border-radius:50%; background:rgba(25,135,84,.18); border:2px solid rgba(25,135,84,.40); display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 1.25rem; }
    </style>
</head>
<body>
<?php
$prenom_client = htmlspecialchars($_SESSION['prenom'] ?? '');
$photo_profil  = $_SESSION['photo_profil'] ?? null;
$nb_notifs     = 0;
$page_root     = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<?php if (!empty($msg_type)):
    $toast_type     = $msg_type;
    $toast_message  = htmlspecialchars($msg_content);
    $toast_duration = 6000;
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<main class="resa-page page-transition" id="main-content">
<div class="container" style="max-width:560px;">

    <!-- Retour -->
    <div class="mb-4">
        <a href="javascript:history.back()" class="btn-ghost btn-sm d-inline-flex align-items-center gap-1">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour
        </a>
    </div>

    <?php if (!$prestation): ?>
        <?php
        $es = ['icon'=>'bi-exclamation-circle','message'=>'Prestation introuvable.','cta_label'=>"Retour à l'accueil",'cta_href'=>'/coiffons/index.php'];
        include __DIR__ . '/../views/components/empty_state.php';
        ?>
    <?php elseif ($reservation_ok): ?>
    <!-- ════════ ÉCRAN DE CONFIRMATION ════════ -->
    <div class="success-screen fade-in-card">
        <div class="success-icon" aria-hidden="true"><i class="bi bi-calendar-check-fill" style="color:var(--success-text);"></i></div>
        <h1 style="font-family:var(--font-display);font-size:1.5rem;font-weight:700;color:var(--text-primary);margin-bottom:.5rem;">Demande envoyée !</h1>
        <p style="color:var(--text-muted);font-size:.88rem;margin-bottom:1.5rem;">
            Le coiffeur a été notifié. Vous recevrez une confirmation dès validation.
        </p>
        <div class="resa-card" style="margin-bottom:1.5rem;text-align:left;">
            <div class="resa-row">
                <span class="resa-label">Style</span>
                <span class="resa-value"><?= htmlspecialchars($prestation['nom_service']) ?></span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Date</span>
                <span class="resa-value"><?= htmlspecialchars($date_lisible) ?></span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Heure</span>
                <span class="resa-value"><?= htmlspecialchars($heure_debut_url) ?></span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Fonds gelés</span>
                <span class="resa-value resa-value--gold"><?= $msg_content ?></span>
            </div>
        </div>
        <a href="/coiffons/client/mes_rendezvous.php" class="btn-gold w-100" style="padding:13px;font-size:.92rem;">
            <i class="bi bi-calendar3 me-2" aria-hidden="true"></i>Voir mes réservations
        </a>
    </div>

    <?php else: ?>
    <!-- ════════ FORMULAIRE DE VALIDATION ════════ -->
    <h1 style="font-family:var(--font-display);font-size:1.4rem;font-weight:700;color:var(--text-primary);margin-bottom:1.5rem;letter-spacing:.5px;">
        Votre réservation
    </h1>

    <!-- Bloc coiffeur -->
    <?php if ($coiffeur_recap): ?>
    <div class="resa-coiffeur-block">
        <?php if ($photo_coiffeur_url): ?>
            <img src="<?= htmlspecialchars($photo_coiffeur_url) ?>" class="resa-coif-avatar" alt="">
        <?php else: ?>
            <div class="resa-coif-avatar-ph"><?= strtoupper(substr($coiffeur_recap['nom'],0,1)) ?></div>
        <?php endif; ?>
        <div>
            <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:2px;">Coiffeur</div>
            <div style="font-weight:700;font-size:.95rem;color:var(--text-primary);">
                <?= htmlspecialchars(strtoupper($coiffeur_recap['nom']) . ' ' . ($coiffeur_recap['prenom'] ?? '')) ?>
            </div>
            <?php if ($coiffeur_recap['note_moy']): ?>
                <div style="font-size:.75rem;color:var(--gold);margin-top:2px;">
                    <?php $nm = floatval($coiffeur_recap['note_moy']); for($s=1;$s<=5;$s++) echo ($s<=round($nm)?'★':'☆'); ?>
                    <?= $nm ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" id="resa-form" novalidate>
        <input type="hidden" name="id_service" value="<?= (int)$id_service ?>">
        <input type="hidden" id="input-prix-base" value="<?= (int)($prestation['prix'] ?? 0) ?>">
        <input type="hidden" id="input-duree" value="<?= $duree_coiffeur ?>">

        <!-- ── Récap prestation ── -->
        <div class="resa-card">
            <div class="resa-card-header">
                <i class="bi bi-scissors" style="color:var(--gold);" aria-hidden="true"></i>
                <span class="resa-card-header-title">Style</span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Prestation</span>
                <span class="resa-value"><?= htmlspecialchars($prestation['nom_service']) ?></span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Durée</span>
                <span class="resa-value"><?= $duree_coiffeur ?> minutes</span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Prix de base</span>
                <span class="resa-value resa-value--gold"><?= number_format($prestation['prix'],0,',','') ?> FCFA</span>
            </div>
        </div>

        <!-- ── Date / heure : récap ou sélecteur secours ── -->
        <div class="resa-card">
            <div class="resa-card-header">
                <i class="bi bi-calendar3" style="color:var(--gold);" aria-hidden="true"></i>
                <span class="resa-card-header-title">Date &amp; heure</span>
            </div>

            <?php if ($mode_recap): ?>
            <!-- Mode récapitulatif — champs cachés -->
            <input type="hidden" name="date_rdv"    value="<?= htmlspecialchars($date_rdv_url) ?>">
            <input type="hidden" name="heure_debut" id="input-heure-debut" value="<?= htmlspecialchars($heure_debut_url) ?>">
            <input type="hidden" name="heure_fin"   id="input-heure-fin"   value="<?= htmlspecialchars($heure_fin_calculee) ?>">
            <div class="resa-row">
                <span class="resa-label">Date</span>
                <span class="resa-value"><?= htmlspecialchars($date_lisible) ?></span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Début</span>
                <span class="resa-value"><?= htmlspecialchars($heure_debut_url) ?></span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Fin estimée</span>
                <span class="resa-value" style="color:var(--text-muted);"><?= htmlspecialchars($heure_fin_calculee) ?></span>
            </div>

            <?php else: ?>
            <!-- Mode secours — sélecteurs -->
            <div style="padding:16px 20px;">
                <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:1rem;">
                    <i class="bi bi-info-circle me-1" style="color:var(--gold);" aria-hidden="true"></i>
                    Aucune date sélectionnée. Choisissez ci-dessous.
                </p>
                <div class="mb-3">
                    <label for="date_rdv_input" class="form-label-cct">Date du rendez-vous</label>
                    <input type="date" id="date_rdv_input" name="date_rdv" class="fc-dark"
                           min="<?= date('Y-m-d') ?>" required aria-required="true">
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <label for="heure_debut_input" class="form-label-cct">Heure de début</label>
                        <input type="time" id="heure_debut_input" name="heure_debut"
                               class="fc-dark" id="input-heure-debut" required aria-required="true">
                    </div>
                    <div class="col-6">
                        <label for="heure_fin_input" class="form-label-cct">Fin <span style="color:var(--text-muted);font-weight:400;">(auto)</span></label>
                        <input type="time" id="heure_fin_input" name="heure_fin"
                               class="fc-dark" id="input-heure-fin"
                               style="color:var(--gold) !important;font-weight:700;" readonly>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Options supplémentaires ── -->
        <?php if (!empty($options_catalogue)): ?>
        <div class="resa-card">
            <div class="resa-card-header">
                <i class="bi bi-plus-circle" style="color:var(--gold);" aria-hidden="true"></i>
                <span class="resa-card-header-title">Options</span>
            </div>
            <div style="padding:4px 20px 12px;">
                <?php foreach ($options_catalogue as $idx => $opt): ?>
                <label class="opt-label" for="opt-<?= $idx ?>">
                    <input type="checkbox"
                           class="option-checkbox-js"
                           name="options_selectionnees[]"
                           id="opt-<?= $idx ?>"
                           value="<?= htmlspecialchars($opt['prix']) ?>"
                           data-prix="<?= htmlspecialchars($opt['prix']) ?>">
                    <span><?= htmlspecialchars($opt['nom']) ?></span>
                    <span class="opt-price">+<?= number_format($opt['prix'],0,',','') ?> FCFA</span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Adresse ── -->
        <div class="resa-card">
            <div class="resa-card-header">
                <i class="bi bi-geo-alt-fill" style="color:var(--gold);" aria-hidden="true"></i>
                <span class="resa-card-header-title">Adresse</span>
            </div>
            <div style="padding:14px 20px;">
                <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.5rem;">
                    L'adresse utilisée est celle enregistrée dans votre profil.
                </p>
                <p style="font-size:.85rem;color:var(--text-primary);margin:0;">
                    <i class="bi bi-house-fill me-1" style="color:var(--gold);" aria-hidden="true"></i>
                    <?= htmlspecialchars($_SESSION['nom_ville'] ?? 'Non spécifiée') ?>
                    <span style="color:var(--text-muted);"> — mise à jour dans vos paramètres</span>
                </p>
            </div>
        </div>

        <!-- ── Paiement / Solde ── -->
        <div class="resa-card">
            <div class="resa-card-header">
                <i class="bi bi-wallet2" style="color:var(--gold);" aria-hidden="true"></i>
                <span class="resa-card-header-title">Paiement</span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Méthode</span>
                <span class="resa-value">Portefeuille Coiffe Chez Toi</span>
            </div>
            <div class="resa-row">
                <span class="resa-label">Solde dispo</span>
                <span class="resa-value resa-value--success">
                    <?= number_format($solde_actuel,0,',','') ?> FCFA
                    <?php if ($mode_test): ?><span style="color:var(--text-disabled);font-size:.68rem;"> (simulé)</span><?php endif; ?>
                </span>
            </div>
        </div>

        <!-- ── Total ── -->
        <div class="total-block" aria-live="polite">
            <div>
                <span class="total-label-txt">Total à geler</span>
                <span class="total-val-txt"><span id="total-display"><?= number_format($prestation['prix'],0,',','') ?></span> FCFA</span>
            </div>
            <i class="bi bi-shield-lock-fill" style="color:var(--gold);font-size:1.5rem;" aria-hidden="true"></i>
        </div>

        <!-- ── Bouton confirmation ── -->
        <button type="submit" name="confirmer_rdv"
                class="btn-gold w-100"
                style="padding:15px;font-size:.95rem;font-weight:700;">
            <i class="bi bi-calendar-check me-2" aria-hidden="true"></i>
            Confirmer la réservation
        </button>

        <p style="font-size:.7rem;color:var(--text-muted);text-align:center;margin-top:.75rem;line-height:1.5;">
            <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
            Le montant sera gelé jusqu'à confirmation du coiffeur. Remboursement intégral si annulation &gt; 24h.
        </p>

    </form>
    <?php endif; ?>

</div>
</main>

<?php
$page_root    = '/coiffons';
include __DIR__ . '/../views/components/footer_global.php';
$current_page = 'reserver.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const prixBase   = parseFloat(document.getElementById('input-prix-base')?.value || 0);
    const duree      = parseInt(document.getElementById('input-duree')?.value || 60);
    const checkboxes = document.querySelectorAll('.option-checkbox-js');
    const totalEl    = document.getElementById('total-display');

    function majTotal() {
        let extras = 0;
        checkboxes.forEach(cb => { if (cb.checked) extras += parseFloat(cb.getAttribute('data-prix') || 0); });
        if (totalEl) totalEl.textContent = (prixBase + extras).toLocaleString('fr-FR');
    }
    checkboxes.forEach(cb => cb.addEventListener('change', majTotal));

    // Mode secours : calcul heure de fin
    const hdInput  = document.getElementById('heure_debut_input');
    const hfInput  = document.getElementById('heure_fin_input');
    if (hdInput && hfInput) {
        hdInput.addEventListener('change', function () {
            const val = this.value;
            if (!val) return;
            const [hh, mm] = val.split(':').map(Number);
            const total    = mm + duree;
            const nh       = (hh + Math.floor(total / 60)) % 24;
            const nm       = total % 60;
            hfInput.value  = String(nh).padStart(2,'0') + ':' + String(nm).padStart(2,'0');
        });
    }
})();
</script>

</body>
</html>
