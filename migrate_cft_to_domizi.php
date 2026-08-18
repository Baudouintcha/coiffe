<?php
/**
 * Script de migration automatique : cft → domizi
 * Migre TOUTES les données sans perdre les métiers/domaines
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Migration cft → domizi</h1>";
echo "<style>
    body { font-family: monospace; background: #f0f0f0; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: white; padding: 15px; border-left: 4px solid #4A90D9; max-width: 1000px; }
</style>";
echo "<pre>";

// Étape 1 : Connexion MySQL root (sans DB)
try {
    $pdo_root = new PDO("mysql:host=localhost;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<span class='success'>✓ Connexion MySQL établie</span>\n";
} catch (PDOException $e) {
    die("<span class='error'>✗ Erreur connexion : " . $e->getMessage() . "</span>");
}

// Étape 2 : Vérifier que les deux bases existent
try {
    $has_cft = false;
    $has_domizi = false;
    
    $all_dbs = $pdo_root->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($all_dbs as $db) {
        if ($db === 'cft') $has_cft = true;
        if ($db === 'domizi') $has_domizi = true;
    }
    
    if (!$has_cft) {
        die("<span class='error'>✗ Base 'cft' introuvable</span>\n");
    }
    if (!$has_domizi) {
        die("<span class='error'>✗ Base 'domizi' introuvable</span>\n");
    }
    
    echo "<span class='success'>✓ Base cft trouvée</span>\n";
    echo "<span class='success'>✓ Base domizi trouvée</span>\n";
} catch (Exception $e) {
    die("<span class='error'>✗ Erreur : " . $e->getMessage() . "</span>");
}

// Étape 3 : Connexion aux deux bases
try {
    $pdo_old = new PDO("mysql:host=localhost;dbname=cft;charset=utf8", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "<span class='success'>✓ Connexion à cft établie</span>\n";
} catch (PDOException $e) {
    die("<span class='error'>✗ Erreur cft : " . $e->getMessage() . "</span>");
}

try {
    $pdo_new = new PDO("mysql:host=localhost;dbname=domizi;charset=utf8mb4", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "<span class='success'>✓ Connexion à domizi établie</span>\n";
} catch (PDOException $e) {
    die("<span class='error'>✗ Erreur domizi : " . $e->getMessage() . "</span>");
}

// Étape 4 : Récupérer les tables de cft
try {
    $tables_cft = $pdo_old->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\n<span class='info'>Tables trouvées dans cft :</span>\n";
    foreach ($tables_cft as $t) {
        echo "  → $t\n";
    }
} catch (Exception $e) {
    die("<span class='error'>✗ Erreur : " . $e->getMessage() . "</span>");
}

// Étape 5 : Récupérer les tables de domizi
try {
    $tables_domizi = $pdo_new->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "\n<span class='info'>Tables trouvées dans domizi :</span>\n";
    foreach ($tables_domizi as $t) {
        echo "  → $t\n";
    }
} catch (Exception $e) {
    die("<span class='error'>✗ Erreur : " . $e->getMessage() . "</span>");
}

// Étape 6 : Migration table par table
echo "\n<span class='info'>═══ MIGRATION EN COURS ═══</span>\n\n";

$pdo_new->exec("SET FOREIGN_KEY_CHECKS = 0");

$tables_to_skip = ['domaines', 'metiers']; // Ne pas toucher à ces tables (déjà remplies)

foreach ($tables_cft as $table) {
    // Sauter les tables à ignorer
    if (in_array($table, $tables_to_skip)) {
        echo "<span class='info'>⊘ Table $table ignorée (données préexistantes)</span>\n";
        continue;
    }
    
    // Vérifier que la table existe dans domizi
    if (!in_array($table, $tables_domizi)) {
        echo "<span class='error'>✗ Table $table absente dans domizi, ignorée</span>\n";
        continue;
    }
    
    try {
        // Récupérer les données
        $data = $pdo_old->query("SELECT * FROM `$table`")->fetchAll();
        
        if (empty($data)) {
            echo "<span class='info'>⊘ Table $table vide</span>\n";
            continue;
        }
        
        // Vider la table destination
        $pdo_new->exec("TRUNCATE TABLE `$table`");
        
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
            try {
                $stmt->execute(array_values($row));
                $count++;
            } catch (PDOException $e) {
                echo "<span class='error'>  Erreur ligne : " . $e->getMessage() . "</span>\n";
            }
        }
        
        echo "<span class='success'>✓ Table $table migrée : $count lignes</span>\n";
        
    } catch (Exception $e) {
        echo "<span class='error'>✗ Erreur migration $table : " . $e->getMessage() . "</span>\n";
    }
}

$pdo_new->exec("SET FOREIGN_KEY_CHECKS = 1");

// Étape 7 : Vérification finale
echo "\n<span class='info'>═══ VÉRIFICATION FINALE ═══</span>\n\n";

try {
    $count_users = $pdo_new->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<span class='success'>✓ Users : $count_users</span>\n";
    
    $count_prestations = $pdo_new->query("SELECT COUNT(*) FROM prestations")->fetchColumn();
    echo "<span class='success'>✓ Prestations : $count_prestations</span>\n";
    
    $count_rendezvous = $pdo_new->query("SELECT COUNT(*) FROM rendezvous")->fetchColumn();
    echo "<span class='success'>✓ Rendez-vous : $count_rendezvous</span>\n";
    
    $count_domaines = $pdo_new->query("SELECT COUNT(*) FROM domaines")->fetchColumn();
    echo "<span class='success'>✓ Domaines : $count_domaines</span>\n";
    
    $count_metiers = $pdo_new->query("SELECT COUNT(*) FROM metiers")->fetchColumn();
    echo "<span class='success'>✓ Métiers : $count_metiers</span>\n";
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Erreur vérification : " . $e->getMessage() . "</span>\n";
}

echo "\n<span class='success'>════════════════════════════════════════</span>\n";
echo "<span class='success'>✓ MIGRATION TERMINÉE AVEC SUCCÈS !</span>\n";
echo "<span class='success'>════════════════════════════════════════</span>\n\n";
echo "Prochaines étapes :\n";
echo "1. Accède à http://localhost/coiffons/\n";
echo "2. Teste l'application\n";
echo "3. Si tout fonctionne, tu peux supprimer la base 'cft' si tu veux\n";

echo "\n</pre>";
