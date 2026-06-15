<?php
// =================================================================
// DÉTECTEUR AUTOMATIQUE DE SESSION & SYSTÈME DE NOTIFICATIONS
// =================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

$est_connecte = isset($_SESSION['id_user']);
$role_utilisateur = $_SESSION['role'] ?? 'invite';
$nom_utilisateur = $_SESSION['nom'] ?? 'INVITÉ';

$nb_notifs = 0;
$liste_notifs = [];

if ($est_connecte) {
    $user_logge_id = $_SESSION['id_user'];

    // 1. Compte des notifications non lues en Base de Données
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE id_user = ? AND statut_lecture = 'non_lu'");
    $stmt_count->execute([$user_logge_id]);
    $nb_notifs = $stmt_count->fetchColumn();

    // 2. Liste des 5 dernières notifications en Base de Données
    $stmt_list = $pdo->prepare("SELECT * FROM notifications WHERE id_user = ? ORDER BY date_notification DESC LIMIT 5");
    $stmt_list->execute([$user_logge_id]);
    $liste_notifs = $stmt_list->fetchAll();

    // 3. Ajustement du compteur global si une notification de session (Abonnement) est active
    if ($role_utilisateur === 'coiffeur' && isset($_SESSION['alerte_abo_cloche'])) {
        $nb_notifs++;
    }
    
    // 💡 ASTUCE EXPERT : Si vous ajoutez une autre notification basée sur les SESSIONS plus bas, 
    // n'oubliez pas de faire un "$nb_notifs++;" pour que la pastille rouge s'allume !
}

