<?php
/**
 * client/mes_rendezvous.php — Liste et gestion des rendez-vous client
 * Migration Design System v2.0 — Parcours Client — Page 2
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — Seuls HTML/CSS/composants ont été modifiés.
 *     SQL, CSRF, calculs d'annulation, transactions BDD : strictement inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    echo "<script>window.location.href='/coiffons/index.php?page=login';</script>";
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../security/config.php';

$id_client     = $_SESSION['id_user'];
$message_flash = null;

// ── ROBOT D'ANNULATION — logique métier inchangée ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_annuler'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message_flash = ['type' => 'danger', 'text' => 'Erreur de sécurité (CSRF invalide).'];
    } else {
        $id_rdv = intval($_POST['id_rdv']);
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("SELECT r.*, p.prix FROM rendez_vous r JOIN prestations p ON r.coiffure_id = p.id_prestation WHERE r.id = ? AND r.client_id = ?");
                $stmt->execute([$id_rdv, $id_client]);
                $rdv = $stmt->fetch();

                if ($rdv) {
                    $date_heure_rdv  = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_debut']);
                    $maintenant      = new DateTime();
                    $interval        = $maintenant->diff($date_heure_rdv);
                    $heures_restantes = ($interval->days * 24) + $interval->h;
                    if ($interval->invert) $heures_restantes = -1;

                    $prix_total             = $rdv['prix'];
                    $remboursement_client   = 0;
                    $dedommagement_coiffeur = 0;
                    $cas_regle              = "";

                    if ($heures_restantes >= 24) {
                        $remboursement_client = $prix_total;
                        $cas_regle = "Plus de 24h à l'avance : Remboursement intégral (100%).";
                    } elseif ($heures_restantes >= 2 && $heures_restantes < 24) {
                        $remboursement_client   = $prix_total * 0.5;
                        $dedommagement_coiffeur = $prix_total * 0.5;
                        $cas_regle = "Moins de 24h : Remboursement de 50%. Les 50% restants reviennent au coiffeur.";
                    } else {
                        $dedommagement_coiffeur = $prix_total;
                        $cas_regle = "Dernière minute ou RDV passé : Aucun remboursement.";
                    }

                    $pdo->beginTransaction();
                    $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'annule' WHERE id = ?")->execute([$id_rdv]);
                    if ($remboursement_client > 0)   $pdo->prepare("UPDATE users SET solde = solde + ? WHERE id = ?")->execute([$remboursement_client, $id_client]);
                    if ($dedommagement_coiffeur > 0) $pdo->prepare("UPDATE users SET solde = solde + ? WHERE id = ?")->execute([$dedommagement_coiffeur, $rdv['coiffeur_id']]);
                    $pdo->commit();

                    $message_flash = [
                        'type' => 'success',
                        'text' => 'Rendez-vous annulé. ' . $cas_regle . ' Recrédité : ' . number_format($remboursement_client, 0, ',', ' ') . ' FCFA.',
                    ];
                } else {
                    $message_flash = ['type' => 'danger', 'text' => 'Rendez-vous introuvable.'];
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message_flash = ['type' => 'danger', 'text' => 'Une erreur technique est survenue.'];
            }
        }
    }
}

// ── Récupération des RDV — SQL inchangé ──
$rendezvous = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT r.*, p.nom_style, p.prix, u.nom AS coiffeur_nom, u.prenom AS coiffeur_prenom 
                               FROM rendez_vous r 
                               JOIN prestations p ON r.coiffure_id = p.id_prestation 
                               JOIN users u ON r.coiffeur_id = u.id 
                               WHERE r.client_id = ? 
                               ORDER BY r.date_rdv DESC, r.heure_debut DESC");
        $stmt->execute([$id_client]);
        $rendezvous = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// Helper local pour le badge statut
function badge_rdv(string $statut): string {
    return match($statut) {
        'en_attente'         => '<span class="badge-pending">En attente</span>',
        'confirme','accepte' => '<span class="badge-confirmed">Confirmé</span>',
        'termine'            => '<span class="badge-done">Terminé</span>',
        default              => '<span class="badge-cancelled">Annulé</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations — Coiffe Chez Toi</title>

    <!-- Design System v2.0 -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Styles spécifiques à mes_rendezvous.php ── */
    .rdv-page           { padding-top:calc(var(--navbar-height) + 2rem); padding-bottom:calc(var(--bottomnav-height) + 2rem); min-height:100vh; background:var(--dark); }
    .rdv-page-title     { font-family:var(--font-display); font-size:clamp(1.4rem,3vw,2rem); font-weight:700; color:var(--text-primary); text-align:center; letter-spacing:2px; margin-bottom:2rem; }

    /* Table dark DS §16 */
    .rdv-table-wrap     { border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--glass-border); }

    /* Carte RDV mobile */
    .rdv-card           { background:var(--dark-2); border:1px solid rgba(212,175,55,0.25); border-radius:var(--radius-lg); padding:1rem 1.25rem; margin-bottom:12px; }
    .rdv-card-date      { color:var(--gold); font-weight:700; font-size:.88rem; }
    .rdv-card-style     { font-weight:700; color:var(--text-primary); font-size:.9rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .rdv-card-prix      { color:var(--success-text); font-weight:700; font-size:.85rem; }

    /* Modal DS règles annulation */
    .rules-block        { background:var(--dark-3); border-radius:var(--radius-md); padding:12px 14px; margin-bottom:8px; border-left:3px solid; }
    .rules-block.success{ border-color:var(--success-text); }
    .rules-block.warning{ border-color:var(--warning-color); }
    .rules-block.danger { border-color:var(--danger-icon); }
    .countdown-box      { background:var(--dark-2); border:1px solid var(--glass-border); border-radius:var(--radius-md); padding:12px; text-align:center; margin-bottom:1rem; }
    </style>
</head>
<body>

<!-- Navbar -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<!-- Toast message flash -->
<?php if ($message_flash):
    $toast_type    = $message_flash['type'];
    $toast_message = $message_flash['text'];
    $toast_duration = 6000;
    include __DIR__ . '/../views/components/toast.php';
endif; ?>

<main class="rdv-page page-transition" id="main-content">
    <div class="container" style="max-width:960px;">

        <h1 class="rdv-page-title">MES RÉSERVATIONS</h1>

        <?php if (empty($rendezvous)): ?>
            <!-- Empty State DS §17 -->
            <?php
            $es = [
                'icon'      => 'bi-calendar-x',
                'message'   => 'Vous n\'avez aucun rendez-vous enregistré.',
                'cta_label' => 'Trouver un coiffeur',
                'cta_href'  => '/coiffons/filter/annuaire_coiffeurs.php',
            ];
            include __DIR__ . '/../views/components/empty_state.php';
            ?>
        <?php else: ?>

            <!-- ── Tableau desktop — DataTable DS §16 ── -->
            <div class="d-none d-md-block mb-4">
                <div class="rdv-table-wrap">
                    <table class="table-dark-cct" style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>Date &amp; Heure</th>
                                <th>Prestation</th>
                                <th>Coiffeur</th>
                                <th>Statut</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rendezvous as $rdv): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></strong><br>
                                        <span style="color:var(--gold);font-family:monospace;font-size:.82rem;">
                                            <?= substr($rdv['heure_debut'], 0, 5) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight:700;"><?= htmlspecialchars($rdv['nom_style']) ?></span><br>
                                        <small style="color:var(--success-text);font-weight:700;">
                                            <?= number_format($rdv['prix'], 0, ',', ' ') ?> FCFA
                                        </small>
                                    </td>
                                    <td style="color:var(--text-secondary);">
                                        <?= htmlspecialchars(strtoupper($rdv['coiffeur_nom'] . ' ' . $rdv['coiffeur_prenom'])) ?>
                                    </td>
                                    <td><?= badge_rdv($rdv['statut_rdv']) ?></td>
                                    <td style="text-align:right;">
                                        <button class="btn-outline-gold btn-sm"
                                                onclick="openModal('modal-rdv-<?= $rdv['id'] ?>')"
                                                aria-label="Gérer le RDV du <?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?>">
                                            <i class="bi bi-gear me-1" aria-hidden="true"></i>Gérer
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── Cartes mobile ── -->
            <div class="d-md-none mb-4">
                <?php foreach ($rendezvous as $rdv): ?>
                    <div class="rdv-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="rdv-card-date">
                                <?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?> —
                                <?= substr($rdv['heure_debut'], 0, 5) ?>
                            </span>
                            <?= badge_rdv($rdv['statut_rdv']) ?>
                        </div>
                        <div class="rdv-card-style mb-1"><?= htmlspecialchars($rdv['nom_style']) ?></div>
                        <div class="rdv-card-prix mb-3"><?= number_format($rdv['prix'], 0, ',', ' ') ?> FCFA</div>
                        <button class="btn-outline-gold btn-sm w-100"
                                onclick="openModal('modal-rdv-<?= $rdv['id'] ?>')"
                                aria-label="Gérer le RDV">
                            <i class="bi bi-gear me-1" aria-hidden="true"></i>Gérer la réservation
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

        <!-- CTA retour -->
        <div class="text-center mt-4">
            <a href="/coiffons/filter/annuaire_coiffeurs.php"
               class="btn-ghost btn-sm d-inline-flex align-items-center gap-2">
                <i class="bi bi-scissors" aria-hidden="true"></i>
                Trouver un nouveau coiffeur
            </a>
        </div>

    </div>
