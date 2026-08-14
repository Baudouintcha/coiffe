<?php
/**
 * FIX: Corriger les chemins des diplômes en BD
 * Changement: uploads/diplomes/ → access/uploads/diplomes/
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';

// Vérifier que c'est un admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("❌ Accès refusé. Admin seulement.");
}

// Récupérer tous les coiffeurs avec diplômes
$coiffeurs = $pdo->query("
    SELECT id, nom, prenom, diplome 
    FROM users 
    WHERE role = 'coiffeur' AND diplome IS NOT NULL AND diplome != ''
")->fetchAll(PDO::FETCH_ASSOC);

$fixed_count = 0;
$errors = [];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fix Diplômes Paths</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #000; color: #fff; }
        .success { color: #51cf66; }
        .error { color: #ff6b6b; }
        .info { color: #74c0fc; }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">🔧 Correction des Chemins de Diplômes</h1>
        
        <div class="card bg-dark border-secondary p-4">
            <h3>📋 Traitement</h3>
            
            <?php
            // Traiter chaque coiffeur
            foreach ($coiffeurs as $coiffeur) {
                $old_path = $coiffeur['diplome'];
                
                // Extraire juste le nom de fichier
                $filename = basename($old_path);
                
                // Construire le nouveau chemin
                $new_path = 'access/uploads/diplomes/' . $filename;
                
                // Vérifier que le fichier existe avec le nouveau chemin
                $full_path = __DIR__ . '/' . $new_path;
                $file_exists = file_exists($full_path);
                
                echo "<div class='card bg-dark border-secondary mb-3 p-3'>";
                echo "<strong>ID {$coiffeur['id']}: " . htmlspecialchars($coiffeur['nom'] . ' ' . $coiffeur['prenom']) . "</strong>";
                echo "<br>";
                echo "<small class='text-muted'>Ancien: " . htmlspecialchars($old_path) . "</small>";
                echo "<br>";
                echo "<small class='text-info'>Nouveau: " . htmlspecialchars($new_path) . "</small>";
                echo "<br>";
                
                if ($file_exists) {
                    // Mettre à jour la BD
                    try {
                        $update = $pdo->prepare("UPDATE users SET diplome = ? WHERE id = ?");
                        $update->execute([$new_path, $coiffeur['id']]);
                        echo "<span class='success'>✅ Mis à jour (fichier trouvé)</span>";
                        $fixed_count++;
                    } catch (Exception $e) {
                        echo "<span class='error'>❌ Erreur BD: " . $e->getMessage() . "</span>";
                        $errors[] = "ID {$coiffeur['id']}: " . $e->getMessage();
                    }
                } else {
                    echo "<span class='error'>❌ Fichier non trouvé: " . htmlspecialchars($full_path) . "</span>";
                }
                
                echo "</div>";
            }
            ?>
            
            <div class="alert alert-info mt-4">
                <strong>Résumé:</strong><br>
                ✅ Diplômes mis à jour: <span class="success fw-bold"><?php echo $fixed_count; ?>/<?php echo count($coiffeurs); ?></span><br>
                <?php if (!empty($errors)): ?>
                    ❌ Erreurs:
                    <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo $e; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card bg-dark border-secondary p-4 mt-4">
            <h3>✅ Prochaines Étapes</h3>
            <ol>
                <li>Allez à <code>/first/admin_dashboard.php</code></li>
                <li>Onglet "Validations & Flux"</li>
                <li>Section "Diplômes en Attente"</li>
                <li>Cliquez sur "Voir" pour vérifier les diplômes</li>
                <li>Les fichiers doivent s'ouvrir (pas 404)</li>
            </ol>
        </div>

    </div>
</body>
</html>
