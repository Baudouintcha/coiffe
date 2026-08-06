<?php
require_once __DIR__ . '/security/config.php';

echo "<h1>Inspection des tables quartier/quartiers</h1>";

// 1. Vérifier quelles tables existent
echo "<h2>Tables disponibles :</h2>";
$tables = $pdo->query("SHOW TABLES LIKE 'quartier%'")->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($tables);
echo "</pre>";

// 2. Structure de quartiers (la nouvelle)
echo "<h2>Structure de la table 'quartiers' :</h2>";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM quartiers")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($cols);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}

// 3. Exemples de données
echo "<h2>Exemples de données de 'quartiers' :</h2>";
try {
    $data = $pdo->query("SELECT * FROM quartiers LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}

// 4. Test de requête
echo "<h2>Test de requête SELECT sur quartiers :</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM quartiers WHERE id_ville = 1 LIMIT 3");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
