<?php
/**
 * coiffeurs/profil_public.php — Profil public d'un coiffeur
 * Refonte UX/UI — Parcours de réservation premium
 *
 * Nouveau flux :
 *   1. Présentation coiffeur
 *   2. Catalogue prestations (sélection visuelle)
 *   3. Disponibilités (jours + créneaux, après sélection prestation)
 *   4. Avis clients
 *
 * LOGIQUE MÉTIER INTACTE — SQL, sessions, sécurité : inchangés.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

$est_connecte = isset($_SESSION['user_id']) && !empty($_SESSION['role']) && $_SESSION['role'] !== 'invite';

$prestataire_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
            ?? filter_input(INPUT_GET, 'prestataire_id', FILTER_VALIDATE_INT);

if (!$prestataire_id) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='background:#0a0a0a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;'><div style='text-align:center'><p style='color:rgba(255,255,255,0.4);'>Coiffeur introuvable.</p><a href='/coiffons/index.php' style='color:#D4AF37;'>Retour à l'accueil</a></div></body></html>";
    exit();
}

try {
    // Coiffeur — SQL inchangé
    $stmt = $pdo->prepare("
        SELECT u.*, v.nom_ville, q.nom_quartier, pp.abonnement_status
        FROM users u
        LEFT JOIN profils_prestataires pp ON pp.user_id = u.id
        LEFT JOIN villes v ON u.id_ville = v.id
        LEFT JOIN quartiers q ON u.id_quartier = q.id
        WHERE u.id = ? AND LOWER(u.role) = 'coiffeur'
        AND (pp.abonnement_status = 1 OR LOWER(pp.abonnement_status) = 'actif')
    ");
    $stmt->execute([$prestataire_id]);
    $coiffeur = $stmt->fetch();

    if (!$coiffeur) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='background:#0a0a0a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;'><div style='text-align:center'><p style='color:rgba(255,255,255,0.4);'>Ce coiffeur n'est pas disponible.</p><a href='/coiffons/index.php' style='color:#D4AF37;'>Retour</a></div></body></html>";
        exit();
    }

    // Prestations — SQL inchangé
    $stmt_presta = $pdo->prepare("SELECT * FROM services WHERE id_prestataire = ? ORDER BY id_service ASC");
    $stmt_presta->execute([$prestataire_id]);
    $prestations = $stmt_presta->fetchAll();

    // Avis — SQL inchangé
    try {
        $stmt_avis = $pdo->prepare("
            SELECT a.*, u.nom as client_nom, u.prenom as client_prenom
            FROM avis a
            JOIN users u ON a.client_id = u.id
            WHERE a.prestataire_id = ? AND a.type = 'public'
            ORDER BY a.date_creation DESC LIMIT 5
        ");
        $stmt_avis->execute([$prestataire_id]);
        $avis_publics = $stmt_avis->fetchAll();
    } catch (Exception $e) { $avis_publics = []; }

    // Note moyenne — SQL inchangé
    try {
        $stmt_note = $pdo->prepare("SELECT AVG(note) as moy, COUNT(*) as nb FROM avis WHERE prestataire_id = ? AND type = 'public'");
        $stmt_note->execute([$prestataire_id]);
        $stats_note   = $stmt_note->fetch();
        $note_moyenne = $stats_note['nb'] > 0 ? round($stats_note['moy'], 1) : null;
        $stats_avis   = ['total' => intval($stats_note['nb'])];
    } catch (Exception $e) { $note_moyenne = null; $stats_avis = ['total' => 0]; }

    // Disponibilités — SQL inchangé
    $stmt_dispo = $pdo->prepare("SELECT * FROM disponibilites WHERE prestataire_id = ?");
    $stmt_dispo->execute([$prestataire_id]);
    $planning_coiffeur = [];
    foreach ($stmt_dispo->fetchAll() as $d) {
        $planning_coiffeur[strtolower(trim($d['jour_semaine']))] = [
            'debut' => $d['heure_debut'],
            'fin'   => $d['heure_fin'],
        ];
    }

    // Créneaux occupés — SQL inchangé
    $stmt_rdv = $pdo->prepare("
        SELECT date_rdv, heure_debut FROM rendez_vous
        WHERE prestataire_id = ? AND statut_rdv IN ('en_attente','accepte','confirme')
    ");
    $stmt_rdv->execute([$prestataire_id]);
    $busy_slots = [];
    foreach ($stmt_rdv->fetchAll() as $r) {
        $busy_slots[$r['date_rdv'] . ' ' . substr($r['heure_debut'], 0, 5)] = true;
    }

} catch (Exception $e) {
    echo "<div style='background:#0a0a0a;color:#fff;padding:40px;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

// Photo coiffeur
$ps_photo_url = null;
if (!empty($coiffeur['photo_profil'])) {
    foreach ([__DIR__ . '/../access/' . $coiffeur['photo_profil'],
              __DIR__ . '/../uploads/profil/' . basename($coiffeur['photo_profil'])] as $cp) {
        if (file_exists($cp)) { $ps_photo_url = '/coiffons/access/' . $coiffeur['photo_profil']; break; }
    }
}

// Photo de bannière - prendre une photo de style ou photo profil
$banner_photo_url = null;
if (!empty($prestations)) {
    foreach ($prestations as $p) {
        if (!empty($p['photo']) && file_exists(__DIR__ . '/../' . $p['photo'])) {
            $banner_photo_url = '/coiffons/' . $p['photo'];
            break;
        }
    }
}
// Fallback: utiliser la photo de profil comme bannière
if (!$banner_photo_url) {
    $banner_photo_url = $ps_photo_url;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($coiffeur['nom']) ?> <?= htmlspecialchars($coiffeur['prenom'] ?? '') ?> — Coiffe Chez Toi</title>
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    /* ── Profil public — styles DS v2.0 ── */
    .pp-section        { padding: 3.5rem 0; }
    .pp-section--alt   { background: var(--dark-2); }
    .pp-section--dark3 { background: var(--dark-3); }
    .pp-wrap           { max-width: var(--max-width); margin: 0 auto; padding: 0 1.5rem; }
    .section-eyebrow   { font-size:.68rem; font-weight:700; letter-spacing:3px; text-transform:uppercase; color:var(--gold); display:block; margin-bottom:.4rem; }
    .section-heading   { font-family:var(--font-display); font-size:clamp(1.2rem,2.5vw,1.6rem); font-weight:700; color:var(--text-primary); margin-bottom:.4rem; }
    .section-sub       { color:var(--text-muted); font-size:.82rem; }

    /* ── Bannière Hero avec photo ── */
    .profile-hero-banner {
        position: relative;
        height: 320px;
        background: linear-gradient(135deg, var(--dark-3), var(--dark-4));
        overflow: hidden;
        margin-top: var(--navbar-height);
    }
    .profile-hero-bg {
        position: absolute;
        inset: 0;
        background-size: cover;
        background-position: center;
        opacity: 0.35;
        filter: blur(1px);
    }
    .profile-hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(10,10,10,0.2) 0%, rgba(10,10,10,0.85) 100%);
    }
    .profile-hero-content {
        position: relative;
        height: 100%;
        display: flex;
        align-items: flex-end;
        padding: 2rem 0 1.5rem;
    }

    /* ── Carte prestation sélectionnable ── */
    .presta-card {
        background: var(--dark-2);
        border: 2px solid transparent;
        border-radius: var(--radius-lg);
        overflow: hidden;
        cursor: pointer;
        transition: var(--transition-spring), border-color .25s, box-shadow .25s;
        height: 100%;
        display: flex; flex-direction: column;
        position: relative;
    }
    .presta-card:hover {
        border-color: rgba(212,175,55,.35);
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
    }
    .presta-card.selected {
        border-color: var(--gold);
        box-shadow: 0 0 0 3px rgba(212,175,55,.18), var(--shadow-lg);
    }
    .presta-card-check {
        position: absolute; top: 10px; right: 10px;
        width: 28px; height: 28px; border-radius: 50%;
        background: var(--gold); color: #000;
        display: none; align-items: center; justify-content: center;
        font-size: .9rem; font-weight: 800;
        box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    }
    .presta-card.selected .presta-card-check { display: flex; }
    .presta-img { width:100%; height:180px; object-fit:cover; display:block; }
    .presta-img-ph {
        width:100%; height:180px;
        background: linear-gradient(135deg, var(--dark-3), var(--dark-4));
        display:flex; align-items:center; justify-content:center;
        color:rgba(255,255,255,.06); font-size:2.5rem;
    }
    .presta-body { padding: 1rem 1.1rem; flex:1; display:flex; flex-direction:column; }
    .presta-name { font-weight:700; font-size:.92rem; color:var(--text-primary); margin-bottom:4px; }
    .presta-desc { font-size:.75rem; color:var(--text-muted); line-height:1.5; margin-bottom:8px; flex:1; }
    .presta-footer {
        display:flex; align-items:center; justify-content:space-between;
        padding: 8px 1.1rem 1rem;
    }
    .presta-prix { font-size:1rem; font-weight:800; color:var(--gold); }
    .presta-duree { font-size:.72rem; color:var(--text-muted); }

    /* ── Section disponibilités (masquée par défaut) ── */
    #section-disponibilites {
        display: none;
        padding: 3rem 0;
        background: var(--dark-3);
        border-top: 1px solid var(--glass-border);
        animation: fadeSlideIn .35s ease;
    }
    @keyframes fadeSlideIn { from {opacity:0;transform:translateY(10px)} to {opacity:1;transform:translateY(0)} }

    /* Prestation sélectionnée — mini-recap dans la section dispo */
    .dispo-presta-recap {
        background: rgba(212,175,55,.07);
        border: 1px solid rgba(212,175,55,.25);
        border-radius: var(--radius-md);
        padding: 10px 16px;
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 1.5rem;
    }

    /* Jours scroll */
    .days-scroll { display:flex; gap:10px; overflow-x:auto; padding-bottom:6px; scrollbar-width:none; }
    .days-scroll::-webkit-scrollbar { display:none; }

    /* Slots container */
    .pp-slots-wrap {
        background: var(--dark-2);
        border: 1px dashed rgba(212,175,55,.25);
        border-radius: var(--radius-md);
        padding: 1.25rem;
        text-align: center;
        margin-top: 1.25rem;
    }
    .pp-slots-title { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--gold); margin-bottom:.75rem; }

    /* CTA Continuer */
    #btn-continuer-wrap { display:none; margin-top:1.5rem; text-align:center; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<?php
