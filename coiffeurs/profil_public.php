<?php
/**
 * coiffeurs/profil_public.php — Profil public d'un coiffeur
 * Migration Design System v2.0 — Parcours Visiteur — Page 5/6
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — Seuls HTML/CSS/composants ont été modifiés.
 *     Aucune requête SQL, variable PHP, session ou sécurité touchée.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

// Redirection si non connecté — inchangé
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'invite') {
    header("Location: /coiffons/index.php?page=login");
    exit();
}

// Fix URL — inchangé
$id_coiffeur = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
            ?? filter_input(INPUT_GET, 'id_coiffeur', FILTER_VALIDATE_INT);

if (!$id_coiffeur) {
    // Page d'erreur inline — conservée telle quelle
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erreur</title></head><body style='background:#0a0a0a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;'><div style='text-align:center'><p style='color:rgba(255,255,255,0.4);'>Coiffeur introuvable.</p><a href='/coiffons/index.php' style='color:#D4AF37;'>Retour à l'accueil</a></div></body></html>";
    exit();
}

try {
    // Requête coiffeur — SQL inchangé
    $stmt = $pdo->prepare("
        SELECT u.*, v.nom_ville, q.nom_quartier
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        LEFT JOIN quartiers q ON u.id_quartier = q.id
        WHERE u.id = ? AND LOWER(u.role) = 'coiffeur'
        AND (u.abonnement_status = 1 OR LOWER(u.abonnement_status) = 'actif')
    ");
    $stmt->execute([$id_coiffeur]);
    $coiffeur = $stmt->fetch();

    if (!$coiffeur) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='background:#0a0a0a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;'><div style='text-align:center'><p style='color:rgba(255,255,255,0.4);'>Ce coiffeur n'est pas disponible.</p><a href='/coiffons/index.php' style='color:#D4AF37;'>Retour</a></div></body></html>";
        exit();
    }

    // Catalogue prestations — SQL inchangé
    $stmt_presta = $pdo->prepare("SELECT * FROM prestations");
    $stmt_presta->execute();
    $toutes_les_prestas = $stmt_presta->fetchAll();
    $prestations = [];
    foreach ($toutes_les_prestas as $p) {
        $cles_nettoyees = array_combine(array_map('trim', array_keys($p)), array_values($p));
        if (isset($cles_nettoyees['id_coiffeur']) && $cles_nettoyees['id_coiffeur'] == $id_coiffeur) {
            $prestations[] = $p;
        }
    }

    // Note moyenne — SQL inchangé
    try {
        $stmt_note = $pdo->prepare("SELECT AVG(note) as moy, COUNT(*) as nb FROM commentaires WHERE id_coiffeur = ?");
        $stmt_note->execute([$id_coiffeur]);
        $stats_note   = $stmt_note->fetch();
        $note_moyenne = $stats_note['nb'] > 0 ? round($stats_note['moy'], 1) : null;
        $stats_avis   = ['total' => intval($stats_note['nb'])];
    } catch (Exception $e) {
        $note_moyenne = null;
        $stats_avis   = ['total' => 0];
    }

    // Disponibilités — SQL inchangé
    $stmt_dispo = $pdo->prepare("SELECT * FROM disponibilites WHERE coiffeur_id = ?");
    $stmt_dispo->execute([$id_coiffeur]);
    $toutes_les_dispos = $stmt_dispo->fetchAll();
    $planning_coiffeur = [];
    foreach ($toutes_les_dispos as $d) {
        $planning_coiffeur[strtolower(trim($d['jour_semaine']))] = [
            'debut' => $d['heure_debut'],
            'fin'   => $d['heure_fin']
        ];
    }

    // Créneaux occupés — SQL inchangé
    $stmt_rdv = $pdo->prepare("
        SELECT date_rdv, heure_debut FROM rendez_vous
        WHERE coiffeur_id = ? AND statut_rdv IN ('en_attente','accepte','confirme')
    ");
    $stmt_rdv->execute([$id_coiffeur]);
    $busy_slots = [];
    foreach ($stmt_rdv->fetchAll() as $r) {
        $key = $r['date_rdv'] . ' ' . substr($r['heure_debut'], 0, 5);
        $busy_slots[$key] = true;
    }

} catch (Exception $e) {
    echo "<div style='background:#0a0a0a;color:#fff;padding:40px;font-family:sans-serif;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($coiffeur['nom']) ?> <?= htmlspecialchars($coiffeur['prenom'] ?? '') ?> — Coiffe Chez Toi</title>

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
    /* ── Styles spécifiques à profil_public, tous via var(--*) DS ── */

    /* Sections */
    .profile-summary-section { padding:4rem 0; background:var(--glass-bg-subtle); border-bottom:1px solid var(--glass-border); }
    .section-booking  { padding:4rem 0; background:var(--dark-3); }
    .section-portfolio{ padding:4rem 0; background:var(--dark); }

    /* Titres de section */
    .section-heading  { font-family:var(--font-display); font-size:1.2rem; font-weight:700; color:var(--text-primary); margin-bottom:.25rem; }
    .section-sub      { color:var(--text-muted); font-size:.82rem; margin-bottom:2rem; }

    /* Nom du coiffeur */
    .profile-name     { font-family:var(--font-display); font-size:clamp(1.4rem,3vw,2rem); font-weight:700; color:var(--gold); text-transform:uppercase; letter-spacing:2px; margin-bottom:.4rem; }

    /* Bio */
    .profile-bio      { color:var(--text-secondary); font-size:.9rem; line-height:1.6; max-width:600px; margin:0 auto; }

    /* Badge note muted */
    .badge-note-muted { background:rgba(255,255,255,0.04); border:1px solid var(--glass-border); color:var(--text-muted); font-size:.75rem; font-weight:700; padding:4px 12px; border-radius:20px; display:inline-flex; align-items:center; gap:5px; }

    /* SlotsContainer – intégré dans la section réservation */
    .slots-container  { background:var(--dark-2); border:1px dashed rgba(212,175,55,.30); border-radius:var(--radius-md); padding:1.5rem; text-align:center; }
    .slots-title      { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:var(--gold); margin-bottom:1rem; }

    /* Disponibility cards scroll horizontal mobile */
    .days-scroll      { display:flex; justify-content:center; flex-wrap:wrap; gap:8px; overflow-x:auto; padding-bottom:4px; }
    @media (max-width:576px) {
        .days-scroll  { flex-wrap:nowrap; justify-content:flex-start; }
        .day-selector-card { flex-shrink:0; }
    }

    /* GalleryCard — portfolio */
    .portfolio-grid .gallery-card { cursor:default; }
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
     PROFILE SUMMARY — Composant CL §28
     ══════════════════════════════════════════ -->
