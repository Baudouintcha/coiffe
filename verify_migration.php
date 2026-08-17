<?php
/**
 * Script de vérification post-migration
 * Teste que domizi_v2 est correctement configuré
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Vérification Migration domizi_v2</h1>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: white; padding: 15px; border-left: 4px solid #4A90D9; }
</style>";
echo "<pre>";

// Test 1 : Fichier .env
echo "\n═══ Test 1 : Configuration .env ═══\n";
require_once __DIR__ . '/vendor/autoload.php';

$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo "<span class='error'>✗ Fichier .env introuvable</span>\n";
} else {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
    
    $dbName = $_ENV['DB_NAME'] ?? 'non défini';
    if ($dbName === 'domizi') {
        echo "<span class='success'>✓ DB_NAME = domizi</span>\n";
    } else {
        echo "<span class='error'>✗ DB_NAME = $dbName (devrait être domizi)</span>\n";
    }
}

// Test 2 : Connexion Database::getInstance()
echo "\n═══ Test 2 : Connexion Database::getInstance() ═══\n";
try {
    use App\Core\Database;
    $pdo = Database::getInstance();
    echo "<span class='success'>✓ Connexion Database singleton OK</span>\n";
    
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<span class='success'>✓ Base active : $dbName</span>\n";
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Erreur connexion : " . $e->getMessage() . "</span>\n";
    die("\n</pre>");
}

// Test 3 : Tables métiers et domaines
echo "\n═══ Test 3 : Tables métiers/domaines ═══\n";
try {
    // Test domaines
    $stmt = $pdo->query("SELECT COUNT(*) FROM domaines");
    $countDomaines = $stmt->fetchColumn();
    
    if ($countDomaines > 0) {
        echo "<span class='success'>✓ Table domaines : $countDomaines enregistrement(s)</span>\n";
        
        $domaines = $pdo->query("SELECT id, nom, slug, actif FROM domaines")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($domaines as $d) {
            $actif = $d['actif'] ? '✓ Actif' : '⊘ Inactif';
            echo "  → {$d['nom']} (slug: {$d['slug']}) $actif\n";
        }
    } else {
        echo "<span class='warning'>⚠ Table domaines vide</span>\n";
    }
    
    // Test métiers
    $stmt = $pdo->query("SELECT COUNT(*) FROM metiers");
    $countMetiers = $stmt->fetchColumn();
    
    if ($countMetiers > 0) {
        echo "<span class='success'>✓ Table metiers : $countMetiers enregistrement(s)</span>\n";
        
        $metiers = $pdo->query("SELECT m.id, m.nom, m.slug, m.actif, d.nom as domaine_nom 
                                FROM metiers m 
                                JOIN domaines d ON m.id_domaine = d.id")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($metiers as $m) {
            $actif = $m['actif'] ? '✓ Actif' : '⊘ Inactif';
            echo "  → {$m['nom']} (domaine: {$m['domaine_nom']}) $actif\n";
        }
    } else {
        echo "<span class='warning'>⚠ Table metiers vide</span>\n";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Erreur : " . $e->getMessage() . "</span>\n";
}

// Test 4 : Tables utilisateurs
echo "\n═══ Test 4 : Données utilisateurs ═══\n";
try {
    $countUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<span class='success'>✓ Table users : $countUsers utilisateur(s)</span>\n";
    
    $countClients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
    echo "  → Clients : $countClients\n";
    
    $countCoiffeurs = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur'")->fetchColumn();
    echo "  → Coiffeurs : $countCoiffeurs\n";
    
    $countAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    echo "  → Admins : $countAdmins\n";
    
} catch (Exception $e) {
    echo "<span class='error'>✗ Erreur : " . $e->getMessage() . "</span>\n";
}

// Test 5 : Autres tables importantes
echo "\n═══ Test 5 : Autres tables ═══\n";
$tablesToCheck = [
    'prestations' => 'Prestations',
    'rendezvous' => 'Rendez-vous',
    'commentaires' => 'Commentaires',
    'villes' => 'Villes',
    'quartiers' => 'Quartiers'
];

foreach ($tablesToCheck as $table => $label) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<span class='success'>✓ Table $table : $count enregistrement(s)</span>\n";
    } catch (Exception $e) {
        echo "<span class='error'>✗ Table $table : " . $e->getMessage() . "</span>\n";
    }
}

// Test 6 : Structure des fichiers
echo "\n═══ Test 6 : Structure fichiers ═══\n";
$files = [
    'index.php' => 'Point d\'entrée Domizi',
    'app.php' => 'Routeur Coiffe Chez Toi',
    'views/domizi/home.php' => 'Vue Domizi',
    'views/home.php' => 'Vue Coiffe Chez Toi',
    'src/Core/Database.php' => 'Classe Database',
    'security/domizi_v2.sql' => 'Schéma SQL'
];

foreach ($files as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<span class='success'>✓ $file ($description)</span>\n";
    } else {
        echo "<span class='error'>✗ $file manquant</span>\n";
    }
}

// Résumé
echo "\n";
echo "════════════════════════════════════════\n";
echo "         RÉSUMÉ DE LA VÉRIFICATION      \n";
echo "════════════════════════════════════════\n";

$allGood = ($countDomaines > 0 && $countMetiers > 0 && $dbName === 'domizi_v2');

if ($allGood) {
    echo "<span class='success'>\n";
    echo "✓ TOUT EST BON !\n";
    echo "\n";
    echo "Vous pouvez maintenant :\n";
    echo "1. Accéder à http://localhost/coiffons/\n";
    echo "2. Sélectionner un domaine puis un métier\n";
    echo "3. Utiliser l'application normalement\n";
    echo "</span>\n";
} else {
    echo "<span class='error'>\n";
    echo "⚠ PROBLÈMES DÉTECTÉS\n";
    echo "\n";
    echo "Actions recommandées :\n";
    if ($dbName !== 'domizi') {
        echo "- Vérifier le fichier .env (DB_NAME=domizi)\n";
    }
    if ($countDomaines === 0 || $countMetiers === 0) {
        echo "- Exécuter migrate_to_domizi_v2.php\n";
    }
    echo "</span>\n";
}

echo "\n</pre>";
