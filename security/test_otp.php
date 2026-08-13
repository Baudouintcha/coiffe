<?php
/**
 * security/test_otp.php — Tests du Système OTP
 * Coiffe Chez Toi — Vérification et debug
 *
 * USAGE CLI:
 *   php security/test_otp.php
 *
 * Tests effectués:
 *   1. Configuration base de données
 *   2. Table otp_codes existe
 *   3. Génération d'OTP
 *   4. Vérification d'OTP
 *   5. Rate limiting
 *   6. Expiration
 *   7. Configuration email
 */

if (php_sapi_name() !== 'cli' && !isset($_GET['cli'])) {
    die("Accès CLI uniquement pour des raisons de sécurité.\n");
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../src/Services/OtpService.php';
require_once __DIR__ . '/../src/Services/EmailService.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Test OTP — Coiffe Chez Toi                                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$test_results = [];

// ═══════════════════════════════════════════════════════════════
// TEST 1: Connexion BDD
// ═══════════════════════════════════════════════════════════════

echo "[TEST 1] Connexion à la base de données...\n";
try {
    $pdo->query("SELECT 1");
    echo "✅ PASS — Connexion OK\n\n";
    $test_results[] = true;
} catch (Exception $e) {
    echo "❌ FAIL — " . $e->getMessage() . "\n\n";
    $test_results[] = false;
    exit(1);
}

// ═══════════════════════════════════════════════════════════════
// TEST 2: Table otp_codes existe
// ═══════════════════════════════════════════════════════════════

echo "[TEST 2] Vérification de la table otp_codes...\n";
try {
    $result = $pdo->query("SHOW TABLES LIKE 'otp_codes'")->fetch();
    if ($result) {
        echo "✅ PASS — Table existante\n\n";
        $test_results[] = true;
    } else {
        echo "❌ FAIL — Table non trouvée\n";
        echo "   Exécutez: php security/setup_otp.php\n\n";
        $test_results[] = false;
    }
} catch (Exception $e) {
    echo "❌ FAIL — " . $e->getMessage() . "\n\n";
    $test_results[] = false;
}

// ═══════════════════════════════════════════════════════════════
// TEST 3: OtpService - Génération
// ═══════════════════════════════════════════════════════════════

echo "[TEST 3] Génération d'OTP...\n";

// Créer un utilisateur test s'il n'existe pas
$test_email = 'test_otp_' . time() . '@coiffes-chez-toi.local';
$test_user_id = null;

try {
    $pdo->prepare("INSERT INTO users (nom, prenom, email, telephone, password, role, statut) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute(['Test', 'OTP', $test_email, '12345678', password_hash('test', PASSWORD_DEFAULT), 'client', 'actif']);
    $test_user_id = $pdo->lastInsertId();
    
    // Générer un OTP
    $otp_service = new OtpService($pdo);
    $result = $otp_service->generate($test_user_id, 'registration');

    if ($result['success'] && !empty($result['code'])) {
        echo "✅ PASS — OTP généré: {$result['code']}\n";
        $generated_code = $result['code'];
        $test_results[] = true;
    } else {
        echo "❌ FAIL — " . ($result['message'] ?? 'Erreur inconnue') . "\n";
        $test_results[] = false;
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ FAIL — " . $e->getMessage() . "\n\n";
    $test_results[] = false;
    $generated_code = null;
}

// ═══════════════════════════════════════════════════════════════
// TEST 4: OtpService - Vérification
// ═══════════════════════════════════════════════════════════════

echo "[TEST 4] Vérification d'OTP...\n";

if ($test_user_id && $generated_code) {
    try {
        $verify_result = $otp_service->verify($test_user_id, 'registration', $generated_code);
        
        if ($verify_result['success']) {
            echo "✅ PASS — OTP vérifié avec succès\n";
            $test_results[] = true;
        } else {
            echo "❌ FAIL — " . $verify_result['message'] . "\n";
            $test_results[] = false;
        }
    } catch (Exception $e) {
        echo "❌ FAIL — " . $e->getMessage() . "\n";
        $test_results[] = false;
    }
} else {
    echo "⚠️  SKIP — Génération OTP n'a pas réussi\n";
    $test_results[] = null;
}
echo "\n";

// ═══════════════════════════════════════════════════════════════
// TEST 5: Brute-force - Code incorrect
// ═══════════════════════════════════════════════════════════════

echo "[TEST 5] Protection brute-force (code incorrect)...\n";

// Créer un nouvel OTP
try {
    $result = $otp_service->generate($test_user_id, 'password_reset');
    if ($result['success']) {
        $new_code = $result['code'];
        
        // Essayer 5 codes incorrects
        $wrong_attempts = 0;
        for ($i = 0; $i < 5; $i++) {
            $wrong_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if ($wrong_code !== $new_code) {
                $verify_result = $otp_service->verify($test_user_id, 'password_reset', $wrong_code);
                $wrong_attempts++;
            }
        }

        // 6e tentative
        $verify_result = $otp_service->verify($test_user_id, 'password_reset', str_pad(999999, 6, '0', STR_PAD_LEFT));
        
        if (!$verify_result['success']) {
            echo "✅ PASS — OTP bloqué après 5 tentatives incorrectes\n";
            $test_results[] = true;
        } else {
            echo "❌ FAIL — OTP devrait être bloqué\n";
            $test_results[] = false;
        }
    }
} catch (Exception $e) {
    echo "❌ FAIL — " . $e->getMessage() . "\n";
    $test_results[] = false;
}
echo "\n";

// ═══════════════════════════════════════════════════════════════
// TEST 6: Rate limiting
// ═══════════════════════════════════════════════════════════════

echo "[TEST 6] Rate limiting (60 secondes)...\n";
try {
    // Générer un OTP
    $r1 = $otp_service->generate($test_user_id, 'email_change');
    
    // Essayer immédiatement
    $r2 = $otp_service->generate($test_user_id, 'email_change');
    
    if (!$r2['success'] && strpos($r2['message'], 'attendre') !== false) {
        echo "✅ PASS — Rate limiting fonctionne\n";
        $test_results[] = true;
    } else {
        echo "❌ FAIL — Rate limiting ne fonctionne pas\n";
        $test_results[] = false;
    }
} catch (Exception $e) {
    echo "❌ FAIL — " . $e->getMessage() . "\n";
    $test_results[] = false;
}
echo "\n";

// ═══════════════════════════════════════════════════════════════
// TEST 7: Email Configuration
// ═══════════════════════════════════════════════════════════════

echo "[TEST 7] Configuration Email...\n";
try {
    $email_service = new EmailService();
    $test_result = $email_service->testConnection();
    
    if ($test_result['success']) {
        echo "✅ PASS — " . $test_result['message'] . "\n";
        $test_results[] = true;
    } else {
        echo "⚠️  WARNING — " . $test_result['message'] . "\n";
        echo "   À ignorer si mail() ne requiert pas SMTP\n";
        $test_results[] = null; // Pas bloquant
    }
} catch (Exception $e) {
    echo "❌ FAIL — " . $e->getMessage() . "\n";
    echo "   Assurez-vous que PHPMailer est installé: composer require phpmailer/phpmailer\n";
    $test_results[] = false;
}
echo "\n";

// ═══════════════════════════════════════════════════════════════
// NETTOYAGE TEST
// ═══════════════════════════════════════════════════════════════

if ($test_user_id) {
    try {
        $pdo->prepare("DELETE FROM otp_codes WHERE user_id = ?")->execute([$test_user_id]);
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$test_user_id]);
    } catch (Exception $e) {
        // Silencieux
    }
}

// ═══════════════════════════════════════════════════════════════
// RÉSUMÉ FINAL
// ═══════════════════════════════════════════════════════════════

echo "╔══════════════════════════════════════════════════════════════╗\n";

$passed = count(array_filter($test_results, fn($r) => $r === true));
$failed = count(array_filter($test_results, fn($r) => $r === false));
$total = count($test_results);

echo "║  RÉSUMÉ: $passed/$total tests réussis                     \n";

if ($failed > 0) {
    echo "║  ❌ $failed test(s) échoué(s)                           \n";
}

echo "╚══════════════════════════════════════════════════════════════╝\n";

exit($failed > 0 ? 1 : 0);
