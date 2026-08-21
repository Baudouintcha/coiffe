<?php
/**
 * Diagnostic du Schéma Base de Données
 * Trouve les colonnes mal nommées
 */

set_time_limit(60);
require_once __DIR__ . '/security/config.php';

echo "🔍 DIAGNOSTIC SCHÉMA BASE DE DONNÉES\n";
echo "====================================\n\n";

// Récupérer toutes les tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "📊 Tables trouvées: " . count($tables) . "\n\n";

$typos_found = [];

// Vérifier chaque table
foreach ($tables as $table) {
    echo "📋 Table: $table\n";
    
    $columns = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        $col_name = $col['Field'];
        echo "   ├─ $col_name\n";
        
        // Chercher les typos
        if (stripos($col_name, 'prestantaire') !== false) {
            $typos_found[] = [
                'table' => $table,
                'column' => $col_name,
                'error' => 'Typo: prestantaire_id (deux a)'
            ];
            echo "   │  ❌ TYPO DÉTECTÉ!\n";
        }
        
        if (stripos($col_name, 'prestataire_id') !== false && $table !== 'profils_prestataires') {
            echo "   │  ✓ prestataire_id (correct)\n";
        }
    }
    echo "\n";
}

echo "\n====================================\n";
echo "📊 RÉSUMÉ\n";
echo "====================================\n\n";

if (empty($typos_found)) {
    echo "✅ Aucune typo trouvée dans la base de données\n";
    echo "\nLes colonnes principales sont:\n";
    echo "   • commentaires: id_prestataire\n";
    echo "   • avis: prestataire_id\n";
    echo "   • services: id_prestataire\n";
    echo "   • disponibilites: prestataire_id\n";
    echo "   • rendez_vous: prestataire_id\n";
} else {
    echo "❌ " . count($typos_found) . " typo(s) détecté(e)(s):\n\n";
    foreach ($typos_found as $typo) {
        echo "   Fichier: {$typo['table']}\n";
        echo "   Colonne: {$typo['column']}\n";
        echo "   Problème: {$typo['error']}\n\n";
    }
}

echo "✅ Diagnostic terminé\n";