<section class="profile-summary-section page-transition"
         aria-labelledby="coiffeur-nom">
    <div class="container text-center" style="max-width:var(--max-width);">

        <!-- Lien retour -->
        <div class="text-start mb-4">
            <a href="javascript:history.back()"
               class="btn-ghost btn-sm d-inline-flex align-items-center gap-1"
               aria-label="Retour à la page précédente">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Retour
            </a>
        </div>

        <!-- Avatar — Composant CL §25 -->
        <div class="mb-3">
            <?php
            $ps_photo_url = null;
            if (!empty($coiffeur['photo_profil'])) {
                $check_paths = [
                    __DIR__ . '/../access/' . $coiffeur['photo_profil'],
                    __DIR__ . '/../uploads/profil/' . basename($coiffeur['photo_profil']),
                ];
                foreach ($check_paths as $cp) {
                    if (file_exists($cp)) {
                        $ps_photo_url = '/coiffons/access/' . $coiffeur['photo_profil'];
                        break;
                    }
                }
            }
            ?>
            <?php if ($ps_photo_url): ?>
                <img src="<?= htmlspecialchars($ps_photo_url) ?>"
                     alt="Photo de profil de <?= htmlspecialchars($coiffeur['nom']) ?>"
                     class="avatar-placeholder"
                     style="width:80px;height:80px;font-size:2rem;box-shadow:0 0 20px rgba(212,175,55,0.30);">
            <?php else: ?>
                <div class="avatar-placeholder d-inline-flex"
                     style="width:80px;height:80px;font-size:2rem;box-shadow:0 0 20px rgba(212,175,55,0.30);"
                     aria-label="Initiale du coiffeur">
                    <?= htmlspecialchars(strtoupper(substr($coiffeur['nom'], 0, 1))) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Nom -->
        <h1 id="coiffeur-nom" class="profile-name">
            <?= htmlspecialchars(strtoupper($coiffeur['nom'])) ?>
            <?php if (!empty($coiffeur['prenom'])): ?>
                <?= htmlspecialchars(strtoupper($coiffeur['prenom'])) ?>
            <?php endif; ?>
        </h1>

        <!-- Localisation -->
        <p class="mb-3" style="color:var(--text-muted);font-size:.85rem;">
            <i class="bi bi-geo-alt-fill" style="color:var(--gold);" aria-hidden="true"></i>
            <?= htmlspecialchars($coiffeur['nom_ville'] ?? 'Non spécifiée') ?>
            &mdash;
            <?= htmlspecialchars($coiffeur['nom_quartier'] ?? 'Général') ?>
        </p>

        <!-- Badge note — .badge-gold DS §14 -->
        <div class="mb-3">
            <?php if ($note_moyenne): ?>
                <span class="badge-gold" aria-label="Note : <?= $note_moyenne ?> sur 5, <?= $stats_avis['total'] ?> avis">
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <?= $note_moyenne ?> / 5
                    <span style="opacity:.7;">(<?= $stats_avis['total'] ?> avis)</span>
                </span>
            <?php else: ?>
                <span class="badge-note-muted" aria-label="Aucun avis pour le moment">
                    <i class="bi bi-star" aria-hidden="true"></i>
                    Aucun avis pour le moment
                </span>
            <?php endif; ?>
        </div>

        <!-- Bio -->
        <p class="profile-bio">
            <?= !empty($coiffeur['bio'])
                ? htmlspecialchars($coiffeur['bio'])
                : 'Artisan coiffeur professionnel sélectionné par notre plateforme, expert en créations capillaires haut de gamme à domicile.' ?>
        </p>

    </div>
