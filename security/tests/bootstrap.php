<?php
/**
 * security/tests/bootstrap.php
 * Bootstrap file for PHPUnit test suite
 * 
 * Loads necessary components and sets up test environment
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables (test environment)
if (file_exists(__DIR__ . '/../../.env.test')) {
    $env_file = parse_ini_file(__DIR__ . '/../../.env.test');
    foreach ($env_file as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Set default environment variables for testing
if (!isset($_ENV['APP_ENV'])) {
    $_ENV['APP_ENV'] = 'test';
}

if (!isset($_ENV['MAIL_DRIVER'])) {
    $_ENV['MAIL_DRIVER'] = 'mail';
}

if (!isset($_ENV['MAIL_FROM_ADDRESS'])) {
    $_ENV['MAIL_FROM_ADDRESS'] = 'test@coiffeschez-toi.local';
}

if (!isset($_ENV['MAIL_FROM_NAME'])) {
    $_ENV['MAIL_FROM_NAME'] = 'Coiffe Chez Toi Test';
}

// Load Services
require_once __DIR__ . '/../../src/Services/OtpService.php';
require_once __DIR__ . '/../../src/Services/EmailService.php';

// Load test helpers
require_once __DIR__ . '/Helpers/TestDatabaseHelper.php';
require_once __DIR__ . '/Helpers/TestOtpHelper.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');