$prenom_client = htmlspecialchars($_SESSION['prenom'] ?? '');
$photo_profil  = $_SESSION['photo_profil'] ?? null;
$nb_notifs     = 0;
$page_root     = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<!-- ════════════ BANNIÈRE HERO ════════════ -->
<section class="profile-hero-banner">
    <?php if ($banner_photo_url): ?>
        <div class="profile-hero-bg" style="background-image: url('<?= htmlspecialchars($banner_photo_url) ?>');"></div>
    <?php endif; ?>
    <div class="profile-hero-overlay"></div>
    
    <div class="profile-hero-content">
        <div class="pp-wrap w-100">
            <div class="text-start mb-3">
                <a href="javascript:history.back()" class="btn-ghost btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Retour
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ════════════ 1. PRÉSENTATION COIFFEUR ════════════ -->
<section class="pp-section" style="background:var(--dark);padding-top:2rem;" aria-labelledby="coiffeur-nom">
    <div class="pp-wrap text-center">

        <!-- Avatar -->
        <div class="mb-3">
            <?php if ($ps_photo_url): ?>
                <img src="<?= htmlspecialchars($ps_photo_url) ?>"
                     alt="Photo de <?= htmlspecialchars($coiffeur['nom']) ?>"
                     style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--gold);box-shadow:0 0 24px rgba(212,175,55,.30);">
            <?php else: ?>
                <div class="avatar-placeholder d-inline-flex"
                     style="width:90px;height:90px;font-size:2.2rem;box-shadow:0 0 24px rgba(212,175,55,.30);">
                    <?= htmlspecialchars(strtoupper(substr($coiffeur['nom'], 0, 1))) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Nom + badge certifié -->
        <h1 id="coiffeur-nom" style="font-family:var(--font-display);font-size:clamp(1.5rem,3vw,2.2rem);font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:2px;margin-bottom:.3rem;">
            <?= htmlspecialchars(strtoupper($coiffeur['nom'] . ' ' . ($coiffeur['prenom'] ?? ''))) ?>
        </h1>

        <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.75rem;">
            <i class="bi bi-geo-alt-fill" style="color:var(--gold);" aria-hidden="true"></i>
            <?= htmlspecialchars($coiffeur['nom_ville'] ?? '') ?>
            <?= !empty($coiffeur['nom_quartier']) ? ' · ' . htmlspecialchars($coiffeur['nom_quartier']) : '' ?>
        </p>

        <!-- Note -->
        <div class="mb-3">
            <?php if ($note_moyenne && $stats_avis['total'] > 0): ?>
                <span class="badge-gold" aria-label="Note : <?= $note_moyenne ?>/5, <?= $stats_avis['total'] ?> avis">
                    <?php for ($s=1;$s<=5;$s++): ?>
                        <span style="color:<?= $s<=round($note_moyenne)?'#D4AF37':'rgba(255,255,255,.3)' ?>;">★</span>
                    <?php endfor; ?>
                    <strong style="margin-left:5px;"><?= $note_moyenne ?></strong>/5
                    <span style="opacity:.7;margin-left:4px;">(<?= $stats_avis['total'] ?> avis)</span>
                </span>
            <?php else: ?>
                <span style="color:var(--text-muted);font-size:.78rem;">Aucun avis pour le moment</span>
            <?php endif; ?>
        </div>

        <!-- Bio -->
        <p style="color:var(--text-secondary);font-size:.88rem;line-height:1.7;max-width:560px;margin:0 auto;">
            <?= !empty($coiffeur['bio'])
                ? htmlspecialchars($coiffeur['bio'])
                : 'Artisan coiffeur professionnel sélectionné par notre plateforme, expert en créations capillaires haut de gamme à domicile.' ?>
        </p>
    </div>
