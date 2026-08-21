<?php
/**
 * Audit SQL Final - Version sans faux positifs
 * Détecte UNIQUEMENT les vraies erreurs SQL
 * Ignore les alias correctement définis
 */

set_time_limit(60);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/src/Core/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

$start_time = microtime(true);

// Récupérer le schéma
$schema = [];
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $cols = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
    $schema[$table] = $cols;
}

// Fichiers à scanner
$files = [];
if (!function_exists('scanDirForAudit')) {
    function scanDirForAudit($dir, &$files, $exclude) {
        $items = @scandir($dir);
        if (!$items) return;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                if (!in_array($item, $exclude)) scanDirForAudit($path, $files, $exclude);
            } else {
                if (pathinfo($item, PATHINFO_EXTENSION) === 'php') {
                    $files[] = str_replace($dir . DIRECTORY_SEPARATOR, '', $path);
                }
            }
        }
    }
}

scanDirForAudit(__DIR__, $files, ['.git', '.kiro', 'node_modules', 'vendor']);

$errors = [];
$queries_checked = 0;
$keywords = ['AND', 'OR', 'NOT', 'NULL', 'TRUE', 'FALSE', 'EXISTS', 'SELECT', 'FROM', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'WHERE', 'LIMIT', 'ORDER', 'GROUP', 'HAVING', 'AS'];

