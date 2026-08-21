<?php
/**
 * 🔍 AUDIT SQL AUTOMATIQUE V2 - DÉTECTION D'ERREURS DE COLONNES
 * Version améliorée avec détection intelligente du contexte
 * 
 * Ce script scanne tous les fichiers PHP et détecte:
 * - Colonnes inexistantes dans les requêtes SQL
 * - Analyse intelligente du contexte (JOIN, sous-requêtes)
 * - Élimination des faux positifs
 * 
 * Usage: http://localhost/coiffons/audit_sql_columns.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600);

require_once __DIR__ . '/vendor/autoload.php';
use App\Core\Database;

// Style CSS intégré
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit SQL V2 - Domizi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(45, 45, 45, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #d4af37;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .subtitle {
            text-align: center;
            color: #999;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .progress {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #d4af37;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            text-align: center;
        }
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #d4af37;
            margin: 10px 0;
        }
        .stat-card .label {
            color: #999;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .error-card {
            background: rgba(255, 68, 68, 0.1);
            border: 2px solid #ff4444;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
        }
        .error-card h3 {
            color: #ff4444;
            margin-bottom: 10px;
        }
        .error-details {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .success-box {
            background: rgba(68, 255, 68, 0.1);
            border: 2px solid #44ff44;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .success-box h2 {
            color: #44ff44;
            font-size: 2em;
            margin-bottom: 10px;
        }
        .schema-table {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .schema-table h4 {
            color: #55ffff;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        .columns {
            color: #aaa;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        .icon { margin-right: 8px; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin: 5px 5px 5px 0;
        }
        .badge-error { background: #ff4444; color: white; }
        .badge-warning { background: #ffaa00; color: black; }
        .badge-success { background: #44ff44; color: black; }
        .file-path {
            color: #55ffff;
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .suggestion {
            background: rgba(212, 175, 55, 0.1);
            border-left: 4px solid #d4af37;
            padding: 15px;
            margin-top: 10px;
            border-radius: 4px;
        }
        .suggestion strong {
            color: #d4af37;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Audit SQL Automatique V2</h1>
        <div class="subtitle">Détection intelligente des erreurs de colonnes avec analyse contextuelle</div>

<?php

try {
    $start_time = microtime(true);
    
    // ========================================================================
    // ÉTAPE 1: CONNEXION ET RÉCUPÉRATION DU SCHÉMA
    // ========================================================================
    
    echo '<div class="progress"><span class="icon">📊</span><strong>Étape 1/4:</strong> Récupération du schéma de la base de données...</div>';
    flush();
    
    $pdo = Database::getInstance();
    
    $schema = [];
    $tables_query = $pdo->query("SHOW TABLES");
    $tables = $tables_query->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $columns_query = $pdo->query("DESCRIBE `$table`");
        $columns = $columns_query->fetchAll(PDO::FETCH_ASSOC);
        $schema[$table] = [];
        foreach ($columns as $col) {
            $schema[$table][] = $col['Field'];
        }
    }
    
    $total_columns = array_sum(array_map('count', $schema));
    
    echo '<div class="progress">✅ <strong>' . count($tables) . '</strong> tables et <strong>' . $total_columns . '</strong> colonnes recensées</div>';
    flush();
    
    // ========================================================================
    // ÉTAPE 2: SCANNER LES FICHIERS PHP
    // ========================================================================
    
    echo '<div class="progress"><span class="icon">📁</span><strong>Étape 2/4:</strong> Scan des fichiers PHP du projet...</div>';
    flush();
    
    $files = [];
    $excluded_dirs = ['vendor', 'node_modules', '.git', '.kiro', 'tests'];
    
    function scanDirectory($dir, &$files, $excluded, $base_dir) {
        $items = @scandir($dir);
        if (!$items) return;
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            
            if (is_dir($path)) {
                if (!in_array($item, $excluded)) {
                    scanDirectory($path, $files, $excluded, $base_dir);
                }
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $relative = str_replace($base_dir . DIRECTORY_SEPARATOR, '', $path);
                $files[] = $relative;
            }
        }
    }
    
    scanDirectory(__DIR__, $files, $excluded_dirs, __DIR__);
    
    echo '<div class="progress">✅ <strong>' . count($files) . '</strong> fichiers PHP trouvés</div>';
    flush();
    
    // ========================================================================
    // ÉTAPE 3: ANALYSER LES REQUÊTES SQL (VERSION AMÉLIORÉE)
    // ========================================================================
    
    echo '<div class="progress"><span class="icon">🔍</span><strong>Étape 3/4:</strong> Analyse intelligente des requêtes SQL...</div>';
    flush();
    
    $errors = [];
    $queries_checked = 0;
    
    /**
     * Fonction améliorée pour extraire le nom de la table du contexte SQL
     * Gère les alias, sous-requêtes, et JOIN complexes
     */
    function extractTableFromContext($sql_chunk, $column, $schema) {
        // Enlever les commentaires SQL
        $sql_chunk = preg_replace('/--[^\n]*/', '', $sql_chunk);
        $sql_chunk = preg_replace('/\/\*.*?\*\//s', '', $sql_chunk);
        
        // Chercher si la colonne a un alias de table explicite (ex: u.role, av.note)
        if (preg_match('/\b([a-z_]\w*)\.' . preg_quote($column, '/') . '\b/i', $sql_chunk, $alias_match)) {
            $alias = $alias_match[1];
            
            // Chercher la définition de l'alias dans FROM ou JOIN (avec AS optionnel)
            if (preg_match('/(?:FROM|JOIN)\s+`?(\w+)`?\s+(?:AS\s+)?`?' . preg_quote($alias, '/') . '`?\b/i', $sql_chunk, $table_match)) {
                return $table_match[1];
            }
            
            // Si alias trouvé mais pas de définition de table, retourner null
            // Cela évite les faux positifs où l'alias pointe vers une autre table
            return null;
        }
        
        // Chercher FROM ... WHERE pattern (sans alias)
        if (preg_match('/FROM\s+`?(\w+)`?[^;]*?WHERE[^;]*?\b' . preg_quote($column, '/') . '\b/is', $sql_chunk, $match)) {
            return $match[1];
        }
        
        // Chercher UPDATE ... SET pattern
        if (preg_match('/UPDATE\s+`?(\w+)`?[^;]*?SET[^;]*?\b' . preg_quote($column, '/') . '\b/is', $sql_chunk, $match)) {
            return $match[1];
        }
        
        // Chercher INSERT INTO ... pattern
        if (preg_match('/INSERT\s+INTO\s+`?(\w+)`?[^;]*?\b' . preg_quote($column, '/') . '\b/is', $sql_chunk, $match)) {
            return $match[1];
        }
        
        return null;
    }
    
    foreach ($files as $file) {
        $path = __DIR__ . DIRECTORY_SEPARATOR . $file;
        $content = @file_get_contents($path);
        if (!$content) continue;
        
        // Extraire toutes les requêtes SQL avec meilleure détection
        $sql_patterns = [
            '/SELECT\s+.+?(?:FROM|;|$)/is',
            '/INSERT\s+INTO\s+.+?(?:VALUES|;|$)/is',
            '/UPDATE\s+.+?(?:SET|;|$)/is',
            '/DELETE\s+FROM\s+.+?(?:WHERE|;|$)/is'
        ];
        
        $sql_matches = [[]];
        foreach ($sql_patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $sql_matches[] = $matches[0];
            }
        }
        $sql_chunks = array_merge(...$sql_matches);
        
        foreach ($sql_chunks as $sql_chunk) {
            if (strlen($sql_chunk) < 20) continue; // Ignorer les fragments trop courts
            
            // Extraire les colonnes dans WHERE clauses
            if (preg_match_all('/WHERE[^;]*?\b(\w+)\s*(?:=|!=|<|>|<=|>=|LIKE|IN)/is', $sql_chunk, $where_matches)) {
                foreach ($where_matches[1] as $column) {
                    $queries_checked++;
                    
                    // Ignorer les mots-clés SQL
                    $keywords = ['AND', 'OR', 'NOT', 'NULL', 'TRUE', 'FALSE', 'EXISTS', 'SELECT', 'FROM', 'JOIN'];
                    if (in_array(strtoupper($column), $keywords)) continue;
                    
                    // Extraire la table du contexte
                    $table = extractTableFromContext($sql_chunk, $column, $schema);
                    
                    if ($table && isset($schema[$table]) && !in_array($column, $schema[$table])) {
                        $errors[] = [
                            'file' => $file,
                            'table' => $table,
                            'column' => $column,
                            'type' => 'WHERE',
                            'available' => $schema[$table],
                            'context' => substr($sql_chunk, 0, 200)
                        ];
                    }
                }
            }
            
            // Extraire les colonnes dans SET clauses
            if (preg_match_all('/SET[^;]*?\b(\w+)\s*=/is', $sql_chunk, $set_matches)) {
                foreach ($set_matches[1] as $column) {
                    $queries_checked++;
                    
                    $table = extractTableFromContext($sql_chunk, $column, $schema);
                    
                    if ($table && isset($schema[$table]) && !in_array($column, $schema[$table])) {
                        $errors[] = [
                            'file' => $file,
                            'table' => $table,
                            'column' => $column,
                            'type' => 'SET',
                            'available' => $schema[$table],
                            'context' => substr($sql_chunk, 0, 200)
                        ];
                    }
                }
            }
            
            // Extraire les colonnes dans INSERT clauses
            if (preg_match('/INSERT\s+INTO\s+`?(\w+)`?\s*\(([^)]+)\)/is', $sql_chunk, $insert_match)) {
                $queries_checked++;
                $table = $insert_match[1];
                $columns_str = $insert_match[2];
                
                preg_match_all('/`?(\w+)`?/i', $columns_str, $col_matches);
                foreach ($col_matches[1] as $column) {
                    if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                        $errors[] = [
                            'file' => $file,
                            'table' => $table,
                            'column' => $column,
                            'type' => 'INSERT',
                            'available' => $schema[$table],
                            'context' => substr($sql_chunk, 0, 200)
                        ];
                    }
                }
            }
        }
    }
    
    echo '<div class="progress">✅ <strong>' . $queries_checked . '</strong> vérifications effectuées</div>';
    flush();
    
    // ========================================================================
    // ÉTAPE 4: AFFICHER LES RÉSULTATS
    // ========================================================================
    
    echo '<div class="progress"><span class="icon">📊</span><strong>Étape 4/4:</strong> Génération du rapport...</div>';
    flush();
    
    $execution_time = round(microtime(true) - $start_time, 2);
    
    // Statistiques
    echo '<div class="stats">';
    echo '<div class="stat-card"><div class="label">Fichiers Scannés</div><div class="number">' . count($files) . '</div></div>';
    echo '<div class="stat-card"><div class="label">Requêtes Analysées</div><div class="number">' . $queries_checked . '</div></div>';
    echo '<div class="stat-card"><div class="label">Tables BDD</div><div class="number">' . count($tables) . '</div></div>';
    echo '<div class="stat-card"><div class="label">Erreurs Détectées</div><div class="number" style="color:' . (count($errors) > 0 ? '#ff4444' : '#44ff44') . '">' . count($errors) . '</div></div>';
    echo '</div>';
    
    // Afficher les résultats
    if (empty($errors)) {
        echo '<div class="success-box">';
        echo '<h2>✅ Aucune Erreur Détectée !</h2>';
        echo '<p>Toutes les colonnes référencées dans les requêtes SQL existent dans la base de données.</p>';
        echo '<p style="color:#999;margin-top:15px">Analyse terminée en ' . $execution_time . 's</p>';
        echo '</div>';
    } else {
        echo '<h2 style="color:#ff4444;margin:30px 0 20px 0">❌ ' . count($errors) . ' Erreur(s) Détectée(s)</h2>';
        
        // Regrouper par fichier
        $errors_by_file = [];
        foreach ($errors as $error) {
            $errors_by_file[$error['file']][] = $error;
        }
        
        $error_num = 1;
        foreach ($errors_by_file as $file => $file_errors) {
            echo '<div class="error-card">';
            echo '<h3>📁 Fichier: <span class="file-path">' . htmlspecialchars($file) . '</span></h3>';
            echo '<span class="badge badge-error">' . count($file_errors) . ' erreur(s)</span>';
            
            foreach ($file_errors as $error) {
                echo '<div class="error-details">';
                echo '<strong>❌ Erreur #' . $error_num . '</strong><br>';
                echo '<strong>Type:</strong> ' . $error['type'] . ' clause<br>';
                echo '<strong>Table:</strong> <span style="color:#ffaa00">' . htmlspecialchars($error['table']) . '</span><br>';
                echo '<strong>Colonne invalide:</strong> <span style="color:#ff4444">' . htmlspecialchars($error['column']) . '</span><br>';
                echo '<strong>Colonnes valides:</strong><br>';
                echo '<div style="margin-top:8px;color:#44ff44">' . implode(', ', $error['available']) . '</div>';
                if (isset($error['context'])) {
                    echo '<br><strong>Contexte SQL:</strong><br>';
                    echo '<div style="margin-top:8px;color:#aaa;font-size:0.8em">' . htmlspecialchars($error['context']) . '...</div>';
                }
                echo '</div>';
                
                // Suggestion de correction
                $similar = [];
                foreach ($error['available'] as $valid_col) {
                    $lev = levenshtein(strtolower($error['column']), strtolower($valid_col));
                    if ($lev <= 3) {
                        $similar[] = $valid_col;
                    }
                }
                
                if (!empty($similar)) {
                    echo '<div class="suggestion">';
                    echo '<strong>💡 Suggestion:</strong> Vouliez-vous dire: <span style="color:#44ff44">' . implode('</span> ou <span style="color:#44ff44">', $similar) . '</span> ?';
                    echo '</div>';
                }
                
                $error_num++;
            }
            
            echo '</div>';
        }
        
        // Générer rapport Markdown
        $report = "# 🔍 RAPPORT AUDIT SQL V2\n\n";
        $report .= "**Date**: " . date('Y-m-d H:i:s') . "\n";
        $report .= "**Durée**: {$execution_time}s\n\n";
        $report .= "## 📊 Statistiques\n\n";
        $report .= "- Fichiers scannés: " . count($files) . "\n";
        $report .= "- Requêtes analysées: $queries_checked\n";
        $report .= "- Erreurs détectées: " . count($errors) . "\n\n";
        $report .= "## ❌ Erreurs Détectées\n\n";
        
        foreach ($errors_by_file as $file => $file_errors) {
            $report .= "### 📁 `$file`\n\n";
            foreach ($file_errors as $error) {
                $report .= "- **Type**: {$error['type']}\n";
                $report .= "- **Table**: `{$error['table']}`\n";
                $report .= "- **Colonne invalide**: `{$error['column']}`\n";
                $report .= "- **Colonnes valides**: " . implode(', ', $error['available']) . "\n\n";
            }
        }
        
        $report_file = __DIR__ . '/AUDIT_SQL_ERRORS.md';
        file_put_contents($report_file, $report);
        
        echo '<div class="progress" style="margin-top:30px">📄 Rapport détaillé sauvegardé: <strong>AUDIT_SQL_ERRORS.md</strong></div>';
    }
    
    // Afficher le schéma complet
    echo '<h2 style="color:#d4af37;margin:40px 0 20px 0">📊 Schéma Complet de la Base de Données</h2>';
    
    foreach ($schema as $table => $columns) {
        echo '<div class="schema-table">';
        echo '<h4>📋 ' . htmlspecialchars($table) . ' <span class="badge badge-success">' . count($columns) . ' colonnes</span></h4>';
        echo '<div class="columns">' . implode(', ', $columns) . '</div>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="error-card">';
    echo '<h3>❌ Erreur Critique</h3>';
    echo '<div class="error-details">' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '</div>';
}

