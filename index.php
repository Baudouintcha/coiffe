<?php
/**
 * index.php — Routeur de "Coiffe Chez Toi"
 * Appelé par app.php quand l'utilisateur choisit le métier Coiffure
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';

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
        $id_ville_client    = $_SESSION['ville'] ?? 0;
        $id_quartier_client = $_SESSION['id_quartier'] ?? 0;
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.*, u.nom as nom_coiffeur,
                    q.nom_quartier as quartier, v.nom_ville as ville,
                    u.is_approved, u.photo_profil as photo_profil_coiffeur
                FROM prestations p
                JOIN users u ON p.id_coiffeur = u.id
                JOIN villes v ON u.ville = v.id
                LEFT JOIN zones_coiffeur z ON u.id = z.id_coiffeur
                LEFT JOIN quartiers q ON u.id_quartier = q.id
                WHERE u.role = 'coiffeur' AND u.abonnement_status = 1
                AND u.ville = ? AND z.id_quartier = ?
                ORDER BY RAND() LIMIT 9
            ");
            $stmt->execute([$id_ville_client, $id_quartier_client]);
            $coiffeurs_matching = $stmt->fetchAll();
            if (empty($coiffeurs_matching)) {
                $stmt2 = $pdo->prepare("
                    SELECT DISTINCT p.*, u.nom as nom_coiffeur,
                        q.nom_quartier as quartier, v.nom_ville as ville,
                        u.is_approved, u.photo_profil as photo_profil_coiffeur
                    FROM prestations p
                    JOIN users u ON p.id_coiffeur = u.id
                    JOIN villes v ON u.ville = v.id
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE u.role = 'coiffeur' AND u.abonnement_status = 1 AND u.ville = ?
                    ORDER BY RAND() LIMIT 9
                ");
                $stmt2->execute([$id_ville_client]);
                $coiffeurs_matching = $stmt2->fetchAll();
            }
        } catch (Exception $e) {
            $coiffeurs_matching = [];
        }
        include __DIR__ . '/views/client/dashboard.php';
        exit();

    case 'home':
    default:
        // La vue home.php est autonome (slider + header intégré)
        // Elle ne nécessite pas le layout/header.php

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
            ['id_prestation'=>1,'id_coiffeur'=>1,'nom_style'=>'Gros Rastas Premium','prix'=>7000,'nom_coiffeur'=>'Alliance Coiffure','ville'=>'Cotonou','quartier'=>'Fidjrossè','photo_style'=>null],
            ['id_prestation'=>2,'id_coiffeur'=>2,'nom_style'=>'Dégradé Américain / Wave','prix'=>3000,'nom_coiffeur'=>'Barber Shop Pro','ville'=>'Calavi','quartier'=>'Zogbadjè','photo_style'=>null],
            ['id_prestation'=>3,'id_coiffeur'=>3,'nom_style'=>'Nattes Collées Graphiques','prix'=>5000,'nom_coiffeur'=>'Divine Mains','ville'=>'Porto-Novo','quartier'=>'Ouando','photo_style'=>null],
        ];

        // Données pour le coiffeur connecté
        if ($coiffeur_id && $role_actuel === 'coiffeur') {
            try {
                $chk = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $chk->execute([$coiffeur_id]);
                $db_coiffeur = $chk->fetch() ?: [];

                $stmt = $pdo->prepare("
                    SELECT p.*, v.nom_ville as ville, q.nom_quartier as quartiers
                    FROM prestations p
                    JOIN users u ON p.id_coiffeur = u.id
                    LEFT JOIN villes v ON u.ville = v.id
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE p.id_coiffeur = ?
                    ORDER BY p.id_prestation DESC
                ");
                $stmt->execute([$coiffeur_id]);
                $mes_coiffures = $stmt->fetchAll();
            } catch (Exception $e) {
                $mes_coiffures = [];
            }
        }

        // Données pour le client connecté (matching géographique)
        if ($role_actuel === 'client') {
            $id_ville_client   = $_SESSION['ville'] ?? 0;
            $id_quartier_client= $_SESSION['id_quartier'] ?? 0;
            try {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT p.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville
                    FROM prestations p
                    JOIN users u ON p.id_coiffeur = u.id
                    JOIN villes v ON u.ville = v.id
                    INNER JOIN zones_coiffeur z ON u.id = z.id_coiffeur
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE u.role = 'coiffeur' AND u.abonnement_status = 1
                    AND u.ville = ? AND z.id_quartier = ?
                    ORDER BY RAND() LIMIT 6
                ");
                $stmt->execute([$id_ville_client, $id_quartier_client]);
                $coiffeurs_matching = $stmt->fetchAll();

                if (empty($coiffeurs_matching)) {
                    $stmt2 = $pdo->prepare("
                        SELECT p.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville
                        FROM prestations p
                        JOIN users u ON p.id_coiffeur = u.id
                        JOIN villes v ON u.ville = v.id
                        LEFT JOIN quartiers q ON u.id_quartier = q.id
                        WHERE u.role = 'coiffeur' AND u.abonnement_status = 1 AND u.ville = ?
                        ORDER BY RAND() LIMIT 6
                    ");
                    $stmt2->execute([$id_ville_client]);
                    $coiffeurs_matching = $stmt2->fetchAll();
                }

                if (empty($coiffeurs_matching)) {
                    $coiffeurs_matching = $pdo->query("
                        SELECT p.*, u.nom as nom_coiffeur, q.nom_quartier as quartier, v.nom_ville as ville
                        FROM prestations p
                        JOIN users u ON p.id_coiffeur = u.id
                        JOIN villes v ON u.ville = v.id
                        LEFT JOIN quartiers q ON u.id_quartier = q.id
                        WHERE u.abonnement_status = 1 ORDER BY RAND() LIMIT 6
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
                    SELECT p.*, u.nom as nom_coiffeur, v.nom_ville as ville, q.nom_quartier as quartier
                    FROM prestations p
                    JOIN users u ON p.id_coiffeur = u.id
                    JOIN villes v ON u.ville = v.id
                    LEFT JOIN quartiers q ON u.id_quartier = q.id
                    WHERE u.abonnement_status = 1 ORDER BY RAND() LIMIT 6
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
