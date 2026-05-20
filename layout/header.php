<?php if (session_status() === PHP_SESSION_NONE) {
    session_start();
} ?>
<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Coiffe_Chez_Toi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --gold: #D4AF37;
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
        }

        body {
            background-color: var(--dark-bg);
            color: white;
            font-family: 'Segoe UI', sans-serif;
            overflow-x: hidden;
        }

        .navbar-dark.bg-dark {
            background-color: #000 !important;
            border-bottom: 2px solid var(--gold);
        }

        .nav-logo {
            height: 45px;
            margin-right: 12px;
            cursor: pointer;
            transition: 0.3s;
        }

        .nav-logo:hover {
            transform: scale(1.1);
        }

        /* --- STYLE DU MENU LATÉRAL (SIDEBAR) --- */
        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            z-index: 3000;
            top: 0;
            left: 0;
            background-color: #000;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
            border-right: 2px solid var(--gold);
        }

        .sidebar a {
            padding: 15px 32px;
            text-decoration: none;
            font-size: 1.1rem;
            color: white;
            display: block;
            transition: 0.3s;
            border-bottom: 1px solid #111;
        }

        .sidebar a:hover {
            color: var(--gold);
            background-color: #111;
            padding-left: 45px;
        }

        .sidebar .closebtn {
            position: absolute;
            top: 10px;
            right: 25px;
            font-size: 36px;
            border: none;
            background: none;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2999;
            top: 0;
            left: 0;
        }

        .btn-gold {
            background-color: var(--gold);
            color: black;
            font-weight: bold;
            border: none;
        }

        /* Animation pour le dropdown About Us */
        #navbarHeader {
            transition: all 0.4s ease-in-out;
        }
    </style>
</head>

<body>

    <div id="mainOverlay" class="sidebar-overlay" onclick="closeNav()"></div>

    <div id="mySidebar" class="sidebar">
        <button class="closebtn text-warning" onclick="closeNav()">&times;</button>
        <div class="px-4 mb-4 text-center">
            <h5 class="text-warning small mb-0">BIENVENU</h5>
            <p class="fw-bold"><?php echo strtoupper($_SESSION['nom'] ?? 'INVITÉ'); ?></p>
            <hr class="border-warning">
        </div>

        <a href="index.php"><i class="bi bi-house-door"></i> Accueil</a>

        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coiffeur'): ?>
            <a href="annuaire_coiffeurs.php" class="text-warning fw-bold"><i class="bi bi-search"></i> Trouver un coiffeur</a>
        <?php endif; ?>

        <a href="catalogue.php"><i class="bi bi-images"></i> Catalogue des styles</a>

        <?php if (!isset($_SESSION['id_user'])): ?>
            <a href="inscription.php"><i class="bi bi-person-plus"></i> Créer un compte</a>
            <a href="connexion.php"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
        <?php else: ?>
            <?php if ($_SESSION['role'] == 'client'): ?>
                <a href="mes_rendezvous.php"><i class="bi bi-calendar-check"></i> Mes RDV</a>
                <a href="profil.php"><i class="bi bi-person-circle"></i> Mon Profil</a>
            <?php endif; ?>

            <?php if ($_SESSION['role'] == 'coiffeur'): ?>
                <a href="valider_rendezvous.php"><i class="bi bi-calendar-check"></i> Mes rendez-vous</a>
                <a href="gestion_catalogue.php"><i class="bi bi-scissors"></i> Gérer mes coiffures</a>
                <a href="agenda_coiffeurs.php"><i class="bi bi-people"></i> Mes clients</a>
                <a href="portefeuille.php"><i class="bi bi-wallet2"></i> Mes gains</a>
                <a href="profil.php"><i class="bi bi-person-badge"></i> Mon Profil Pro</a>
            <?php endif; ?>

            <a href="deconnexion.php" class="text-danger fw-bold border border-danger rounded text-center m-3 py-2"><i class="bi bi-power"></i> Déconnexion</a>
        <?php endif; ?>

        <a href="https://wa.me/2290100000000" target="_blank" class="mt-2 small text-secondary text-decoration-none">
            <i class="bi bi-whatsapp"></i> Support 24/7
        </a>
    </div>

    <header data-bs-theme="dark">
        <div class="collapse text-bg-dark" id="navbarHeader">
            <div class="container">
                <div class="row">
                    <div class="col-sm-8 col-md-7 py-4">
                        <h4 class="text-warning">About us</h4>
                        <p class="text-body-secondary">
                            Sur notre site, nous présentons uniquement les coiffures que nous avons la possibilité de réaliser.
                            <br><strong>Note importante :</strong> Nous ne sommes pas des magiciens, si vos contours sont partis, ils sont partis !
                        </p>
                    </div>
                    <div class="col-sm-4 offset-md-1 py-4">
                        <h4 class="text-warning">Contact</h4>
                        <ul class="list-unstyled">
                            <li><a href="#" class="text-white text-decoration-none">Follow on X</a></li>
                            <li><a href="#" class="text-white text-decoration-none">Facebook</a></li>
                            <li><a href="#" class="text-white text-decoration-none">Instagram</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="navbar navbar-dark bg-dark shadow-sm">
            <div class="container d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="images/logo.png" alt="Logo" class="nav-logo" onclick="openNav()">
                    <a href="index.php" class="navbar-brand d-flex align-items-center">
                        <strong>Coiffe_Chez_Toi</strong>
                    </a>
                </div>

                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coiffeur'): ?>
                    <div class="d-none d-md-flex align-items-center me-3">
                        <a href="annuaire_coiffeurs.php" class="btn btn-warning btn-sm fw-bold px-3 py-2 text-dark">
                            <i class="bi bi-search"></i> Trouver un coiffeur
                        </a>
                    </div>
                <?php endif; ?>

                <button class="navbar-toggler border-warning shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarHeader">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
    </header>

    <script>
        // Fonction Sidebar
        function openNav() {
            document.getElementById("mySidebar").style.width = "280px";
            document.getElementById("mainOverlay").style.display = "block";
        }

        // Ajout de la fermeture de la sidebar après clic
        function closeNav() {
            document.getElementById("mySidebar").style.width = "0";
            document.getElementById("mainOverlay").style.display = "none";
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>