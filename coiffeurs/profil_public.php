<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

// Redirection si non connecté
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'invite') {
    header("Location: /coiffons/index.php?page=login");
    exit();
}

// Fix URL : accepte ?id= ET ?id_coiffeur= pour compatibilité
$id_coiffeur = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
            ?? filter_input(INPUT_GET, 'id_coiffeur', FILTER_VALIDATE_INT);

if (!$id_coiffeur) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erreur</title></head><body style='background:#0a0a0a;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:sans-serif;'><div style='text-align:center'><p style='color:rgba(255,255,255,0.4);'>Coiffeur introuvable.</p><a href='/coiffons/index.php' style='color:#D4AF37;'>Retour à l'accueil</a></div></body></html>";
    exit();
}

try {
    // Requête coiffeur — compatible cft (abonnement_status=1) et domizi (actif)
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

    // 4. REQUÊTE AUTORÉPARATRICE POUR LE CATALOGUE DES PRESTATIONS
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

    // Note moyenne — requête directe, propre et compatible domizi
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

    // Disponibilités — requête directe filtrée
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

    // Créneaux occupés — filtrés par coiffeur
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

// Charge le header client (navbar premium)
require_once __DIR__ . '/../security/config.php';
// Header standalone pour profil public
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($coiffeur['nom']) ?> — Coiffe Chez Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #D4AF37; --gold-dim: rgba(212,175,55,0.12); }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0a0a0a; color:#fff; font-family:'Inter',sans-serif; }
        .profil-topbar {
            position:sticky;top:0;z-index:100;
            display:flex;align-items:center;justify-content:space-between;
            padding:0 2rem;height:58px;
            background:rgba(0,0,0,0.9);backdrop-filter:blur(20px);
            border-bottom:1px solid rgba(255,255,255,0.06);
        }
        .profil-topbar a { color:rgba(255,255,255,0.4);text-decoration:none;font-size:0.82rem;display:flex;align-items:center;gap:6px;transition:color 0.2s; }
        .profil-topbar a:hover { color:var(--gold); }
        .profil-brand { font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;color:var(--gold)!important; }
        .page-anim { animation:fadeIn 0.35s ease forwards; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
        .day-selector-card { flex:1 1 75px;max-width:90px;padding:12px 5px;text-align:center;border-radius:10px;cursor:pointer;transition:all 0.3s;border:2px solid transparent;background:#1a1a1a; }
        .day-item-open { border-color:rgba(25,135,84,0.3); }
        .day-item-open:hover { background:#222;border-color:var(--gold); }
        .day-item-closed { border-color:rgba(220,53,69,0.4);background:rgba(220,53,69,0.05);opacity:0.5;cursor:not-allowed; }
        .active-day { background:#ffc107!important;color:#000!important;transform:scale(1.05);box-shadow:0 4px 15px rgba(255,193,7,0.4); }
        .active-day span { color:#000!important; }
        .style-slots-box { background:#111;border:1px dashed rgba(212,175,55,0.3); }
        .luxury-profile-card { background:#121212!important;border:1px solid rgba(255,255,255,0.05)!important;border-radius:12px!important;overflow:hidden;transition:transform 0.3s,border-color 0.3s; }
        .luxury-profile-card:hover { transform:translateY(-4px);border-color:rgba(212,175,55,0.3)!important; }
        .luxury-img-container { width:100%;height:240px;overflow:hidden;background:#1a1a1a; }
        .luxury-img-container img { width:100%;height:100%;object-fit:cover; }
    </style>
</head>
<body>
<nav class="profil-topbar">
    <a href="/coiffons/index.php" class="profil-brand">Coiffe Chez Toi</a>
    <a href="javascript:history.back()"><i class="bi bi-arrow-left"></i> Retour</a>
</nav>
<div class="page-anim">

<div class="luxury-profile-wrapper" style="background: linear-gradient(135deg, #0a0a0a 0%, #121212 100%); color: #fff; min-height: 100vh;">
    <section class="py-5 border-bottom border-secondary" style="background: rgba(255,255,255,0.02);">
        <div class="container text-center">
            <div class="mb-3">
                <div class="avatar-placeholder d-inline-flex justify-content-center align-items-center bg-warning text-dark rounded-circle fw-bold shadow-lg" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?php echo strtoupper(substr($coiffeur['nom'], 0, 1)); ?>
                </div>
            </div>
            
            <h1 class="text-warning fw-bold h2 mb-1"><?php echo strtoupper(htmlspecialchars($coiffeur['nom'])); ?></h1>
            <p class="text-secondary small mb-3">
                <i class="bi bi-geo-alt-fill text-warning"></i> 
                <?php echo htmlspecialchars($coiffeur['nom_ville'] ?? 'Non spécifiée'); ?> — <?php echo htmlspecialchars($coiffeur['nom_quartier'] ?? 'Général'); ?>
            </p>

            <div class="mb-3">
                <?php if ($note_moyenne): ?>
                    <span class="badge bg-dark border border-warning text-warning px-3 py-2">
                        <i class="bi bi-star-fill text-warning me-1"></i> <?php echo $note_moyenne; ?> / 5 (<?php echo $stats_avis['total']; ?> avis)
                    </span>
                <?php else: ?>
                    <span class="badge bg-dark border border-secondary text-secondary px-3 py-2">
                        <i class="bi bi-star me-1"></i> Aucun avis pour le moment
                    </span>
                <?php endif; ?>
            </div>

            <p class="text-light opacity-75 small mx-auto mb-4" style="max-width: 600px;">
                <?php echo !empty($coiffeur['bio']) ? htmlspecialchars($coiffeur['bio']) : "Artisan coiffeur professionnel sélectionné par notre plateforme, expert en créations capillaires haut de gamme à domicile."; ?>
            </p>
        </div>
    </section>

    <section class="py-5 border-bottom border-secondary bg-dark bg-opacity-25">
        <div class="container">
            <h3 class="text-center text-white h4 mb-4">
                <i class="bi bi-calendar3 text-warning me-2"></i> RÉSERVATION EXPRESS : CHOISISSEZ UN CRÉNEAU
            </h3>
            
            <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">
                <?php
                $jours_traduction = [
                    'monday' => 'lundi', 'tuesday' => 'mardi', 'wednesday' => 'mercredi', 
                    'thursday' => 'jeudi', 'friday' => 'vendredi', 'saturday' => 'samedi', 'sunday' => 'dimanche'
                ];

                for ($i = 0; $i < 7; $i++) {
                    $timestamp = strtotime("+$i days");
                    $date_cle = date('Y-m-d', $timestamp);
                    $jour_en = strtolower(date('l', $timestamp));
                    $jour_fr = $jours_traduction[$jour_en];
                    $num_jour = date('d', $timestamp);
                    $nom_mois = date('M', $timestamp);

                    $travaille_ce_jour = isset($planning_coiffeur[$jour_fr]);
                    $class_statut = $travaille_ce_jour ? 'day-item-open' : 'day-item-closed';
                    $heure_debut_brute = $travaille_ce_jour ? $planning_coiffeur[$jour_fr]['debut'] : '';
                    $heure_fin_brute = $travaille_ce_jour ? $planning_coiffeur[$jour_fr]['fin'] : '';
                    ?>
                    <div class="day-selector-card <?php echo $class_statut; ?>" 
                         data-date="<?php echo $date_cle; ?>" 
                         data-open="<?php echo $travaille_ce_jour ? '1' : '0'; ?>"
                         data-start="<?php echo $heure_debut_brute; ?>"
                         data-end="<?php echo $heure_fin_brute; ?>"
                         onclick="selectionnerJour(this)">
                        <span class="small text-uppercase opacity-70 d-block"><?php echo substr($jour_fr, 0, 3); ?>.</span>
                        <span class="fs-4 fw-bold my-1 d-block"><?php echo $num_jour; ?></span>
                        <span class="small opacity-50 d-block"><?php echo $nom_mois; ?></span>
                    </div>
                <?php } ?>
            </div>

            <div id="zone-slots-horaires" class="p-4 rounded text-center style-slots-box d-none">
                <h5 class="text-warning small mb-3 text-uppercase fw-bold">Heures disponibles pour la date sélectionnée :</h5>
                <div id="slots-container" class="d-flex flex-wrap justify-content-center gap-2"></div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <h3 class="text-white mb-4 h4 text-center text-md-start">
                <i class="bi bi-scissors text-warning me-2"></i> LE PORTFOLIO DE SES CRÉATIONS
            </h3>

            <?php if (empty($prestations)): ?>
                <div class="text-center text-muted py-5 border border-secondary border-dashed rounded" style="border-style: dashed !important;">
                    <i class="bi bi-camera-video display-4 text-secondary mb-3 d-block"></i>
                    Ce coiffeur n'a pas encore publié d'images ou de styles dans son catalogue privé.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($prestations as $p): ?>
                        <div class="col-sm-6 col-md-4">
                            <div class="card luxury-profile-card h-100 border-0 text-white shadow-sm">
                                <div class="luxury-img-container">
                                    <?php if (!empty($p['photo_style']) && file_exists(__DIR__ . '/../uploads/' . $p['photo_style'])): ?>
                                        <img src="<?php echo '/coiffons/uploads/' . $p['photo_style']; ?>" class="card-img-top" alt="Style">
                                    <?php else: ?>
                                        <div class="profile-placeholder-img d-flex flex-column justify-content-center align-items-center">
                                            <i class="bi bi-stars text-warning opacity-50 display-6 mb-2"></i>
                                            <span class="text-muted text-uppercase font-monospace" style="font-size: 0.6rem; letter-spacing: 1px;">Création Studio</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body d-flex flex-column p-4 text-center">
                                    <h5 class="fw-bold text-white mb-2"><?php echo htmlspecialchars($p['nom_style'] ?? 'Style sans nom'); ?></h5>
                                    <h4 class="text-warning fw-bold font-monospace mb-3 h5">
                                        <?php echo number_format($p['prix'] ?? 0, 0, ',', ' '); ?> <span style="font-size: 0.75rem;">FCFA</span>
                                    </h4>
                                    <div class="mt-auto">
                                        <a href="/coiffons/client/reserver.php?coiffeur_id=<?php echo $coiffeur['id']; ?>&presta_id=<?php echo $p['id_prestation'] ?? ''; ?>" class="btn btn-outline-warning btn-sm w-100 py-2 fw-bold">
                                            CHOISIR CE STYLE
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</div><!-- /.luxury-profile-wrapper -->
</div><!-- /.page-anim -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
const slotsOccupes = <?php echo json_encode($busy_slots); ?>;
const coiffeurId = <?php echo $id_coiffeur; ?>;

function selectionnerJour(element) {
    document.querySelectorAll('.day-selector-card').forEach(el => el.classList.remove('active-day'));
    
    if (element.getAttribute('data-open') === '0') {
        document.getElementById('zone-slots-horaires').classList.add('d-none');
        alert("Ce coiffeur ne travaille pas ce jour-là.");
        return;
    }

    element.classList.add('active-day');
    
    const dateChoisie = element.getAttribute('data-date');
    const heureDebut = element.getAttribute('data-start');
    const heureFin = element.getAttribute('data-end');
    
    const containerSlots = document.getElementById('slots-container');
    containerSlots.innerHTML = "";

    let [hStart, mStart] = heureDebut.split(':').map(Number);
    let [hEnd, mEnd] = heureFin.split(':').map(Number);

    let heureCourante = hStart;
    let minuteCourante = mStart;

    const urlParams = new URLSearchParams(window.location.search);
    const prestaId = urlParams.get('presta_id') || '';

    while (heureCourante < hEnd || (heureCourante === hEnd && minuteCourante < mEnd)) {
        let formatHeure = String(heureCourante).padStart(2, '0');
        let formatMinute = String(minuteCourante).padStart(2, '0');
        let slotHeure = `${formatHeure}:${formatMinute}`;
        
        let cleVerification = `${dateChoisie} ${slotHeure}`;

        let boutonSlot = document.createElement('a');
        boutonSlot.classList.add('btn', 'btn-sm', 'm-1', 'fw-bold', 'px-3', 'py-2');
        boutonSlot.innerText = slotHeure;

        if (slotsOccupes[cleVerification]) {
            boutonSlot.classList.add('btn-secondary', 'opacity-25', 'disabled');
            boutonSlot.removeAttribute('href');
            boutonSlot.title = "Déjà réservé";
        } else {
            boutonSlot.classList.add('btn-outline-light', 'text-warning', 'border-warning');
            boutonSlot.href = `/coiffons/client/reserver.php?coiffeur_id=${coiffeurId}&presta_id=${prestaId}&date_rdv=${dateChoisie}&heure_debut=${slotHeure}`;
        }

        containerSlots.appendChild(boutonSlot);
        heureCourante += 1;
    }

    document.getElementById('zone-slots-horaires').classList.remove('d-none');
}
</script>

<style>
    .day-selector-card {
    /* flex-grow: 1 permet aux cartes de s'étirer pour remplir l'espace */
    flex: 1 1 75px; 
    max-width: 90px;
    padding: 12px 5px;
    text-align: center;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    background-color: #1a1a1a;
}
    .day-item-open {
        border-color: rgba(25, 135, 84, 0.3);
    }
    .day-item-open:hover {
        background-color: #222;
        border-color: var(--gold);
    }
    .day-item-closed {
        border-color: rgba(220, 53, 69, 0.4);
        background-color: rgba(220, 53, 69, 0.05);
        opacity: 0.5;
        cursor: not-allowed;
    }
    .active-day {
        background-color: #ffc107 !important;
        color: black !important;
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
    }
    .active-day span {
        color: black !important;
    }
    .style-slots-box {
        background-color: #111;
        border: 1px dashed rgba(212, 175, 55, 0.3);
    }
    .luxury-profile-card {
        background-color: #121212 !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 12px !important;
        overflow: hidden;
    }
    .luxury-img-container {
        width: 100%;
        height: 240px;
        overflow: hidden;
        background-color: #1a1a1a;
    }
    .luxury-img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .profile-placeholder-img {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #151515 0%, #252525 100%);
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>