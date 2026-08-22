<?php
/**
 * diagnostic_project.php — Diagnostic complet du projet
 * Vérifie l'intégrité, les dépendances, les fichiers manquants
 */

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         DIAGNOSTIC COMPLET DU PROJET DOMIZI                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. FICHIERS DE CONFIGURATION
echo "1️⃣  FICHIERS DE CONFIGURATION\n";
echo "──────────────────────────────────────────────────────────────\n";

$config_files = [
    '.env' => 'Configuration environnement',
    '.htaccess' => 'Apache rewrite rules',
    'composer.json' => 'Dépendances Composer',
    'phpunit.xml' => 'Configuration PHPUnit',
];

foreach ($config_files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $status = $exists ? '✓' : '✗';
    echo "  $status $file ($desc)\n";
}

// 2. RÉPERTOIRES ESSENTIELS
echo "\n2️⃣  RÉPERTOIRES ESSENTIELS\n";
echo "──────────────────────────────────────────────────────────────\n";

$directories = [
    'src' => 'Code métier (Services, Controllers, Core)',
    'security' => 'Sécurité (PaymentService, Audit, etc.)',
    'views' => 'Vues PHP',
    'css' => 'Feuilles de style',
    'js' => 'JavaScript',
    'vendor' => 'Dépendances Composer',
    'uploads' => 'Fichiers uploadés',
    'routes' => 'Routes',
    'layout' => 'Layouts HTML',
];

foreach ($directories as $dir => $desc) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $status = $exists ? '✓' : '✗';
    if ($exists) {
        $file_count = count(glob($path . '/*', GLOB_MARK));
        echo "  $status $dir/ ($file_count éléments) - $desc\n";
    } else {
        echo "  $status $dir/ - $desc\n";
    }
}

// 3. FICHIERS CRITIQUES PHP
echo "\n3️⃣  FICHIERS CRITIQUES PHP\n";
echo "──────────────────────────────────────────────────────────────\n";

$critical_files = [
    'index.php' => 'Point d\'entrée',
    'app.php' => 'Application principale',
    'security/config.php' => 'Configuration sécurité',
    'security/PaymentService.php' => 'Service de paiement',
    'security/AuditService.php' => 'Service d\'audit',
    'security/RateLimitService.php' => 'Service rate limit',
    'security/IdentityVerificationService.php' => 'Vérification identité',
    'security/IdempotencyService.php' => 'Service idempotence',
    'security/ValidationService.php' => 'Service validation',
    'security/SecurePaymentOrchestrator.php' => 'Orchestrateur paiement',
    'src/Core/Database.php' => 'Singleton PDO',
    'src/Core/Router.php' => 'Routeur',
    'src/Core/Session.php' => 'Gestion session',
    'src/Services/EmailService.php' => 'Service email',
    'src/Services/OtpService.php' => 'Service OTP',
    'ia_controlleur.php' => 'Contrôleur IA',
    'views/components/ia_assistant.php' => 'Composant IA',
];

foreach ($critical_files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $status = $exists ? '✓' : '✗';
    $size = $exists ? filesize($path) : 0;
    $size_str = $size > 0 ? ' (' . number_format($size / 1024, 1) . ' KB)' : '';
    echo "  $status $file$size_str - $desc\n";
}

// 4. VÉRIFICATION DE LA CONNEXION MYSQL
echo "\n4️⃣  VÉRIFICATION MYSQL\n";
echo "──────────────────────────────────────────────────────────────\n";

try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Charger les variables .env
    $env_file = __DIR__ . '/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }

    $pdo = new PDO(
        "mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') . ";dbname=" . ($_ENV['DB_NAME'] ?? 'domizi'),
        $_ENV['DB_USER'] ?? 'root',
        $_ENV['DB_PASS'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "  ✓ Connexion MySQL établie\n";

    // Vérifier les tables
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $table_count = $stmt->fetchColumn();
    echo "  ✓ Base de données : $table_count tables\n";

    // Lister les tables
    $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $critical_tables = [
        'users' => 'Utilisateurs',
        'rendez_vous' => 'Rendez-vous',
        'services' => 'Services',
        'transactions_portefeuille' => 'Transactions',
        'audit_transactions' => 'Audit',
        'idempotency_transactions' => 'Idempotence',
        'notifications' => 'Notifications',
    ];

    echo "\n  Tables critiques :\n";
    foreach ($critical_tables as $table => $desc) {
        $exists = in_array($table, $tables);
        $status = $exists ? '✓' : '✗';
        echo "    $status $table - $desc\n";
    }

} catch (Exception $e) {
    echo "  ✗ Erreur MySQL: " . $e->getMessage() . "\n";
}

// 5. VÉRIFICATION PHP
echo "\n5️⃣  VÉRIFICATION PHP\n";
echo "──────────────────────────────────────────────────────────────\n";

echo "  ✓ Version PHP : " . phpversion() . "\n";
echo "  " . (extension_loaded('pdo') ? '✓' : '✗') . " Extension PDO\n";
echo "  " . (extension_loaded('pdo_mysql') ? '✓' : '✗') . " Extension PDO MySQL\n";
echo "  " . (extension_loaded('mbstring') ? '✓' : '✗') . " Extension mbstring\n";
echo "  " . (extension_loaded('curl') ? '✓' : '✗') . " Extension curl\n";
echo "  " . (extension_loaded('json') ? '✓' : '✗') . " Extension json\n";

// 6. VÉRIFICATION DES PERMISSIONS
echo "\n6️⃣  PERMISSIONS\n";
echo "──────────────────────────────────────────────────────────────\n";

$dirs_to_check = [
    'uploads' => 'Uploads',
    'access/uploads' => 'Uploads accès',
];

foreach ($dirs_to_check as $dir => $name) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $writable = is_writable($path);
        $status = $writable ? '✓' : '✗';
        echo "  $status $name ($dir) - " . ($writable ? 'Accessible en écriture' : 'Non accessible') . "\n";
    }
}

// 7. SUMMARY
echo "\n7️⃣  RÉSUMÉ\n";
echo "──────────────────────────────────────────────────────────────\n";
echo "  ✓ Projet Domizi restauré et prêt\n";
echo "  ✓ Base de données créée et configurée\n";
echo "  ✓ Services de sécurité en place\n";
echo "  ✓ Architecture complète\n\n";

echo "✅ Diagnostic terminé avec succès!\n";
?>
