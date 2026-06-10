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

// =========================================================================
// CONFIGURATION DU MODE TEST (Idéal pour tes simulations avant le jury)
// =========================================================================
$mode_test = true; // Passe à false quand ta table portefeuilles sera active
$solde_test_simulation = 15000; // Modifie cette valeur pour tester (ex: 500 pour voir le blocage)
// =========================================================================

// 1. CHARGEMENT DES INFOS DE LA PRESTATION (ET DU COIFFEUR ASSOCIÉ)
$prestation = null;
if ($id_prestation > 0 && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.id AS id_coiffeur, u.nom AS nom_coiffeur, u.prenom AS prenom_coiffeur, v.nom_ville, q.nom_quartier
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

// Sécurité si aucune prestation n'est reçue
if (!$prestation) {
    echo "<div class='container mt-5 py-5 text-center'><div class='alert alert-danger fw-bold'>Prestation introuvable. Veuillez sélectionner une coiffure valide depuis le catalogue ou le profil d'un coiffeur.</div></div>";
    include __DIR__ . '/../layout/footer.php';
    exit();
}

// 2. RÉCUPÉRATION DU SOLDE (RÉEL OU SIMULÉ)
$solde_client = $solde_test_simulation; 

if (!$mode_test && isset($pdo)) {
    // Si on n'est plus en mode test, on prend le vrai solde en BDD
    $stmt_wallet = $pdo->prepare("SELECT solde FROM portefeuilles WHERE user_id = ?");
    $stmt_wallet->execute([$id_client]);
    $wallet = $stmt_wallet->fetch();
    if ($wallet) { 
        $solde_client = $wallet['solde']; 
    }
}

// 3. BARRIÈRE FINANCIÈRE : On vérifie si le solde est suffisant
$prix_prestation = $prestation['prix'];
if ($solde_client < $prix_prestation) {
    $erreur = "🚨 Solde insuffisant ! Votre solde actuel est de " . number_format($solde_client, 0, ',', ' ') . " F CFA, alors que cette prestation coûte " . number_format($prix_prestation, 0, ',', ' ') . " F CFA. Veuillez recharger votre portefeuille pour pouvoir réserver.";
}

// 4. TRAITEMENT DU FORMULAIRE DE RÉSERVATION (SOUUMISSION POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erreur)) {
    $id_prestation_post = intval($_POST['id_prestation']);
    $date_rdv = trim($_POST['date_rdv']);
    $heure_rdv = trim($_POST['heure_rdv']);
    $id_coiffeur = $prestation['id_coiffeur'];
    
    if (empty($date_rdv) || empty($heure_rdv)) {
        $erreur = "Veuillez sélectionner un jour et une heure pour votre rendez-vous.";
    } else {
        if (isset($pdo)) {
            try {
                // On lance l'écriture du rendez-vous
                $stmt_insert = $pdo->prepare("
                    INSERT INTO rendez_vous (id_client, id_coiffeur, id_prestation, date_rdv, heure_rdv, statut_rdv, date_creation)
                    VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())
                ");
                $stmt_insert->execute([$id_client, $id_coiffeur, $id_prestation_post, $date_rdv, $heure_rdv]);

                // S'il n'est pas en mode test, on met aussi à jour le vrai portefeuille en BDD
                if (!$mode_test) {
                    $nouveau_solde = $solde_client - $prix_prestation;
                    $stmt_update_wallet = $pdo->prepare("UPDATE portefeuilles SET solde = ? WHERE user_id = ?");
                    $stmt_update_wallet->execute([$nouveau_solde, $id_client]);
                    $solde_client = $nouveau_solde;
                } else {
                    // En mode test, on simule juste le calcul visuel
                    $solde_client = $solde_client - $prix_prestation;
                }

                $prix_formate = number_format($prix_prestation, 0, ',', ' ');

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
            } catch (Exception $e) {
                $erreur = "Erreur technique lors de la réservation : " . $e->getMessage();
            }
        }
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
                    <div class="alert alert-danger text-center fw-bold mb-4"><?php echo $erreur; ?></div>
                <?php endif; ?>

                <?php if (!empty($succes)): ?>
                    <div class="alert bg-dark border border-success text-white p-4 mb-4" style="border-radius: 15px;">
                        <?php echo $succes; ?>
                        <hr class="border-secondary">
                        <div class="text-center">
                            <a href="../index.php" class="btn btn-warning btn-sm fw-bold text-black px-4" style="border-radius: 8px;">
                                <i class="bi bi-house-door-fill"></i> Retour à l'accueil
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($erreur) && empty($succes) && $prestation): ?>
                    <form method="POST" id="formReservation">
                        <input type="hidden" name="id_prestation" value="<?php echo $prestation['id_prestation']; ?>">
                        <input type="hidden" name="date_rdv" id="input_date_rdv" required>
                        <input type="hidden" name="heure_rdv" id="input_heure_rdv" required>

                        <div class="card p-4 mb-4 text-start" style="background-color: #111; border: 1px solid var(--gold); border-radius: 15px;">
                            <div class="d-flex justify-content-between align-items-start border-bottom border-secondary pb-2 mb-3">
                                <div>
                                    <h4 class="text-warning font-monospace mb-1"><?php echo htmlspecialchars($prestation['nom_style']); ?></h4>
                                    <small class="text-secondary">Professionnel : <strong class="text-white"><?php echo htmlspecialchars(strtoupper($prestation['prenom_coiffeur'] . ' ' . $prestation['nom_coiffeur'])); ?></strong></small>
                                    <?php if (!empty($prestation['nom_quartier'])): ?>
                                        <br><small class="text-muted"><i class="bi bi-geo-alt"></i> Secteur : <?php echo htmlspecialchars($prestation['nom_quartier']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="fs-4 fw-bold text-warning"><?php echo number_format($prestation['prix'], 0, ',', ' '); ?> F</span>
                            </div>
                            <div class="mt-2 text-end small text-success fw-bold">💰 Votre solde disponible : <?php echo number_format($solde_client, 0, ',', ' '); ?> FCFA <?php echo $mode_test ? '(Mode Simulation)' : ''; ?></div>
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
                            <i class="bi bi-shield-lock-fill"></i> Bloquer ce créneau & Réserver
                        </button>

                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<style>
    .days-slider { display: flex; gap: 12px; overflow-x: auto; padding: 10px 0; scrollbar-width: none; }
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

    .hours-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 15px; padding-top: 10px; }
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