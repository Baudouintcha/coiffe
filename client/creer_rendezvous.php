<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// SÉCURITÉ : Strictement réservé aux clients connectés
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: ../access/connexion.php");
    exit();
}

$id_client = $_SESSION['id_user'];
$id_prestation = isset($_GET['id_prestation']) ? intval($_GET['id_prestation']) : 0;
$erreur = "";
$succes = "";

// 1. RÉCUPÉRATION DE LA ZONE GÉOGRAPHIQUE DU CLIENT
$ville_client = 1; 
if (isset($pdo)) {
    $stmt_client = $pdo->prepare("SELECT ville FROM users WHERE id = ?");
    $stmt_client->execute([$id_client]);
    $client_zone = $stmt_client->fetch();
    if ($client_zone) {
        $ville_client = $client_zone['ville'];
    }
}

// 2. CAS DU CATALOGUE : RÉCUPÉRATION DE LA BDD
$prestations_zone = [];
if ($id_prestation === 0) {
    if (isset($pdo)) {
        try {
            $stmt_zone = $pdo->prepare("
                SELECT p.id_prestation, p.nom_style, p.prix, u.nom AS nom_coiffeur, u.prenom AS prenom_coiffeur
                FROM prestations p 
                JOIN users u ON p.id_coiffeur = u.id 
                WHERE u.role = 'coiffeur' AND u.ville = ?
                ORDER BY RAND()
            ");
            $stmt_zone->execute([$ville_client]);
            $prestations_zone = $stmt_zone->fetchAll();
        } catch (Exception $e) {
            // Anti-crash si structure en cours de manipulation
        }
    }

    // =========================================================================
    // TRUC A ENLEVER APRES LES TESTS : INJECTION DE COIFFURES PAR DÉFAUT
    // (Tu pourras supprimer tout ce bloc IF ci-dessous quand ta BDD sera remplie)
    // =========================================================================
    if (empty($prestations_zone)) {
        $prestations_zone = [
            [
                'id_prestation' => 101,
                'nom_style' => 'Dégradé Américain (Waves)',
                'prix' => 3000,
                'prenom_coiffeur' => 'Marc',
                'nom_coiffeur' => 'Koffi',
                'image_test' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=500&q=80',
                'description' => 'Un dégradé à blanc ultra-propre avec des contours au rasoir dessinés au millimètre.'
            ],
            [
                'id_prestation' => 102,
                'nom_style' => 'Tresses Africaines (Nattes)',
                'prix' => 7000,
                'prenom_coiffeur' => 'Chantal',
                'nom_coiffeur' => 'Gandonou',
                'image_test' => 'https://images.unsplash.com/photo-1605497746445-97d1b0a9eaf4?w=500&q=80',
                'description' => 'Des nattes collées protectrices et soignées. Idéal pour nourrir et laisser reposer vos cheveux.'
            ],
            [
                'id_prestation' => 103,
                'nom_style' => 'Dreadlocks & Locks Roots',
                'prix' => 12000,
                'prenom_coiffeur' => 'Innocent',
                'nom_coiffeur' => 'Dossou',
                'image_test' => 'https://images.unsplash.com/photo-1595642527925-4d41cb781653?w=500&q=80',
                'description' => 'Départ ou reprise de locks au crochet. Soin complet du cuir chevelu inclus.'
            ]
        ];
    }
    // =========================================================================
    // FIN DU TRUC A ENLEVER APRES LES TESTS
    // =========================================================================
}

// 3. CAS DU CALENDRIER : CHARGEMENT DES INFOS DU STYLE SÉLECTIONNÉ
$prestation = null;
if ($id_prestation > 0) {
    
    // =========================================================================
    // TRUC A ENLEVER APRES LES TESTS : SIMULATION DU CLIC SUR UN STYLE DE TEST
    // =========================================================================
    if ($id_prestation >= 101) {
        $simulation_zone = [
            101 => ['id_prestation' => 101, 'nom_style' => 'Dégradé Américain (Waves)', 'prix' => 3000, 'prenom_coiffeur' => 'Marc', 'nom_coiffeur' => 'Koffi'],
            102 => ['id_prestation' => 102, 'nom_style' => 'Tresses Africaines (Nattes)', 'prix' => 7000, 'prenom_coiffeur' => 'Chantal', 'nom_coiffeur' => 'Gandonou'],
            103 => ['id_prestation' => 103, 'nom_style' => 'Dreadlocks & Locks Roots', 'prix' => 12000, 'prenom_coiffeur' => 'Innocent', 'nom_coiffeur' => 'Dossou']
        ];
        $prestation = $simulation_zone[$id_prestation] ?? null;
    } 
    // =========================================================================
    // FIN DU TRUC A ENLEVER
    // =========================================================================
    else if (isset($pdo)) {
        $stmt = $pdo->prepare("
            SELECT p.*, u.nom AS nom_coiffeur, u.prenom AS prenom_coiffeur, v.nom_ville, q.nom_quartier
            FROM prestations p
            JOIN users u ON p.id_coiffeur = u.id
            LEFT JOIN villes v ON u.ville = v.id
            LEFT JOIN quartiers q ON u.id_quartier = q.id
            WHERE p.id_prestation = ?
        ");
        $stmt->execute([$id_prestation]);
        $prestation = $stmt->fetch();
    }
}

// Récupération du solde du client (Fictif ou Réel)
$solde_client = 15000; 
if (isset($pdo)) {
    $stmt_wallet = $pdo->prepare("SELECT solde FROM portefeuilles WHERE user_id = ?");
    $stmt_wallet->execute([$id_client]);
    $wallet = $stmt_wallet->fetch();
    if ($wallet) { $solde_client = $wallet['solde']; }
}

// 4. TRAITEMENT DU FORMULAIRE DE RÉSERVATION (BOUTON CLIQUÉ)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prestation = intval($_POST['id_prestation']);
    $date_rdv = trim($_POST['date_rdv']);
    $heure_rdv = trim($_POST['heure_rdv']);
    
    if (empty($date_rdv) || empty($heure_rdv)) {
        $erreur = "Veuillez sélectionner un jour et une heure.";
    } else {
        // Détermination du prix pour la notification dynamique
        $prix_animation = 0;
        if ($id_prestation >= 101) {
            $simulation_zone = [101 => 3000, 102 => 7000, 103 => 12000];
            $prix_animation = $simulation_zone[$id_prestation] ?? 5000;
        } else if (isset($pdo)) {
            $stmt_p = $pdo->prepare("SELECT prix FROM prestations WHERE id_prestation = ?");
            $stmt_p->execute([$id_prestation]);
            $p_data = $stmt_p->fetch();
            $prix_animation = $p_data['prix'] ?? 0;
        }

        $prix_formate = number_format($prix_animation, 0, ',', ' ');

        // Injection du HTML personnalisé et explicatif dans la notification
        $succes = "
            <div class='text-start'>
                <h5 class='text-success fw-bold'><i class='bi bi-send-check-fill'></i> Demande envoyée au coiffeur !</h5>
                <p class='mb-2 small text-white-50'>Le professionnel a été notifié de votre demande pour le créneau choisi.</p>
                <div class='p-3 rounded bg-dark border border-warning my-3'>
                    <span class='text-warning fw-bold d-block'><i class='bi bi-shield-lock-fill'></i> Sécurisation des fonds :</span>
                    <span class='fs-5 fw-bold font-monospace text-white'>$prix_formate F CFA</span> ont été temporairement <strong>gelés</strong> sur votre portefeuille.
                </div>
                <small class='text-muted d-block italic'>* Si le coiffeur décline ou ne répond pas, ce montant sera instantanément libéré sur votre solde.</small>
            </div>
        ";
    }
}
?>

<section class="py-5" style="background-color: #000; min-height: 90vh; color: #fff;">
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <h2 class="text-center text-warning fw-bold mb-4">
                    <i class="bi bi-clock-history"></i> PRENDRE RENDEZ-VOUS
                </h2>

                <?php if (!empty($erreur)): ?>
                    <div class="alert alert-danger text-center fw-bold"><?php echo $erreur; ?></div>
                <?php endif; ?>

                <?php if (!empty($succes)): ?>
                    <div class="alert bg-dark border border-success text-white p-4 mb-4" style="border-radius: 15px;">
                        <?php echo $succes; ?>
                        <hr class="border-secondary">
                        <div class="text-center">
                            <a href="creer_rendezvous.php" class="btn btn-warning btn-sm fw-bold text-black px-4" style="border-radius: 8px;">
                                <i class="bi bi-calendar-plus"></i> Planifier un autre RDV
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($succes)): ?>
                    
                    <?php if ($id_prestation === 0): ?>
                        <div class="card p-4 mb-4" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                            <h5 class="text-warning mb-4 text-center fw-bold">
                                <i class="bi bi-geo-alt-fill text-danger"></i> Coiffures disponibles dans votre secteur
                            </h5>
                            
                            <div class="row g-4">
                                <?php foreach ($prestations_zone as $pz): ?>
                                    <?php 
                                        $img = $pz['image_test'] ?? 'https://images.unsplash.com/photo-1562322140-8baeececf3df?w=500&q=80'; 
                                        $desc = $pz['description'] ?? 'Une coiffure soignée réalisée à domicile par un professionnel expérimenté de votre secteur.';
                                    ?>
                                    <div class="col-md-6">
                                        <div class="card bg-dark border-secondary h-100 p-0 overflow-hidden" style="border-radius: 15px;">
                                            
                                            <img src="<?php echo $img; ?>" class="w-100" style="height: 180px; object-fit: cover;" alt="Coiffure">
                                            
                                            <div class="p-3 d-flex flex-column justify-content-between h-100">
                                                <div>
                                                    <h5 class="text-warning fw-bold mb-1"><?php echo htmlspecialchars($pz['nom_style']); ?></h5>
                                                    <small class="text-muted d-block mb-2">💇‍♂️ Par : <?php echo htmlspecialchars($pz['prenom_coiffeur'] . ' ' . $pz['nom_coiffeur']); ?></small>
                                                    
                                                    <p class="small text-secondary mb-3"><?php echo htmlspecialchars($desc); ?></p>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary">
                                                    <span class="fs-4 fw-bold text-warning font-monospace"><?php echo number_format($pz['prix'], 0, ',', ' '); ?> F</span>
                                                    <a href="creer_rendezvous.php?id_prestation=<?php echo $pz['id_prestation']; ?>" class="btn btn-warning btn-sm fw-bold text-black px-3" style="border-radius: 8px;">
                                                        Réserver <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($prestation): ?>
                        <form method="POST" id="formReservation">
                            <input type="hidden" name="id_prestation" value="<?php echo $prestation['id_prestation']; ?>">
                            <input type="hidden" name="date_rdv" id="input_date_rdv" required>
                            <input type="hidden" name="heure_rdv" id="input_heure_rdv" required>

                            <div class="card p-4 mb-4 text-start" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                                <div class="d-flex justify-content-between align-items-start border-bottom border-secondary pb-2 mb-3">
                                    <div>
                                        <h4 class="text-warning font-monospace mb-1"><?php echo htmlspecialchars($prestation['nom_style']); ?></h4>
                                        <small class="text-secondary">Professionnel : <strong class="text-white"><?php echo htmlspecialchars(strtoupper($prestation['prenom_coiffeur'] . ' ' . $prestation['nom_coiffeur'])); ?></strong></small>
                                    </div>
                                    <span class="fs-4 fw-bold text-warning"><?php echo number_format($prestation['prix'], 0, ',', ' '); ?> F</span>
                                </div>
                                <div class="mt-2 text-end small text-success fw-bold">💰 Votre solde : <?php echo number_format($solde_client, 0, ',', ' '); ?> FCFA</div>
                            </div>

                            <h5 class="text-warning mb-2"><i class="bi bi-calendar3"></i> 1. Choisissez le jour du rendez-vous</h5>
                            <div class="days-slider mb-4">
                                <?php 
                                $jours_semaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
                                for ($i = 0; $i < 7; $i++) {
                                    $timestamp = strtotime("+$i days");
                                    $date_val = date('Y-m-d', $timestamp);
                                    $nom_jour = $jours_semaine[date('w', $timestamp)];
                                    $num_jour = date('d', $timestamp);
                                    
                                    echo "
                                    <div class='day-card' data-date='$date_val' onclick='selectDay(this)'>
                                        <span class='day-name'>$nom_jour</span>
                                        <span class='day-num'>$num_jour</span>
                                    </div>";
                                }
                                ?>
                            </div>

                            <div id="section_heures" style="display:none;">
                                <h5 class="text-warning mb-2"><i class="bi bi-clock"></i> 2. À quelle heure le coiffeur doit venir ?</h5>
                                <div class="hours-grid mb-5">
                                    <?php 
                                    $heures_dispos = ['08:00', '09:30', '11:00', '12:30', '14:00', '15:30', '17:00', '18:30', '20:00'];
                                    foreach ($heures_dispos as $h) {
                                        echo "<div class='hour-circle' data-time='$h' onclick='selectHour(this)'>$h</div>";
                                    }
                                    ?>
                                </div>
                            </div>

                            <button type="submit" id="btnValider" class="btn btn-warning w-100 py-3 fw-bold text-uppercase fs-5 text-black" style="display:none; border-radius: 30px; background-color: var(--gold); border:none;">
                                <i class="bi bi-shield-lock-fill"></i> Bloquer ce créneau & Demander
                            </button>

                        </form>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<style>
    /* Slider de Jours (Carrés) */
    .days-slider {
        display: flex; gap: 12px; overflow-x: auto; padding: 10px 0; scrollbar-width: none;
    }
    .days-slider::-webkit-scrollbar { display: none; }
    .day-card {
        background-color: #111; border: 1px solid #333; border-radius: 12px;
        min-width: 75px; height: 85px; display: flex; flex-direction: column;
        justify-content: center; align-items: center; cursor: pointer; transition: all 0.3s ease;
    }
    .day-card:hover { border-color: var(--gold); }
    .day-card.selected {
        background-color: var(--gold) !important; border-color: var(--gold); color: black !important;
        transform: scale(1.05); font-weight: bold;
    }
    .day-card .day-name { font-size: 0.8rem; color: #aaa; }
    .day-card.selected .day-name { color: #222; }
    .day-card .day-num { font-size: 1.4rem; font-weight: bold; }

    /* Grille d'heures (Cercles) */
    .hours-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 15px; padding-top: 10px;
    }
    .hour-circle {
        background-color: #111; border: 1px solid #333; color: #fff; height: 45px;
        border-radius: 25px; display: flex; justify-content: center; align-items: center;
        font-weight: bold; cursor: pointer; transition: all 0.2s ease;
    }
    .hour-circle:hover { border-color: var(--gold); color: var(--gold); }
    .hour-circle.selected {
        background-color: #fff !important; color: #000 !important; border-color: #fff;
        box-shadow: 0 0 10px rgba(255,255,255,0.5);
    }
</style>

<script>
function selectDay(element) {
    document.querySelectorAll('.day-card').forEach(card => card.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('input_date_rdv').value = element.getAttribute('data-date');
    document.getElementById('section_heures').style.display = 'block';
    verifierFormulaire();
}

function selectHour(element) {
    document.querySelectorAll('.hour-circle').forEach(circle => circle.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('input_heure_rdv').value = element.getAttribute('data-time');
    verifierFormulaire();
}

function verifierFormulaire() {
    var date_ok = document.getElementById('input_date_rdv').value;
    var heure_ok = document.getElementById('input_heure_rdv').value;
    if(date_ok && heure_ok) {
        document.getElementById('btnValider').style.display = 'block';
    }
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>