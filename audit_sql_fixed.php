<?php
/**
 * Audit SQL V3 - Version optimisée sans faux positifs
 * Détecte uniquement les vraies erreurs de colonnes SQL
 */

set_time_limit(60);
ini_set('memory_limit', '512M');

// Connexion BDD
require_once __DIR__ . '/includes/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

$start_time = microtime(true);

// Schéma de la base
$schema = [];
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
    $schema[$table] = $cols;
}

// Fichiers à scanner
$extensions = ['php'];
$exclude_dirs = ['.git', '.kiro', 'node_modules', 'vendor'];
$files = [];

function scanDirectory($dir, &$files, $exclude_dirs, $extensions) {
    $items = @scandir($dir);
    if (!$items) return;
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            if (!in_array($item, $exclude_dirs)) {
                scanDirectory($path, $files, $exclude_dirs, $extensions);
            }
        } else {
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            if (in_array($ext, $extensions)) {
                $files[] = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
            }
        }
    }
}

scanDirectory(__DIR__, $files, $exclude_dirs, $extensions);

// Analyse
$errors = [];
$queries_checked = 0;
$keywords = ['AND', 'OR', 'NOT', 'NULL', 'TRUE', 'FALSE', 'EXISTS', 'SELECT', 'FROM', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'WHERE', 'LIMIT', 'ORDER', 'GROUP', 'HAVING'];

foreach ($files as $file) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $file;
    $content = @file_get_contents($path);
    if (!$content) continue;
    
    // ═══════════════════════════════════════════════════════
    // PATTERN 1: UPDATE table SET column = value
    // ═══════════════════════════════════════════════════════
    if (preg_match_all('/UPDATE\s+`?(\w+)`?\s+SET\s+([^;]+)/i', $content, $update_matches, PREG_SET_ORDER)) {
        foreach ($update_matches as $match) {
            $table = $match[1];
            $set_clause = $match[2];
            
            // Extraire toutes les colonnes du SET (col1=val1, col2=val2, ...)
            if (preg_match_all('/`?(\w+)`?\s*=/', $set_clause, $col_matches)) {
                foreach ($col_matches[1] as $column) {
                    $queries_checked++;
                    if (in_array(strtoupper($column), $keywords)) continue;
                    
                    if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                        $errors[] = [
                            'file' => $file,
                            'table' => $table,
                            'column' => $column,
                            'type' => 'UPDATE SET',
                            'available' => $schema[$table],
                            'line' => substr_count(substr($content, 0, strpos($content, $match[0])), "\n") + 1
                        ];
                    }
                }
            }
        }
    }
    
    // ═══════════════════════════════════════════════════════
    // PATTERN 2: INSERT INTO table (columns) VALUES
    // ═══════════════════════════════════════════════════════
    if (preg_match_all('/INSERT\s+INTO\s+`?(\w+)`?\s*\(([^)]+)\)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $table = $match[1];
            $columns_str = $match[2];
            
            preg_match_all('/`?(\w+)`?/', $columns_str, $col_matches);
            foreach ($col_matches[1] as $column) {
                $queries_checked++;
                if (in_array(strtoupper($column), $keywords)) continue;
                
                if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                    $errors[] = [
                        'file' => $file,
                        'table' => $table,
                        'column' => $column,
                        'type' => 'INSERT',
                        'available' => $schema[$table],
                        'line' => substr_count(substr($content, 0, strpos($content, $match[0])), "\n") + 1
                    ];
                }
            }
        }
    }
    
    // ═══════════════════════════════════════════════════════
    // PATTERN 3: WHERE avec colonnes non-préfixées
    // FROM table WHERE column = value (sans alias)
    // ═══════════════════════════════════════════════════════
    if (preg_match_all('/FROM\s+`?(\w+)`?\s+(?:WHERE|SET)[^;\'"]*?`?(\w+)`?\s*(?:=|!=|<|>|<=|>=|LIKE|IN|IS)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $table = $match[1];
            $column = $match[2];
            
            $queries_checked++;
            if (in_array(strtoupper($column), $keywords)) continue;
            if (strpos($column, '.') !== false) continue; // Ignore aliased columns
            if (is_numeric($column)) continue; // Ignore numeric values
            
            if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                $errors[] = [
                    'file' => $file,
                    'table' => $table,
                    'column' => $column,
                    'type' => 'WHERE',
                    'available' => $schema[$table],
                    'line' => substr_count(substr($content, 0, strpos($content, $match[0])), "\n") + 1
                ];
            }
        }
    }
    
    // ═══════════════════════════════════════════════════════
    // PATTERN 4: SELECT avec colonnes spécifiques
    // SELECT column1, column2 FROM table
    // ═══════════════════════════════════════════════════════
    if (preg_match_all('/SELECT\s+([^;]+?)\s+FROM\s+`?(\w+)`?(?:\s+(?:WHERE|ORDER|LIMIT|GROUP|$))/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $select_clause = $match[1];
            $table = $match[2];
            
            // Ignorer SELECT * et les fonctions d'agrégation
            if (strpos($select_clause, '*') !== false) continue;
            if (preg_match('/COUNT|SUM|AVG|MAX|MIN|CONCAT/i', $select_clause)) continue;
            
            // Extraire les colonnes (ignorer les alias AS)
            $columns = preg_split('/,\s*/', $select_clause);
            foreach ($columns as $col) {
                $col = trim($col);
                
                // Extraire le nom de colonne (avant AS si présent)
                if (preg_match('/^`?(\w+)`?(?:\s+AS)?/i', $col, $col_match)) {
                    $column = $col_match[1];
                    $queries_checked++;
                    
                    if (in_array(strtoupper($column), $keywords)) continue;
                    if (strpos($column, '.') !== false) continue; // Ignore aliased columns
                    
                    if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                        $errors[] = [
                            'file' => $file,
                            'table' => $table,
                            'column' => $column,
                            'type' => 'SELECT',
                            'available' => $schema[$table],
                            'line' => substr_count(substr($content, 0, strpos($content, $match[0])), "\n") + 1
                        ];
                    }
                }
            }
        }
    }
    
    // ═══════════════════════════════════════════════════════
    // PATTERN 5: ORDER BY et GROUP BY
    // ═══════════════════════════════════════════════════════
    if (preg_match_all('/(?:ORDER|GROUP)\s+BY\s+`?(\w+)\.?(\w+)?`?/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $queries_checked++;
            
            // Si format: table.column
            if (!empty($match[2])) {
                $table = $match[1];
                $column = $match[2];
            } else {
                // Sinon, essayer de trouver la table dans le contexte
                $column = $match[1];
                
                // Chercher FROM table avant ORDER BY
                $before_context = substr($content, 0, strpos($content, $match[0]));
                if (preg_match('/FROM\s+`?(\w+)`?[^;]*$/i', $before_context, $context_match)) {
                    $table = $context_match[1];
                } else {
                    continue; // Impossible de déterminer la table
                }
            }
            
            if (in_array(strtoupper($column), $keywords)) continue;
            
            if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                $errors[] = [
                    'file' => $file,
                    'table' => $table,
                    'column' => $column,
                    'type' => 'ORDER/GROUP BY',
                    'available' => $schema[$table],
                    'line' => substr_count(substr($content, 0, strpos($content, $match[0])), "\n") + 1
                ];
            }
        }
    }
}

