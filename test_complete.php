<?php
/**
 * test_complete.php — Tests de fonctionnalité complète
 * Vérifie toutes les fonctionnalités du projet
 */

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         TESTS COMPLETS — Domizi v1.0                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$tests_passed = 0;
$tests_failed = 0;

function test($name, $condition, $details = '') {
    global $tests_passed, $tests_failed;
    if ($condition) {
        echo "  ✓ $name\n";
        if ($details) echo "    → $details\n";
        $tests_passed++;
    } else {
        echo "  ✗ $name\n";
        if ($details) echo "    → $details\n";
        $tests_failed++;
    }
}

// ─────────────────────────────────────────────────────────────────
// 1. FICHIERS CRITIQUES
// ─────────────────────────────────────────────────────────────────
echo "1️⃣  FICHIERS CRITIQUES\n";
echo "──────────────────────────────────────────────────────────────\n";

$critical_files = [
    'index.php',
    'app.php',
    'security/PaymentService.php',
    'security/AuditService.php',
    'security/RateLimitService.php',
    'security/IdentityVerificationService.php',
    'security/IdempotencyService.php',
    'security/ValidationService.php',
    'security/SecurePaymentOrchestrator.php',
    'ia_controlleur.php',
    'views/components/ia_assistant.php',
    'src/Core/Database.php',
];

foreach ($critical_files as $file) {
    $path = __DIR__ . '/' . $file;
    test($file, file_exists($path), file_exists($path) ? filesize($path) . ' bytes' : 'FILE NOT FOUND');
}

// ─────────────────────────────────────────────────────────────────
// 2. CONFIGURATION
// ─────────────────────────────────────────────────────────────────
echo "\n2️⃣  CONFIGURATION\n";
echo "──────────────────────────────────────────────────────────────\n";

test('.env exists', file_exists(__DIR__ . '/.env'), '.env' . (file_exists(__DIR__ . '/.env') ? ' OK' : ' MISSING'));
test('composer.json exists', file_exists(__DIR__ . '/composer.json'));

$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $env = file_get_contents($env_file);
    test('DB_HOST configured', strpos($env, 'DB_HOST=') !== false);
    test('DB_NAME=domizi', strpos($env, 'DB_NAME=domizi') !== false);
}

// ─────────────────────────────────────────────────────────────────
// 3. CONNEXION MYSQL
// ─────────────────────────────────────────────────────────────────
echo "\n3️⃣  MYSQL & BASE DE DONNÉES\n";
echo "──────────────────────────────────────────────────────────────\n";

try {
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Charger .env
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
    
    test('MySQL Connection', true, 'Connected successfully');
    
    // Vérifier les tables
    $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $table_count = $stmt->fetchColumn();
    test('Database Tables', $table_count === 27, "$table_count tables found (expected 27)");
    
    // Vérifier les tables critiques
    $critical_tables = [
        'users',
        'rendez_vous',
        'services',
        'transactions_portefeuille',
        'audit_transactions',
        'idempotency_transactions',
        'notifications',
        'profils_prestataires',
        'services',
        'disponibilites',
        'avis',
        'favoris',
    ];
    
    foreach ($critical_tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table'");
        $exists = $stmt->fetchColumn();
        test("Table: $table", $exists === 1);
    }
    
} catch (Exception $e) {
    test('MySQL Connection', false, $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────
// 4. SERVICES DE SÉCURITÉ
// ─────────────────────────────────────────────────────────────────
echo "\n4️⃣  SERVICES DE SÉCURITÉ\n";
echo "──────────────────────────────────────────────────────────────\n";

// Vérifier les classes de sécurité
$security_classes = [
    'AuditService',
    'RateLimitService',
    'IdentityVerificationService',
    'IdempotencyService',
    'ValidationService',
    'PaymentService',
    'SecurePaymentOrchestrator',
];

foreach ($security_classes as $class) {
    $file = __DIR__ . '/security/' . $class . '.php';
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $has_class = strpos($content, "class $class") !== false;
        test($class, $has_class, $has_class ? 'Class found' : 'Class definition missing');
    } else {
        test($class, false, 'File not found');
    }
}

// ─────────────────────────────────────────────────────────────────
// 5. AGENT IA
// ─────────────────────────────────────────────────────────────────
echo "\n5️⃣  AGENT IA\n";
echo "──────────────────────────────────────────────────────────────\n";

$ia_controller = __DIR__ . '/ia_controlleur.php';
$ia_component = __DIR__ . '/views/components/ia_assistant.php';

if (file_exists($ia_controller)) {
    $content = file_get_contents($ia_controller);
    test('IA Controller', true, filesize($ia_controller) . ' bytes');
    test('Gemini API Integration', strpos($content, 'generativelanguage.googleapis.com') !== false);
    test('Context System', strpos($content, 'contexte_systeme') !== false);
    test('Multiple Roles', strpos($content, "role_actuel") !== false && strpos($content, "'client'") !== false);
} else {
    test('IA Controller', false, 'File not found');
}

if (file_exists($ia_component)) {
    $content = file_get_contents($ia_component);
    test('IA Component', true, filesize($ia_component) . ' bytes');
    test('Orb Interface', strpos($content, 'ia-orb') !== false);
    test('Chat Window', strpos($content, 'ia-chat-window') !== false);
    test('State Management', strpos($content, 'ia-state-') !== false);
    test('Visual States', 
        strpos($content, 'listening') !== false && 
        strpos($content, 'thinking') !== false &&
        strpos($content, 'acting') !== false,
        'Inactif, Écoute, Réflexion, Action, Réponse'
    );
    test('Voice Recognition', strpos($content, 'SpeechRecognition') !== false);
    test('Voice Synthesis', strpos($content, 'speechSynthesis') !== false);
} else {
    test('IA Component', false, 'File not found');
}

// ─────────────────────────────────────────────────────────────────
// 6. PHP & EXTENSIONS
// ─────────────────────────────────────────────────────────────────
echo "\n6️⃣  PHP & EXTENSIONS\n";
echo "──────────────────────────────────────────────────────────────\n";

test('PHP Version', version_compare(PHP_VERSION, '8.1.0', '>='), 'PHP ' . PHP_VERSION);

$extensions = ['pdo', 'pdo_mysql', 'mbstring', 'curl', 'json', 'gd'];
foreach ($extensions as $ext) {
    test("Extension: $ext", extension_loaded($ext));
}

// ─────────────────────────────────────────────────────────────────
// 7. PERMISSIONS
// ─────────────────────────────────────────────────────────────────
echo "\n7️⃣  PERMISSIONS\n";
echo "──────────────────────────────────────────────────────────────\n";

$dirs_check = [
    'uploads' => 'Uploads',
    'access/uploads' => 'Uploads accès',
];

foreach ($dirs_check as $dir => $name) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $writable = is_writable($path);
        test("$name writable", $writable, $writable ? 'OK' : 'NOT WRITABLE');
    } else {
        test("$name exists", false, 'Directory not found');
    }
}

