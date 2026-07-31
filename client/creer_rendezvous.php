<?php
/**
 * client/creer_rendezvous.php — Création d'un rendez-vous (sélecteur jour/heure)
 * Migration Design System v2.0 — Parcours Client — Page 5 (dernière)
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, mode_test, solde, sécurité : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

// Sécurité — inchangée
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: /coiffons/index.php?page=login');
    exit();
}

$id_client    = $_SESSION['id_user'];
$id_prestation = isset($_GET['id_prestation']) ? intval($_GET['id_prestation']) : 0;
$erreur       = "";
$succes       = "";

// Mode test — inchangé
$mode_test             = true;
$solde_test_simulation = 15000;

// Chargement prestation — SQL inchangé
$prestation = null;
if ($id_prestation > 0 && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.id AS id_coiffeur, u.nom AS nom_coiffeur, u.prenom AS prenom_coiffeur,
                   v.nom_ville, q.nom_quartier
            FROM prestations p
            JOIN users u ON p.id_coiffeur = u.id
            LEFT JOIN villes v ON u.ville = v.id
            LEFT JOIN quartiers q ON u.id_quartier = q.id
            WHERE p.id_prestation = ?
        ");
        $stmt->execute([$id_prestation]);
        $prestation = $stmt->fetch();
    } catch (Exception $e) {
        $erreur = "Erreur lors du chargement de la prestation.";
    }
}

// Solde client — inchangé
$solde_client = $solde_test_simulation;
if (!$mode_test && isset($pdo)) {
    $stmt_wallet = $pdo->prepare("SELECT solde FROM portefeuilles WHERE user_id = ?");
    $stmt_wallet->execute([$id_client]);
    $wallet = $stmt_wallet->fetch();
    if ($wallet) $solde_client = $wallet['solde'];
}

// Barrière financière — inchangée
$prix_prestation = $prestation['prix'] ?? 0;
if ($prestation && $solde_client < $prix_prestation) {
    $erreur = '🚨 Solde insuffisant ! Votre solde actuel est de '
            . number_format($solde_client, 0, ',', ' ')
            . ' FCFA, alors que cette prestation coûte '
            . number_format($prix_prestation, 0, ',', ' ')
            . ' FCFA. Veuillez recharger votre portefeuille.';
}

// Traitement POST — SQL inchangé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erreur)) {
    $id_prestation_post = intval($_POST['id_prestation']);
    $date_rdv           = trim($_POST['date_rdv']);
    $heure_rdv          = trim($_POST['heure_rdv']);
    $id_coiffeur        = $prestation['id_coiffeur'];

    if (empty($date_rdv) || empty($heure_rdv)) {
        $erreur = "Veuillez sélectionner un jour et une heure.";
    } elseif (isset($pdo)) {
        try {
            $pdo->prepare("
                INSERT INTO rendez_vous (id_client, id_coiffeur, id_prestation, date_rdv, heure_rdv, statut_rdv, date_creation)
                VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())
            ")->execute([$id_client, $id_coiffeur, $id_prestation_post, $date_rdv, $heure_rdv]);

            if (!$mode_test) {
                $nouveau_solde = $solde_client - $prix_prestation;
                $pdo->prepare("UPDATE portefeuilles SET solde = ? WHERE user_id = ?")->execute([$nouveau_solde, $id_client]);
                $solde_client = $nouveau_solde;
            } else {
                $solde_client -= $prix_prestation;
            }

            $succes = number_format($prix_prestation, 0, ',', ' ');
        } catch (Exception $e) {
            $erreur = "Erreur technique lors de la réservation : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prendre rendez-vous — Coiffe Chez Toi</title>

    <!-- Design System v2.0 -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Styles spécifiques à creer_rendezvous.php — tous var(--*) DS ── */
    .rdv-page       { padding-top:calc(var(--navbar-height) + 2rem); padding-bottom:calc(var(--bottomnav-height) + 2rem); min-height:100vh; background:var(--dark); }
    .rdv-page-title { font-family:var(--font-display); font-size:clamp(1.4rem,3vw,1.8rem); font-weight:700; color:var(--text-primary); text-align:center; letter-spacing:1px; margin-bottom:2rem; }

    /* Récap prestation */
    .prestation-recap { background:var(--glass-bg); border:1px solid rgba(212,175,55,0.25); border-radius:var(--radius-lg); padding:1.25rem 1.5rem; margin-bottom:2rem; }
    .prestation-name  { font-family:var(--font-display); font-size:1rem; font-weight:700; color:var(--gold); margin-bottom:4px; }
    .prestation-coiff { font-size:.82rem; color:var(--text-muted); }
    .prestation-prix  { font-size:1.25rem; font-weight:800; color:var(--gold); white-space:nowrap; }
    .prestation-solde { font-size:.78rem; color:var(--success-text); text-align:right; margin-top:6px; }

    /* Sélecteur de jours */
    .days-slider      { display:flex; gap:10px; overflow-x:auto; padding:4px 0 8px; scrollbar-width:none; }
    .days-slider::-webkit-scrollbar { display:none; }
    .day-card         { background:var(--dark-2); border:1px solid var(--glass-border); border-radius:var(--radius-md); min-width:72px; height:82px; display:flex; flex-direction:column; justify-content:center; align-items:center; cursor:pointer; transition:var(--transition-base); flex-shrink:0; }
    .day-card:hover   { border-color:var(--gold); }
    .day-card.selected{ background:var(--gold) !important; border-color:var(--gold); color:var(--black) !important; transform:scale(1.05); }
    .day-name         { font-size:.75rem; color:var(--text-muted); margin-bottom:2px; }
    .day-card.selected .day-name { color:var(--black); }
    .day-num          { font-size:1.3rem; font-weight:800; }

    /* Grille d'heures */
    .hours-grid       { display:grid; grid-template-columns:repeat(auto-fill,minmax(78px,1fr)); gap:10px; padding-top:8px; }
    .hour-pill        { background:var(--dark-2); border:1px solid var(--glass-border); color:var(--text-primary); height:42px; border-radius:var(--radius-pill); display:flex; justify-content:center; align-items:center; font-weight:700; font-size:.82rem; cursor:pointer; transition:var(--transition-fast); }
    .hour-pill:hover  { border-color:var(--gold); color:var(--gold); }
    .hour-pill.selected { background:var(--text-primary) !important; color:var(--black) !important; border-color:var(--text-primary); box-shadow:0 0 12px rgba(255,255,255,0.2); }

    /* Bloc succès */
    .success-block    { background:var(--success-bg); border:1px solid var(--success-border); border-radius:var(--radius-lg); padding:1.5rem; }
    .freeze-block     { background:var(--dark-3); border:1px solid rgba(212,175,55,0.20); border-radius:var(--radius-md); padding:12px 16px; margin:1rem 0; }

    /* Étape label */
    .step-label       { font-size:.72rem; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--gold); border-bottom:1px solid var(--glass-border); padding-bottom:6px; margin-bottom:14px; margin-top:4px; }
    </style>