</section>

<!-- ══════════════════════════════════════════
     SECTION RÉSERVATION EXPRESS
     AvailabilityCard CL §8 + SlotsContainer CL §35
     ══════════════════════════════════════════ -->
<section class="section-booking" aria-labelledby="booking-heading">
    <div class="container" style="max-width:var(--max-width);">

        <h2 id="booking-heading" class="section-heading text-center">
            <i class="bi bi-calendar3 me-2" style="color:var(--gold);" aria-hidden="true"></i>
            Réservation Express
        </h2>
        <p class="section-sub text-center">Choisissez un créneau disponible</p>

        <!-- AvailabilityCard — Composant CL §8 -->
        <div class="days-scroll mb-4" role="list" aria-label="Jours disponibles">
            <?php
            $jours_traduction = [
                'monday'=>'lundi','tuesday'=>'mardi','wednesday'=>'mercredi',
                'thursday'=>'jeudi','friday'=>'vendredi','saturday'=>'samedi','sunday'=>'dimanche'
            ];
            for ($i = 0; $i < 7; $i++):
                $timestamp   = strtotime("+{$i} days");
                $date_cle    = date('Y-m-d', $timestamp);
                $jour_en     = strtolower(date('l', $timestamp));
                $jour_fr     = $jours_traduction[$jour_en];
                $num_jour    = date('d', $timestamp);
                $nom_mois    = date('M', $timestamp);
                $travaille   = isset($planning_coiffeur[$jour_fr]);
                $class_stat  = $travaille ? 'day-item-open' : 'day-item-closed';
                $h_debut     = $travaille ? $planning_coiffeur[$jour_fr]['debut'] : '';
                $h_fin       = $travaille ? $planning_coiffeur[$jour_fr]['fin']   : '';
            ?>
                <div class="day-selector-card <?= $class_stat ?>"
                     data-date="<?= htmlspecialchars($date_cle) ?>"
                     data-open="<?= $travaille ? '1' : '0' ?>"
                     data-start="<?= htmlspecialchars($h_debut) ?>"
                     data-end="<?= htmlspecialchars($h_fin) ?>"
                     onclick="selectionnerJour(this)"
                     role="button"
                     tabindex="0"
                     onkeypress="if(event.key==='Enter')selectionnerJour(this)"
                     aria-label="<?= htmlspecialchars($jour_fr) ?> <?= $num_jour ?> <?= $nom_mois ?> — <?= $travaille ? 'disponible' : 'indisponible' ?>"
                     <?= !$travaille ? 'aria-disabled="true"' : '' ?>>
                    <span class="small text-uppercase opacity-70 d-block">
                        <?= htmlspecialchars(substr($jour_fr, 0, 3)) ?>.
                    </span>
                    <span class="fs-4 fw-bold my-1 d-block"><?= $num_jour ?></span>
                    <span class="small opacity-50 d-block"><?= $nom_mois ?></span>
                </div>
            <?php endfor; ?>
        </div>

        <!-- SlotsContainer — Composant CL §35 -->
        <div id="zone-slots-horaires"
             class="slots-container d-none"
             role="region"
             aria-live="polite"
             aria-label="Créneaux horaires disponibles">
            <div class="slots-title">Heures disponibles pour la date sélectionnée</div>
            <div id="slots-container"
                 class="d-flex flex-wrap justify-content-center gap-2"
                 role="list">
            </div>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════
     SECTION PORTFOLIO — GalleryCard CL §7
     ══════════════════════════════════════════ -->