// Action : Marquer tout comme lu
if (isset($_GET['action_notif']) && $_GET['action_notif'] === 'marquer_lu') {
    if ($est_connecte) {
        $pdo->prepare("UPDATE notifications SET statut_lecture = 'lu' WHERE id_user = ?")->execute([$_SESSION['id_user']]);
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit();
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coiffe_Chez_Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --gold: #D4AF37; --dark-bg: #121212; }
        
        body { 
            background-color: var(--dark-bg); 
            color: white; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        .navbar-dark.bg-dark { background-color: #000 !important; border-bottom: 2px solid var(--gold); }
        .nav-logo { height: 45px; transition: 0.3s; cursor: pointer; }
        .nav-logo:hover { transform: scale(1.1); }

        /* --- SIDEBAR ACCORDEON --- */
        .sidebar { height: 100%; width: 0; position: fixed; z-index: 3000; top: 0; left: 0; background-color: #000; overflow-x: hidden; transition: 0.5s; padding-top: 60px; border-right: 2px solid var(--gold); }
        .sidebar a, .sidebar .accordion-button { padding: 12px 32px; text-decoration: none; font-size: 1rem; color: white; display: block; transition: 0.3s; background: none; border: none; width: 100%; text-align: left; font-family: 'Segoe UI', sans-serif; }
        .sidebar a:hover, .sidebar .accordion-button:hover { color: var(--gold); background-color: #111; }
        
        .sidebar .accordion-item { background: transparent; border: none; }
        .sidebar .accordion-button { color: var(--gold); font-weight: bold; font-size: 0.9rem; }
        .sidebar .accordion-button::after { filter: invert(1) sepia(1) saturate(5) hue-rotate(10deg); }
        .sidebar .accordion-button:not(.collapsed) { background-color: #080808; color: var(--gold); box-shadow: none; }
        .sidebar .submenu-link { padding-left: 50px !important; font-size: 0.9rem !important; opacity: 0.8; border-bottom: 1px solid #111; }

        .sidebar-overlay { display: none; position: fixed; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 2999; top: 0; left: 0; }
        
        /* Notifications */
        .no-arrow::after { display: none !important; }
        .animate-pulse-bell { animation: pulseBell 1.5s infinite; }
        @keyframes pulseBell { 0% { transform: scale(0.9); opacity: 0.8; } 50% { transform: scale(1.1); opacity: 1; } 100% { transform: scale(0.9); opacity: 0.8; } }
    </style>
</head>
<body>

<div id="mainOverlay" class="sidebar-overlay" onclick="closeNav()"></div>

<div id="mySidebar" class="sidebar">
    <button class="closebtn text-warning" style="position:absolute; top:10px; right:25px; font-size:36px; background:none; border:none;" onclick="closeNav()">&times;</button>
    
    <div class="px-4 mb-3 text-center">
        <h5 class="text-warning small mb-0">BIENVENUE</h5>
        <p class="fw-bold mb-2"><?php echo strtoupper($nom_utilisateur); ?></p>
        <hr class="border-warning my-1">
    </div>

    <a href="/coiffons/index.php"><i class="bi bi-house-door me-2"></i> Accueil</a>

    <?php if ($role_utilisateur == 'coiffeur'): ?>
        <div class="accordion accordion-flush" id="accordionSidebar">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#act">
                        <i class="bi bi-calendar-event me-2"></i> Mon Activity
                    </button>
                </h2>
                <div id="act" class="accordion-collapse collapse" data-bs-parent="#accordionSidebar">
                    <div class="accordion-body p-0">
                        <a href="/coiffons/coiffeurs/valider_rendezvous.php" class="submenu-link">Mes demandes</a>
                        <a href="/coiffons/coiffeurs/agenda_coiffeurs.php" class="submenu-link">Mon agenda</a>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#conf">
                        <i class="bi bi-tools me-2"></i> Ma Configuration
                    </button>
                </h2>
                <div id="conf" class="accordion-collapse collapse" data-bs-parent="#accordionSidebar">
                    <div class="accordion-body p-0">
                        <a href="/coiffons/coiffeurs/gestion_catalogue.php" class="submenu-link">Mes coiffures</a>
                        <a href="/coiffons/coiffeurs/mes_zones.php" class="submenu-link">Zones d'intervention</a>
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#biz">
                        <i class="bi bi-briefcase me-2"></i> Mon Business
                    </button>
                </h2>
                <div id="biz" class="accordion-collapse collapse" data-bs-parent="#accordionSidebar">
                    <div class="accordion-body p-0">
                        <a href="/coiffons/coiffeurs/portefeuille.php" class="submenu-link">Mes gains</a>
                        <a href="/coiffons/profil.php" class="submenu-link">Mon Profil</a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($role_utilisateur == 'client'): ?>
        <a href="/coiffons/filter/annuaire_coiffeurs.php"><i class="bi bi-search-heart me-2"></i> Trouver un coiffeur</a>
        <a href="/coiffons/client/mes_rendezvous.php"><i class="bi bi-calendar-check me-2"></i> Mes RDV</a>
        <a href="/coiffons/profil.php"><i class="bi bi-person-circle me-2"></i> Mon Profil</a>
    <?php endif; ?>

    <?php if ($est_connecte): ?>
        <div class="px-4 py-3 mt-3">
            <a href="/coiffons/deconnexion.php" class="btn btn-outline-danger w-100 fw-bold py-2 d-flex align-items-center justify-content-center" style="font-size: 0.9rem; border-radius: 6px; transition: 0.2s;">
                <i class="bi bi-power me-2"></i> Déconnexion
            </a>
        </div>
    <?php else: ?>
        <div class="px-4 py-3 mt-3">
            <a href="/coiffons/access/connexion.php" class="btn btn-warning w-100 fw-bold py-2 text-dark d-flex align-items-center justify-content-center" style="font-size: 0.9rem; border-radius: 6px;">
                <i class="bi bi-box-arrow-in-right me-2"></i> Connexion
            </a>
        </div>
    <?php endif; ?>
</div>

<header>
    <div class="navbar navbar-dark bg-dark shadow-sm py-2">
        <div class="container d-flex justify-content-between align-items-center flex-nowrap">
            
            <div class="d-flex align-items-center text-truncate">
                <?php if ($est_connecte): ?>
                    <button class="btn text-warning border-0 p-0 me-2 fs-3" onclick="history.back()"><i class="bi bi-arrow-left-short"></i></button>
                <?php endif; ?>
                <img src="/coiffons/images/logo.png" alt="Logo" class="nav-logo flex-shrink-0" onclick="openNav()">
                <a href="/coiffons/index.php" class="navbar-brand ms-2 mb-0 h1 fs-6 text-truncate"><strong>Coiffe_Chez_Toi</strong></a>
            </div>

            <div class="d-flex align-items-center gap-2 gap-sm-3 flex-shrink-0">
                
                <?php if ($role_utilisateur == 'client'): ?>
                    <a href="/coiffons/filter/annuaire_coiffeurs.php" class="btn btn-outline-warning btn-sm d-flex align-items-center px-2 py-1 fw-bold" title="Trouver un coiffeur" style="font-size: 0.85rem;">
                        <i class="bi bi-search <?php echo $est_connecte ? 'me-md-1' : 'me-1'; ?>"></i> 
                        <span class="d-none d-md-inline">Trouver un coiffeur</span>
                    </a>
                <?php endif; ?>

                <?php if ($est_connecte): ?>
                    <div class="dropdown d-flex align-items-center">
                        <a class="text-warning position-relative dropdown-toggle no-arrow d-flex align-items-center p-1" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-4"></i>
                            <?php if ($nb_notifs > 0): ?>
                                <span class="position-absolute top-0 start-50 p-1 bg-danger border border-black rounded-circle animate-pulse-bell"></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark border-secondary shadow-lg p-2 mt-2" style="width: 280px; background-color: #0c0c0c;">
                            <div class="d-flex justify-content-between border-bottom border-secondary pb-2 mb-2 px-2 small">
                                <span class="text-warning fw-bold">Alertes (<?php echo $nb_notifs; ?>)</span>
                                <?php if ($nb_notifs > 0 && !empty($liste_notifs)): ?>
                                    <a href="?action_notif=marquer_lu" class="text-muted text-decoration-none">Tout lire</a>
                                <?php endif; ?>
                            </div>

                            <?php
                            // =================================================================
                            // CENTRALISATION DES NOTIFICATIONS DANS UN TABLEAU UNIQUE
                            // =================================================================
                            $toutes_les_notifs = [];

                            // 1. Notification Prioritaire : Échéance Abonnement Coiffeur
                            if ($role_utilisateur === 'coiffeur' && isset($_SESSION['alerte_abo_cloche'])) {
                                $toutes_les_notifs[] = [
                                    'type'   => 'danger',
                                    'message'=> $_SESSION['alerte_abo_cloche'],
                                    'icone'  => 'bi-exclamation-triangle-fill animate-pulse-bell',
                                    'lien'   => '/coiffons/coiffeurs/portefeuille.php',
                                    'date'   => 'Urgent'
                                ];
                            }

                            // 🛠️ ZONE DE RAJOUT : COMMENT AJOUTER UNE NOUVELLE NOTIFICATION ?
                            // Il vous suffit de copier/coller le modèle ci-dessous et d'adapter vos conditions :
                            /*
                            if (VOTRE_CONDITION_ICI) {
                                $toutes_les_notifs[] = [
                                    'type'   => 'warning',          // Choix : 'danger', 'warning', 'info' ou 'standard'
                                    'message'=> "Votre message",    // Le texte qui sera écrit dans la cloche
                                    'icone'  => 'bi-chat-left-text',// L'icône Bootstrap de votre choix
                                    'lien'   => '/votre_lien.php',  // Où va le coiffeur quand il clique dessus
                                    'date'   => 'Maintenant'        // Le petit texte affiché en bas à droite
                                ];
                            }
                            */

                            // 2. Intégration des notifications standards provenant de la Base de Données
                            foreach ($liste_notifs as $n) {
                                $toutes_les_notifs[] = [
                                    'type'   => ($n['statut_lecture'] == 'non_lu') ? 'info' : 'standard',
                                    'message'=> $n['message'],
                                    'icone'  => 'bi-info-circle',
                                    'lien'   => '#', 
                                    'date'   => date('d/m H:i', strtotime($n['date_notification']))
                                ];
                            }
                            ?>

                            <?php if (empty($toutes_les_notifs)): ?>
                                <li class="text-muted text-center py-2 small">Rien à signaler</li>
                            <?php else: ?>
                                <?php foreach ($toutes_les_notifs as $notif): ?>
                                    <?php 
                                    // Gestion des designs selon le niveau d'importance ('type')
                                    $bg_style = '';
                                    $text_class = 'text-light';
                                    
                                    if ($notif['type'] === 'danger') {
                                        $bg_style = 'background-color: #1a0505; border: 1px solid #4a1414;';
                                        $text_class = 'text-danger fw-bold';
                                    } elseif ($notif['type'] === 'warning') {
                                        $bg_style = 'background-color: #241c08; border: 1px solid #543d0a;';
                                        $text_class = 'text-warning fw-bold';
                                    } elseif ($notif['type'] === 'info') {
                                        $bg_style = 'background-color: #0b131f;';
                                        $text_class = 'text-info';
                                    }
                                    ?>
                                    <li class="p-2 rounded mb-1" style="<?php echo $bg_style; ?>">
                                        <a href="<?php echo $notif['lien']; ?>" class="text-decoration-none">
                                            <p class="mb-0 <?php echo $text_class; ?> small" style="white-space:normal;">
                                                <i class="bi <?php echo $notif['icone']; ?> me-1"></i> 
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                            </p>
                                            <small class="text-muted d-block text-end" style="font-size:0.6rem;"><?php echo $notif['date']; ?></small>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <button class="navbar-toggler border-warning p-1" type="button" data-bs-toggle="collapse" data-bs-target="#navbarHeader">
                    <span class="navbar-toggler-icon" style="width: 1.25rem; height: 1.25rem;"></span>
                </button>
            </div>

        </div>
    </div>

    <div class="collapse bg-dark border-bottom border-secondary" id="navbarHeader">
        <div class="container py-4">
            <div class="row">
                <div class="col-sm-8"><h4 class="text-warning">À propos</h4><p class="text-secondary small">Expertise en coiffure à domicile. Nous realizons vos styles préférés chez vous.</p></div>
                <div class="col-sm-4"><h4 class="text-warning">Contact</h4><ul class="list-unstyled small"><li>Instagram</li><li>WhatsApp</li></ul></div>
            </div>
        </div>
    </div>
</header>

<script>
    function openNav() { document.getElementById("mySidebar").style.width = "280px"; document.getElementById("mainOverlay").style.display = "block"; }
    function closeNav() { document.getElementById("mySidebar").style.width = "0"; document.getElementById("mainOverlay").style.display = "none"; }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<main>