$execution_time = round(microtime(true) - $start_time, 2);

// ═══════════════════════════════════════════════════════
// AFFICHAGE HTML
// ═══════════════════════════════════════════════════════
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit SQL V3 - Optimisé</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #1a1a2e;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            padding: 40px;
        }
        h1 {
            color: #fff;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            color: #b8b8d1;
            text-align: center;
            margin-bottom: 40px;
            font-size: 1.1em;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: linear-gradient(135deg, #2d2d44 0%, #1f1f30 100%);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #3a3a52;
        }
        .stat-card .label {
            color: #b8b8d1;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-card .number {
            color: #fff;
            font-size: 2.5em;
            font-weight: bold;
        }
        .success-box {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            color: white;
            margin-top: 30px;
        }
        .success-box h2 {
            font-size: 2em;
            margin-bottom: 15px;
        }
        .error-card {
            background: #2d2d44;
            border: 2px solid #ff4444;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .error-card h3 {
            color: #fff;
            font-size: 1.3em;
            margin-bottom: 15px;
        }
        .file-path {
            color: #ffaa00;
            font-family: 'Courier New', monospace;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin-left: 15px;
        }
        .badge-error {
            background: #ff4444;
            color: white;
        }
        .error-details {
            background: #1f1f30;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #ff4444;
            color: #e0e0e0;
            line-height: 1.8;
        }
        .error-details strong {
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Audit SQL V3</h1>
        <p class="subtitle">Détection automatique des erreurs de colonnes SQL (version optimisée)</p>

        <div class="stats">
            <div class="stat-card">
                <div class="label">Fichiers Scannés</div>
                <div class="number"><?= count($files) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Requêtes Analysées</div>
                <div class="number"><?= $queries_checked ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Tables BDD</div>
                <div class="number"><?= count($tables) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Erreurs Détectées</div>
                <div class="number" style="color:<?= count($errors) > 0 ? '#ff4444' : '#44ff44' ?>"><?= count($errors) ?></div>
            </div>
        </div>

        <?php if (empty($errors)): ?>
            <div class="success-box">
                <h2>✅ Aucune Erreur Détectée !</h2>
                <p>Toutes les colonnes référencées dans les requêtes SQL existent dans la base de données.</p>
                <p style="color:#f0f0f0;margin-top:15px;opacity:0.8">Analyse terminée en <?= $execution_time ?>s</p>
            </div>
        <?php else: ?>
            <h2 style="color:#ff4444;margin:30px 0 20px 0">❌ <?= count($errors) ?> Erreur(s) Détectée(s)</h2>

            <?php
            // Regrouper par fichier
            $errors_by_file = [];
            foreach ($errors as $error) {
                $errors_by_file[$error['file']][] = $error;
            }

            $error_num = 1;
            foreach ($errors_by_file as $file => $file_errors): ?>
                <div class="error-card">
                    <h3>📁 Fichier: <span class="file-path"><?= htmlspecialchars($file) ?></span></h3>
                    <span class="badge badge-error"><?= count($file_errors) ?> erreur(s)</span>

                    <?php foreach ($file_errors as $error): ?>
                        <div class="error-details">
                            <strong>❌ Erreur #<?= $error_num++ ?></strong><br>
                            <strong>Type:</strong> <?= $error['type'] ?><br>
                            <strong>Table:</strong> <span style="color:#ffaa00"><?= htmlspecialchars($error['table']) ?></span><br>
                            <strong>Colonne invalide:</strong> <span style="color:#ff4444"><?= htmlspecialchars($error['column']) ?></span><br>
                            <strong>Colonnes valides:</strong><br>
                            <div style="margin-top:8px;color:#44ff44"><?= implode(', ', $error['available']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <p style="color:#b8b8d1;text-align:center;margin-top:30px;font-size:0.9em">
            ⏱️ Temps d'exécution: <?= $execution_time ?>s | 
            📊 Patterns détectés: UPDATE SET, INSERT INTO, WHERE (sans alias)
        </p>
    </div>
</body>
</html>