<section class="section-portfolio" aria-labelledby="portfolio-heading">
    <div class="container" style="max-width:var(--max-width);">

        <h2 id="portfolio-heading" class="section-heading">
            <i class="bi bi-scissors me-2" style="color:var(--gold);" aria-hidden="true"></i>
            Portfolio des créations
        </h2>
        <p class="section-sub">Découvrez les styles proposés par ce coiffeur</p>

        <?php if (empty($prestations)): ?>
            <!-- Empty State CL DS §17 -->
            <?php
            $es = [
                'icon'      => 'bi-camera',
                'message'   => 'Ce coiffeur n\'a pas encore publié de styles dans son catalogue.',
                'cta_label' => '',
                'cta_href'  => '',
            ];
            include __DIR__ . '/../views/components/empty_state.php';
            ?>
        <?php else: ?>
            <div class="row g-4 portfolio-grid">
                <?php foreach ($prestations as $p):
                    $photo_url = null;
                    if (!empty($p['photo_style'])) {
                        $photo_check = __DIR__ . '/../' . $p['photo_style'];
                        if (file_exists($photo_check)) {
                            $photo_url = '/coiffons/' . $p['photo_style'];
                        }
                    }
                ?>
                    <div class="col-12 col-sm-6 col-md-4">
                        <!-- GalleryCard variante view — CL §7 -->
                        <?php
                        $gallery = [
                            'nom_style'      => $p['nom_style'] ?? 'Style sans nom',
                            'prix'           => $p['prix'] ?? 0,
                            'photo_url'      => $photo_url,
                            'href_reserver'  => '/coiffons/client/reserver.php?coiffeur_id=' . $coiffeur['id'] . '&presta_id=' . ($p['id_prestation'] ?? ''),
                            'variante'       => 'view',
                        ];
                        include __DIR__ . '/../views/components/gallery_card.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- ══════════════════════════════════════════
     FOOTER GLOBAL + BOTTOM NAV
     ══════════════════════════════════════════ -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../views/components/footer_global.php';

$current_page = 'profil_public.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ══ JAVASCRIPT UI — logique selectionnerJour inchangée ══ -->
<script>
(function() {
    const slotsOccupes = <?= json_encode($busy_slots) ?>;
    const coiffeurId   = <?= (int)$id_coiffeur ?>;
    const pageRoot     = '/coiffons';

    window.selectionnerJour = function(element) {
        // Reset sélection
        document.querySelectorAll('.day-selector-card').forEach(el => el.classList.remove('active-day'));

        if (element.getAttribute('data-open') === '0') {
            document.getElementById('zone-slots-horaires').classList.add('d-none');
            // Toast non bloquant à la place du alert
            if (typeof showToast === 'function') {
                showToast('Ce coiffeur ne travaille pas ce jour-là.', 'warning');
            }
            return;
        }

        element.classList.add('active-day');

        const dateChoisie = element.getAttribute('data-date');
        const heureDebut  = element.getAttribute('data-start');
        const heureFin    = element.getAttribute('data-end');
        const container   = document.getElementById('slots-container');
        const zone        = document.getElementById('zone-slots-horaires');

        container.innerHTML = '';

        if (!heureDebut || !heureFin) { zone.classList.add('d-none'); return; }

        const [hStart] = heureDebut.split(':').map(Number);
        const [hEnd]   = heureFin.split(':').map(Number);

        const urlParams = new URLSearchParams(window.location.search);
        const prestaId  = urlParams.get('presta_id') || '';

        for (let h = hStart; h < hEnd; h++) {
            const slotHeure = String(h).padStart(2,'0') + ':00';
            const cleVerif  = dateChoisie + ' ' + slotHeure;
            const btn = document.createElement('a');
            btn.setAttribute('role', 'listitem');

            if (slotsOccupes[cleVerif]) {
                btn.className = 'btn-ghost btn-sm disabled';
                btn.style.opacity = '.25';
                btn.title = 'Déjà réservé';
                btn.setAttribute('aria-disabled', 'true');
                btn.textContent = slotHeure;
            } else {
                btn.className = 'btn-outline-gold btn-sm';
                btn.href = `${pageRoot}/client/reserver.php?coiffeur_id=${coiffeurId}&presta_id=${prestaId}&date_rdv=${dateChoisie}&heure_debut=${slotHeure}`;
                btn.setAttribute('aria-label', `Réserver le créneau ${slotHeure}`);
                btn.textContent = slotHeure;
            }
            container.appendChild(btn);
        }

        zone.classList.remove('d-none');
    };
})();
</script>

</body>
</html>