</head>
<body>

<!-- Navbar -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<?php if (!empty($erreur) && !$prestation):
    // Erreur préstation introuvable — Toast + vide
    $toast_type = 'danger'; $toast_message = 'Prestation introuvable. Veuillez sélectionner une coiffure valide.';
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<main class="rdv-page page-transition" id="main-content">
    <div class="container" style="max-width:680px;">

        <!-- Lien retour -->
        <div class="mb-4">
            <a href="javascript:history.back()"
               class="btn-ghost btn-sm d-inline-flex align-items-center gap-1"
               aria-label="Retour">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour
            </a>
        </div>

        <h1 class="rdv-page-title">
            <i class="bi bi-clock-history me-2" style="color:var(--gold);" aria-hidden="true"></i>
            PRENDRE RENDEZ-VOUS
        </h1>

        <!-- Toast erreur -->
        <?php if (!empty($erreur) && $prestation):
            $toast_type = 'danger'; $toast_message = htmlspecialchars($erreur); $toast_duration = 8000;
            include __DIR__ . '/../views/components/toast.php';
        endif; ?>

        <!-- Succès -->
        <?php if (!empty($succes)): ?>
            <div class="success-block fade-in-card">
                <h5 style="color:var(--success-text);font-weight:700;margin-bottom:.75rem;">
                    <i class="bi bi-send-check-fill me-2" aria-hidden="true"></i>
                    Demande envoyée au coiffeur !
                </h5>
                <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.75rem;">
                    Le professionnel a été notifié de votre demande pour le créneau choisi.
                </p>
                <div class="freeze-block">
                    <span style="color:var(--gold);font-weight:700;display:block;margin-bottom:4px;">
                        <i class="bi bi-shield-lock-fill me-1" aria-hidden="true"></i>
                        Sécurisation des fonds
                    </span>
                    <span style="font-size:1.2rem;font-weight:800;color:var(--text-primary);">
                        <?= $succes ?> FCFA
                    </span>
                    ont été temporairement <strong>gelés</strong> sur votre portefeuille.
                </div>
                <small style="color:var(--text-muted);font-size:.75rem;display:block;margin-bottom:1.25rem;">
                    * Si le coiffeur décline ou ne répond pas, ce montant sera instantanément libéré.
                </small>
                <a href="/coiffons/client/mes_rendezvous.php"
                   class="btn-gold w-100"
                   style="padding:12px;display:flex;justify-content:center;gap:8px;">
                    <i class="bi bi-calendar-check" aria-hidden="true"></i>
                    Voir mes réservations
                </a>
            </div>

        <?php elseif ($prestation && empty($erreur) || ($prestation && $solde_client >= $prix_prestation)): ?>

            <!-- Récapitulatif prestation -->
            <div class="prestation-recap">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="prestation-name"><?= htmlspecialchars($prestation['nom_style']) ?></div>
                        <div class="prestation-coiff">
                            <i class="bi bi-person-fill" style="color:var(--gold);" aria-hidden="true"></i>
                            <?= htmlspecialchars(strtoupper($prestation['prenom_coiffeur'] . ' ' . $prestation['nom_coiffeur'])) ?>
                        </div>
                        <?php if (!empty($prestation['nom_quartier'])): ?>
                            <div class="prestation-coiff">
                                <i class="bi bi-geo-alt" style="color:var(--gold);" aria-hidden="true"></i>
                                <?= htmlspecialchars($prestation['nom_quartier']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="prestation-prix">
                        <?= number_format($prix_prestation, 0, ',', ' ') ?> FCFA
                    </span>
                </div>
                <div class="prestation-solde">
                    <i class="bi bi-wallet2 me-1" aria-hidden="true"></i>
                    Solde disponible :
                    <strong><?= number_format($solde_client, 0, ',', ' ') ?> FCFA</strong>
                    <?php if ($mode_test): ?>
                        <span style="color:var(--text-disabled);font-size:.7rem;">(simulé)</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulaire de sélection — logique PHP/JS inchangée -->
            <form method="POST" id="formReservation" novalidate>
                <input type="hidden" name="id_prestation" value="<?= $prestation['id_prestation'] ?>">
                <input type="hidden" name="date_rdv"      id="input_date_rdv" required>
                <input type="hidden" name="heure_rdv"     id="input_heure_rdv" required>

                <!-- Étape 1 : Choisir le jour -->
                <div class="step-label">Étape 1 — Choisissez le jour</div>
                <div class="days-slider mb-4" role="group" aria-label="Sélection du jour">
                    <?php
                    $jours_semaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
                    for ($i = 0; $i < 7; $i++):
                        $timestamp = strtotime("+$i days");
                        $date_val  = date('Y-m-d', $timestamp);
                        $nom_jour  = $jours_semaine[date('w', $timestamp)];
                        $num_jour  = date('d', $timestamp);
                    ?>
                        <div class="day-card"
                             data-date="<?= $date_val ?>"
                             onclick="selectDay(this)"
                             role="button"
                             tabindex="0"
                             onkeypress="if(event.key==='Enter')selectDay(this)"
                             aria-label="<?= $nom_jour ?> <?= $num_jour ?>">
                            <span class="day-name"><?= $nom_jour ?></span>
                            <span class="day-num"><?= $num_jour ?></span>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Étape 2 : Choisir l'heure (masquée jusqu'à sélection du jour) -->
                <div id="section_heures" style="display:none;">
                    <div class="step-label">Étape 2 — Choisissez l'heure</div>
                    <div class="hours-grid mb-4" role="group" aria-label="Sélection de l'heure">
                        <?php
                        $heures_dispos = ['08:00','09:30','11:00','12:30','14:00','15:30','17:00','18:30','20:00'];
                        foreach ($heures_dispos as $h):
                        ?>
                            <div class="hour-pill"
                                 data-time="<?= $h ?>"
                                 onclick="selectHour(this)"
                                 role="button"
                                 tabindex="0"
                                 onkeypress="if(event.key==='Enter')selectHour(this)"
                                 aria-label="<?= $h ?>">
                                <?= $h ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Bouton submit (masqué jusqu'à sélection complète) -->
                <button type="submit"
                        id="btnValider"
                        class="btn-gold w-100"
                        style="display:none;padding:14px;font-size:.95rem;"
                        aria-label="Confirmer le rendez-vous">
                    <i class="bi bi-shield-lock-fill me-2" aria-hidden="true"></i>
                    Bloquer ce créneau &amp; Réserver
                </button>

            </form>

        <?php elseif ($prestation && !empty($erreur)): ?>
            <!-- Erreur solde insuffisant -->
            <?php
            $es = [
                'icon'      => 'bi-wallet-fill',
                'message'   => $erreur,
                'cta_label' => 'Retour à mes réservations',
                'cta_href'  => '/coiffons/client/mes_rendezvous.php',
            ];
            include __DIR__ . '/../views/components/empty_state.php';
            ?>
        <?php endif; ?>

    </div>
</main>

<!-- Footer + Bottom Nav -->
<?php
include __DIR__ . '/../views/components/footer_global.php';
$current_page = 'creer_rendezvous.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- JS UI — logique de sélection inchangée -->
<script>
function selectDay(element) {
    document.querySelectorAll('.day-card').forEach(c => c.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('input_date_rdv').value = element.getAttribute('data-date');
    document.getElementById('section_heures').style.display = 'block';
    verifierFormulaire();
}
function selectHour(element) {
    document.querySelectorAll('.hour-pill').forEach(c => c.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('input_heure_rdv').value = element.getAttribute('data-time');
    verifierFormulaire();
}
function verifierFormulaire() {
    const d = document.getElementById('input_date_rdv').value;
    const h = document.getElementById('input_heure_rdv').value;
    document.getElementById('btnValider').style.display = (d && h) ? 'flex' : 'none';
}
</script>

</body>
</html>
