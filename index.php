<?php

/**
 * app.php — Point d'entrée XAMPP local
 * Gère aussi les actions API et le changement de langue
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use App\Core\Session;
use App\Core\Lang;
use App\Core\Database;

Session::start();

// ── CHANGEMENT DE LANGUE (via ?lang=fr) ──
if (isset($_GET['lang'])) {
    Session::setLang($_GET['lang']);
    // Retire le paramètre lang de l'URL et redirige proprement
    $params = $_GET;
    unset($params['lang']);
    $redirect = '/coiffons/index.php';
    if (!empty($params)) {
        $redirect .= '?' . http_build_query($params);
    }
    header('Location: ' . $redirect);
    exit();
}

Lang::init();

// ── API MÉTIERS (appel AJAX ?action=api_metiers&domaine=1) ──
if (isset($_GET['action']) && $_GET['action'] === 'api_metiers') {
    header('Content-Type: application/json; charset=utf-8');
    $idDomaine = intval($_GET['domaine'] ?? 0);

    try {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT id, nom, slug, icone, description
            FROM metiers
            WHERE id_domaine = ? AND actif = 1
            ORDER BY nom ASC
        ");
        $stmt->execute([$idDomaine]);
        $metiers = $stmt->fetchAll();

        echo json_encode(['success' => true, 'metiers' => $metiers], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// ── BRANCHE MÉTIER (?metier=coiffure) ──
if (isset($_GET['metier'])) {
    $slugMetier = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['metier']));

    try {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT m.*, d.nom as domaine_nom
            FROM metiers m
            JOIN domaines d ON m.id_domaine = d.id
            WHERE m.slug = ? AND m.actif = 1
        ");
        $stmt->execute([$slugMetier]);
        $metier = $stmt->fetch();

        if (!$metier) {
            http_response_code(404);
            echo "<h1 style='color:#D4AF37;background:#0a0a0a;padding:40px;font-family:sans-serif'>Métier introuvable</h1>";
            exit();
        }

        // Sauvegarde le métier choisi en session pour tout le parcours
        Session::set('metier_actif', $metier);

        // Redirige vers la page d'accueil du métier
        // Pour coiffure → app.php (routeur Coiffe Chez Toi)
        // Les autres métiers auront leurs propres pages après la soutenance
        switch ($slugMetier) {
            case 'coiffure':
            default:
                require __DIR__ . '/app.php';
                break;
        }

    } catch (Exception $e) {
        echo "<p style='color:red;background:#0a0a0a;padding:20px'>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
    exit();
}

// ── REDIRECTION UTILISATEURS CONNECTÉS VERS LEURS DASHBOARDS (SAUF SI ?metier) ──
// Cette redirection ne s'exécute que si on n'a pas de ?metier
if (isset($_SESSION['user_id'])) {
    $role_session = $_SESSION['role'] ?? 'client';
    if ($role_session === 'prestataire') {
        header("Location: /coiffons/index.php?metier=coiffure&page=dashboard_coiffeur");
        exit();
    } elseif ($role_session === 'client') {
        header("Location: /coiffons/index.php?metier=coiffure&page=dashboard");
        exit();
    } elseif ($role_session === 'admin') {
        header("Location: /coiffons/first/admin_dashboard.php");
        exit();
    }
}

// ── PAGE PRINCIPALE DOMIZI ──
use App\Core\View;

try {
    $pdo  = Database::getInstance();
    $stmt = $pdo->query("
        SELECT d.*, COUNT(m.id) as nb_metiers
        FROM domaines d
        LEFT JOIN metiers m ON m.id_domaine = d.id AND m.actif = 1
        WHERE d.actif = 1 OR d.actif = 0
        GROUP BY d.id
        ORDER BY d.id ASC
    ");
    $domaines = $stmt->fetchAll();
} catch (Exception $e) {
    $domaines = [];
}

$lang  = Lang::current();
$isRtl = Lang::isRtl();
require __DIR__ . '/views/domizi/home.php';
