<?php
/**
 * security/setup_otp.php — Configuration et Migration OTP
 * Coiffe Chez Toi — Script de setup du système OTP
 *
 * USAGE:
 *   1. Exécuter via PHP CLI: php security/setup_otp.php
 *   2. Ou inclure dans un script d'initialisation
 *
 * ACTIONS:
 *   - Créer la table otp_codes
 *   - Créer les colonnes manquantes dans users
 *   - Vérifier la configuration email
 *   - Afficher un rapport
 */

if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

require_once __DIR__ . '/config.php';

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  Setup OTP — Coiffe Chez Toi                               ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════
// 1. CRÉER LA TABLE OTP_CODES
// ═══════════════════════════════════════════════════════════════

echo "[1/3] Création de la table otp_codes...\n";

$migration_sql = file_get_contents(__DIR__ . '/migrations/001_create_otp_codes_table.sql');

try {
    $pdo->exec($migration_sql);
    echo "✅ Table otp_codes créée (ou existante).\n\n";
} catch (PDOException $e) {
    echo "❌ Erreur lors de la création de la table:\n";
    echo "   " . $e->getMessage() . "\n\n";
}

// ═══════════════════════════════════════════════════════════════
// 2. VÉRIFIER LES COLONNES USERS
// ═══════════════════════════════════════════════════════════════

echo "[2/3] Vérification des colonnes users...\n";

try {
    $columns_to_check = [
        'email_verified_at' => "DATETIME NULL DEFAULT NULL AFTER `email_verifie`",
        'otp_verified_at' => "DATETIME NULL DEFAULT NULL AFTER `email_verified_at`",
    ];

    foreach ($columns_to_check as $col => $def) {
        $check = $pdo->query("SHOW COLUMNS FROM users WHERE Field = '$col'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
            echo "✅ Colonne $col ajoutée.\n";
        } else {
            echo "✅ Colonne $col existe déjà.\n";
        }
    }
    echo "\n";
} catch (PDOException $e) {
    echo "⚠️  Erreur lors de la vérification des colonnes:\n";
    echo "   " . $e->getMessage() . "\n\n";
}

// ═══════════════════════════════════════════════════════════════
// 3. RÉSUMÉ ET RECOMMANDATIONS
// ═══════════════════════════════════════════════════════════════

echo "[3/3] Résumé de la configuration...\n\n";

// Vérifier la table
$count = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'otp_codes'")->fetchColumn();
if ($count > 0) {
    echo "✅ Table otp_codes: PRÊTE\n";
} else {
    echo "❌ Table otp_codes: NON TROUVÉE\n";
}

// Vérifier les indexes
$indexes = $pdo->query("SHOW INDEXES FROM otp_codes")->fetchAll(PDO::FETCH_COLUMN, 2);
echo "   Indexes: " . count($indexes) . " trouvés\n";

// Info sur la configuration email
echo "\n📧 Configuration Email:\n";
echo "   MAIL_DRIVER: " . ($_ENV['MAIL_DRIVER'] ?? 'mail (défaut)') . "\n";
echo "   MAIL_FROM: " . ($_ENV['MAIL_FROM_ADDRESS'] ?? 'non configuré') . "\n";

// Vérifier PHPMailer
$composer_file = __DIR__ . '/../composer.json';
if (file_exists($composer_file)) {
    $composer = json_decode(file_get_contents($composer_file), true);
    if (isset($composer['require']['phpmailer/phpmailer'])) {
        echo "   PHPMailer: INSTALLÉ\n";
    } else {
        echo "   ⚠️  PHPMailer: NON INSTALLÉ (run: composer require phpmailer/phpmailer)\n";
    }
}

// Résumé final
echo "\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  SETUP TERMINÉ                                              ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
echo "║  Prochaines étapes:                                         ║\n";
echo "║  1. ✅ Inclure les services OTP dans vos pages             ║\n";
echo "║  2. ✅ Intégrer le composant UI (otp_verification.php)     ║\n";
echo "║  3. ✅ Tester avec security/test_otp.php                  ║\n";
echo "║  4. ✅ Configurer l'email (.env)                          ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
