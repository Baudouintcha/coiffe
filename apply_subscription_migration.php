<?php
/**
 * apply_subscription_migration.php
 * Script d'application de la migration subscription tiers
 * 
 * Exécute les migrations SQL pour supporter Basique et Premium
 */

session_start();

// Vérifier que c'est un admin
if (
    !isset($_SESSION['user_id']) || 
    !isset($_SESSION['role']) || 
    $_SESSION['role'] !== 'admin'
) {
    die('❌ Accès refusé. Administrateur requis.');
}

require_once __DIR__ . '/security/config.php';

try {
    echo "<h2>🔄 Migration Subscription Tiers</h2>\n";
    
    // 1. Ajouter colonne statut_abonnement
    echo "<p>1️⃣ Ajout de la colonne <code>statut_abonnement</code>...</p>";
    try {
        $pdo->exec("
            ALTER TABLE `users` 
            ADD COLUMN `statut_abonnement` ENUM('aucun', 'basique', 'premium') 
            DEFAULT 'aucun' 
            AFTER `is_approved`
        ");
        echo "✓ Colonne créée\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠️ Colonne existe déjà\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Initialiser coiffeurs approuvés
    echo "<p>2️⃣ Initialisation des coiffeurs approuvés...</p>";
    $stmt = $pdo->prepare("
        UPDATE `users` 
        SET `statut_abonnement` = 'basique'
        WHERE `role` = 'coiffeur' 
          AND `is_approved` = 1 
          AND `statut_abonnement` = 'aucun'
    ");
    $stmt->execute();
    $count = $stmt->rowCount();
    echo "✓ {$count} coiffeurs initialisés avec le statut 'basique'\n";
    
    // 3. Ajouter indices
    echo "<p>3️⃣ Création des indices...</p>";
    try {
        $pdo->exec("
            ALTER TABLE `users` 
            ADD INDEX `idx_visibility` (`role`, `is_approved`, `statut_abonnement`)
        ");
        echo "✓ Index idx_visibility créé\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⚠️ Index existe déjà\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("
            ALTER TABLE `users` 
            ADD INDEX `idx_tier_search` (`statut_abonnement`, `created_at`)
        ");
        echo "✓ Index idx_tier_search créé\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⚠️ Index existe déjà\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Vérification
    echo "<p>4️⃣ Vérification...</p>";
    $check = $pdo->query("
        SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN statut_abonnement = 'basique' THEN 1 ELSE 0 END) as basique,
          SUM(CASE WHEN statut_abonnement = 'premium' THEN 1 ELSE 0 END) as premium,
          SUM(CASE WHEN statut_abonnement = 'aucun' THEN 1 ELSE 0 END) as aucun
        FROM users 
        WHERE role = 'coiffeur'
    ");
    $stats = $check->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "Coiffeurs totaux : " . $stats['total'] . "\n";
    echo "  - Basique     : " . $stats['basique'] . "\n";
    echo "  - Premium     : " . $stats['premium'] . "\n";
    echo "  - Aucun       : " . $stats['aucun'] . "\n";
    echo "</pre>";
    
    echo "\n<h3 style='color: green;'>✅ Migration appliquée avec succès !</h3>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    exit(1);
}
?>