// ─────────────────────────────────────────────────────────────────
// 8. ARCHITECTURE PHP
// ─────────────────────────────────────────────────────────────────
echo "\n8️⃣  ARCHITECTURE PHP\n";
echo "──────────────────────────────────────────────────────────────\n";

$arch_files = [
    'src/Core/Database.php' => 'PDO Singleton',
    'src/Core/Router.php' => 'Routing',
    'src/Core/Session.php' => 'Session Management',
    'src/Services/EmailService.php' => 'Email Service',
    'src/Services/OtpService.php' => 'OTP Service',
];

foreach ($arch_files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    test($desc, file_exists($path), file_exists($path) ? 'OK' : 'MISSING');
}

// ─────────────────────────────────────────────────────────────────
// 9. ROUTES & CONTRÔLEURS
// ─────────────────────────────────────────────────────────────────
echo "\n9️⃣  ROUTES & CONTRÔLEURS\n";
echo "──────────────────────────────────────────────────────────────\n";

test('Routes file', file_exists(__DIR__ . '/routes/web.php'));
test('Base Controller', file_exists(__DIR__ . '/src/Controllers/BaseController.php'));

// ─────────────────────────────────────────────────────────────────
// 10. CSS & JS
// ─────────────────────────────────────────────────────────────────
echo "\n🔟  CSS & JS\n";
echo "──────────────────────────────────────────────────────────────\n";

$css_files = glob(__DIR__ . '/css/*.css');
$js_files = glob(__DIR__ . '/js/*.js');

test('CSS Files', count($css_files) > 0, count($css_files) . ' file(s) found');
test('JS Files', count($js_files) > 0, count($js_files) . ' file(s) found');

// ─────────────────────────────────────────────────────────────────
// RÉSUMÉ
// ─────────────────────────────────────────────────────────────────

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RÉSUMÉ DES TESTS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

$total = $tests_passed + $tests_failed;
$percentage = $total > 0 ? round(($tests_passed / $total) * 100, 1) : 0;

echo "  ✓ Réussis : $tests_passed\n";
echo "  ✗ Échoués : $tests_failed\n";
echo "  📊 Total : $total\n";
echo "  📈 Pourcentage : $percentage%\n";
echo "\n";

if ($tests_failed === 0) {
    echo "✅ TOUS LES TESTS RÉUSSIS!\n";
    echo "\n";
    echo "🚀 Le projet est prêt à fonctionner sur XAMPP\n";
    echo "\n";
    echo "Prochaines étapes :\n";
    echo "  1. Démarrer Apache et MySQL dans XAMPP Control Panel\n";
    echo "  2. Visiter : http://localhost/coiffons/\n";
    echo "  3. Tester l'agent IA\n";
} else {
    echo "⚠️  ATTENTION : $tests_failed test(s) ont échoué\n";
    echo "\n";
    echo "À corriger :\n";
    echo "  - Vérifier les fichiers manquants\n";
    echo "  - Vérifier la connexion MySQL\n";
    echo "  - Vérifier les permissions\n";
}

echo "\n";
?>
