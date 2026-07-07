<?php

/**
 * public/index.php — Point d'entrée unique de DOMIZI
 * VERSION XAMPP : Fonctionne depuis /coiffons/public/index.php
 * ET depuis /coiffons/ via le .htaccess racine
 */

declare(strict_types=1);

// Autoloader Composer — chemin relatif depuis public/
require_once __DIR__ . '/../vendor/autoload.php';

// Variables d'environnement
$envFile = __DIR__ . '/../.env';
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

// Erreurs
if (($_ENV['APP_DEBUG'] ?? 'true') === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

use App\Core\Session;
use App\Core\Lang;
use App\Core\Router;

Session::start();
Lang::init();

$router = new Router();
require_once __DIR__ . '/../routes/web.php';
$router->dispatch();
