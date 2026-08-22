<?php
/**
 * ANALYSE BD — Structure actuelle pour implémentation abonnements
 */

require_once __DIR__ . '/security/config.php';

echo "================================================================================\n";
echo "ANALYSE STRUCTURE BD POUR SYSTÈME D'ABONNEMENT\n";
echo "================================================================================\n\n";

// 1. Vérifier colonnes users
echo "TABLE USERS (Coiffeurs/Clients):\n";
echo str_repeat("-", 80) . "\n";
$cols_users = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols_users as $col) {
    $extra = '';
    if ($col['Key'] === 'PRI') $extra .= '[PRIMARY]';
    if ($col['Extra'] !== '') $extra .= " [" . $col['Extra'] . "]";
    $field = str_pad($col['Field'], 30);
    $type = str_pad($col['Type'], 20);
    $null = str_pad($col['Null'], 5);
    echo "  $field $type $null $extra\n";
}
echo "\n";

// 2. Chercher colonnes abonnement existantes
echo "COLONNES ABONNEMENT EXISTANTES:\n";
echo str_repeat("-", 80) . "\n";
$abo_cols = ['abonnement_status', 'date_expiration_abo', 'subscription_tier', 'subscription_level', 'abo_type'];
foreach ($abo_cols as $col) {
    $check = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='$col'")->fetch();
    echo "  $col : " . ($check ? "OUI (existe)" : "NON") . "\n";
}
echo "\n";

// 3. Vérifier table avis
if ($pdo->query("SHOW TABLES LIKE 'avis'")->fetch()) {
    echo "TABLE AVIS:\n";
    echo str_repeat("-", 80) . "\n";
    $cols = $pdo->query("DESCRIBE avis")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']:<30} {$col['Type']:<20}\n";
    }
    echo "\n";
} else {
    echo "TABLE AVIS : NON trouvée\n\n";
}

// 4. Vérifier table commentaires
if ($pdo->query("SHOW TABLES LIKE 'commentaires'")->fetch()) {
    echo "TABLE COMMENTAIRES (Avis/Reviews):\n";
    echo str_repeat("-", 80) . "\n";
    $cols = $pdo->query("DESCRIBE commentaires")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']:<30} {$col['Type']:<20}\n";
    }
    echo "\n";
} else {
    echo "TABLE COMMENTAIRES : NON trouvée\n\n";
}

// 5. Vérifier table services
if ($pdo->query("SHOW TABLES LIKE 'services'")->fetch()) {
    echo "TABLE SERVICES:\n";
    echo str_repeat("-", 80) . "\n";
    $cols = $pdo->query("DESCRIBE services")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']:<30} {$col['Type']:<20}\n";
    }
    echo "\n";
} else {
    echo "TABLE SERVICES : NON trouvée\n\n";
}

// 6. Vérifier table rendez_vous
if ($pdo->query("SHOW TABLES LIKE 'rendez_vous'")->fetch()) {
    echo "TABLE RENDEZ_VOUS:\n";
    echo str_repeat("-", 80) . "\n";
    $cols = $pdo->query("DESCRIBE rendez_vous")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']:<30} {$col['Type']:<20}\n";
    }
    echo "\n";
} else {
    echo "TABLE RENDEZ_VOUS : NON trouvée\n\n";
}

// 7. Compter coiffeurs
$nb_prestataires = $pdo->query("SELECT COUNT(*) FROM users WHERE role='prestataire'")->fetchColumn();
$nb_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn();

echo "STATISTIQUES:\n";
echo str_repeat("-", 80) . "\n";
echo "  Prestataires (coiffeurs) : $nb_prestataires\n";
echo "  Clients : $nb_clients\n";
echo "\n";

// 8. Vérifier colonne note
$check_note = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='note_moyenne'")->fetch();
echo "NOTATION EXISTANTE : " . ($check_note ? "OUI (note_moyenne)" : "NON") . "\n\n";

// 9. Lister ALL tables
echo "TOUTES LES TABLES (" . count($tables) . "):\n";
echo str_repeat("-", 80) . "\n";
$all_tables = $pdo->query("SHOW TABLES FROM domizi")->fetchAll(PDO::FETCH_COLUMN);
foreach ($all_tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    echo "  $t ({$count} rows)\n";
}
echo "\n";

echo "================================================================================\n";
echo "RECOMMANDATIONS:\n";
echo "================================================================================\n";
echo "\n";

// Recommandations basées sur l'analyse
echo "1. COLONNES ABONNEMENT MANQUANTES À AJOUTER À 'users':\n";
echo "   - subscription_tier (ENUM: 'free', 'basique', 'premium') DEFAULT 'free'\n";
echo "   - subscription_start_date DATETIME\n";
echo "   - subscription_end_date DATETIME\n";
echo "   - subscription_status (ENUM: 'active', 'expired', 'cancelled') DEFAULT 'expired'\n";
echo "\n";

echo "2. NOUVELLE TABLE À CRÉER (subscription_transactions):\n";
echo "   - id (PRIMARY KEY)\n";
echo "   - user_id (FK)\n";
echo "   - tier (free/basique/premium)\n";
echo "   - montant (0 pour free, 1500 ou 3000)\n";
echo "   - transaction_date\n";
echo "   - statut (completed, pending, failed)\n";
echo "\n";

echo "3. TABLE POUR SCORING (opcional):\n";
echo "   - user_id (FK)\n";
echo "   - visibility_score (DECIMAL)\n";
echo "   - last_calculated (DATETIME)\n";
echo "\n";

echo "================================================================================\n";
?>
