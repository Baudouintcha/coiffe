<?php
/**
 * Exporte la structure complète de cft en SQL
 * Pour voir toutes les tables et colonnes
 */

try {
    $pdo = new PDO("mysql:host=localhost;dbname=cft;charset=utf8", 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<pre>";
    echo "-- ====================================\n";
    echo "-- STRUCTURE COMPLÈTE DE CFT\n";
    echo "-- ====================================\n\n";
    
    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $sql = $createStmt['Create Table'];
        echo $sql . ";\n\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
