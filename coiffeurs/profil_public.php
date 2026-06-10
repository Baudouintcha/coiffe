<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php'; 
include __DIR__ . '/../layout/header.php';

// 1. SÉCURITÉ ET INTERCEPTION
if (!isset($_SESSION['role']) || $_SESSION['role'] === 'invite') {
    header("Location: /coiffons/access/connexion.php?redirect_reason=booking");
    exit();
}

// 2. RÉCUPÉRATION ET VÉRIFICATION DE L'ID DU COIFFEUR
$id_coiffeur = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_coiffeur) {
    echo "<div class='container py-5 text-center text-white'><h3>Coiffeur introuvable ou ID invalide.</h3><a href='/coiffons/index.php' class='btn btn-warning mt-3'>Retour à l'accueil</a></div>";
    include __DIR__ . '/../layout/footer.php';
    exit();
}

try {
    // 3. REQUÊTE POUR LES INFOS DU COIFFEUR
    $stmt = $pdo->prepare("
        SELECT u.*, v.nom_ville as nom_ville, q.nom_quartier as nom_quartier 
        FROM users u 
        LEFT JOIN villes v ON u.ville = v.id 
        LEFT JOIN quartiers q ON u.id_quartier = q.id 
        WHERE u.id = ? AND u.role = 'coiffeur' AND u.abonnement_status = 1
    ");
    $stmt->execute([$id_coiffeur]);
    $coiffeur = $stmt->fetch();

    if (!$coiffeur) {
        echo "<div class='container py-5 text-center text-white'><h3>Ce coiffeur n'est pas disponible ou son abonnement a expiré.</h3><a href='/coiffons/index.php' class='btn btn-warning mt-3'>Retour à l'accueil</a></div>";
        include __DIR__ . '/../layout/footer.php';
        exit();
    }

    // 4. REQUÊTE POUR LE CATALOGUE DE SES PRESTATIONS
    $stmt_presta = $pdo->prepare("SELECT * FROM prestations WHERE id_coiffeur = ? ORDER BY id_prestation DESC");
    $stmt_presta->execute([$id_coiffeur]);
    $prestations = $stmt_presta->fetchAll();

    // 5. REQUÊTE POUR CALCULER LA NOTE MOYENNE
    $stmt_note = $pdo->prepare("SELECT AVG(note) as moyenne, COUNT(*) as total FROM commentaires WHERE id_coiffeur = ?");
    $stmt_note->execute([$id_coiffeur]);
    $stats_avis = $stmt_note->fetch();
    $note_moyenne = $stats_avis['moyenne'] ? round($stats_avis['moyenne'], 1) : null;

    // =========================================================================
    // ➕ AJOUT TECHNIQUE : RÉCUPÉRATION DES PLAGES HORAIRES & DES RDV EXISTANTS
    // =========================================================================
    // On récupère le planning hebdomadaire du coiffeur
    $stmt_dispo = $pdo->prepare("SELECT * FROM disponibilites WHERE coiffeur_id = ?");
    $stmt_dispo->execute([$id_coiffeur]);
    $dispos_brutes = $stmt_dispo->fetchAll();

    // Organisation en tableau indexé par le nom du jour en français (minuscules)
    $planning_coiffeur = [];
    foreach ($dispos_brutes as $d) {
        $planning_coiffeur[strtolower(trim($d['jour_semaine']))] = [
            'debut' => $d['heure_debut'],
            'fin' => $d['heure_fin']
        ];
    }

    // On récupère tous les rendez-vous déjà pris pour ce coiffeur pour bloquer les doublons (Gris)
    $stmt_rdv = $pdo->prepare("SELECT date_rdv, heure_debut FROM rendez_vous WHERE coiffeur_id = ? AND statut_rdv IN ('en_attente', 'accepte')");
    $stmt_rdv->execute([$id_coiffeur]);
    $rdv_existants = $stmt_rdv->fetchAll(PDO::FETCH_ASSOC);

    // Formatage des réservations pour comparaison rapide en JS : ['2026-06-10 14:00' => true]
    $busy_slots = [];
    foreach ($rdv_existants as $r) {
        $key = $r['date_rdv'] . ' ' . substr($r['heure_debut'], 0, 5);
        $busy_slots[$key] = true;
    }

} catch (Exception $e) {
    echo "<div class='container py-5 text-white'>Erreur technique : " . htmlspecialchars($e->getMessage()) . "</div>";
    include __DIR__ . '/../layout/footer.php';
    exit();
}
?>

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

                    // Vérification de la disponibilité du coiffeur ce jour-là
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
                                    <h5 class="fw-bold text-white mb-2"><?php echo htmlspecialchars($p['nom_style']); ?></h5>
                                    <h4 class="text-warning fw-bold font-monospace mb-3 h5">
                                        <?php echo number_format($p['prix'], 0, ',', ' '); ?> <span style="font-size: 0.75rem;">FCFA</span>
                                    </h4>
                                    <div class="mt-auto">
                                        <a href="/coiffons/client/reserver.php?coiffeur_id=<?php echo $coiffeur['id']; ?>&presta_id=<?php echo $p['id_prestation']; ?>" class="btn btn-outline-warning btn-sm w-100 py-2 fw-bold">
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

</div>

<script>
// Injection sécurisée des créneaux indisponibles (Gris) depuis PHP
const slotsOccupes = <?php echo json_encode($busy_slots); ?>;
const coiffeurId = <?php echo $id_coiffeur; ?>;

function selectionnerJour(element) {
    // Retirer la classe active de tous les autres jours
    document.querySelectorAll('.day-selector-card').forEach(el => el.classList.remove('active-day'));
    
    // Si le jour est fermé (Rouge), on cache la zone et on s'arrête
    if (element.getAttribute('data-open') === '0') {
        document.getElementById('zone-slots-horaires').classList.add('d-none');
        alert("Ce coiffeur ne travaille pas ce jour-là.");
        return;
    }

    // Activer graphiquement le jour sélectionné
    element.classList.add('active-day');
    
    const dateChoisie = element.getAttribute('data-date');
    const heureDebut = element.getAttribute('data-start');
    const heureFin = element.getAttribute('data-end');
    
    const containerSlots = document.getElementById('slots-container');
    containerSlots.innerHTML = ""; // Nettoyage de la grille précédente

    // Découpage des heures d'ouverture et fermeture (Ex: "08:00" -> 8)
    let [hStart, mStart] = heureDebut.split(':').map(Number);
    let [hEnd, mEnd] = heureFin.split(':').map(Number);

    let heureCourante = hStart;
    let minuteCourante = mStart;

    // Boucle de génération des créneaux de 60 minutes de l'heure d'ouverture à fermeture
    while (heureCourante < hEnd || (heureCourante === hEnd && minuteCourante < mEnd)) {
        let formatHeure = String(heureCourante).padStart(2, '0');
        let formatMinute = String(minuteCourante).padStart(2, '0');
        let slotHeure = `${formatHeure}:${formatMinute}`;
        
        // Clé unique pour vérifier si ce créneau précis est déjà pris en BDD
        let cleVerification = `${dateChoisie} ${slotHeure}`;

        let boutonSlot = document.createElement('a');
        boutonSlot.classList.add('btn', 'btn-sm', 'm-1', 'fw-bold', 'px-3', 'py-2');
        boutonSlot.innerText = slotHeure;

        if (slotsOccupes[cleVerification]) {
            // 🪙 CRÉNEAU OCCUPÉ : Devient gris et incliquable
            boutonSlot.classList.add('btn-secondary', 'opacity-25', 'disabled');
            boutonSlot.removeAttribute('href');
            boutonSlot.title = "Déjà réservé";
        } else {
            // 🟢 CRÉNEAU DISPONIBLE : Devient blanc/or cliquable, envoie vers reserver.php
            boutonSlot.classList.add('btn-outline-light', 'text-warning', 'border-warning');
            // Redirection directe avec injection de la date et de l'heure pré-sélectionnée
            boutonSlot.href = `/coiffons/client/reserver.php?coiffeur_id=${coiffeurId}&date_rdv=${dateChoisie}&heure_debut=${slotHeure}`;
        }

        containerSlots.appendChild(boutonSlot);

        // Avancement de 60 minutes pour le créneau suivant
        heureCourante += 1;
    }

    // Rendre la zone visible
    document.getElementById('zone-slots-horaires').classList.remove('d-none');
}
</script>

<style>
    /* --- CSS DE L'AGENDA INTERACTIF --- */
    .day-selector-card {
        width: 85px;
        padding: 12px 5px;
        text-align: center;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        background-color: #1a1a1a;
    }
    /* Vert / Ouvert */
    .day-item-open {
        border-color: rgba(25, 135, 84, 0.3);
    }
    .day-item-open:hover {
        background-color: #222;
        border-color: var(--gold);
    }
    /* Rouge / Fermé */
    .day-item-closed {
        border-color: rgba(220, 53, 69, 0.4);
        background-color: rgba(220, 53, 69, 0.05);
        opacity: 0.5;
        cursor: not-allowed;
    }
    /* Sélection active */
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

    /* --- CODES DU PORTFOLIO D'ORIGINE PRESERVÉS --- */
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