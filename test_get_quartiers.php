<?php
require_once __DIR__ . '/security/config.php';

echo "<h2>Test get_quartiers.php</h2>";

// Test avec id_ville = 1 (Cotonou)
$id_ville = 1;

echo "<p><strong>Test avec id_ville = $id_ville</strong></p>";

// 1. Vérifier si la table quartier existe
echo "<h3>1. Vérifier les tables disponibles:</h3>";
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre>";
    print_r($tables);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}

// 2. Vérifier la structure de la table quartier
echo "<h3>2. Structure de la table 'quartier':</h3>";
try {
    $cols = $pdo->query("SHOW COLUMNS FROM quartier")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($cols);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}

// 3. Tester la requête SQL directement
echo "<h3>3. Résultat de la requête SQL:</h3>";
try {
    $stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartier WHERE id_ville = ? ORDER BY nom_quartier ASC");
    $stmt->execute([$id_ville]);
    $quartiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($quartiers);
    echo "</pre>";
    echo "<p>Nombre de résultats: " . count($quartiers) . "</p>";
} catch (Exception $e) {
    echo "Erreur SQL: " . $e->getMessage();
}

// 4. Tester le JSON
echo "<h3>4. JSON encodé:</h3>";
try {
    $stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartier WHERE id_ville = ? ORDER BY nom_quartier ASC");
    $stmt->execute([$id_ville]);
    $quartiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $json = json_encode($quartiers);
    echo "<pre>";
    echo $json;
    echo "</pre>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}

// 5. Lister toutes les colonnes de quartier pour voir les vrais noms
echo "<h3>5. Tous les quartiers (pour vérifier la structure complète):</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM quartier LIMIT 5");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($all);
    echo "</pre>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
