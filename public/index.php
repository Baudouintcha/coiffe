<?php

/**
 * public/index.php — Point d'entrée unique de DOMIZI
 *
 * CONCEPT : TOUTES les requêtes HTTP passent par ce fichier.
 * Le .htaccess redirige tout ici, et ce fichier orchestre :
 *   1. Chargement de l'autoloader Composer (PSR-4)
 *   2. Chargement des variables d'environnement (.env)
 *   3. Démarrage de la session
 *   4. Initialisation de la langue
 *   5. Dispatch vers le bon Controller via le Router
 *
 * C'est le "chef d'orchestre" — il ne fait rien lui-même,
 * il délègue tout aux bonnes classes.
 */

declare(strict_types=1);

// ================================================================
// 1. AUTOLOADER COMPOSER
// Charge automatiquement toutes les classes App\*
// ================================================================
require_once __DIR__ . '/../vendor/autoload.php';

// ================================================================
// 2. VARIABLES D'ENVIRONNEMENT (.env)
// Charge DB_HOST, DB_NAME, APP_URL etc. dans $_ENV
// ================================================================
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignore les commentaires
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// ================================================================
// 3. GESTION DES ERREURS
// En développement : affiche tout
// En production : log seulement
// ================================================================
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ================================================================
// 4. SESSION & LANGUE
// ================================================================
use App\Core\Session;
use App\Core\Lang;
use App\Core\Router;

Session::start();
Lang::init();

// ================================================================
// 5. ROUTEUR
// Charge les routes définies dans routes/web.php
// Dispatch vers le bon Controller
// ================================================================
$router = new Router();

require_once __DIR__ . '/../routes/web.php';

$router->dispatch();