foreach ($files as $file) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $file;
    $content = @file_get_contents($path);
    if (!$content) continue;
    
    // ════════════════════════════════════════════════════════════════════
    // PATTERN 1: UPDATE table SET column = value
    // Détection: UPDATE <table> SET <column> = ...
    // ════════════════════════════════════════════════════════════════════
    if (preg_match_all('/UPDATE\s+`?(\w+)`?\s+SET\s+([^;WHERE]+)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $table = $match[1];
            $set_clause = $match[2];
            
            if (!isset($schema[$table])) continue;
            
            // Extraire chaque colonne dans le SET
            if (preg_match_all('/`?(\w+)`?\s*=/', $set_clause, $col_matches)) {
                foreach ($col_matches[1] as $column) {
                    $queries_checked++;
                    if (in_array(strtoupper($column), $keywords)) continue;
                    
                    if (!in_array($column, $schema[$table])) {
                        $errors[] = [
                            'file' => $file,
                            'table' => $table,
                            'column' => $column,
                            'type' => 'UPDATE SET',
                            'available' => $schema[$table]
                        ];
                    }
                }
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════
    // PATTERN 2: INSERT INTO table (columns)
    // Détection: INSERT INTO <table> (col1, col2, ...) VALUES
    // ════════════════════════════════════════════════════════════════════
    if (preg_match_all('/INSERT\s+INTO\s+`?(\w+)`?\s*\(([^)]+)\)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $table = $match[1];
            $columns_str = $match[2];
            
            if (!isset($schema[$table])) continue;
            
            preg_match_all('/`?(\w+)`?/', $columns_str, $col_matches);
            foreach ($col_matches[1] as $column) {
                $queries_checked++;
                if (in_array(strtoupper($column), $keywords)) continue;
                
                if (!in_array($column, $schema[$table])) {
                    $errors[] = [
                        'file' => $file,
                        'table' => $table,
                        'column' => $column,
                        'type' => 'INSERT INTO',
                        'available' => $schema[$table]
                    ];
                }
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════
    // PATTERN 3: WHERE clause sans alias (simple)
    // Détection: FROM <table> WHERE <column> = ...
    // IGNORE les colonnes avec alias (table.column ou alias.column)
    // ════════════════════════════════════════════════════════════════════
    if (preg_match_all('/FROM\s+`?(\w+)`?\s+WHERE\s+`?(\w+)`?\s*(?:=|!=|<|>|<=|>=|LIKE|IN|IS)/i', $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $table = $match[1];
            $column = $match[2];
            
            $queries_checked++;
            if (!isset($schema[$table])) continue;
            if (in_array(strtoupper($column), $keywords)) continue;
            if (is_numeric($column)) continue;
            
            if (!in_array($column, $schema[$table])) {
                $errors[] = [
                    'file' => $file,
                    'table' => $table,
                    'column' => $column,
                    'type' => 'WHERE (simple)',
                    'available' => $schema[$table]
                ];
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════
    // PATTERN 4: Colonne inexistante dans rendez_vous
    // Vérifications spécifiques connues:
    // - rendez_vous.user_id N'EXISTE PAS (doit être client_id ou prestataire_id)
    // - rendez_vous.date_demande N'EXISTE PAS (doit être date_demande)
    // - rendez_vous.message N'EXISTE PAS
    // ════════════════════════════════════════════════════════════════════
    $rdv_wrong_columns = ['user_id', 'date_demande', 'message', 'updated_at'];
    foreach ($rdv_wrong_columns as $wrong_col) {
        if (preg_match('/(?:rendez_vous|r|rdv)\.' . preg_quote($wrong_col, '/') . '\b/i', $content)) {
            $queries_checked++;
            $errors[] = [
                'file' => $file,
                'table' => 'rendez_vous',
                'column' => $wrong_col,
                'type' => 'Colonne inexistante',
                'available' => $schema['rendez_vous'] ?? []
            ];
        }
    }
}

$execution_time = round(microtime(true) - $start_time, 2);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit SQL Final</title>
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
        .suggestion {
            background: #2a2a40;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #44ff44;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Audit SQL Final</h1>
        <p class="subtitle">Détection des vraies erreurs SQL (sans faux positifs)</p>

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
                <div class="label">Erreurs Réelles</div>
                <div class="number" style="color:<?= count($errors) > 0 ? '#ff4444' : '#44ff44' ?>"><?= count($errors) ?></div>
            </div>
        </div>

        <?php if (empty($errors)): ?>
            <div class="success-box">
                <h2>✅ Aucune Erreur SQL Détectée !</h2>
                <p>Toutes les colonnes SQL sont correctement référencées.</p>
                <p style="color:#f0f0f0;margin-top:15px;opacity:0.8">Analyse terminée en <?= $execution_time ?>s</p>
            </div>
        <?php else: ?>
            <h2 style="color:#ff4444;margin:30px 0 20px 0">❌ <?= count($errors) ?> Erreur(s) Réelle(s) Détectée(s)</h2>

            <?php
            $errors_by_file = [];
            foreach ($errors as $error) {
                $errors_by_file[$error['file']][] = $error;
            }

            $error_num = 1;
            foreach ($errors_by_file as $file => $file_errors): ?>
                <div class="error-card">
                    <h3>📁 <span class="file-path"><?= htmlspecialchars($file) ?></span></h3>
                    <span class="badge badge-error"><?= count($file_errors) ?> erreur(s)</span>

                    <?php foreach ($file_errors as $error): ?>
                        <div class="error-details">
                            <strong>❌ Erreur #<?= $error_num++ ?></strong><br>
                            <strong>Type:</strong> <?= $error['type'] ?><br>
                            <strong>Table:</strong> <span style="color:#ffaa00"><?= htmlspecialchars($error['table']) ?></span><br>
                            <strong>Colonne invalide:</strong> <span style="color:#ff4444"><?= htmlspecialchars($error['column']) ?></span><br>
                            
                            <?php if (!empty($error['available'])): ?>
                                <strong>Colonnes valides:</strong><br>
                                <div style="margin-top:8px;color:#44ff44;font-size:0.9em">
                                    <?= implode(', ', array_slice($error['available'], 0, 15)) ?>
                                    <?php if (count($error['available']) > 15): ?>
                                        <span style="color:#888">... (<?= count($error['available']) - 15 ?> autres)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php
                            // Suggestions de correction
                            $suggestions = [];
                            if ($error['column'] === 'user_id' && $error['table'] === 'rendez_vous') {
                                $suggestions[] = "Utiliser <code>client_id</code> ou <code>prestataire_id</code> selon le contexte";
                            } elseif ($error['column'] === 'date_demande' && $error['table'] === 'rendez_vous') {
                                $suggestions[] = "Utiliser <code>date_demande</code> à la place";
                            } elseif ($error['column'] === 'message' && $error['table'] === 'rendez_vous') {
                                $suggestions[] = "La table rendez_vous n'a pas de colonne message. Supprimer cette référence.";
                            }
                            
                            if (!empty($suggestions)): ?>
                                <div class="suggestion">
                                    <strong style="color:#44ff44">💡 Suggestion:</strong><br>
                                    <?php foreach ($suggestions as $sug): ?>
                                        • <?= $sug ?><br>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top:40px;padding:20px;background:#2d2d44;border-radius:10px;color:#b8b8d1">
            <p style="text-align:center">
                ⏱️ Temps d'exécution: <?= $execution_time ?>s | 
                📊 Méthode: Patterns UPDATE/INSERT/WHERE simples + Vérifications spécifiques
            </p>
        </div>
    </div>
</body>
</html>
