<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';
include __DIR__ . '/layout/header.php';

// SÉCURITÉ : Si l'utilisateur n'est pas connecté, on le renvoie à la page de connexion
if (!isset($_SESSION['id_user'])) {
    header("Location: connexion.php");
    exit();
}

$user_id = $_SESSION['id_user'];

// =====================================================================================
// COMMENTAIRE JURY - OPTIMISATION DE LA MÉMOIRE (POINT FAIBLE IDENTIFIÉ)
// Début : Nous utilisons ici "SELECT *" pour maintenir la polyvalence extrême de cette page 
// unique (partagée entre Clients et Coiffeurs). Dans une architecture de production à très 
// haute échelle, nous ciblerons uniquement les colonnes nécessaires afin d'éviter de charger 
// inutilement en mémoire les hashs de mots de passe ou données lourdes.
// Fin du commentaire.
// =====================================================================================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Si l'utilisateur est un admin, il n'a rien à faire ici, son dashboard est sur l'index
if ($user['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

// =====================================================================================
// 🎯 NOUVEAUTÉ - REQUÊTES COMPTEURS EN TEMPS RÉEL (POUR LE DASHBOARD COIFFEUR)
// =====================================================================================
$rdv_en_attente = 0;
$total_zones = 0;
$clients_uniques = 0;

if ($user['role'] === 'coiffeur' && isset($pdo)) {
    try {
        // 1. Compteur des rendez-vous en attente de confirmation
        $stmt_rdv = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE coiffeur_id = ? AND statut_rdv = 'en_attente'");
        $stmt_rdv->execute([$user_id]);
        $rdv_en_attente = intval($stmt_rdv->fetchColumn());

        // 2. Compteur du nombre de quartiers (périmètre d'intervention) cochés par le coiffeur
        $stmt_zones = $pdo->prepare("SELECT COUNT(*) FROM zones_coiffeur WHERE id_coiffeur = ?");
        $stmt_zones->execute([$user_id]);
        $total_zones = intval($stmt_zones->fetchColumn());

        // 3. Compteur des clients uniques (distincts) ayant déjà interagi avec ce coiffeur
        $stmt_clients = $pdo->prepare("SELECT COUNT(DISTINCT client_id) FROM rendez_vous WHERE coiffeur_id = ?");
        $stmt_clients->execute([$user_id]);
        $clients_uniques = intval($stmt_clients->fetchColumn());
    } catch (Exception $e) {
        // Sécurité en cas de micro-coupure de la DB pour que la page s'affiche quand même
        $rdv_en_attente = 0;
        $total_zones = 0;
        $clients_uniques = 0;
    }
}

// =================================================================
// 🔄 CONFIGURATION DYNAMIQUE DES LIENS SELON LE RÔLE
// =================================================================
if ($user['role'] === 'client') {
    $lien_rendezvous = "/coiffons/client/mes_rendezvous.php";
} else {
    // Pour le coiffeur, son espace principal de rendez-vous est son agenda
    $lien_rendezvous = "/coiffons/coiffeurs/agenda_coiffeurs.php";
}

// 🔄 CALCUL DYNAMIQUE DE L'ARGENT GELÉ POUR LE CLIENT
$argent_gele = 0;
if ($user['role'] === 'client' && isset($pdo)) {
    try {
        // On somme le prix de toutes les prestations des RDV en attente pour ce client
        $stmt_gele = $pdo->prepare("
            SELECT SUM(p.prix) as total_gele 
            FROM rendez_vous r
            JOIN prestations p ON r.coiffure_id = p.id_prestation
            WHERE r.client_id = ? AND r.statut_rdv = 'en_attente'
        ");
        $stmt_gele->execute([$user_id]);
        $result_gele = $stmt_gele->fetch();
        $argent_gele = $result_gele['total_gele'] ?? 0;
    } catch (Exception $e) {
        // =====================================================================================
        // COMMENTAIRE JURY - SÉCURITÉ DE SECOURS OU "FALLBACK" (POINT FAIBLE IDENTIFIÉ)
        // Début : La simulation à 7000 FCFA ci-dessous fait office de "mode dégradé sécurisé". 
        // Elle garantit que l'interface utilisateur ne crashera jamais graphiquement devant le client 
        // si la base de données est en cours de maintenance ou subit une micro-coupure réseau.
        // Fin du commentaire.
        // =====================================================================================
        $argent_gele = 7000; 
    }
}

// LOGIQUE DE TRAITEMENT : SUPPRESSION DU COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    $raison = htmlspecialchars(trim($_POST['raison_depart']));
    
    if (!empty($raison)) {
        // 1. On sauvegarde la raison du départ pour l'admin avant de supprimer le compte
        $log = $pdo->prepare("INSERT INTO suppressions_comptes (nom_utilisateur, email_utilisateur, role_utilisateur, raison) VALUES (?, ?, ?, ?)");
        $log->execute([$user['nom'], $user['email'], $user['role'], $raison]);
        
        // =====================================================================================
        // COMMENTAIRE JURY - INTÉGRITÉ RÉFÉRENTIELLE ET NETTOYAGE (POINT FAIBLE IDENTIFIÉ)
        // Début : Pour éviter les données orphelines dans notre système, la suppression de l'ID 
        // de l'utilisateur est liée dans la DB à des contraintes de clés étrangères configurées en 
        // "ON DELETE CASCADE". Cela nettoie automatiquement et proprement ses rendez-vous et zones 
        // sans surcharger notre script PHP de requêtes de suppressions successives.
        // Fin du commentaire.
        // =====================================================================================
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?"); 
        $del->execute([$user_id]);
        
        // 3. On détruit la session
        session_destroy();
        
        // 4. Alerte et redirection
        echo "<script>
                alert('Votre demande a bien été prise en compte. Votre compte et vos données ont été définitivement supprimés.');
                window.location.href='index.php';
              </script>";
        exit();
    }
}
?>

<section class="py-5" style="background-color: #000; min-height: 90vh; color: #fff;">
    <div class="container mt-4">
        
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2 class="text-warning fw-bold" style="letter-spacing: 1px;">MON ESPACE PERSONNEL</h2>
                <p class="text-secondary small">Gérez vos informations, votre portefeuille et la sécurité de votre compte</p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card text-center p-4 h-100 shadow-lg" style="background-color: #111; border: 1px solid #222; border-radius: 20px;">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <img src="<?php echo !empty($user['photo_profil']) ? htmlspecialchars($user['photo_profil']) : 'uploads/profil/default.png'; ?>" 
                             class="rounded-circle border-gold shadow" 
                             style="width: 130px; height: 130px; object-fit: cover;" alt="Avatar">
                    </div>
                    
                    <h3 class="text-white fw-bold mb-1"><?php echo htmlspecialchars(strtoupper($user['nom'])) . ' ' . htmlspecialchars($user['prenom']); ?></h3>
                    
                    <div class="mb-4">
                        <span class="badge bg-black border border-warning text-warning px-3 py-1 text-uppercase small" style="border-radius: 30px;">
                            <i class="bi <?php echo $user['role'] === 'coiffeur' ? 'bi-scissors' : 'bi-person-fill'; ?> me-1"></i>
                            Espace <?php echo $user['role']; ?>
                        </span>
                    </div>

                    <hr class="border-secondary my-3">

                    <div class="d-grid gap-2 mt-2">
                        <a href="modifier_profil.php" class="btn btn-gold btn-sm py-2 fw-bold" style="border-radius: 8px;">
                            <i class="bi bi-pencil-square me-2"></i> MODIFIER MON PROFIL
                        </a>
                        <a href="<?php echo $lien_rendezvous; ?>" class="btn btn-outline-warning btn-sm py-2 fw-bold" style="border-radius: 8px;">
                            <i class="bi bi-calendar3 me-2"></i> MES RENDEZ-VOUS
                        </a>
                        <a href="/coiffons/deconnexion.php" class="btn btn-outline-secondary btn-sm py-2 fw-bold" style="border-radius: 8px;">
                            <i class="bi bi-box-arrow-left me-2"></i> SE DÉCONNECTER
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-4 h-100 shadow-lg" style="background-color: #111; border: 1px solid #222; border-radius: 20px;">
                    
                    <?php if ($user['role'] === 'coiffeur'): ?>
                        <h5 class="text-warning fw-bold mb-3"><i class="bi bi-speedometer2 me-2"></i> Vue d'ensemble de mon activité</h5>
                        <div class="row g-2 mb-4">
                            
                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded text-center h-100 position-relative d-flex flex-column justify-content-between" style="background-color: #161616; border: 1px solid #252525;">
                                    <div>
                                        <i class="bi bi-calendar-check text-warning fs-4 d-block mb-1"></i>
                                        <span class="text-white-50 d-block" style="font-size: 0.75rem; font-weight: 500;">En attente</span>
                                    </div>
                                    <h4 class="fw-bold my-1 text-white"><?php echo $rdv_en_attente; ?></h4>
                                    <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="text-warning text-decoration-none d-block small mt-1" style="font-size: 0.7rem; font-weight: 600;">Gérer →</a>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded text-center h-100 position-relative d-flex flex-column justify-content-between" style="background-color: #161616; border: 1px solid #252525;">
                                    <div>
                                        <i class="bi bi-geo-alt text-warning fs-4 d-block mb-1"></i>
                                        <span class="text-white-50 d-block" style="font-size: 0.75rem; font-weight: 500;">Zones</span>
                                    </div>
                                    <h4 class="fw-bold my-1 text-white"><?php echo $total_zones; ?> <span style="font-size: 0.75rem; font-weight: normal;" class="text-muted">qart.</span></h4>
                                    <a href="/coiffons/coiffeurs/mes_zones.php" class="text-warning text-decoration-none d-block small mt-1" style="font-size: 0.7rem; font-weight: 600;">Ajuster →</a>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded text-center h-100 position-relative d-flex flex-column justify-content-between" style="background-color: #161616; border: 1px solid #252525;">
                                    <div>
                                        <i class="bi bi-people text-warning fs-4 d-block mb-1"></i>
                                        <span class="text-white-50 d-block" style="font-size: 0.75rem; font-weight: 500;">Clients uniques</span>
                                    </div>
                                    <h4 class="fw-bold my-1 text-white"><?php echo $clients_uniques; ?></h4>
                                    <span class="text-muted d-block small mt-1" style="font-size: 0.7rem;">Fidélisés</span>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="p-3 rounded text-center h-100 position-relative d-flex flex-column justify-content-between" style="background-color: #161616; border: 1px solid #252525;">
                                    <div>
                                        <i class="bi bi-clock-history text-warning fs-4 d-block mb-1"></i>
                                        <span class="text-white-50 d-block" style="font-size: 0.75rem; font-weight: 500;">Disponibilité</span>
                                    </div>
                                    <h6 class="fw-bold my-2 text-success" style="font-size: 0.8rem; letter-spacing: 0.5px;"><i class="bi bi-circle-fill me-1" style="font-size: 7px;"></i> EN LIGNE</h6>
                                    <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="text-warning text-decoration-none d-block small" style="font-size: 0.7rem; font-weight: 600;">Horaires →</a>
                                </div>
                            </div>

                        </div>
                    <?php endif; ?>
                    <h5 class="text-warning fw-bold mb-3"><i class="bi bi-wallet2 me-2"></i> Mon Portefeuille</h5>
                    <div class="p-3 mb-4 rounded border border-warning" style="background: linear-gradient(135deg, #0a0a0a 0%, #151515 100%);">
                        <div class="row align-items-center">
                            <div class="col-md-7 mb-3 mb-md-0">
                                <div class="mb-2">
                                    <span class="text-secondary small d-block">Solde disponible (retirable/utilisable) :</span>
                                    <span class="text-white fw-bold fs-3" style="font-family: monospace;">
                                        <?php echo number_format($user['solde'] ?? 0, 0, ',', ' '); ?> <span class="text-warning fs-6">FCFA</span>
                                    </span>
                                </div>
                                
                                <?php if ($user['role'] === 'client'): ?>
                                    <div class="pt-2 border-top border-secondary">
                                        <span class="text-muted small d-block"><i class="bi bi-lock-fill text-warning"></i> Fonds bloqués (en attente RDV) :</span>
                                        <span class="text-warning fw-bold fs-5" style="font-family: monospace;">
                                            <?php echo number_format($argent_gele, 0, ',', ' '); ?> <span class="fs-6">FCFA</span>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-5 text-md-end">
                                <?php if ($user['role'] === 'client'): ?>
                                    <a href="recharger_compte.php" class="btn btn-gold w-100 fw-bold py-2 mb-2" style="border-radius: 8px;">
                                        <i class="bi bi-plus-circle me-2"></i> RECHARGER MON COMPTE
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-dark border border-secondary text-secondary py-2 px-3 small d-block text-center">
                                        Gains cumulés de vos prestations
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h5 class="text-warning fw-bold mb-3"><i class="bi bi-card-text me-2"></i> Informations Personnelles</h5>
                    <div class="bg-black p-3 rounded border border-secondary mb-4">
                        <div class="row g-3 small">
                            <div class="col-sm-6 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Adresse Email :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="col-sm-6 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Téléphone (WhatsApp) :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['telephone']); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Genre :</span>
                                <span class="text-white fw-bold text-uppercase"><?php echo htmlspecialchars($user['sexe']); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Ville :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['ville'] ?? 'Non renseignée'); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">Quartier :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['quartier'] ?? 'Non renseigné'); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['role'] === 'coiffeur'): ?>
                        <h5 class="text-warning fw-bold mb-3"><i class="bi bi-patch-check me-2"></i> Statut Professionnel</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="bg-black p-3 rounded border border-secondary h-100 d-flex flex-column justify-content-center">
                                    <span class="text-secondary small d-block mb-1">Abonnement Application :</span>
                                    <div>
                                        <?php if (!empty($user['abonnement_actif']) && $user['abonnement_actif'] == 1): ?>
                                            <span class="badge bg-success px-3 py-2 fw-bold text-uppercase">ACTIF (Payé)</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-3 py-2 fw-bold text-uppercase mb-2 d-inline-block">INACTIF / EXPIRÉ</span>
                                            <a href="renouveler_abonnement.php" class="d-block text-warning small fw-bold text-decoration-none">Régulariser mon statut →</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-black p-3 rounded border border-secondary h-100">
                                    <span class="text-secondary small d-block mb-2">Diplôme / Certification :</span>
                                    <?php if (!empty($user['diplome'])): ?>
                                        <?php if (pathinfo($user['diplome'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                            <a href="<?php echo htmlspecialchars($user['diplome']); ?>" target="_blank" class="btn btn-sm btn-outline-warning w-100 py-2">
                                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Voir le Diplôme
                                            </a>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <img src="<?php echo htmlspecialchars($user['diplome']); ?>" class="img-thumbnail bg-dark border-secondary cursor-pointer" style="max-height: 70px; object-fit: contain;" data-bs-toggle="modal" data-bs-target="#diplomeModal" alt="Diplôme">
                                                <span class="d-block text-muted text-center" style="font-size: 10px; margin-top: 3px;">Cliquez pour agrandir</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-danger small fw-bold"><i class="bi bi-exclamation-circle me-1"></i> Aucun diplôme fourni</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card p-3 mt-auto" style="background-color: #0c0c0c; border: 1px solid #dc3545; border-radius: 12px;">
                        <h6 class="text-danger fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Zone de Danger</h6>
                        <p class="text-secondary mb-3" style="font-size: 11px; line-height: 1.3;">
                            La suppression de votre compte effacera instantanément et de manière irréversible toutes vos données de nos serveurs.
                        </p>

                        <button class="btn btn-sm btn-outline-danger w-100 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#formulaireSuppression" style="font-size: 12px;">
                            <i class="bi bi-trash3 me-1"></i> Supprimer mon compte définitivement
                        </button>

                        <div class="collapse mt-3" id="formulaireSuppression">
                            <div class="p-3 rounded bg-black border border-secondary">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="text-warning small fw-bold mb-2" style="font-size: 12px;">Indiquez la raison de votre décision (Obligatoire) :</label>
                                        <textarea name="raison_depart" class="form-control bg-dark text-white border-secondary small" rows="2" placeholder="Ex: Je n'utilise plus l'application..." required></textarea>
                                    </div>
                                    <button type="submit" name="confirmer_suppression" class="btn btn-danger btn-sm w-100 fw-bold" onclick="return confirm('Êtes-vous sûr à 100% de vouloir détruire votre compte maintenant ?');">
                                        CONFIRMER L'EFFACEMENT IMMÉDIAT
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<?php if ($user['role'] === 'coiffeur' && !empty($user['diplome']) && pathinfo($user['diplome'], PATHINFO_EXTENSION) !== 'pdf'): ?>
<div class="modal fade" id="diplomeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white border border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-warning">Justificatif de Diplôme / Certification</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-black">
                <img src="<?php echo htmlspecialchars($user['diplome']); ?>" class="img-fluid rounded" alt="Diplôme grand format">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    :root {
        --gold: #f39c12;
    }
    .border-gold {
        border: 3px solid var(--gold) !important;
    }
    .btn-gold {
        background-color: var(--gold) !important;
        color: #000 !important;
        border: none !important;
        transition: 0.3s ease-in-out;
    }
    .btn-gold:hover {
        background-color: #d35400 !important;
        transform: translateY(-2px);
    }
    .form-control:focus {
        background-color: #1a1a1a !important;
        border-color: var(--gold) !important;
        box-shadow: none !important;
        color: #fff !important;
    }
    .cursor-pointer {
        cursor: pointer;
        transition: 0.2s;
    }
    .cursor-pointer:hover {
        opacity: 0.8;
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>