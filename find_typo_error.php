<?php
/**
 * Trouve où se cache l'erreur prestataire_id
 */

function scanDirForError($dir, &$files) {
    $items = @scandir($dir);
    if (!$items) return;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path) && !in_array($item, ['.git', '.kiro', 'node_modules', 'vendor'])) {
            scanDirForError($path, $files);
        } else if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
}

echo "🔎 RECHERCHE DE L'ERREUR 'prestataire_id'\n";
echo "========================================\n\n";

$all_files = [];
scanDirForError(__DIR__, $all_files);

$errors_found = [];

// Chercher toutes les variantes possibles
$search_patterns = [
    'prestataire_id',      // La typo exacte
    'prestantaire',         // Variante
    'prest antaire',        // Avec espace
];

foreach ($all_files as $file) {
    $content = @file_get_contents($file);
    if (!$content) continue;
    
    foreach ($search_patterns as $pattern) {
        if (stripos($content, $pattern) !== false) {
            // Trouver la ligne
            $lines = explode("\n", $content);
            foreach ($lines as $line_num => $line) {
                if (stripos($line, $pattern) !== false) {
                    $relative = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);
                    $errors_found[] = [
                        'file' => $relative,
                        'line' => $line_num + 1,
                        'pattern' => $pattern,
                        'content' => trim(substr($line, 0, 150))
                    ];
                }
            }
        }
    }
}

if (empty($errors_found)) {
    echo "✅ Aucune erreur 'prestataire_id' trouvée dans les fichiers !\n\n";
    echo "La typo n'est probablement pas dans le code PHP.\n";
    echo "Elle vient peut-être du navigateur ou d'une requête mal formée.\n";
} else {
    echo "❌ " . count($errors_found) . " occurrence(s) trouvée(s) :\n\n";
    foreach ($errors_found as $err) {
        echo "📁 {$err['file']} (ligne {$err['line']})\n";
        echo "   ❌ Motif: {$err['pattern']}\n";
        echo "   Code: {$err['content']}...\n\n";
    }
}

echo "========================================\n";
echo "✅ Recherche terminée\n";
