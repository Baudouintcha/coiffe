<?php
/**
 * Script de debug pour le dashboard client - affichage des coiffeurs
 */

session_start();
require_once __DIR__ . '/security/config.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
body { font-family: 'Courier New', monospace; background: #0a0a0a; color: #0f0; padding: 20px; }
h1, h2 { color: #0ff; }
.success { color: #0f0; }
.error { color: #f00; }
.warning { color: #ff0; }
pre { background: #111; padding: 10px; border-left: 3px solid #0ff; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #333; text-align: left; }
th { background: #1a1a1a; color: #0ff; }
</style></head><body>";

echo "<h1>🔍 DEBUG DASHBOARD CLIENT - COIFFEURS</h1>";
echo "<p>Script de diagnostic pour identifier pourquoi les coiffeurs ne s'affichent pas</p>";
echo "<hr>";

// 1. Vérifier la session
echo "<h2>1️⃣ SESSION UTILISATEUR</h2>";
if (!isset($_SESSION['id_user'])) {
    echo "<p class='error'>❌ Aucun utilisateur connecté</p>";
    echo "<p>👉 <a href='/coiffons/access/connexion.php' style='color:#0ff;'>Se connecter</a></p>";
    exit;
}

echo "<table>";
echo "<tr><th>Clé</th><th>Valeur</th></tr>";
$session_keys = ['id_user', 'nom', 'prenom', 'role', 'id_ville', 'id_quartier', 'photo_profil'];
foreach ($session_keys as $key) {
    $value = $_SESSION[$key] ?? '<span class="error">NON DÉFINI</span>';
    $class = isset($_SESSION[$key]) ? 'success' : 'error';
    echo "<tr><td>$key</td><td class='$class'>$value</td></tr>";
}
echo "</table>";

if ($_SESSION['role'] !== 'client') {
    echo "<p class='warning'>⚠️ Vous êtes connecté en tant que : " . $_SESSION['role'] . "</p>";
    echo "<p>Ce dashboard est pour les clients uniquement.</p>";
}

$id_ville_client = $_SESSION['id_ville'] ?? 0;
$id_quartier_client = $_SESSION['id_quartier'] ?? 0;

if ($id_ville_client == 0) {
    echo "<p class='error'>❌ ID VILLE NON DÉFINI - Les coiffeurs ne peuvent pas être chargés</p>";
    echo "<p>Solution: Reconnectez-vous pour charger les données de ville/quartier</p>";
}

echo "<hr>";

// 2. Vérifier la structure de la table users
echo "<h2>2️⃣ STRUCTURE TABLE USERS</h2>";
try {
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    $col_names = array_column($columns, 'Field');
    
    $required_cols = ['id', 'ville', 'id_quartier', 'role', 'is_approved', 'statut', 'abonnement_status'];
    echo "<p>Colonnes requises :</p><ul>";
    foreach ($required_cols as $col) {
        if (in_array($col, $col_names)) {
            echo "<li class='success'>✓ $col</li>";
        } else {
            echo "<li class='error'>✗ $col (MANQUANTE)</li>";
        }
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 3. Statistiques coiffeurs
echo "<h2>3️⃣ STATISTIQUES COIFFEURS</h2>";
try {
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN role = 'coiffeur' THEN 1 ELSE 0 END) as coiffeurs,
            SUM(CASE WHEN role = 'coiffeur' AND is_approved = 1 THEN 1 ELSE 0 END) as approuves,
            SUM(CASE WHEN role = 'coiffeur' AND statut = 'actif' THEN 1 ELSE 0 END) as actifs,
            SUM(CASE WHEN role = 'coiffeur' AND (abonnement_status = 'actif' OR abonnement_status = 1) THEN 1 ELSE 0 END) as avec_abo
        FROM users
    ")->fetch();
    
    echo "<table>";
    echo "<tr><th>Métrique</th><th>Valeur</th></tr>";
    echo "<tr><td>Total utilisateurs</td><td>" . $stats['total'] . "</td></tr>";
    echo "<tr><td>Coiffeurs</td><td>" . $stats['coiffeurs'] . "</td></tr>";
    echo "<tr><td>Coiffeurs approuvés</td><td>" . $stats['approuves'] . "</td></tr>";
    echo "<tr><td>Coiffeurs actifs</td><td>" . $stats['actifs'] . "</td></tr>";
    echo "<tr><td>Avec abonnement</td><td>" . $stats['avec_abo'] . "</td></tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 4. Liste des coiffeurs avec détails
echo "<h2>4️⃣ LISTE DES COIFFEURS</h2>";
try {
    $coiffeurs = $pdo->query("
        SELECT u.id, u.nom, u.prenom, u.ville, u.id_quartier, u.is_approved, 
               u.statut, u.abonnement_status, v.nom_ville, q.nom_quartier
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        LEFT JOIN quartiers q ON u.id_quartier = q.id
        WHERE u.role = 'coiffeur'
        ORDER BY u.id
    ")->fetchAll();
    
    if (empty($coiffeurs)) {
        echo "<p class='warning'>⚠️ Aucun coiffeur dans la base de données</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Nom</th><th>Ville</th><th>Quartier</th><th>Approuvé</th><th>Statut</th><th>Abonnement</th><th>✓</th></tr>";
        foreach ($coiffeurs as $c) {
            $approved = $c['is_approved'] == 1;
            $actif = $c['statut'] == 'actif';
            $abo = ($c['abonnement_status'] == 'actif' || $c['abonnement_status'] == 1);
            $ok = $approved && $actif && $abo;
            
            $row_class = $ok ? 'success' : 'error';
            
            echo "<tr class='$row_class'>";
            echo "<td>{$c['id']}</td>";
            echo "<td>{$c['prenom']} {$c['nom']}</td>";
            echo "<td>{$c['nom_ville']} (ID:{$c['ville']})</td>";
            echo "<td>{$c['nom_quartier']} (ID:{$c['id_quartier']})</td>";
            echo "<td>" . ($approved ? '✓' : '✗') . "</td>";
            echo "<td>{$c['statut']}</td>";
            echo "<td>{$c['abonnement_status']}</td>";
            echo "<td>" . ($ok ? '✓' : '✗') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Erreur: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 5. Test requête dashboard exacte
echo "<h2>5️⃣ TEST REQUÊTE DASHBOARD</h2>";
echo "<p>Paramètres : ville=$id_ville_client, quartier=$id_quartier_client</p>";

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id as id_coiffeur, 
            u.nom as nom_coiffeur,
            u.photo_profil as photo_profil_coiffeur,
            q.nom_quartier as quartier, 
            v.nom_ville as ville,
            u.is_approved,
            u.statut,
            u.abonnement_status,
            (SELECT MIN(p2.prix) FROM prestations p2 WHERE p2.id_coiffeur = u.id) as prix,
            (SELECT p3.photo_style FROM prestations p3 WHERE p3.id_coiffeur = u.id AND p3.photo_style IS NOT NULL LIMIT 1) as photo_style
        FROM users u
        LEFT JOIN villes v ON u.ville = v.id
        LEFT JOIN zones_coiffeur z ON u.id = z.id_coiffeur
        LEFT JOIN quartiers q ON u.id_quartier = q.id
        WHERE u.role = 'coiffeur' 
        AND u.is_approved = 1
        AND u.statut = 'actif'
        AND (u.abonnement_status = 'actif' OR u.abonnement_status = 1)
        AND u.ville = ? 
        AND (u.id_quartier = ? OR z.id_quartier = ?)
        LIMIT 9
    ");
    
    $stmt->execute([$id_ville_client, $id_quartier_client, $id_quartier_client]);
    $results = $stmt->fetchAll();
    
    echo "<p class='success'>✓ Requête exécutée : " . count($results) . " résultat(s)</p>";
    
    if (empty($results)) {
        echo "<p class='warning'>⚠️ AUCUN RÉSULTAT - Test du fallback (toute la ville)...</p>";
        
        $stmt2 = $pdo->prepare("
            SELECT DISTINCT u.id as id_coiffeur, 
                u.nom as nom_coiffeur,
                v.nom_ville as ville,
                q.nom_quartier as quartier
            FROM users u
            LEFT JOIN villes v ON u.ville = v.id
            LEFT JOIN quartiers q ON u.id_quartier = q.id
            WHERE u.role = 'coiffeur' 
            AND u.is_approved = 1
            AND u.statut = 'actif'
            AND (u.abonnement_status = 'actif' OR u.abonnement_status = 1)
            AND u.ville = ?
            LIMIT 9
        ");
        $stmt2->execute([$id_ville_client]);
        $results2 = $stmt2->fetchAll();
        
        if (!empty($results2)) {
            echo "<p class='success'>✓ Fallback: " . count($results2) . " coiffeur(s) trouvé(s) dans toute la ville</p>";
            echo "<pre>" . print_r($results2, true) . "</pre>";
        } else {
            echo "<p class='error'>✗ Aucun coiffeur même avec fallback</p>";
            echo "<h3>🔧 DIAGNOSTIC</h3>";
            echo "<ol>";
            echo "<li>Aucun coiffeur dans votre ville (ID: $id_ville_client)</li>";
            echo "<li>Ou tous les coiffeurs ne remplissent pas les critères</li>";
            echo "<li>👉 <a href='/coiffons/activer_coiffeurs.php' style='color:#0ff;'>Activer les coiffeurs</a></li>";
            echo "</ol>";
        }
    } else {
        echo "<pre>" . print_r($results, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur SQL: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// 6. Actions recommandées
echo "<h2>6️⃣ ACTIONS RECOMMANDÉES</h2>";
echo "<ol>";
echo "<li><a href='/coiffons/activer_coiffeurs.php' style='color:#0ff;'>Activer tous les coiffeurs</a></li>";
echo "<li><a href='/coiffons/access/connexion.php' style='color:#0ff;'>Se reconnecter</a> (pour recharger ville/quartier)</li>";
echo "<li><a href='/coiffons/index.php?page=dashboard' style='color:#0ff;'>Aller au dashboard</a></li>";
echo "</ol>";

echo "</body></html>";
