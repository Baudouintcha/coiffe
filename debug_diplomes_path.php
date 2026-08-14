<?php
/**
 * DEBUG: Vérifier les chemins des diplômes stockés en BD
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';

// Récupérer les diplômes
$diplomes = $pdo->query("
    SELECT id, nom, prenom, diplome
    FROM users
    WHERE role = 'coiffeur' AND diplome IS NOT NULL
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Debug Diplômes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #000; color: #fff; font-family: monospace; }
        .code { background-color: #1a1a1a; border-left: 3px solid gold; padding: 10px; margin: 10px 0; overflow-x: auto; }
        .problem { color: #ff6b6b; }
        .success { color: #51cf66; }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1>🔍 Debug Diplômes - Vérifier les Chemins</h1>
        
        <div class="card bg-dark border-secondary p-4 my-4">
            <h3>📋 Diplômes en BD</h3>
            
            <?php if (count($diplomes) == 0): ?>
                <p class="problem">❌ Aucun diplôme trouvé en BD</p>
            <?php else: ?>
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Coiffeur</th>
                            <th>Chemin Stocké</th>
                            <th>Type de Chemin</th>
                            <th>Fichier Existe?</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diplomes as $d): 
                            $path = $d['diplome'];
                            $chemin_type = 'unknown';
                            $existe = false;
                            $full_path = '';
                            
                            // Déterminer le type de chemin
                            if (strpos($path, 'http') === 0) {
                                $chemin_type = 'URL absolute';
                            } elseif (strpos($path, '/uploads/') === 0) {
                                $chemin_type = 'Chemin absolu (/uploads/)';
                                $full_path = __DIR__ . $path;
                                $existe = file_exists($full_path);
                            } elseif (strpos($path, 'uploads/') === 0) {
                                $chemin_type = 'Chemin relatif (uploads/)';
                                $full_path = __DIR__ . '/' . $path;
                                $existe = file_exists($full_path);
                            } elseif (strpos($path, './') === 0 || strpos($path, '../') === 0) {
                                $chemin_type = 'Chemin relatif (./ ou ../)';
                                $full_path = __DIR__ . '/' . $path;
                                $existe = file_exists($full_path);
                            } else {
                                $chemin_type = 'Format inconnu';
                            }
                        ?>
                            <tr>
                                <td><?php echo $d['id']; ?></td>
                                <td><?php echo htmlspecialchars($d['nom'] . ' ' . $d['prenom']); ?></td>
                                <td>
                                    <div class="code">
                                        <?php echo htmlspecialchars($path); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="<?php echo $chemin_type === 'unknown' ? 'problem' : ''; ?>">
                                        <?php echo $chemin_type; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($chemin_type === 'URL absolute'): ?>
                                        <span class="success">✅ URL externe</span>
                                    <?php elseif ($existe): ?>
                                        <span class="success">✅ Oui</span>
                                    <?php else: ?>
                                        <span class="problem">❌ Non trouvé</span>
                                        <?php if ($full_path): ?>
                                            <div class="small mt-2">
                                                Chemin testé: <?php echo htmlspecialchars($full_path); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($path); ?>" target="_blank" class="btn btn-sm btn-info">
                                        Tester
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card bg-dark border-secondary p-4 my-4">
            <h3>🔧 Solutions Possibles</h3>
            
            <h5>Si "Fichier Existe? = ❌ Non trouvé":</h5>
            <p>Les chemins en BD ne pointent pas vers les fichiers réels. Causes possibles:</p>
            <ul>
                <li>❌ Les fichiers ont été uploadés dans un mauvais dossier</li>
                <li>❌ Les chemins en BD sont incomplets ou incorrects</li>
                <li>❌ Les fichiers ont été supprimés</li>
                <li>❌ Le dossier <code>/access/uploads/diplomes/</code> n'existe pas</li>
            </ul>

            <h5 class="mt-3">Solution 1: Vérifier le dossier uploads</h5>
            <div class="code">
                Dossier expected: <code>c:\xampp\htdocs\coiffons\access\uploads\diplomes\</code>
                <?php 
                    $upload_path = __DIR__ . '/access/uploads/diplomes';
                    if (is_dir($upload_path)) {
                        $files = scandir($upload_path);
                        $files = array_diff($files, ['.', '..']);
                        echo "<br><span class='success'>✅ Dossier EXISTE</span>";
                        echo "<br>Fichiers trouvés: " . count($files);
                        if (count($files) > 0) {
                            foreach ($files as $f) {
                                echo "<br>  - " . $f;
                            }
                        }
                    } else {
                        echo "<br><span class='problem'>❌ Dossier N'EXISTE PAS</span>";
                    }
                ?>
            </div>

            <h5 class="mt-3">Solution 2: Corriger les chemins en BD</h5>
            <div class="code">
-- Si les fichiers existent mais chemins mauvais en BD, exécuter:
UPDATE users SET diplome = CONCAT('/access/uploads/diplomes/', 
    SUBSTRING_INDEX(diplome, '/', -1)) 
WHERE role = 'coiffeur' AND diplome IS NOT NULL;
            </div>

            <h5 class="mt-3">Solution 3: Tester avec une URL complète</h5>
            <div class="code">
-- Remplacer les chemins par une URL complète:
UPDATE users SET diplome = 
    CONCAT('http://localhost/coiffons/access/uploads/diplomes/', 
        SUBSTRING_INDEX(diplome, '/', -1)) 
WHERE role = 'coiffeur' AND diplome IS NOT NULL;
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
