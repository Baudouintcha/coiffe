<?php
/**
 * Script de Correction Finale - Répare TOUTES les erreurs SQL restantes
 */

set_time_limit(120);

echo "🔧 CORRECTION FINALE DES ERREURS SQL\n";
echo "====================================\n\n";

// Patterns à corriger dans TOUS les fichiers
$patterns_to_fix = [
    // Typo: prestataire_id (deux 'a') → prestataire_id
    'prestataire_id' => 'prestataire_id',
    
    // Autres typos possibles
    'prestataire_id' => 'prestataire_id',
    'heure_debut' => 'heure_debut',
    'date_demande' => 'date_demande',  // Dans rendez_vous uniquement
    'date_demande' => 'date_demande',
    'lu' => 'lu',
    'user_id' => 'user_id',
    'date_creation' => 'date_demande',
    'tentatives' => 'tentatives',
];

function scanDirForFix($dir, &$files, $exclude) {
    $items = @scandir($dir);
    if (!$items) return;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            if (!in_array($item, $exclude)) {
                scanDirForFix($path, $files, $exclude);
            }
        } else {
            if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                $files[] = $path;
            }
        }
    }
}

$all_files = [];
$exclude_dirs = ['.git', '.kiro', 'node_modules', 'vendor', 'tests'];
scanDirForFix(__DIR__, $all_files, $exclude_dirs);

echo "📂 Scanning " . count($all_files) . " fichiers PHP...\n\n";

$total_fixes = 0;
$files_modified = [];

foreach ($all_files as $file) {
    $content = @file_get_contents($file);
    if (!$content) continue;
    
    $original = $content;
    $file_fixes = 0;
    
    // Appliquer chaque correction
    foreach ($patterns_to_fix as $bad => $good) {
        // Simple string replace (no regex for safety)
        $count = 0;
        $content = str_replace($bad, $good, $content, $count);
        if ($count > 0) {
            $file_fixes += $count;
        }
    }
    
    // Si des changements ont été faits
    if ($content !== $original) {
        file_put_contents($file, $content);
        $relative_path = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);
        $files_modified[] = $relative_path;
        $total_fixes += $file_fixes;
        
        echo "✅ $relative_path: $file_fixes correction(s)\n";
    }
}

echo "\n====================================\n";
echo "📊 RÉSUMÉ FINAL\n";
echo "====================================\n\n";

if ($total_fixes > 0) {
    echo "✅ $total_fixes correction(s) appliquée(s) dans " . count($files_modified) . " fichier(s)\n\n";
    echo "📁 Fichiers corrigés:\n";
    foreach ($files_modified as $file) {
        echo "   ✓ $file\n";
    }
} else {
    echo "ℹ️  Aucune correction supplémentaire nécessaire\n";
}

echo "\n✅ Script de correction finale terminé\n";
