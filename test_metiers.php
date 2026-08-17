<?php
/**
 * test_metiers.php - Test de chargement des métiers et domaines
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

// Charge le .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use App\Core\Database;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Métiers & Domaines</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { color: #D4AF37; border-bottom: 2px solid #D4AF37; padding-bottom: 10px; }
        .success { color: green; }
        .error { color: red; }
        .item { padding: 10px; margin: 5px 0; background: #f9f9f9; border-left: 3px solid #D4AF37; }
        code { background: #eee; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>";

echo "<h1>🧪 Test Métiers & Domaines</h1>";

try {
    echo "<div class='section'>";
    echo "<h2>1️⃣ Connexion à la Base de Données</h2>";
    
    $pdo = Database::getInstance();
    echo "<div class='success'>✓ Connexion PDO établie</div>";
    echo "<p><code>" . ($_ENV['DB_NAME'] ?? 'domizi') . "</code></p>";
    
    // Test 1: Vérifier les domaines
    echo "</div><div class='section'>";
    echo "<h2>2️⃣ Domaines</h2>";
    
    $stmt = $pdo->query("SELECT * FROM domaines ORDER BY id ASC");
    $domaines = $stmt->fetchAll();
    
    if (empty($domaines)) {
        echo "<div class='error'>❌ Aucun domaine trouvé</div>";
    } else {
        echo "<div class='success'>✓ " . count($domaines) . " domaine(s) trouvé(s)</div>";
        foreach ($domaines as $d) {
            $actif = $d['actif'] ? '✓' : '✗';
            echo "<div class='item'>
                <strong>{$d['nom']}</strong> ($actif)
                <br>Slug: <code>{$d['slug']}</code>
                <br>Icone: <code>{$d['icone']}</code>
            </div>";
        }
    }
    
    // Test 2: Vérifier les métiers
    echo "</div><div class='section'>";
    echo "<h2>3️⃣ Métiers</h2>";
    
    $stmt = $pdo->query("SELECT m.*, d.nom as domaine FROM metiers m JOIN domaines d ON m.id_domaine = d.id ORDER BY m.id ASC");
    $metiers = $stmt->fetchAll();
    
    if (empty($metiers)) {
        echo "<div class='error'>❌ Aucun métier trouvé</div>";
    } else {
        echo "<div class='success'>✓ " . count($metiers) . " métier(s) trouvé(s)</div>";
        foreach ($metiers as $m) {
            $actif = $m['actif'] ? '✓' : '✗';
            echo "<div class='item'>
                <strong>{$m['nom']}</strong> ($actif)
                <br>Domaine: <strong>{$m['domaine']}</strong>
                <br>Slug: <code>{$m['slug']}</code>
                <br>Description: {$m['description']}
                <br>Icone: <code>{$m['icone']}</code>
            </div>";
        }
    }
    
    // Test 3: Test API
    echo "</div><div class='section'>";
    echo "<h2>4️⃣ Test API Métiers</h2>";
    echo "<p>Simulation de l'appel API <code>?action=api_metiers&domaine=1</code></p>";
    
    if (!empty($metiers)) {
        $id_domaine = $metiers[0]['id_domaine'];
        $stmt = $pdo->prepare("SELECT id, nom, slug, icone, description FROM metiers WHERE id_domaine = ? AND actif = 1 ORDER BY nom ASC");
        $stmt->execute([$id_domaine]);
        $api_metiers = $stmt->fetchAll();
        
        echo "<pre>";
        echo json_encode(['success' => true, 'metiers' => $api_metiers], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
    }
    
    // Test 4: Vérifier les fichiers
    echo "</div><div class='section'>";
    echo "<h2>5️⃣ Vérification des Fichiers</h2>";
    
    $files = [
        'index.php' => 'Page d\'accueil Domizi',
        'app.php' => 'Routeur Coiffe Chez Toi',
        'views/domizi/home.php' => 'Vue Domizi',
        'views/home.php' => 'Vue Coiffe Chez Toi',
        'views/coiffeur/dashboard.php' => 'Dashboard coiffeur',
        'views/client/dashboard.php' => 'Dashboard client'
    ];
    
    foreach ($files as $file => $desc) {
        $path = __DIR__ . '/' . $file;
        $exists = file_exists($path) ? '✓' : '❌';
        echo "<div class='item'>$exists <strong>$file</strong><br>$desc</div>";
    }
    
    echo "</div><div class='section' style='background: #d4f1d4;'>";
    echo "<h2>✅ Configuration OK</h2>";
    echo "<p>Tu peux maintenant :</p>";
    echo "<ol>";
    echo "<li>Aller sur <strong>/coiffons/index.php</strong> (page d'accueil Domizi)</li>";
    echo "<li>Cliquer sur un domaine pour voir ses métiers</li>";
    echo "<li>Cliquer sur \"Coiffure\" pour accéder à l'app</li>";
    echo "<li>Te connecter ou t'inscrire</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section' style='background: #f1d4d4;'>";
    echo "<h2>❌ Erreur</h2>";
    echo "<div class='error'>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>
