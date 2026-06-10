<?php
// 1. TOUT EN HAUT : Sécurité et Session (AVANT d'inclure le header)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Si l'utilisateur n'est pas connecté ou n'est pas un client, redirection radicale
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    echo "<script>window.location.href='connexion.php';</script>";
    exit();
}

// Génération d'un jeton CSRF pour sécuriser l'annulation automatique
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inclusion de la configuration et de la connexion BDD
require_once __DIR__ . '/../security/config.php';

$id_client = $_SESSION['id_user'];
$message_flash = null;

// =========================================================================
// LE ROBOT AUTOMATIQUE D'ANNULATION (TRAITEMENT DU FORMULAIRE)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_annuler'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message_flash = ['type' => 'danger', 'text' => 'Erreur de sécurité (CSRF invalide).'];
    } else {
        $id_rdv = intval($_POST['id_rdv']);
        
        if (isset($pdo)) {
            try {
                // Récupérer les détails précis du rendez-vous pour le calcul financier
                $stmt = $pdo->prepare("SELECT r.*, p.prix 
                                       FROM rendez_vous r 
                                       JOIN prestations p ON r.coiffure_id = p.id_prestation 
                                       WHERE r.id = ? AND r.client_id = ?");
                $stmt->execute([$id_rdv, $id_client]);
                $rdv = $stmt->fetch();

                if ($rdv) {
                    $date_heure_rdv = new DateTime($rdv['date_rdv'] . ' ' . $rdv['heure_debut']);
                    $maintenant = new DateTime(); // Année courante 2026
                    
                    // Calcul de l'intervalle de temps
                    $interval = $maintenant->diff($date_heure_rdv);
                    $heures_restantes = ($interval->days * 24) + $interval->h;
                    
                    if ($interval->invert) {
                        $heures_restantes = -1; // Le rendez-vous est déjà passé
                    }

                    $prix_total = $rdv['prix'];
                    $remboursement_client = 0;
                    $dedommagement_coiffeur = 0;
                    $cas_regle = "";

                    // Application stricte des règles du robot PHP
                    if ($heures_restantes >= 24) {
                        $remboursement_client = $prix_total;
                        $cas_regle = "Plus de 24h à l'avance : Remboursement intégral (100%).";
                    } elseif ($heures_restantes >= 2 && $heures_restantes < 24) {
                        $remboursement_client = $prix_total * 0.5;
                        $dedommagement_coiffeur = $prix_total * 0.5;
                        $cas_regle = "Moins de 24h à l'avance : Remboursement de 50%. Les 50% restants reviennent au coiffeur.";
                    } else {
                        $dedommagement_coiffeur = $prix_total;
                        $cas_regle = "Dernière minute ou RDV passé : Aucun remboursement. L'intégralité est versée au coiffeur.";
                    }

                    // DÉBUT DE LA TRANSACTION BDD BANCAIRE
                    $pdo->beginTransaction();

                    // 1. Annulation du créneau
                    $update_rdv = $pdo->prepare("UPDATE rendez_vous SET statut_rdv = 'annule' WHERE id = ?");
                    $update_rdv->execute([$id_rdv]);

                    // 2. Crédit Client
                    if ($remboursement_client > 0) {
                        $update_client = $pdo->prepare("UPDATE users SET solde = solde + ? WHERE id = ?");
                        $update_client->execute([$remboursement_client, $id_client]);
                    }

                    // 3. Crédit Coiffeur
                    if ($dedommagement_coiffeur > 0) {
                        $update_coiffeur = $pdo->prepare("UPDATE users SET solde = solde + ? WHERE id = ?");
                        $update_coiffeur->execute([$dedommagement_coiffeur, $rdv['coiffeur_id']]);
                    }

                    $pdo->commit();

                    $message_flash = [
                        'type' => 'success',
                        'text' => "<strong>Rendez-vous annulé avec succès !</strong><br>Règle appliquée : $cas_regle<br>• Recrédité sur votre solde : " . number_format($remboursement_client, 0, ',', ' ') . " FCFA"
                    ];

                } else {
                    $message_flash = ['type' => 'danger', 'text' => 'Rendez-vous introuvable.'];
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $message_flash = ['type' => 'danger', 'text' => 'Une erreur technique est survenue.'];
            }
        }
    }
}

// Récupération de l'historique réel depuis la BDD
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

include __DIR__ . '/../layout/header.php';
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">MES RESERVATIONS</h2>

        <?php if ($message_flash): ?>
            <div class="alert alert-<?php echo $message_flash['type']; ?> alert-dismissible fade show text-center border-0 mb-4" role="alert">
                <?php echo $message_flash['text']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($rendezvous)): ?>
            <div class="alert alert-secondary text-center py-4 border-0">
                <i class="bi bi-calendar-x fs-3 d-block mb-2"></i> Vous n'avez aucun rendez-vous enregistré.
            </div>
        <?php else: ?>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-dark table-hover table-bordered border-warning align-middle text-center">
                    <thead>
                        <tr class="text-warning">
                            <th>Date & Heure</th>
                            <th>Prestation</th>
                            <th>Statut</th>
                            <th>Gestion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rendezvous as $rdv): ?>
                            <tr>
                                <td>
                                    <strong class="text-white"><?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?></strong><br>
                                    <span class="text-warning font-monospace"><?php echo substr($rdv['heure_debut'], 0, 5); ?></span>
                                </td>
                                <td class="text-start ps-3">
                                    <span class="fw-bold"><?php echo htmlspecialchars($rdv['nom_style']); ?></span><br>
                                    <small class="text-success fw-bold"><?php echo number_format($rdv['prix'], 0, ',', ' '); ?> FCFA</small>
                                </td>
                                <td>
                                    <?php if ($rdv['statut_rdv'] == 'en_attente'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-2">En attente</span>
                                    <?php elseif (in_array($rdv['statut_rdv'], ['confirme', 'accepte'])): ?>
                                        <span class="badge bg-primary px-3 py-2">Confirmé</span>
                                    <?php elseif ($rdv['statut_rdv'] == 'termine'): ?>
                                        <span class="badge bg-success px-3 py-2">Terminé</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-3 py-2">Annulé</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-outline-warning btn-sm px-4" data-bs-toggle="modal" data-bs-target="#modalRdv<?php echo $rdv['id']; ?>">
                                        <i class="bi bi-gear"></i> Gérer
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-md-none">
                <?php foreach ($rendezvous as $rdv): ?>
                    <div class="card bg-dark border border-warning mb-3 text-white">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-warning fw-bold"><?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?> - <?php echo substr($rdv['heure_debut'], 0, 5); ?></span>
                                <span class="badge <?php echo ($rdv['statut_rdv'] == 'en_attente') ? 'bg-warning text-dark' : (in_array($rdv['statut_rdv'], ['confirme','accepte']) ? 'bg-primary' : (($rdv['statut_rdv'] == 'termine') ? 'bg-success' : 'bg-secondary')); ?>">
                                    <?php echo ($rdv['statut_rdv'] == 'en_attente') ? 'En attente' : (in_array($rdv['statut_rdv'], ['confirme','accepte']) ? 'Confirmé' : (($rdv['statut_rdv'] == 'termine') ? 'Terminé' : 'Annulé')); ?>
                                </span>
                            </div>
                            <h6 class="mb-1 text-truncate"><?php echo htmlspecialchars($rdv['nom_style']); ?></h6>
                            <p class="text-success fw-bold mb-3"><?php echo number_format($rdv['prix'], 0, ',', ' '); ?> FCFA</p>
                            <button class="btn btn-outline-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#modalRdv<?php echo $rdv['id']; ?>">
                                <i class="bi bi-gear"></i> Gérer la réservation
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-5">
            <a href="/coiffons/client/catalogue.php" class="btn btn-gold px-5 py-2 fw-bold">
                <i class="bi bi-scissors"></i> Retour au catalogue
            </a>
        </div>
    </div>
</section>

<?php foreach ($rendezvous as $rdv): 
    // Calcul PHP préparatoire pour injecter le compte à rebours réel
    $datetime_rdv_brut = $rdv['date_rdv'] . ' ' . $rdv['heure_debut'];
    $dt_rdv = new DateTime($datetime_rdv_brut);
    $dt_now = new DateTime();
    $diff = $dt_now->diff($dt_rdv);
    
    // Calcul global précis du nombre d'heures totales restantes
    $total_heures_restantes = ($diff->days * 24) + $diff->h;
    if ($diff->invert) { 
        $total_heures_restantes = -1; 
    }
?>
<div class="modal fade" id="modalRdv<?php echo $rdv['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border border-warning">
            <div class="modal-header border-bottom border-warning">
                <h5 class="modal-title text-warning"><i class="bi bi-info-circle"></i> Détails du Rendez-vous</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body" id="body-principal-<?php echo $rdv['id']; ?>">
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item bg-dark text-white border-secondary px-0">
                        <small class="text-secondary d-block">Style choisi</small>
                        <span class="fw-bold text-warning"><?php echo htmlspecialchars($rdv['nom_style']); ?></span>
                    </li>
                    <li class="list-group-item bg-dark text-white border-secondary px-0">
                        <small class="text-secondary d-block">Artisan Coiffeur</small>
                        <span><?php echo htmlspecialchars(strtoupper($rdv['coiffeur_nom'] . ' ' . $rdv['coiffeur_prenom'])); ?></span>
                    </li>
                    <li class="list-group-item bg-dark text-white border-secondary px-0">
                        <small class="text-secondary d-block">Lieu fixé</small>
                        <span><i class="bi bi-geo-alt text-danger"></i> <?php echo htmlspecialchars($rdv['adresse_prestation'] ?? 'À domicile'); ?></span>
                    </li>
                    <li class="list-group-item bg-dark text-white border-none px-0 pb-0">
                        <small class="text-secondary d-block">Montant payé (Gelé sur la plateforme)</small>
                        <span class="text-success fw-bold fs-5"><?php echo number_format($rdv['prix'], 0, ',', ' '); ?> FCFA</span>
                    </li>
                </ul>

                <button type="button" class="btn btn-danger w-100 py-2 fw-bold" onclick="afficherPrevention(<?php echo $rdv['id']; ?>)">
                    <i class="bi bi-x-circle"></i> Annuler mon rendez-vous
                </button>
            </div>

            <div class="modal-body d-none" id="prevention-box-<?php echo $rdv['id']; ?>" style="background-color: #0c0c0c;">
                <h5 class="text-center text-danger fw-bold mb-3"><i class="bi bi-shield-exclamation"></i> RÈGLES DE DÉSISTEMENT</h5>
                
                <div class="small mb-4">
                    <div class="p-2 mb-2 rounded border-start border-success border-3 bg-dark">
                        <strong class="text-success">1. Plus de 24h à l'avance :</strong><br>
                        <span class="text-secondary">L'annulation est entièrement gratuite. Vous êtes remboursé à 100%.</span>
                    </div>
                    <div class="p-2 mb-2 rounded border-start border-warning border-3 bg-dark">
                        <strong class="text-warning">2. Entre 2h et 24h à l'avance :</strong><br>
                        <span class="text-secondary">Le coiffeur a bloqué sa journée. 50% du montant est conservé pour le dédommager.</span>
                    </div>
                    <div class="p-2 mb-3 rounded border-start border-danger border-3 bg-dark">
                        <strong class="text-danger">3. Moins de 2h à l'avance :</strong><br>
                        <span class="text-secondary">Abus de dernière minute ou RDV passé. Aucun remboursement ne sera effectué (0%).</span>
                    </div>
                </div>

                <div class="p-3 text-center rounded mb-4" style="background-color: #1a1a1a; border: 1px solid #333;">
                    <span class="text-secondary d-block small mb-1">Temps restant calculé par le robot :</span>
                    <?php if ($total_heures_restantes >= 0): ?>
                        <span class="fs-4 fw-bold text-white font-monospace"><?php echo $total_heures_restantes; ?> heure(s)</span>
                    <?php else: ?>
                        <span class="fs-5 fw-bold text-danger">Rendez-vous imminent ou expiré</span>
                    <?php endif; ?>
                </div>

                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id_rdv" value="<?php echo $rdv['id']; ?>">
                    
                    <button type="button" class="btn btn-secondary w-50" onclick="annulerPrevention(<?php echo $rdv['id']; ?>)">
                        Retour
                    </button>
                    <button type="submit" name="action_annuler" class="btn btn-danger w-50 fw-bold">
                        Accepter & Annuler
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

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

<style>
    .btn-gold { background-color: #ffc107; color: black; border: none; border-radius: 8px; }
    .btn-gold:hover { background-color: #c99b2c; color: black; }
    .border-none { border: none !important; }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>