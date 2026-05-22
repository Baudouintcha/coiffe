<?php
// 1. TOUT EN HAUT : Sécurité et Session (AVANT d'inclure le header)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sécurité : Si l'utilisateur n'est pas connecté ou n'est pas un client, redirection radicale sans plantage
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    echo "<script>window.location.href='connexion.php';</script>";
    exit();
}

// Maintenant que la sécurité est passée, on inclut le visuel et la configuration
require_once __DIR__ . '/../security/config.php';
include __DIR__ . '/../layout/header.php';

// Utilisation de la clé de session harmonisée
$id_client = $_SESSION['id_user'];

$rendezvous = [];

// Récupération de l'historique avec TES vrais attributs : client_id, coiffeur_id, coiffure_id, statut_rdv
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT r.*, p.nom_style, p.prix, u.nom AS coiffeur_nom, u.prenom AS coiffeur_prenom, u.telephone AS coiffeur_telephone 
                               FROM rendez_vous r 
                               JOIN prestations p ON r.coiffure_id = p.id_prestation 
                               JOIN users u ON r.coiffeur_id = u.id 
                               WHERE r.client_id = ? 
                               ORDER BY r.date_rdv DESC, r.heure_rdv DESC");
        $stmt->execute([$id_client]);
        $rendezvous = $stmt->fetchAll();
    } catch (Exception $e) {
        // Évite le plantage visuel si les tables subissent des ajustements en BDD
    }
}

// =========================================================================
// TRUC A ENLEVER APRES LES TESTS : INJECTION DE FAUX RDV SI LA BDD EST VIDE
// (Tu pourras supprimer tout ce bloc IF ci-dessous quand tes tests seront finis)
// =========================================================================
if (empty($rendezvous)) {
    $rendezvous = [
        [
            'date_rdv' => date('Y-m-d', strtotime('+1 days')),
            'heure_rdv' => '14:00:00',
            'coiffeur_nom' => 'Koffi',
            'coiffeur_prenom' => 'Marc',
            'nom_style' => 'Dégradé Américain (Waves)',
            'prix' => 3000,
            'adresse_prestation' => 'Mènontin, Rue 402, Cotonou',
            'statut_rdv' => 'en_attente',
            'coiffeur_telephone' => '+22990000001'
        ],
        [
            'date_rdv' => date('Y-m-d', strtotime('+3 days')),
            'heure_rdv' => '09:30:00',
            'coiffeur_nom' => 'Gandonou',
            'coiffeur_prenom' => 'Chantal',
            'nom_style' => 'Tresses Africaines (Nattes)',
            'prix' => 7000,
            'adresse_prestation' => 'Fidjrossè, Cotonou',
            'statut_rdv' => 'confirme',
            'coiffeur_telephone' => '+22990000002'
        ],
        [
            'date_rdv' => date('Y-m-d', strtotime('-5 days')),
            'heure_rdv' => '11:00:00',
            'coiffeur_nom' => 'Dossou',
            'coiffeur_prenom' => 'Innocent',
            'nom_style' => 'Dreadlocks Premium',
            'prix' => 12000,
            'adresse_prestation' => 'Calavi, Kpota',
            'statut_rdv' => 'termine',
            'coiffeur_telephone' => '+22990000003'
        ]
    ];
}
// =========================================================================
// FIN DU TRUC A ENLEVER APRES LES TESTS
// =========================================================================
?>

<section class="py-5" style="background-color: #000; min-height: 90vh;">
    <div class="container mt-4">
        <h2 class="text-center text-warning mb-5" style="letter-spacing: 2px;">MES RENDEZ-VOUS</h2>

        <div class="table-responsive">
            <table class="table table-dark table-bordered border-warning align-middle">
                <thead>
                    <tr class="text-warning">
                        <th>Date & Heure</th>
                        <th>Coiffeur</th>
                        <th>Prestation</th>
                        <th>Montant</th>
                        <th>Lieu</th>
                        <th>Statut</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rendezvous)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">
                                <i class="bi bi-calendar-x fs-2"></i><br>
                                Vous n'avez aucune réservation pour le moment.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rendezvous as $rdv): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($rdv['date_rdv']))); ?></strong><br>
                                    <small class="text-secondary"><?php echo htmlspecialchars(substr($rdv['heure_rdv'], 0, 5)); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(strtoupper($rdv['coiffeur_nom'] . ' ' . $rdv['coiffeur_prenom'])); ?></td>
                                <td><?php echo htmlspecialchars($rdv['nom_style']); ?></td>
                                <td class="text-success fw-bold">
                                    <?php echo number_format($rdv['prix'], 0, ',', ' '); ?> FCFA
                                </td>
                                <td><?php echo htmlspecialchars($rdv['adresse_prestation']); ?></td>
                                <td>
                                    <?php if ($rdv['statut_rdv'] == 'en_attente'): ?>
                                        <span class="badge bg-warning text-dark mb-1 d-inline-block">En attente de validation</span><br>
                                        <span class="badge bg-outline-warning border border-warning text-warning small style="font-size: 0.75rem;"><i class="bi bi-shield-lock"></i> Argent Gelé</span>
                                    <?php elseif ($rdv['statut_rdv'] == 'confirme'): ?>
                                        <span class="badge bg-primary">Confirmé</span>
                                    <?php elseif ($rdv['statut_rdv'] == 'termine'): ?>
                                        <span class="badge bg-success">Terminé</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($rdv['statut_rdv']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $rdv['coiffeur_telephone']); ?>" target="_blank" class="btn btn-success btn-sm">
                                        <i class="bi bi-whatsapp"></i> Contacter
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-5">
            <a href="/coiffons/client/catalogue.php" class="btn btn-gold px-5 py-2 fw-bold">
                <i class="bi bi-scissors"></i> Retour au catalogue
            </a>
        </div>
    </div>
</section>

<style>
    .btn-gold {
        background-color: var(--gold);
        color: black;
        border: none;
        border-radius: 8px;
    }

    .btn-gold:hover {
        background-color: #c99b2c;
    }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>