</main>

<!-- ══════════════════════════════════════════
     MODALES — une par RDV (Modal CL §18)
     ══════════════════════════════════════════ -->
<?php foreach ($rendezvous as $rdv):
    $dt_rdv  = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_debut']);
    $dt_now  = new DateTime();
    $diff    = $dt_now->diff($dt_rdv);
    $total_heures = ($diff->days * 24) + $diff->h;
    if ($diff->invert) $total_heures = -1;

    ob_start(); ?>
        <!-- Infos RDV -->
        <div class="mb-4">
            <div style="border-bottom:1px solid var(--glass-border);padding-bottom:10px;margin-bottom:10px;">
                <small style="color:var(--text-muted);display:block;">Style choisi</small>
                <span style="font-weight:700;color:var(--gold);"><?= htmlspecialchars($rdv['nom_style']) ?></span>
            </div>
            <div style="border-bottom:1px solid var(--glass-border);padding-bottom:10px;margin-bottom:10px;">
                <small style="color:var(--text-muted);display:block;">Artisan Coiffeur</small>
                <span><?= htmlspecialchars(strtoupper($rdv['coiffeur_nom'] . ' ' . $rdv['coiffeur_prenom'])) ?></span>
            </div>
            <div style="border-bottom:1px solid var(--glass-border);padding-bottom:10px;margin-bottom:10px;">
                <small style="color:var(--text-muted);display:block;">Date &amp; Heure</small>
                <span><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?> à <?= substr($rdv['heure_debut'], 0, 5) ?></span>
            </div>
            <div>
                <small style="color:var(--text-muted);display:block;">Montant (gelé)</small>
                <span style="color:var(--success-text);font-weight:700;font-size:1.1rem;">
                    <?= number_format($rdv['prix'], 0, ',', ' ') ?> FCFA
                </span>
            </div>
        </div>

        <!-- Bouton annuler — affiché seulement si RDV annulable -->
        <?php if (!in_array($rdv['statut_rdv'], ['annule', 'termine'])): ?>
            <div id="body-principal-<?= $rdv['id'] ?>">
                <button type="button"
                        class="btn-danger-cct w-100"
                        style="padding:12px;"
                        onclick="afficherPrevention(<?= $rdv['id'] ?>)">
                    <i class="bi bi-x-circle me-2" aria-hidden="true"></i>
                    Annuler ce rendez-vous
                </button>
            </div>

            <!-- Zone de prévention -->
            <div id="prevention-box-<?= $rdv['id'] ?>" class="d-none">
                <h6 style="text-align:center;color:var(--danger-icon);font-weight:700;margin-bottom:1rem;">
                    <i class="bi bi-shield-exclamation me-1" aria-hidden="true"></i>
                    RÈGLES DE DÉSISTEMENT
                </h6>

                <div class="rules-block success mb-2">
                    <strong style="color:var(--success-text);">1. Plus de 24h à l'avance :</strong><br>
                    <small style="color:var(--text-muted);">Remboursement intégral (100%).</small>
                </div>
                <div class="rules-block warning mb-2">
                    <strong style="color:var(--warning-color);">2. Entre 2h et 24h :</strong><br>
                    <small style="color:var(--text-muted);">50% remboursé. 50% au coiffeur.</small>
                </div>
                <div class="rules-block danger mb-3">
                    <strong style="color:var(--danger-icon);">3. Moins de 2h :</strong><br>
                    <small style="color:var(--text-muted);">Aucun remboursement.</small>
                </div>

                <div class="countdown-box">
                    <small style="color:var(--text-muted);display:block;margin-bottom:4px;">Temps restant :</small>
                    <?php if ($total_heures >= 0): ?>
                        <span style="font-size:1.4rem;font-weight:800;color:var(--text-primary);font-family:monospace;">
                            <?= $total_heures ?> heure<?= $total_heures > 1 ? 's' : '' ?>
                        </span>
                    <?php else: ?>
                        <span style="color:var(--danger-icon);font-weight:700;">Rendez-vous imminent ou passé</span>
                    <?php endif; ?>
                </div>

                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id_rdv"    value="<?= $rdv['id'] ?>">
                    <button type="button"
                            class="btn-ghost w-50"
                            onclick="annulerPrevention(<?= $rdv['id'] ?>)">
                        Retour
                    </button>
                    <button type="submit"
                            name="action_annuler"
                            class="btn-danger-cct w-50"
                            style="padding:10px;">
                        Confirmer l'annulation
                    </button>
                </form>
            </div>
        <?php else: ?>
            <p style="color:var(--text-muted);font-size:.82rem;text-align:center;margin-top:.5rem;">
                Ce rendez-vous est <?= $rdv['statut_rdv'] === 'termine' ? 'terminé' : 'annulé' ?> et ne peut plus être modifié.
            </p>
        <?php endif; ?>

    <?php $modal_content = ob_get_clean();

    $modal_id    = 'modal-rdv-' . $rdv['id'];
    $modal_title = 'Rendez-vous — ' . date('d/m/Y', strtotime($rdv['date_rdv']));
    $modal_width = '520px';
    include __DIR__ . '/../views/components/modal.php';
endforeach; ?>

<!-- Footer + Bottom Nav -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../views/components/footer_global.php';
$current_page = 'mes_rendezvous.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function afficherPrevention(id) {
    document.getElementById('body-principal-' + id).classList.add('d-none');
    document.getElementById('prevention-box-' + id).classList.remove('d-none');
}
function annulerPrevention(id) {
    document.getElementById('prevention-box-' + id).classList.add('d-none');
    document.getElementById('body-principal-' + id).classList.remove('d-none');
}
</script>

</body>
</html>
