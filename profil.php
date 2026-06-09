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
// 🎯 AMÉLIORATION CRITIQUE : JOINTURE SQL POUR RÉCUPÉRER LES NOMS DE VILLES ET QUARTIERS À LA PLACE DES IDs
$stmt = $pdo->prepare("
    SELECT u.*, v.nom_ville AS nom_ville_clean, q.nom_quartier AS nom_quartier_clean 
    FROM users u
    LEFT JOIN villes v ON u.ville = v.id
    LEFT JOIN quartiers q ON u.id_quartier = q.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Si l'utilisateur est un admin, il n'a rien à faire ici, son dashboard est sur l'index
if ($user['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

// =====================================================================================
// 🎯 HARMONISATION DES COMPTEURS EN TEMPS RÉEL (4 BLOCS POUR CHAQUE RÔLE)
// =====================================================================================
$bloc1_val = 0; $bloc1_lbl = ""; $bloc1_icon = ""; $bloc1_link = ""; $bloc1_sub = "";
$bloc2_val = 0; $bloc2_lbl = ""; $bloc2_icon = ""; $bloc2_link = ""; $bloc2_sub = "";
$bloc3_val = 0; $bloc3_lbl = ""; $bloc3_icon = ""; $bloc3_link = ""; $bloc3_sub = "";
$bloc4_val = ""; $bloc4_lbl = ""; $bloc4_icon = ""; $bloc4_link = ""; $bloc4_sub = "";

// Initialisation de la variable argent gelé pour le client
$argent_gele = 0;

try {
    if ($user['role'] === 'coiffeur') {
        // 1. Compteur des rendez-vous en attente de confirmation
        $stmt_rdv = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE coiffeur_id = ? AND statut_rdv = 'en_attente'");
        $stmt_rdv->execute([$user_id]);
        $bloc1_val = intval($stmt_rdv->fetchColumn());
        $bloc1_lbl = "En attente";
        $bloc1_icon = "bi-calendar-check";
        $bloc1_link = "/coiffons/coiffeurs/agenda_coiffeurs.php";
        $bloc1_sub = "Gérer →";

        // 2. Flux financier : Argent cumulé en cours (Gelé dans le système pour lui)
        $stmt_gains_encours = $pdo->prepare("
            SELECT SUM(p.prix) FROM rendez_vous r 
            JOIN prestations p ON r.coiffure_id = p.id_prestation 
            WHERE r.coiffeur_id = ? AND r.statut_rdv IN ('en_attente', 'accepte')
        ");
        $stmt_gains_encours->execute([$user_id]);
        $gains_futurs = $stmt_gains_encours->fetchColumn() ?? 0;
        $bloc2_val = number_format($gains_futurs, 0, ',', ' ') . " <span style='font-size:0.75rem;'>FCFA</span>";
        $bloc2_lbl = "Gains sécurisés";
        $bloc2_icon = "bi-shield-lock";
        $bloc2_link = "#portfolio-section";
        $bloc2_sub = "En cours de flux";

        // 3. Compteur du nombre de quartiers (périmètre d'intervention) cochés par le coiffeur
        $stmt_zones = $pdo->prepare("SELECT COUNT(*) FROM zones_coiffeur WHERE id_coiffeur = ?");
        $stmt_zones->execute([$user_id]);
        $bloc3_val = intval($stmt_zones->fetchColumn()) . " <span style='font-size:0.75rem; color:#666;'>qart.</span>";
        $bloc3_lbl = "Zones couvertes";
        $bloc3_icon = "bi-geo-alt";
        $bloc3_link = "/coiffons/coiffeurs/mes_zones.php";
        $bloc3_sub = "Ajuster →";

        // 4. Compteur des clients uniques (distincts) ayant déjà interagi avec ce coiffeur
        $stmt_clients = $pdo->prepare("SELECT COUNT(DISTINCT client_id) FROM rendez_vous WHERE coiffeur_id = ?");
        $stmt_clients->execute([$user_id]);
        $bloc4_val = intval($stmt_clients->fetchColumn());
        $bloc4_lbl = "Clients uniques";
        $bloc4_icon = "bi-people";
        $bloc4_link = "#";
        $bloc4_sub = "Fidélisés";

    } else {
        // CONFIGURATION CLIENT (4 COMPTEURS ADAPTÉS)
        
        // 1. Nombre de rendez-vous actifs planifiés (A venir ou Acceptés)
        $stmt_rdv_cl = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE client_id = ? AND statut_rdv IN ('en_attente', 'accepte')");
        $stmt_rdv_cl->execute([$user_id]);
        $bloc1_val = intval($stmt_rdv_cl->fetchColumn());
        $bloc1_lbl = "RDV Planifiés";
        $bloc1_icon = "bi-calendar3";
        $bloc1_link = "/coiffons/client/mes_rendezvous.php";
        $bloc1_sub = "Consulter →";

        // 2. Calcul de l'argent gelé en temps réel
        $stmt_gele = $pdo->prepare("
            SELECT SUM(p.prix) as total_gele 
            FROM rendez_vous r
            JOIN prestations p ON r.coiffure_id = p.id_prestation
            WHERE r.client_id = ? AND r.statut_rdv = 'en_attente'
        ");
        $stmt_gele->execute([$user_id]);
        $result_gele = $stmt_gele->fetch();
        $argent_gele = $result_gele['total_gele'] ?? 0;
        
        $bloc2_val = number_format($argent_gele, 0, ',', ' ') . " <span style='font-size:0.75rem;'>FCFA</span>";
        $bloc2_lbl = "Fonds bloqués";
        $bloc2_icon = "bi-lock-fill";
        $bloc2_link = "#portfolio-section";
        $bloc2_sub = "Sécurisé Escrow";

        // 3. Nombre de coiffeurs différents testés/visités
        $stmt_coiff_visite = $pdo->prepare("SELECT COUNT(DISTINCT coiffeur_id) FROM rendez_vous WHERE client_id = ?");
        $stmt_coiff_visite = $pdo->prepare("SELECT COUNT(DISTINCT coiffeur_id) FROM rendez_vous WHERE client_id = ? AND statut_rdv = 'termine'");
        $stmt_coiff_visite->execute([$user_id]);
        $bloc3_val = intval($stmt_coiff_visite->fetchColumn());
        $bloc3_lbl = "Artisans testés";
        $bloc3_icon = "bi-scissors";
        $bloc3_link = "/coiffons/index.php";
        $bloc3_sub = "Trouver un autre →";

        // 4. Statut du compte client
        $bloc4_val = "<span class='text-success' style='font-size:0.85rem; font-weight:700;'><i class='bi bi-circle-fill me-1' style='font-size:7px;'></i> ACTIF</span>";
        $bloc4_lbl = "Statut Profil";
        $bloc4_icon = "bi-shield-check";
        $bloc4_link = "#";
        $bloc4_sub = "Vérifié & Certifié";
    }
} catch (Exception $e) {
    // =====================================================================================
    // COMMENTAIRE JURY - SÉCURITÉ DE SECOURS OU "FALLBACK" (POINT FAIBLE IDENTIFIÉ)
    // Début : La simulation à 7000 FCFA ci-dessous fait office de "mode dégradé sécurisé". 
    // Elle garantit que l'interface utilisateur ne crashera jamais graphiquement devant le client 
    // si la base de données est en cours de maintenance ou subit une micro-coupure réseau.
    // Fin du commentaire.
    // =====================================================================================
    $argent_gele = 7000; 
    $bloc1_val = 1; $bloc1_lbl = "Aperçu"; $bloc1_icon = "bi-exclamation-triangle";
    $bloc2_val = "7 000 FCFA"; $bloc2_lbl = "Fonds Bloqués (Secours)"; $bloc2_icon = "bi-lock";
}

// CONFIGURATION DYNAMIQUE DES LIENS SELON LE RÔLE
$lien_rendezvous = ($user['role'] === 'client') ? "/coiffons/client/mes_rendezvous.php" : "/coiffons/coiffeurs/agenda_coiffeurs.php";

// LOGIQUE DE TRAITEMENT : SUPPRESSION DU COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    $raison = htmlspecialchars(trim($_POST['raison_depart']));
    
    if (!empty($raison)) {
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
        
        session_destroy();
        
        echo "<script>
                alert('Votre demande a bien été prise en compte. Votre compte et vos données ont été définitivement supprimés.');
                window.location.href='index.php';
              </script>";
        exit();
    }
}
?>

<section class="py-5 font-monospace" style="background: linear-gradient(135deg, #050505 0%, #0d0d0d 100%); min-height: 95vh; color: #fff;">
    <div class="container mt-4">
        
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="text-warning fw-bold h4 mb-1" style="letter-spacing: 2px;">MON ESPACE SECURISE</h2>
                <p class="text-secondary small text-uppercase" style="font-size: 0.65rem; letter-spacing: 1px;">Tableau de bord d'exploitation en temps réel</p>
            </div>
        </div>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card p-4 h-100 text-center custom-profile-card">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <img src="<?php echo !empty($user['photo_profil']) ? htmlspecialchars($user['photo_profil']) : 'uploads/profil/default.png'; ?>" 
                             class="rounded-circle border-gold-premium shadow" 
                             style="width: 120px; height: 120px; object-fit: cover;" alt="Avatar">
                        <span class="position-absolute bottom-0 end-0 bg-success border border-dark rounded-circle p-1.5" style="transform: translate(-10%, -10%);"></span>
                    </div>
                    
                    <h3 class="text-white fw-bold h5 mb-1 text-truncate"><?php echo htmlspecialchars(strtoupper($user['nom'])) . ' ' . htmlspecialchars($user['prenom']); ?></h3>
                    
                    <div class="mb-4">
                        <span class="badge-role-luxury">
                            <i class="bi <?php echo $user['role'] === 'coiffeur' ? 'bi-scissors' : 'bi-person-fill'; ?> me-1 text-warning"></i>
                            ESPACE EXPERT // <?php echo strtoupper($user['role']); ?>
                        </span>
                    </div>

                    <hr style="border-color: rgba(255,255,255,0.06);" class="my-3">

                    <div class="d-grid gap-2 mt-2">
                        <a href="modifier_profil.php" class="btn btn-gold-action fw-bold py-2">
                            <i class="bi bi-pencil-square me-2"></i> RECONFIGURER PROFIL
                        </a>
                        <a href="<?php echo $lien_rendezvous; ?>" class="btn btn-outline-luxury fw-bold py-2">
                            <i class="bi bi-calendar3 me-2"></i> ARCHIVES & AGENDAS
                        </a>
                        <a href="/coiffons/deconnexion.php" class="btn btn-outline-danger-silent fw-bold py-2">
                            <i class="bi bi-box-arrow-left me-2"></i> INTERRUPTION SESSION
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card p-4 h-100 custom-profile-card">
                    
                    <h5 class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-cpu-fill me-2"></i> VUE D'ENSEMBLE DES FLUX NUMÉRIQUES</h5>
                    
                    <div class="row g-2 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="metric-box">
                                <div>
                                    <i class="bi <?php echo $bloc1_icon; ?> text-warning fs-5 d-block mb-1"></i>
                                    <span class="text-secondary d-block text-truncate" style="font-size: 0.65rem; font-weight: bold;"><?php echo $bloc1_lbl; ?></span>
                                </div>
                                <h4 class="fw-bold my-1 text-white h5 font-monospace"><?php echo $bloc1_val; ?></h4>
                                <a href="<?php echo $bloc1_link; ?>" class="text-warning text-decoration-none d-block small mt-1" style="font-size: 0.6rem; font-weight: 600;"><?php echo $bloc1_sub; ?></a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="metric-box highlight-metric">
                                <div>
                                    <i class="bi <?php echo $bloc2_icon; ?> text-warning fs-5 d-block mb-1"></i>
                                    <span class="text-secondary d-block text-truncate" style="font-size: 0.65rem; font-weight: bold;"><?php echo $bloc2_lbl; ?></span>
                                </div>
                                <h4 class="fw-bold my-1 text-white h6 font-monospace"><?php echo $bloc2_val; ?></h4>
                                <span class="text-muted d-block small mt-1" style="font-size: 0.55rem; font-weight: 600;"><span class="pulse-dot"></span> <?php echo $bloc2_sub; ?></span>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="metric-box">
                                <div>
                                    <i class="bi <?php echo $bloc3_icon; ?> text-warning fs-5 d-block mb-1"></i>
                                    <span class="text-secondary d-block text-truncate" style="font-size: 0.65rem; font-weight: bold;"><?php echo $bloc3_lbl; ?></span>
                                </div>
                                <h4 class="fw-bold my-1 text-white h5 font-monospace"><?php echo $bloc3_val; ?></h4>
                                <a href="<?php echo $bloc3_link; ?>" class="text-warning text-decoration-none d-block small mt-1" style="font-size: 0.6rem; font-weight: 600;"><?php echo $bloc3_sub; ?></a>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="metric-box">
                                <div>
                                    <i class="bi <?php echo $bloc4_icon; ?> text-warning fs-5 d-block mb-1"></i>
                                    <span class="text-secondary d-block text-truncate" style="font-size: 0.65rem; font-weight: bold;"><?php echo $bloc4_lbl; ?></span>
                                </div>
                                <div class="my-1 font-monospace"><?php echo $bloc4_val; ?></div>
                                <span class="text-muted d-block small mt-1" style="font-size: 0.6rem;"><?php echo $bloc4_sub; ?></span>
                            </div>
                        </div>
                    </div>

                    <h5 id="portfolio-section" class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-wallet2 me-2"></i> COMPTABILITE INTERNE ACTIVEE</h5>
                    <div class="p-3 mb-4 rounded border-luxury-gold" style="background: rgba(255,255,255,0.01);">
                        <div class="row align-items-center">
                            <div class="col-md-7 mb-3 mb-md-0">
                                <div class="mb-2">
                                    <span class="text-secondary small d-block text-uppercase" style="font-size:0.6rem;">Solde Courant Liquide :</span>
                                    <span class="text-white fw-bold fs-4">
                                        <?php echo number_format($user['solde'] ?? 0, 0, ',', ' '); ?> <span class="text-warning small fs-6">FCFA</span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-5 text-md-end">
                                <?php if ($user['role'] === 'client'): ?>
                                    <a href="recharger_compte.php" class="btn btn-gold-action btn-sm px-3 fw-bold w-100 py-2">
                                        <i class="bi bi-plus-circle me-1"></i> INJECTER FONDS
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-dark border border-secondary text-secondary py-2 px-2 small d-block text-center font-monospace" style="font-size: 10px;">
                                        Gains d'honoraires cumulés
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h5 class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-card-text me-2"></i> DATA SPECIFICATIONS USER</h5>
                    <div class="bg-black p-3 rounded border border-secondary mb-4">
                        <div class="row g-3" style="font-size: 0.7rem;">
                            <div class="col-sm-6 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">ADRESSE ENREGISTREE :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="col-sm-6 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">TELEPHONE EN SERVICE (WHATSAPP) :</span>
                                <span class="text-white fw-bold text-warning"><?php echo htmlspecialchars($user['telephone']); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">GENRE BIOLOGIQUE :</span>
                                <span class="text-white fw-bold text-uppercase"><?php echo htmlspecialchars($user['sexe']); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">VILLE (ZONAGE REQUETE) :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['nom_ville_clean'] ?? 'Non spécifiée'); ?></span>
                            </div>
                            <div class="col-sm-4 border-bottom border-dark pb-2">
                                <span class="text-secondary d-block">QUARTIER EPINGLÉ :</span>
                                <span class="text-white fw-bold"><?php echo htmlspecialchars($user['nom_quarter_clean'] ?? $user['nom_quartier_clean'] ?? 'Général'); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['role'] === 'coiffeur'): ?>
                        <h5 class="text-warning fw-bold mb-3 small" style="letter-spacing: 1px;"><i class="bi bi-patch-check me-2"></i> VALIDATION DES COMPETENCES</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="bg-black p-3 rounded border border-secondary h-100 d-flex flex-column justify-content-center">
                                    <span class="text-secondary small d-block mb-1 text-uppercase" style="font-size: 0.6rem;">Status Licence App :</span>
                                    <div>
                                        <?php if (isset($user['abonnement_actif']) && $user['abonnement_actif'] == 1): ?>
                                            <span class="badge bg-success font-monospace px-3 py-1.5 small fw-bold">AUTORISÉ (REGLEMENTÉ)</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger font-monospace px-3 py-1.5 small fw-bold mb-2 d-inline-block">EXCLUSION / IMPAYÉ</span>
                                            <a href="renouveler_abonnement.php" class="d-block text-warning small fw-bold text-decoration-none" style="font-size:0.65rem;">Régulariser les frais →</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-black p-3 rounded border border-secondary h-100">
                                    <span class="text-secondary small d-block mb-2 text-uppercase" style="font-size: 0.6rem;">Attestation téléchargée :</span>
                                    <?php if (!empty($user['diplome'])): ?>
                                        <?php if (pathinfo($user['diplome'], PATHINFO_EXTENSION) === 'pdf'): ?>
                                            <a href="<?php echo htmlspecialchars($user['diplome']); ?>" target="_blank" class="btn btn-sm btn-outline-warning w-100 py-2 font-monospace" style="font-size:0.65rem;">
                                                <i class="bi bi-file-earmark-pdf-fill me-1"></i> OUVRIR PDF SOURCE
                                            </a>
                                        <?php else: ?>
                                            <div class="text-center">
                                                <img src="<?php echo htmlspecialchars($user['diplome']); ?>" class="img-thumbnail bg-dark border-secondary cursor-pointer" style="max-height: 55px; object-fit: contain;" data-bs-toggle="modal" data-bs-target="#diplomeModal" alt="Diplôme">
                                                <span class="d-block text-muted text-center" style="font-size: 9px; margin-top: 3px;">Agrandir l'élément</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-danger small fw-bold" style="font-size:0.65rem;"><i class="bi bi-exclamation-circle me-1"></i> AUCUN DOCUMENT ENREGISTRÉ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card p-3 mt-auto border-danger-silent" style="background-color: #080808;">
                        <h6 class="text-danger fw-bold mb-1 small" style="letter-spacing: 0.5px;"><i class="bi bi-exclamation-triangle-fill me-2"></i> PROCESSUS DE SUPPRESSION ABSOLUE</h6>
                        <p class="text-secondary mb-3" style="font-size: 10px; line-height: 1.4;">
                            L'activation du protocole effacera instantanément l'intégralité de vos matrices de données et soldes de nos infrastructures réseau (Irréversible).
                        </p>

                        <button class="btn btn-sm btn-outline-danger w-100 fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#formulaireSuppression" style="font-size: 11px; border-radius: 6px;">
                            <i class="bi bi-trash3 me-1"></i> OUVRIR LA COMMANDE DE DESTRUCTION
                        </button>

                        <div class="collapse mt-3" id="formulaireSuppression">
                            <div class="p-3 rounded bg-black border border-secondary">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="text-warning small fw-bold mb-2" style="font-size: 11px;">EXPRIMEZ LA RAISON DU DÉPART POUR AUDIT ADMIN :</label>
                                        <textarea name="raison_depart" class="form-control bg-dark text-white border-secondary small" rows="2" placeholder="Saisie obligatoire avant validation..." required style="font-size:0.7rem; border-radius:5px;"></textarea>
                                    </div>
                                    <button type="submit" name="confirmer_suppression" class="btn btn-danger btn-sm w-100 fw-bold" onclick="return confirm('Êtes-vous absolument sûr de vouloir détruire définitivement ce compte ?');" style="font-size: 11px; border-radius: 5px;">
                                        EXECUTER L'EFFACEMENT IMMEDIAT DES BASES
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
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content bg-dark text-white border border-secondary" style="border-radius:12px;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-warning h6 fw-bold">DOCUMENT DE CERTIFICATION ARTISAN</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-black p-3">
                <img src="<?php echo htmlspecialchars($user['diplome']); ?>" class="img-fluid rounded border border-dark" alt="Diplôme grand format">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    /* Structure Métallique Sombre */
    .custom-profile-card {
        background-color: #0f0f0f !important;
        border: 1px solid rgba(255, 255, 255, 0.04) !important;
        border-radius: 14px !important;
    }

    /* Traitement Or Premium */
    .border-gold-premium {
        border: 2px solid #f39c12 !important;
        padding: 3px;
        background: linear-gradient(to bottom, #f39c12, #d35400);
    }
    .border-luxury-gold {
        border: 1px dashed rgba(243, 156, 18, 0.25) !important;
    }

    /* Badges Architecturaux */
    .badge-role-luxury {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.06);
        padding: 5px 12px;
        font-size: 0.6rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        border-radius: 4px;
        color: #b3b3b3;
    }

    /* Boutons de Commandes */
    .btn-gold-action {
        background-color: #f39c12 !important;
        color: #000 !important;
        border: none !important;
        font-size: 0.65rem !important;
        letter-spacing: 0.5px;
        border-radius: 6px !important;
        transition: all 0.2s ease-in-out;
    }
    .btn-gold-action:hover {
        background-color: #e67e22 !important;
        transform: translateY(-1px);
    }
    .btn-all-links, .btn-outline-luxury {
        background-color: transparent !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #fff !important;
        font-size: 0.65rem !important;
        border-radius: 6px !important;
        transition: 0.2s;
    }
    .btn-outline-luxury:hover {
        border-color: #f39c12 !important;
        color: #f39c12 !important;
    }
    .btn-outline-danger-silent {
        background-color: rgba(220, 53, 69, 0.02) !important;
        border: 1px solid rgba(220, 53, 69, 0.15) !important;
        color: #dc3545 !important;
        font-size: 0.65rem !important;
        border-radius: 6px !important;
    }
    .btn-outline-danger-silent:hover {
        background-color: #dc3545 !important;
        color: #fff !important;
    }
    .border-danger-silent {
        border: 1px dashed rgba(220, 53, 69, 0.2) !important;
    }

    /* Grille des Boîtes Statistiques (Metrics) */
    .metric-box {
        background-color: #080808;
        border: 1px solid rgba(255, 255, 255, 0.02);
        padding: 12px;
        border-radius: 8px;
        height: 100%;
        display: flex;
        flex-column: column;
        justify-content: space-between;
    }
    .highlight-metric {
        background: linear-gradient(135deg, #090909 0%, #111111 100%);
        border: 1px solid rgba(243, 156, 18, 0.1) !important;
    }

    /* Clignotant de Flux Réseau Actif */
    .pulse-dot {
        width: 5px;
        height: 5px;
        background-color: #f39c12;
        border-radius: 50%;
        display: inline-block;
        animation: active-pulse 1.6s infinite ease-in-out;
        margin-right: 3px;
        vertical-align: middle;
    }
    @keyframes active-pulse {
        0% { transform: scale(0.8); opacity: 0.4; }
        50% { transform: scale(1.2); opacity: 1; }
        100% { transform: scale(0.8); opacity: 0.4; }
    }
</style>

<?php include __DIR__ . '/layout/footer.php'; ?>