</section>

<!-- ════════════ 2. CATALOGUE PRESTATIONS ════════════ -->
<section class="pp-section" style="background:var(--dark);" id="section-catalogue" aria-labelledby="catalogue-heading">
    <div class="pp-wrap">
        <span class="section-eyebrow">Choisissez votre style</span>
        <h2 id="catalogue-heading" class="section-heading">Prestations disponibles</h2>
        <p class="section-sub" style="margin-bottom:2rem;">Sélectionnez une prestation pour voir les disponibilités</p>

        <?php if (empty($prestations)): ?>
            <?php
            $es = ['icon'=>'bi-camera','message'=>'Ce coiffeur n\'a pas encore publié de styles.','cta_label'=>'','cta_href'=>''];
            include __DIR__ . '/../views/components/empty_state.php';
            ?>
        <?php else: ?>
        <div class="row g-4" id="prestations-grid">
            <?php foreach ($prestations as $p):
                $photo_url_p = null;
                if (!empty($p['photo']) && file_exists(__DIR__ . '/../' . $p['photo'])) {
                    $photo_url_p = '/coiffons/' . $p['photo'];
                }
                $explode_p   = explode("|||", $p['description'] ?? '');
                $desc_pure_p = $explode_p[0] ?? '';
                $json_opts_p = $explode_p[1] ?? '[]';
                $opts_p      = json_decode($json_opts_p, true) ?: [];
            ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <article class="presta-card"
                         data-presta-id="<?= (int)$p['id_service'] ?>"
                         data-presta-nom="<?= htmlspecialchars($p['nom_service']) ?>"
                         data-presta-prix="<?= (int)$p['prix'] ?>"
                         data-presta-duree="<?= (int)($p['duree'] ?? 60) ?>"
                         data-presta-options="<?= htmlspecialchars(json_encode($opts_p, JSON_UNESCAPED_UNICODE)) ?>"
                         onclick="selectionnerPrestation(this)"
                         role="button" tabindex="0"
                         onkeypress="if(event.key==='Enter')selectionnerPrestation(this)"
                         aria-label="Choisir <?= htmlspecialchars($p['nom_service']) ?> — <?= number_format($p['prix'],0,',','') ?> FCFA">

                    <!-- Checkmark sélection -->
                    <div class="presta-card-check" aria-hidden="true">
                        <i class="bi bi-check2"></i>
                    </div>

                    <!-- Image -->
                    <?php if ($photo_url_p): ?>
                        <img src="<?= htmlspecialchars($photo_url_p) ?>" class="presta-img" alt="<?= htmlspecialchars($p['nom_service']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="presta-img-ph" aria-hidden="true"><i class="bi bi-scissors"></i></div>
                    <?php endif; ?>

                    <div class="presta-body">
                        <div class="presta-name"><?= htmlspecialchars($p['nom_service']) ?></div>
                        <?php if (!empty($desc_pure_p)): ?>
                            <p class="presta-desc"><?= htmlspecialchars($desc_pure_p) ?></p>
                        <?php else: ?>
                            <div class="presta-desc"></div>
                        <?php endif; ?>
                        <?php if (!empty($opts_p)): ?>
                            <div style="font-size:.7rem;color:var(--text-muted);margin-top:2px;">
                                + options : <?= implode(', ', array_map(fn($o) => htmlspecialchars($o['nom']), $opts_p)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="presta-footer">
                        <span class="presta-prix"><?= number_format($p['prix'],0,',','') ?> FCFA</span>
                        <span class="presta-duree"><i class="bi bi-clock me-1" aria-hidden="true"></i><?= (int)($p['duree'] ?? 60) ?> min</span>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ════════════ 3. DISPONIBILITÉS (masquée jusqu'à sélection prestation) ════════════ -->
<section id="section-disponibilites" aria-labelledby="dispo-heading">
    <div class="pp-wrap">

        <!-- Mini-recap prestation choisie -->
        <div class="dispo-presta-recap" id="dispo-presta-recap">
            <i class="bi bi-scissors" style="color:var(--gold);font-size:1.1rem;flex-shrink:0;" aria-hidden="true"></i>
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:.88rem;color:var(--text-primary);" id="recap-presta-nom">—</div>
                <div style="font-size:.75rem;color:var(--text-muted);">
                    <span id="recap-presta-prix">—</span> FCFA
                    · <span id="recap-presta-duree">—</span> min
                </div>
            </div>
            <button type="button"
                    onclick="deselectionnerPrestation()"
                    class="btn-ghost btn-sm"
                    style="font-size:.72rem;padding:4px 10px;"
                    aria-label="Changer de prestation">
                Changer
            </button>
        </div>

        <span class="section-eyebrow">Quand souhaitez-vous ?</span>
        <h2 id="dispo-heading" class="section-heading">Choisissez un créneau</h2>
        <p class="section-sub" style="margin-bottom:1.5rem;">Sélectionnez un jour puis un horaire disponible</p>

        <!-- Jours disponibles -->
        <div class="days-scroll mb-2" role="list" aria-label="Jours disponibles">
            <?php
            $jours_traduction = ['monday'=>'lundi','tuesday'=>'mardi','wednesday'=>'mercredi',
                                 'thursday'=>'jeudi','friday'=>'vendredi','saturday'=>'samedi','sunday'=>'dimanche'];
            for ($i = 0; $i < 7; $i++):
                $ts        = strtotime("+{$i} days");
                $date_cle  = date('Y-m-d', $ts);
                $jour_en   = strtolower(date('l', $ts));
                $jour_fr   = $jours_traduction[$jour_en];
                $num_jour  = date('d', $ts);
                $nom_mois  = date('M', $ts);
                $travaille = isset($planning_coiffeur[$jour_fr]);
                $h_debut   = $travaille ? $planning_coiffeur[$jour_fr]['debut'] : '';
                $h_fin     = $travaille ? $planning_coiffeur[$jour_fr]['fin']   : '';
            ?>
            <div class="day-selector-card <?= $travaille ? 'day-item-open' : 'day-item-closed' ?>"
                 data-date="<?= htmlspecialchars($date_cle) ?>"
                 data-open="<?= $travaille ? '1' : '0' ?>"
                 data-start="<?= htmlspecialchars($h_debut) ?>"
                 data-end="<?= htmlspecialchars($h_fin) ?>"
                 onclick="ppSelectionnerJour(this)"
                 role="button" tabindex="0"
                 onkeypress="if(event.key==='Enter')ppSelectionnerJour(this)"
                 aria-label="<?= htmlspecialchars($jour_fr) ?> <?= $num_jour ?> <?= $nom_mois ?> — <?= $travaille ? 'disponible' : 'indisponible' ?>"
                 <?= !$travaille ? 'aria-disabled="true"' : '' ?>>
                <span class="small text-uppercase opacity-70 d-block"><?= substr($jour_fr,0,3) ?>.</span>
                <span class="fs-4 fw-bold my-1 d-block"><?= $num_jour ?></span>
                <span class="small opacity-50 d-block"><?= $nom_mois ?></span>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Créneaux horaires -->
        <div id="pp-zone-slots" class="pp-slots-wrap d-none" role="region" aria-live="polite" aria-label="Créneaux horaires">
            <div class="pp-slots-title">Heures disponibles</div>
            <div id="pp-slots-container" class="d-flex flex-wrap justify-content-center gap-2" role="list"></div>
        </div>

        <!-- Bouton Continuer (masqué jusqu'à sélection date + heure) -->
        <div id="btn-continuer-wrap">
            <div style="background:rgba(212,175,55,.07);border:1px solid rgba(212,175,55,.2);border-radius:var(--radius-md);padding:14px 18px;display:inline-flex;align-items:center;gap:12px;margin-bottom:1rem;text-align:left;">
                <i class="bi bi-calendar-check" style="color:var(--gold);font-size:1.2rem;flex-shrink:0;" aria-hidden="true"></i>
                <div>
                    <div style="font-weight:700;font-size:.88rem;color:var(--text-primary);" id="recap-date-heure">—</div>
                    <div style="font-size:.75rem;color:var(--text-muted);" id="recap-coiffeur-nom">Avec <?= htmlspecialchars($coiffeur['nom'] . ' ' . ($coiffeur['prenom'] ?? '')) ?></div>
                </div>
            </div>
            <br>
            <a id="btn-continuer"
               href="#"
               class="btn-gold d-inline-flex align-items-center gap-2"
               style="padding:13px 32px;font-size:.95rem;"
               aria-label="Continuer vers la confirmation">
                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                Continuer
            </a>
        </div>

    </div>
</section>

<!-- ════════════ 4. AVIS CLIENTS ════════════ -->
<section class="pp-section pp-section--alt" aria-labelledby="avis-heading">
    <div class="pp-wrap">
        <span class="section-eyebrow">Réputation</span>
        <h2 id="avis-heading" class="section-heading">Avis clients</h2>
        <?php if ($note_moyenne && $stats_avis['total'] > 0): ?>
            <p class="section-sub" style="margin-bottom:2rem;">
                <strong style="color:var(--gold);"><?= $note_moyenne ?>/5</strong>
                · <?= $stats_avis['total'] ?> avis vérifiés
            </p>
        <?php else: ?>
            <p class="section-sub" style="margin-bottom:2rem;">Soyez le premier à laisser un avis !</p>
        <?php endif; ?>

        <?php if (empty($avis_publics)): ?>
            <div style="background:var(--glass-bg);border:1px solid var(--glass-border);border-radius:var(--radius-lg);padding:2.5rem;text-align:center;">
                <i class="bi bi-chat-square-text" style="font-size:2.5rem;color:rgba(255,255,255,.1);display:block;margin-bottom:.75rem;" aria-hidden="true"></i>
                <p style="color:var(--text-muted);font-size:.88rem;margin:0;">Aucun avis pour l'instant.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($avis_publics as $avis):
                    $note_av  = intval($avis['note'] ?? 0);
                    $init_av  = strtoupper(substr($avis['client_nom'] ?? 'C', 0, 1));
                    $masque   = strtoupper(substr($avis['client_prenom'] ?? 'C', 0, 1)) . '***';
                    $d_av     = new DateTime($avis['date_demande'] ?? 'now');
                    $diff_av  = (new DateTime())->diff($d_av);
                    $date_r   = $diff_av->days === 0 ? "Aujourd'hui"
                              : ($diff_av->days === 1 ? "Hier" : "Il y a {$diff_av->days} jours");
                ?>
                <div class="col-12 col-md-6">
                    <div style="background:var(--dark-3);border:1px solid var(--glass-border);border-radius:var(--radius-lg);padding:1.25rem;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:rgba(212,175,55,.12);border:1px solid rgba(212,175,55,.25);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--gold);font-size:.82rem;flex-shrink:0;">
                                <?= htmlspecialchars($init_av) ?>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:.82rem;font-weight:600;color:var(--text-primary);"><?= htmlspecialchars($masque) ?></div>
                                <div style="font-size:.7rem;color:var(--text-muted);"><?= htmlspecialchars($date_r) ?></div>
                            </div>
                            <div aria-label="Note : <?= $note_av ?>/5">
                                <?php for ($s=1;$s<=5;$s++): ?>
                                    <span style="color:<?= $s<=$note_av?'#D4AF37':'rgba(255,255,255,.15)';?>;font-size:.88rem;">★</span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty($avis['message'])): ?>
                            <p style="color:var(--text-secondary);font-size:.82rem;line-height:1.6;margin:0;">"<?= htmlspecialchars($avis['message']) ?>"</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- FOOTER + IA + BOTTOM NAV -->
<?php
$page_root    = '/coiffons';
include __DIR__ . '/../views/components/footer_global.php';
include __DIR__ . '/../views/components/ia_assistant.php';
$current_page = 'profil_public.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    'use strict';

    /* ── Données PHP → JS ── */
    const COIFFEUR_ID   = <?= (int)$prestataire_id ?>;
    const EST_CONNECTE  = <?= $est_connecte ? 'true' : 'false' ?>;
    const PAGE_ROOT     = '/coiffons';
    const BUSY_SLOTS    = <?= json_encode($busy_slots, JSON_UNESCAPED_UNICODE) ?>;

    /* ── État courant ── */
    let prestaSelectionId   = null;
    let prestaSelectionNom  = '';
    let prestaSelectionPrix = 0;
    let dateSelectionnee    = null;
    let heureSelectionnee   = null;

    /* ══════════════════════════════════════════
       SÉLECTION D'UNE PRESTATION
       ══════════════════════════════════════════ */
    window.selectionnerPrestation = function (card) {
        // Désélectionner toutes les cartes
        document.querySelectorAll('.presta-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');

        prestaSelectionId   = card.getAttribute('data-presta-id');
        prestaSelectionNom  = card.getAttribute('data-presta-nom');
        prestaSelectionPrix = parseInt(card.getAttribute('data-presta-prix'));
        const duree         = parseInt(card.getAttribute('data-presta-duree'));

        // Mettre à jour le mini-recap
        document.getElementById('recap-presta-nom').textContent  = prestaSelectionNom;
        document.getElementById('recap-presta-prix').textContent = prestaSelectionPrix.toLocaleString('fr-FR');
        document.getElementById('recap-presta-duree').textContent = duree;

        // Réinitialiser la sélection date/heure
        dateSelectionnee  = null;
        heureSelectionnee = null;
        document.querySelectorAll('.day-selector-card').forEach(d => d.classList.remove('active-day'));
        document.getElementById('pp-zone-slots').classList.add('d-none');
        document.getElementById('btn-continuer-wrap').style.display = 'none';

        // Afficher la section disponibilités
        const section = document.getElementById('section-disponibilites');
        section.style.display = 'block';

        // Scroll smooth vers disponibilités
        setTimeout(() => {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    };

    window.deselectionnerPrestation = function () {
        prestaSelectionId = null;
        document.querySelectorAll('.presta-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('section-disponibilites').style.display = 'none';
        document.getElementById('section-catalogue').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    /* ══════════════════════════════════════════
       SÉLECTION D'UN JOUR
       ══════════════════════════════════════════ */
    window.ppSelectionnerJour = function (element) {
        document.querySelectorAll('.day-selector-card').forEach(el => el.classList.remove('active-day'));
        heureSelectionnee = null;
        document.getElementById('btn-continuer-wrap').style.display = 'none';

        if (element.getAttribute('data-open') === '0') {
            document.getElementById('pp-zone-slots').classList.add('d-none');
            if (typeof showToast === 'function') showToast('Ce coiffeur ne travaille pas ce jour-là.', 'warning');
            return;
        }

        element.classList.add('active-day');
        dateSelectionnee = element.getAttribute('data-date');

        const heureDebut = element.getAttribute('data-start');
        const heureFin   = element.getAttribute('data-end');
        const container  = document.getElementById('pp-slots-container');
        const zone       = document.getElementById('pp-zone-slots');

        container.innerHTML = '';
        if (!heureDebut || !heureFin) { zone.classList.add('d-none'); return; }

        const hStart = parseInt(heureDebut.split(':')[0]);
        const hEnd   = parseInt(heureFin.split(':')[0]);

        let hasAvailable = false;
        for (let h = hStart; h < hEnd; h++) {
            const slotH  = String(h).padStart(2, '0') + ':00';
            const cleKey = dateSelectionnee + ' ' + slotH;
            const btn    = document.createElement('button');
            btn.type     = 'button';
            btn.setAttribute('role', 'listitem');
            btn.textContent = slotH;

            if (BUSY_SLOTS[cleKey]) {
                btn.className = 'btn-ghost btn-sm';
                btn.style.opacity = '.25';
                btn.disabled = true;
                btn.setAttribute('aria-disabled', 'true');
                btn.title = 'Déjà réservé';
            } else {
                btn.className = 'btn-outline-gold btn-sm';
                btn.setAttribute('aria-label', 'Choisir le créneau ' + slotH);
                btn.addEventListener('click', () => ppSelectionnerHeure(btn, slotH));
                hasAvailable = true;
            }
            container.appendChild(btn);
        }

        if (!hasAvailable) {
            container.innerHTML = '<span style="color:var(--text-muted);font-size:.82rem;">Toutes les heures sont déjà réservées ce jour-là.</span>';
        }

        zone.classList.remove('d-none');
    };

    /* ══════════════════════════════════════════
       SÉLECTION D'UNE HEURE
       ══════════════════════════════════════════ */
    function ppSelectionnerHeure(btn, heure) {
        // Désélectionner tous les boutons d'heure
        document.querySelectorAll('#pp-slots-container button').forEach(b => {
            b.classList.remove('btn-gold');
            if (!b.disabled) b.classList.add('btn-outline-gold');
        });

        btn.classList.remove('btn-outline-gold');
        btn.classList.add('btn-gold');
        heureSelectionnee = heure;

        // Mettre à jour le récap
        const dateObj   = new Date(dateSelectionnee + 'T12:00:00');
        const dateLabel = dateObj.toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long' });
        document.getElementById('recap-date-heure').textContent = dateLabel + ' à ' + heure;

        // Construire le lien de destination
        const continuerURL = EST_CONNECTE
            ? `${PAGE_ROOT}/client/reserver.php?prestataire_id=${COIFFEUR_ID}&presta_id=${prestaSelectionId}&date_rdv=${dateSelectionnee}&heure_debut=${heure}`
            : `${PAGE_ROOT}/index.php?page=login&redirect=${encodeURIComponent(PAGE_ROOT + '/client/reserver.php?prestataire_id=' + COIFFEUR_ID + '&presta_id=' + prestaSelectionId + '&date_rdv=' + dateSelectionnee + '&heure_debut=' + heure)}`;

        document.getElementById('btn-continuer').href = continuerURL;
        document.getElementById('btn-continuer-wrap').style.display = 'block';

        // Scroll vers le bouton
        setTimeout(() => {
            document.getElementById('btn-continuer-wrap').scrollIntoView({ behavior:'smooth', block:'nearest' });
        }, 100);
    }

})();
</script>

</body>
</html>