?>

    </div>
</body>
</html>

// Style CSS intégré
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit SQL - Domizi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(45, 45, 45, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #d4af37;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        .subtitle {
            text-align: center;
            color: #999;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .progress {
            background: #1a1a1a;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            border-left: 4px solid #d4af37;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(212, 175, 55, 0.2);
            text-align: center;
        }
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #d4af37;
            margin: 10px 0;
        }
        .stat-card .label {
            color: #999;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .error-card {
            background: rgba(255, 68, 68, 0.1);
            border: 2px solid #ff4444;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
        }
        .error-card h3 {
            color: #ff4444;
            margin-bottom: 10px;
        }
        .error-details {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .success-box {
            background: rgba(68, 255, 68, 0.1);
            border: 2px solid #44ff44;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .success-box h2 {
            color: #44ff44;
            font-size: 2em;
            margin-bottom: 10px;
        }
        .schema-table {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .schema-table h4 {
            color: #55ffff;
            margin-bottom: 10px;
            font-size: 1.2em;
        }
        .columns {
            color: #aaa;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        .icon { margin-right: 8px; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin: 5px 5px 5px 0;
        }
        .badge-error { background: #ff4444; color: white; }
        .badge-warning { background: #ffaa00; color: black; }
        .badge-success { background: #44ff44; color: black; }
        .file-path {
            color: #55ffff;
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.3);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
        }
        .suggestion {
            background: rgba(212, 175, 55, 0.1);
            border-left: 4px solid #d4af37;
            padding: 15px;
            margin-top: 10px;
            border-radius: 4px;
        }
        .suggestion strong {
            color: #d4af37;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #d4af37;
            font-size: 1.2em;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(212, 175, 55, 0.3);
            border-top-color: #d4af37;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Audit SQL Automatique</h1>
        <div class="subtitle">Détection automatique des erreurs de colonnes dans les requêtes SQL</div>

<?php

try {
    $start_time = microtime(true);

    // ========================================================================
    // ÉTAPE 1: CONNEXION ET RÉCUPÉRATION DU SCHÉMA
    // ========================================================================

    echo '<div class="progress"><span class="icon">📊</span><strong>Étape 1/4:</strong> Récupération du schéma de la base de données...</div>';
    flush();

    $pdo = Database::getInstance();

    $schema = [];
    $tables_query = $pdo->query("SHOW TABLES");
    $tables = $tables_query->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $columns_query = $pdo->query("DESCRIBE `$table`");
        $columns = $columns_query->fetchAll(PDO::FETCH_ASSOC);
        $schema[$table] = [];
        foreach ($columns as $col) {
            $schema[$table][] = $col['Field'];
        }
    }

    $total_columns = array_sum(array_map('count', $schema));

    echo '<div class="progress">✅ <strong>' . count($tables) . '</strong> tables et <strong>' . $total_columns . '</strong> colonnes recensées</div>';
    flush();

    // ========================================================================
    // ÉTAPE 2: SCANNER LES FICHIERS PHP
    // ========================================================================

    echo '<div class="progress"><span class="icon">📁</span><strong>Étape 2/4:</strong> Scan des fichiers PHP du projet...</div>';
    flush();

    $files = [];
    $excluded_dirs = ['vendor', 'node_modules', '.git', '.kiro', 'tests'];

    function scanDirectory($dir, &$files, $excluded, $base_dir) {
        $items = @scandir($dir);
        if (!$items) return;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                if (!in_array($item, $excluded)) {
                    scanDirectory($path, $files, $excluded, $base_dir);
                }
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $relative = str_replace($base_dir . DIRECTORY_SEPARATOR, '', $path);
                $files[] = $relative;
            }
        }
    }

    scanDirectory(__DIR__, $files, $excluded_dirs, __DIR__);

    echo '<div class="progress">✅ <strong>' . count($files) . '</strong> fichiers PHP trouvés</div>';
    flush();

    // ========================================================================
    // ÉTAPE 3: ANALYSER LES REQUÊTES SQL
    // ========================================================================

    echo '<div class="progress"><span class="icon">🔍</span><strong>Étape 3/4:</strong> Analyse des requêtes SQL...</div>';
    flush();

    $errors = [];
    $queries_checked = 0;
    $files_with_sql = 0;

    foreach ($files as $file) {
        $content = @file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . $file);
        if (!$content) continue;

        $file_has_errors = false;

        // Détecter les requêtes avec des colonnes dans WHERE
        if (preg_match_all('/WHERE\s+(?:`?(\w+)`?\.)?\s*`?(\w+)`?\s*(?:=|!=|<|>|<=|>=|LIKE|IN)/i', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $queries_checked++;
                $table_alias = isset($match[1]) ? $match[1] : null;
                $column = $match[2];

                // Ignorer les mots-clés SQL
                $sql_keywords = ['AND', 'OR', 'NOT', 'NULL', 'TRUE', 'FALSE', 'EXISTS'];
                if (in_array(strtoupper($column), $sql_keywords)) continue;

                // Trouver le contexte de la table
                $context_pattern = '/(?:FROM|JOIN|UPDATE)\s+`?(\w+)`?[^;]*?WHERE[^;]*?' . preg_quote($column, '/') . '/is';
                if (preg_match($context_pattern, $content, $ctx_match)) {
                    $table = $ctx_match[1];

                    if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                        $errors[] = [
                            'file' => $file,
                            'table' => $table,
                            'column' => $column,
                            'type' => 'WHERE',
                            'available' => $schema[$table]
                        ];
                        $file_has_errors = true;
                    }
                }
            }
        }

        // Détecter les colonnes dans SET (UPDATE)
        if (preg_match_all('/UPDATE\s+`?(\w+)`?.*?SET\s+`?(\w+)`?\s*=/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $queries_checked++;
                $table = $match[1];
                $column = $match[2];

                if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                    $errors[] = [
                        'file' => $file,
                        'table' => $table,
                        'column' => $column,
                        'type' => 'SET',
                        'available' => $schema[$table]
                    ];
                    $file_has_errors = true;
                }
            }
        }

        // Détecter les colonnes dans INSERT
        if (preg_match_all('/INSERT\s+INTO\s+`?(\w+)`?\s*\(([^)]+)\)/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $queries_checked++;
                $table = $match[1];
                $columns_str = $match[2];

                preg_match_all('/`?(\w+)`?/i', $columns_str, $col_matches);
                foreach ($col_matches[1] as $column) {
                    if (isset($schema[$table]) && !in_array($column, $schema[$table])) {
                        $errors[] = [
                            'file' => $file,
                            'table' => $table,
                            'column' => $column,
                            'type' => 'INSERT',
                            'available' => $schema[$table]
                        ];
                        $file_has_errors = true;
                    }
                }
            }
        }

        if ($file_has_errors) {
            $files_with_sql++;
        }
    }

    echo '<div class="progress">✅ <strong>' . $queries_checked . '</strong> vérifications effectuées</div>';
    flush();

    // ========================================================================
    // ÉTAPE 4: AFFICHER LES RÉSULTATS
    // ========================================================================

    echo '<div class="progress"><span class="icon">📊</span><strong>Étape 4/4:</strong> Génération du rapport...</div>';
    flush();

    $execution_time = round(microtime(true) - $start_time, 2);

    // Statistiques
    echo '<div class="stats">';
    echo '<div class="stat-card"><div class="label">Fichiers Scannés</div><div class="number">' . count($files) . '</div></div>';
    echo '<div class="stat-card"><div class="label">Requêtes Analysées</div><div class="number">' . $queries_checked . '</div></div>';
    echo '<div class="stat-card"><div class="label">Tables BDD</div><div class="number">' . count($tables) . '</div></div>';
    echo '<div class="stat-card"><div class="label">Erreurs Détectées</div><div class="number" style="color:' . (count($errors) > 0 ? '#ff4444' : '#44ff44') . '">' . count($errors) . '</div></div>';
    echo '</div>';

    // Afficher les résultats
    if (empty($errors)) {
        echo '<div class="success-box">';
        echo '<h2>✅ Aucune Erreur Détectée !</h2>';
        echo '<p>Toutes les colonnes référencées dans les requêtes SQL existent dans la base de données.</p>';
        echo '<p style="color:#999;margin-top:15px">Analyse terminée en ' . $execution_time . 's</p>';
        echo '</div>';
    } else {
        echo '<h2 style="color:#ff4444;margin:30px 0 20px 0">❌ ' . count($errors) . ' Erreur(s) Détectée(s)</h2>';

        // Regrouper par fichier
        $errors_by_file = [];
        foreach ($errors as $error) {
            $errors_by_file[$error['file']][] = $error;
        }

        $error_num = 1;
        foreach ($errors_by_file as $file => $file_errors) {
            echo '<div class="error-card">';
            echo '<h3>📁 Fichier: <span class="file-path">' . htmlspecialchars($file) . '</span></h3>';
            echo '<span class="badge badge-error">' . count($file_errors) . ' erreur(s)</span>';

            foreach ($file_errors as $error) {
                echo '<div class="error-details">';
                echo '<strong>❌ Erreur #' . $error_num . '</strong><br>';
                echo '<strong>Type:</strong> ' . $error['type'] . ' clause<br>';
                echo '<strong>Table:</strong> <span style="color:#ffaa00">' . htmlspecialchars($error['table']) . '</span><br>';
                echo '<strong>Colonne invalide:</strong> <span style="color:#ff4444">' . htmlspecialchars($error['column']) . '</span><br>';
                echo '<strong>Colonnes valides:</strong><br>';
                echo '<div style="margin-top:8px;color:#44ff44">' . implode(', ', $error['available']) . '</div>';
                echo '</div>';

                // Suggestion de correction
                $similar = [];
                foreach ($error['available'] as $valid_col) {
                    $lev = levenshtein(strtolower($error['column']), strtolower($valid_col));
                    if ($lev <= 3) {
                        $similar[] = $valid_col;
                    }
                }

                if (!empty($similar)) {
                    echo '<div class="suggestion">';
                    echo '<strong>💡 Suggestion:</strong> Vouliez-vous dire: <span style="color:#44ff44">' . implode('</span> ou <span style="color:#44ff44">', $similar) . '</span> ?';
                    echo '</div>';
                }

                $error_num++;
            }

            echo '</div>';
        }

        // Générer rapport Markdown
        $report = "# 🔍 RAPPORT AUDIT SQL\n\n";
        $report .= "**Date**: " . date('Y-m-d H:i:s') . "\n";
        $report .= "**Durée**: {$execution_time}s\n\n";
        $report .= "## 📊 Statistiques\n\n";
        $report .= "- Fichiers scannés: " . count($files) . "\n";
        $report .= "- Requêtes analysées: $queries_checked\n";
        $report .= "- Erreurs détectées: " . count($errors) . "\n\n";
        $report .= "## ❌ Erreurs Détectées\n\n";

        foreach ($errors_by_file as $file => $file_errors) {
            $report .= "### 📁 `$file`\n\n";
            foreach ($file_errors as $error) {
                $report .= "- **Type**: {$error['type']}\n";
                $report .= "- **Table**: `{$error['table']}`\n";
                $report .= "- **Colonne invalide**: `{$error['column']}`\n";
                $report .= "- **Colonnes valides**: " . implode(', ', $error['available']) . "\n\n";
            }
        }

        $report_file = __DIR__ . '/AUDIT_SQL_ERRORS.md';
        file_put_contents($report_file, $report);

        echo '<div class="progress" style="margin-top:30px">📄 Rapport détaillé sauvegardé: <strong>AUDIT_SQL_ERRORS.md</strong></div>';
    }

    // Afficher le schéma complet
    echo '<h2 style="color:#d4af37;margin:40px 0 20px 0">📊 Schéma Complet de la Base de Données</h2>';

    foreach ($schema as $table => $columns) {
        echo '<div class="schema-table">';
        echo '<h4>📋 ' . htmlspecialchars($table) . ' <span class="badge badge-success">' . count($columns) . ' colonnes</span></h4>';
        echo '<div class="columns">' . implode(', ', $columns) . '</div>';
        echo '</div>';
    }

} catch (Exception $e) {
    echo '<div class="error-card">';
    echo '<h3>❌ Erreur Critique</h3>';
    echo '<div class="error-details">' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '</div>';
}

?>

    </div>
</body>
</html>
