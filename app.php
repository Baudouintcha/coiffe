<?php
/**
 * app.php — Routeur de "Coiffe Chez Toi"
 * Appelé par index.php quand l'utilisateur choisit le métier Coiffure
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

// Récupération de l'instance PDO via le singleton
$pdo = Database::getInstance();

$page = $_GET['page'] ?? 'home';
$role = $_GET['role'] ?? null;

// ── ROUTING ──
switch ($page) {
    case 'login':
        include __DIR__ . '/access/connexion.php';
        exit();

    case 'register':
        $role_valide = in_array($role, ['client', 'prestataire', 'coiffeur']) ? $role : 'client';
        $_GET['role'] = $role_valide;
        include __DIR__ . '/access/inscription.php';
        exit();

    case 'dashboard_coiffeur':
        // Dashboard coiffeur — uniquement pour les coiffeurs connectés
        if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'coiffeur') {
            header("Location: /coiffons/index.php?page=login");
            exit();
        }
        include __DIR__ . '/views/coiffeur/dashboard.php';
        exit();

    case 'dashboard':
        // Dashboard client — uniquement pour les clients connectés
        if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'client') {
            header("Location: /coiffons/index.php?page=login");
            exit();
        }
        // Prépare les données de matching pour la vue
        $coiffeurs_matching = [];
        $id_ville_client    = $_SESSION['id_ville'] ?? 0;
        $id_quartier_client = $_SESSION['id_quartier'] ?? 0;
        try {
            // Requête principale : coiffeurs de la même ville (avec ou sans filtre quartier)
            if ($id_quartier_client > 0) {
                // Priorité 1 : même ville ET même quartier
                $stmt = $pdo->prepare("
                    SELECT DISTINCT u.id as id_coiffeur, 
                        u.nom as nom_coiffeur,
                        u.photo_profil as photo_profil_coiffeur,
                        q.nom_quartier as quartier, 
                        v.nom_ville as ville,
                        u.is_approved,
                        (SELECT MIN(s2.prix) FROM services s2 WHERE s2.id_prestataire = u.id) as prix,
                        (SELECT s3.photo FROM services s3 WHERE s3.id_prestataire = u.id AND s3.photo IS NOT NULL LIMIT 1) as photo,
                        (SELECT ROUND(AVG(av.note), 1) FROM avis av WHERE av.prestataire_id = u.id AND av.type = 'public') AS note_moyenne,
                        (SELECT COUNT(av.id) FROM avis av WHERE av.prestataire_id = u.id AND av.type = 'public') AS nb_avis
                    FROM users u
                    LEFT JOIN villes v ON u.id_ville = v.id
                    LEFT JOIN zones_prestataire z ON u.id = z.id_prestataire
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE u.role = 'prestataire' 
                    AND (u.is_approved = 1 OR u.is_approved = '1')
                    AND (u.statut = 'actif' OR u.statut IS NULL OR u.statut = '')
                    AND u.id_ville = ? 
                    AND (u.id_quartier = ? OR z.id_quartier = ?)
                    ORDER BY RAND() 
                    LIMIT 9
                ");
                $stmt->execute([$id_ville_client, $id_quartier_client, $id_quartier_client]);
                $coiffeurs_matching = $stmt->fetchAll();
            }
            
            // Fallback 1 : si aucun résultat ou pas de quartier, chercher dans toute la ville
            if ((empty($coiffeurs_matching) || $id_quartier_client == 0) && $id_ville_client > 0) {
                $stmt2 = $pdo->prepare("
                    SELECT DISTINCT u.id as id_coiffeur, 
                        u.nom as nom_coiffeur,
                        u.photo_profil as photo_profil_coiffeur,
                        q.nom_quartier as quartier, 
                        v.nom_ville as ville,
                        u.is_approved,
                        (SELECT MIN(s2.prix) FROM services s2 WHERE s2.id_prestataire = u.id) as prix,
                        (SELECT s3.photo FROM services s3 WHERE s3.id_prestataire = u.id AND s3.photo IS NOT NULL LIMIT 1) as photo,
                        (SELECT ROUND(AVG(av.note), 1) FROM avis av WHERE av.prestataire_id = u.id AND av.type = 'public') AS note_moyenne,
                        (SELECT COUNT(av.id) FROM avis av WHERE av.prestataire_id = u.id AND av.type = 'public') AS nb_avis
                    FROM users u
                    LEFT JOIN villes v ON u.id_ville = v.id
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE u.role = 'prestataire' 
                    AND (u.is_approved = 1 OR u.is_approved = '1')
                    AND (u.statut = 'actif' OR u.statut IS NULL OR u.statut = '')
                    AND u.id_ville = ?
                    ORDER BY RAND() 
                    LIMIT 9
                ");
                $stmt2->execute([$id_ville_client]);
                $coiffeurs_matching = $stmt2->fetchAll();
            }
            
            // Fallback 2 : si toujours rien, afficher tous les coiffeurs approuvés
            if (empty($coiffeurs_matching)) {
                $stmt3 = $pdo->prepare("
                    SELECT DISTINCT u.id as id_coiffeur, 
                        u.nom as nom_coiffeur,
                        u.photo_profil as photo_profil_coiffeur,
                        q.nom_quartier as quartier, 
                        v.nom_ville as ville,
                        u.is_approved,
                        (SELECT MIN(s2.prix) FROM services s2 WHERE s2.id_prestataire = u.id) as prix,
                        (SELECT s3.photo FROM services s3 WHERE s3.id_prestataire = u.id AND s3.photo IS NOT NULL LIMIT 1) as photo,
                        (SELECT ROUND(AVG(av.note), 1) FROM avis av WHERE av.prestataire_id = u.id AND av.type = 'public') AS note_moyenne,
                        (SELECT COUNT(av.id) FROM avis av WHERE av.prestataire_id = u.id AND av.type = 'public') AS nb_avis
                    FROM users u
                    LEFT JOIN villes v ON u.id_ville = v.id
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE u.role = 'prestataire' 
                    AND (u.is_approved = 1 OR u.is_approved = '1')
                    AND (u.statut = 'actif' OR u.statut IS NULL OR u.statut = '')
                    ORDER BY RAND() 
                    LIMIT 9
                ");
                $stmt3->execute();
                $coiffeurs_matching = $stmt3->fetchAll();
            }
        } catch (Exception $e) {
            error_log("Erreur chargement coiffeurs dashboard: " . $e->getMessage());
            $coiffeurs_matching = [];
        }
        include __DIR__ . '/views/client/dashboard.php';
        exit();

    case 'home':
    default:
        // Si l'utilisateur est déjà connecté, redirige vers son dashboard
        if (isset($_SESSION['id_user'])) {
            $role_session = $_SESSION['role'] ?? 'client';
            if ($role_session === 'coiffeur') {
                header("Location: /coiffons/app.php?page=dashboard_coiffeur");
                exit();
            } elseif ($role_session === 'client') {
                header("Location: /coiffons/app.php?page=dashboard");
                exit();
            } elseif ($role_session === 'admin') {
                header("Location: /coiffons/first/admin_dashboard.php");
                exit();
            }
        }

        // Invité — affiche la page d'accueil avec le slider

        $role_actuel  = $_SESSION['role'] ?? 'invite';
        $coiffeur_id  = $_SESSION['id_user'] ?? null;
        $nom_affichage_client = $_SESSION['nom'] ?? $_SESSION['prenom'] ?? null;
        $mes_coiffures   = [];
        $db_coiffeur     = [];
        $coiffeurs_matching = [];
        $catalog_demo    = [];
        $all_comments    = [];

        // Données de démo si la BDD est vide
        $coiffures_par_defaut = [
            ['id_service'=>1,'id_prestataire'=>1,'nom_service'=>'Gros Rastas Premium','prix'=>7000,'nom_coiffeur'=>'Alliance Coiffure','ville'=>'Cotonou','quartier'=>'Fidjrossè','photo'=>null],
            ['id_service'=>2,'id_prestataire'=>2,'nom_service'=>'Dégradé Américain / Wave','prix'=>3000,'nom_coiffeur'=>'Barber Shop Pro','ville'=>'Calavi','quartier'=>'Zogbadjè','photo'=>null],
            ['id_service'=>3,'id_prestataire'=>3,'nom_service'=>'Nattes Collées Graphiques','prix'=>5000,'nom_coiffeur'=>'Divine Mains','ville'=>'Porto-Novo','quartier'=>'Ouando','photo'=>null],
        ];

        // Données pour le coiffeur connecté
        if ($coiffeur_id && $role_actuel === 'coiffeur') {
            try {
                $chk = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $chk->execute([$coiffeur_id]);
                $db_coiffeur = $chk->fetch() ?: [];

                $stmt = $pdo->prepare("
                    SELECT s.*, v.nom_ville as ville, q.nom_quartier as quartiers
                    FROM services s
                    JOIN users u ON s.id_prestataire = u.id
                    LEFT JOIN villes v ON u.id_ville = v.id
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE s.id_prestataire = ?
                    ORDER BY s.id_service DESC
                ");
                $stmt->execute([$coiffeur_id]);
                $mes_coiffures = $stmt->fetchAll();
            } catch (Exception $e) {
                $mes_coiffures = [];
            }
        }

        // Données pour le client connecté (matching géographique)
        if ($role_actuel === 'client') {
            $id_ville_client   = $_SESSION['id_ville'] ?? 0;
            $id_quartier_client= $_SESSION['id_quartier'] ?? 0;
            try {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT s.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville
                    FROM services s
                    JOIN users u ON s.id_prestataire = u.id
                    JOIN villes v ON u.id_ville = v.id
                    INNER JOIN zones_prestataire z ON u.id = z.id_prestataire
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE u.role = 'prestataire'
                    AND u.id_ville = ? AND z.id_quartier = ?
                    ORDER BY RAND() LIMIT 6
                ");
                $stmt->execute([$id_ville_client, $id_quartier_client]);
                $coiffeurs_matching = $stmt->fetchAll();

                if (empty($coiffeurs_matching)) {
                    $stmt2 = $pdo->prepare("
                        SELECT s.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville
                        FROM services s
                        JOIN users u ON s.id_prestataire = u.id
                        JOIN villes v ON u.id_ville = v.id
                        LEFT JOIN quartiers q ON u.id_quartier = q.id
                        WHERE u.role = 'prestataire' AND u.id_ville = ?
                        ORDER BY RAND() LIMIT 6
                    ");
                    $stmt2->execute([$id_ville_client]);
                    $coiffeurs_matching = $stmt2->fetchAll();
                }

                if (empty($coiffeurs_matching)) {
                    $coiffeurs_matching = $pdo->query("
                        SELECT s.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville
                        FROM services s
                        JOIN users u ON s.id_prestataire = u.id
                        JOIN villes v ON u.id_ville = v.id
                        LEFT JOIN quartiers q ON u.id_quartier = q.id
                        ORDER BY RAND() LIMIT 6
                    ")->fetchAll();
                }
            } catch (Exception $e) {
                $coiffeurs_matching = [];
            }
            if (empty($coiffeurs_matching)) $coiffeurs_matching = $coiffures_par_defaut;
        }

        // Catalogue de démo pour les invités
        if ($role_actuel === 'invite') {
            try {
                $catalog_demo = $pdo->query("
                    SELECT s.*, u.nom as nom_coiffeur, v.nom_ville as ville, q.nom_quartier as quartier
                    FROM services s
                    JOIN users u ON s.id_prestataire = u.id
                    JOIN villes v ON u.id_ville = v.id
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    ORDER BY RAND() LIMIT 6
                ")->fetchAll();
            } catch (Exception $e) { $catalog_demo = []; }
            if (empty($catalog_demo)) $catalog_demo = $coiffures_par_defaut;
        }

        // Avis clients
        try {
            $all_comments = $pdo->query("
                SELECT c.*, u.nom FROM commentaires c
                JOIN users u ON c.id_client = u.id
                ORDER BY c.date_creation DESC LIMIT 6
            ")->fetchAll();
        } catch (Exception $e) { $all_comments = []; }

        // Affiche la vue — elle gère son propre HTML complet
        include __DIR__ . '/views/home.php';
        break;
}
