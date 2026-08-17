<?php
/**
 * Script de migration : cft → domizi_v2
 * 
 * Ce script :
 * 1. Crée la base domizi_v2 si elle n'existe pas
 * 2. Importe le schéma depuis domizi_v2.sql
 * 3. Migre toutes les données de cft vers domizi_v2
 * 4. Conserve les données de métiers/domaines déjà dans domizi_v2.sql
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Migration cft → domizi_v2</h1>";
echo "<pre>";

// Connexion à MySQL sans base spécifique
try {
    $pdo_root = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✓ Connexion MySQL établie\n";
} catch (PDOException $e) {
    die("✗ Erreur connexion MySQL : " . $e->getMessage());
}

// Étape 1 : Créer la base domizi_v2 si elle n'existe pas
try {
    $pdo_root->exec("CREATE DATABASE IF NOT EXISTS `domizi_v2` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "✓ Base domizi_v2 créée/vérifiée\n";
} catch (PDOException $e) {
    die("✗ Erreur création base : " . $e->getMessage());
}

// Étape 2 : Importer le schéma depuis domizi_v2.sql
try {
    $sqlFile = __DIR__ . '/security/domizi_v2.sql';
    if (!file_exists($sqlFile)) {
        die("✗ Fichier domizi_v2.sql introuvable\n");
    }
    
    echo "✓ Lecture du fichier SQL...\n";
    $sql = file_get_contents($sqlFile);
    
    // Connexion à la base domizi_v2
    $pdo_new = new PDO("mysql:host=localhost;dbname=domizi_v2;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Désactiver les contraintes temporairement
    $pdo_new->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Exécuter le fichier SQL
    $pdo_new->exec($sql);
    
    // Réactiver les contraintes
    $pdo_new->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "✓ Schéma domizi_v2 importé avec succès (métiers et domaines inclus)\n";
} catch (PDOException $e) {
    die("✗ Erreur import schéma : " . $e->getMessage());
}

// Étape 3 : Connexion à la base source (cft)
try {
    $pdo_old = new PDO("mysql:host=localhost;dbname=cft;charset=utf8", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connexion à la base cft établie\n";
} catch (PDOException $e) {
    die("✗ Erreur connexion cft : " . $e->getMessage() . "\n(Si la base cft n'existe pas, la migration est terminée)\n");
}

// Étape 4 : Vérifier les tables existantes dans cft
$tables = $pdo_old->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "✓ Tables trouvées dans cft : " . count($tables) . "\n";

// Étape 5 : Migrer les données table par table
$tablesToMigrate = [
    'pays',
    'villes',
    'quartiers',
    'users',
    'prestations',
    'zones_coiffeur',
    'disponibilites',
    'rendezvous',
    'commentaires',
    'notifications',
    'transactions',
    'messages',
    'abonnements',
    'categories_styles',
    'propositions_prix'
];

$pdo_new->exec("SET FOREIGN_KEY_CHECKS = 0");

foreach ($tablesToMigrate as $table) {
    if (!in_array($table, $tables)) {
        echo "⊘ Table $table absente dans cft, ignorée\n";
        continue;
    }
    
    try {
        // Vider la table destination (sauf domaines et metiers qui viennent du SQL)
        if (!in_array($table, ['domaines', 'metiers'])) {
            $pdo_new->exec("TRUNCATE TABLE `$table`");
        }
        
        // Récupérer les données
        $data = $pdo_old->query("SELECT * FROM `$table`")->fetchAll();
        
        if (empty($data)) {
            echo "⊘ Table $table vide, ignorée\n";
            continue;
        }
        
        // Préparer l'insertion
        $columns = array_keys($data[0]);
        $placeholders = array_fill(0, count($columns), '?');
        
        $insertSql = sprintf(
            "INSERT INTO `$table` (`%s`) VALUES (%s)",
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );
        
        $stmt = $pdo_new->prepare($insertSql);
        
        $count = 0;
        foreach ($data as $row) {
            $stmt->execute(array_values($row));
            $count++;
        }
        
        echo "✓ Table $table migrée : $count lignes\n";
        
    } catch (PDOException $e) {
        echo "✗ Erreur migration $table : " . $e->getMessage() . "\n";
    }
}

$pdo_new->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\n";
echo "========================================\n";
echo "MIGRATION TERMINÉE AVEC SUCCÈS !\n";
echo "========================================\n";
echo "\n";
echo "PROCHAINES ÉTAPES :\n";
echo "1. Vérifier le fichier .env : DB_NAME=domizi\n";
echo "2. Tester l'application sur http://localhost/coiffons/\n";
echo "3. Si tout fonctionne, vous pouvez supprimer la base 'cft'\n";
echo "\n";
echo "</pre>";
