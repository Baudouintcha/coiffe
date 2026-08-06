<?php
require_once __DIR__ . '/security/config.php';

echo "<h1>Migration des tables</h1>";

$migrations = [
    // 1. Créer la nouvelle table quartiers avec la structure correcte
    "CREATE TABLE IF NOT EXISTS `quartiers_new` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `id_ville` INT UNSIGNED NOT NULL,
        `nom_quartier` VARCHAR(100) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `id_ville` (`id_ville`),
        CONSTRAINT `quartiers_new_ibfk_1` FOREIGN KEY (`id_ville`)
            REFERENCES `villes` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    // 2. Copier les données de l'ancienne table vers la nouvelle
    "INSERT INTO `quartiers_new` (`id`, `id_ville`, `nom_quartier`)
     SELECT `id_quartier`, `id_ville`, `nom_quartier` FROM `quartier`",
    
    // 3. Supprimer l'ancienne table
    "DROP TABLE IF EXISTS `quartier`",
    
    // 4. Renommer la nouvelle table
    "RENAME TABLE `quartiers_new` TO `quartiers`"
];

$success_count = 0;
$error_count = 0;

foreach ($migrations as $i => $sql) {
    echo "<h2>Migration " . ($i+1) . ":</h2>";
    echo "<p><code>" . htmlspecialchars(substr($sql, 0, 100)) . "...</code></p>";
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'><strong>✓ SUCCESS</strong></p>";
        $success_count++;
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>✗ ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        $error_count++;
    }
}

echo "<hr>";
echo "<h2>Résumé :</h2>";
echo "<p><strong>Succès :</strong> $success_count / " . count($migrations) . "</p>";
echo "<p><strong>Erreurs :</strong> $error_count / " . count($migrations) . "</p>";

if ($error_count === 0) {
    echo "<p style='color: green;'><strong>✓ Migration complète !</strong></p>";
    echo "<p><a href='test_get_quartiers.php'>Tester la table quartiers</a></p>";
} else {
    echo "<p style='color: red;'><strong>✗ Des erreurs se sont produites. Vérifiez manuellement.</strong></p>";
}
?>
