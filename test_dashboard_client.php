<?php
/**
 * Script de test pour vérifier les données du dashboard client
 */

session_start();
require_once __DIR__ . '/security/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
.section { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 8px; }
.success { color: #4caf50; }
.error { color: #f44336; }
.warning { color: #ff9800; }
pre { background: #1a1a1a; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 Test Dashboard Client</h1>";

// 1. Vérifier la session
echo "<div class='section'>";
echo "<h2>1. Session Active</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✓ Utilisateur connecté : ID=" . $_SESSION['user_id'] . "</p>";
    echo "<p>Nom : " . ($_SESSION['nom'] ?? 'N/A') . "</p>";
    echo "<p>Prénom : " . ($_SESSION['prenom'] ?? 'N/A') . "</p>";
    echo "<p>Rôle : " . ($_SESSION['role'] ?? 'N/A') . "</p>";
    echo "<p>ID Ville : " . ($_SESSION['id_ville'] ?? 'N/A') . "</p>";
    echo "<p>ID Quartier : " . ($_SESSION['id_quartier'] ?? 'N/A') . "</p>";
} else {
    echo "<p class='error'>✗ Aucun utilisateur connecté</p>";
    echo "<p>Connectez-vous d'abord, puis revenez sur cette page.</p>";
}
echo "</div>";

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'client') {
    $id_ville_client = $_SESSION['id_ville'] ?? 0;
    $id_quartier_client = $_SESSION['id_quartier'] ?? 0;
    
    // 2. Vérifier les coiffeurs dans la ville
    echo "<div class='section'>";
    echo "<h2>2. Coiffeurs Disponibles</h2>";
    
    try {
        // Tous les coiffeurs
        $stmt_all = $pdo->query("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approuves,
                   SUM(CASE WHEN abonnement_status = 'actif' OR abonnement_status = 1 THEN 1 ELSE 0 END) as avec_abo,
                   SUM(CASE WHEN statut = 'actif' THEN 1 ELSE 0 END) as actifs
            FROM users WHERE role = 'prestataire'
        ");
        $stats = $stmt_all->fetch();
        
        echo "<p>📊 <strong>Statistiques globales :</strong></p>";
        echo "<ul>";
        echo "<li>Total coiffeurs : " . $stats['total'] . "</li>";
        echo "<li>Approuvés : " . $stats['approuves'] . "</li>";
        echo "<li>Avec abonnement actif : " . $stats['avec_abo'] . "</li>";
        echo "<li>Statut actif : " . $stats['actifs'] . "</li>";
        echo "</ul>";
        
        // Coiffeurs dans la ville du client
        if ($id_ville_client > 0) {
            $stmt_ville = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM users u
                WHERE u.role = 'prestataire' 
                AND u.ville = ?
            ");
            $stmt_ville->execute([$id_ville_client]);
            $count_ville = $stmt_ville->fetchColumn();
            
            echo "<p>🏙️ <strong>Dans votre ville (ID=$id_ville_client) :</strong> $count_ville coiffeur(s)</p>";
            
            // Coiffeurs qui remplissent tous les critères
            $stmt_eligibles = $pdo->prepare("
                SELECT u.id, u.nom, u.prenom, u.is_approved, u.abonnement_status, u.statut, 
                       u.id_ville, u.id_quartier, v.nom_ville, q.nom_quartier
                FROM users u
                LEFT JOIN villes v ON u.id_ville = v.id
                LEFT JOIN quartiers q ON u.id_quartier = q.id
                WHERE u.role = 'prestataire' 
                AND u.id_ville = ?
            ");
            $stmt_eligibles->execute([$id_ville_client]);
            $coiffeurs_ville = $stmt_eligibles->fetchAll();
            
            if (!empty($coiffeurs_ville)) {
                echo "<p class='success'>✓ Coiffeurs trouvés dans votre ville :</p>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%; color: #fff;'>";
                echo "<tr><th>ID</th><th>Nom</th><th>Ville</th><th>Quartier</th><th>Approuvé</th><th>Abo</th><th>Statut</th></tr>";
                foreach ($coiffeurs_ville as $c) {
                    $approved_badge = $c['is_approved'] ? '✓' : '✗';
                    $abo_badge = ($c['abonnement_status'] === 'actif' || $c['abonnement_status'] == 1) ? '✓' : '✗';
                    $statut_badge = $c['statut'] === 'actif' ? '✓' : '✗';
                    
                    echo "<tr>";
                    echo "<td>{$c['id']}</td>";
                    echo "<td>{$c['prenom']} {$c['nom']}</td>";
                    echo "<td>{$c['nom_ville']}</td>";
                    echo "<td>{$c['nom_quartier']}</td>";
                    echo "<td>{$approved_badge}</td>";
                    echo "<td>{$abo_badge}</td>";
                    echo "<td>{$statut_badge}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠ Aucun coiffeur dans votre ville</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Erreur : " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // 3. Test de la requête du dashboard
    echo "<div class='section'>";
    echo "<h2>3. Requête Dashboard (avec critères stricts)</h2>";
    
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id as id_coiffeur, 
                u.nom as nom_coiffeur,
                u.photo_profil as photo_profil_coiffeur,
                q.nom_quartier as quartier, 
                v.nom_ville as ville,
                u.is_approved,
                u.abonnement_status,
                u.statut,
                (SELECT MIN(p2.prix) FROM prestations p2 WHERE p2.id_coiffeur = u.id) as prix,
                (SELECT p3.photo_style FROM prestations p3 WHERE p3.id_coiffeur = u.id AND p3.photo_style IS NOT NULL LIMIT 1) as photo_style,
                (SELECT ROUND(AVG(c.note), 1) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS note_moyenne,
                (SELECT COUNT(c.id_commentaire) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS nb_avis
            FROM users u
            LEFT JOIN villes v ON u.id_ville = v.id
            LEFT JOIN zones_prestataire z ON u.id = z.id_prestataire
            LEFT JOIN quartiers q ON u.id_quartier = q.id
            WHERE u.role = 'coiffeur' 
            AND u.is_approved = 1
            AND u.statut = 'actif'
            AND (u.abonnement_status = 'actif' OR u.abonnement_status = 1)
            AND u.id_ville = ? 
            AND (u.id_quartier = ? OR z.id_quartier = ?)
            ORDER BY RAND() 
            LIMIT 9
        ");
        $stmt->execute([$id_ville_client, $id_quartier_client, $id_quartier_client]);
        $coiffeurs_matching = $stmt->fetchAll();
        
        echo "<p><strong>Paramètres :</strong></p>";
        echo "<ul>";
        echo "<li>ID Ville : $id_ville_client</li>";
        echo "<li>ID Quartier : $id_quartier_client</li>";
        echo "</ul>";
        
        echo "<p><strong>Résultats :</strong> " . count($coiffeurs_matching) . " coiffeur(s) trouvé(s)</p>";
        
        if (!empty($coiffeurs_matching)) {
            echo "<p class='success'>✓ Les coiffeurs suivants devraient s'afficher sur le dashboard :</p>";
            echo "<pre>";
            print_r($coiffeurs_matching);
            echo "</pre>";
        } else {
            echo "<p class='warning'>⚠ Aucun résultat - Tentative du fallback (toute la ville)...</p>";
            
            // Fallback
            $stmt2 = $pdo->prepare("
                SELECT DISTINCT u.id as id_coiffeur, 
                    u.nom as nom_coiffeur,
                    u.photo_profil as photo_profil_coiffeur,
                    q.nom_quartier as quartier, 
                    v.nom_ville as ville,
                    u.is_approved,
                    (SELECT MIN(p2.prix) FROM prestations p2 WHERE p2.id_coiffeur = u.id) as prix,
                    (SELECT p3.photo_style FROM prestations p3 WHERE p3.id_coiffeur = u.id AND p3.photo_style IS NOT NULL LIMIT 1) as photo_style,
                    (SELECT ROUND(AVG(c.note), 1) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS note_moyenne,
                    (SELECT COUNT(c.id_commentaire) FROM commentaires c WHERE c.id_coiffeur = u.id AND c.type_commentaire = 'public') AS nb_avis
                FROM users u
                LEFT JOIN villes v ON u.id_ville = v.id
                LEFT JOIN quartiers q ON u.id_quartier = q.id
                WHERE u.role = 'prestataire' 
                AND u.is_approved = 1
                AND u.statut = 'actif'
                AND (u.abonnement_status = 'actif' OR u.abonnement_status = 1)
                AND u.id_ville = ?
                ORDER BY RAND() 
                LIMIT 9
            ");
            $stmt2->execute([$id_ville_client]);
            $coiffeurs_fallback = $stmt2->fetchAll();
            
            if (!empty($coiffeurs_fallback)) {
                echo "<p class='success'>✓ Fallback réussi : " . count($coiffeurs_fallback) . " coiffeur(s) trouvé(s) dans toute la ville</p>";
                echo "<pre>";
                print_r($coiffeurs_fallback);
                echo "</pre>";
            } else {
                echo "<p class='error'>✗ Aucun coiffeur trouvé même avec le fallback</p>";
                echo "<p>🔧 <strong>Actions à effectuer :</strong></p>";
                echo "<ul>";
                echo "<li>Vérifier que des coiffeurs existent dans votre ville</li>";
                echo "<li>S'assurer qu'ils sont approuvés (is_approved = 1)</li>";
                echo "<li>Vérifier que leur statut est 'actif'</li>";
                echo "<li>Vérifier que leur abonnement est actif</li>";
                echo "</ul>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Erreur SQL : " . $e->getMessage() . "</p>";
    }
    echo "</div>";
}

echo "<div class='section'>";
echo "<h2>💡 Actions Recommandées</h2>";
echo "<ol>";
echo "<li>Si aucun coiffeur n'apparaît, vérifiez qu'ils ont un abonnement actif</li>";
echo "<li>Vérifiez que is_approved = 1 pour les coiffeurs</li>";
echo "<li>Vérifiez que le statut est 'actif' (pas 'banni')</li>";
echo "<li>Actualisez le dashboard client après ces vérifications</li>";
echo "</ol>";
echo "</div>";

echo "<p style='margin-top: 30px;'><a href='/coiffons/index.php?page=dashboard' style='color: #d4af37;'>← Retour au Dashboard</a></p>";
echo "</body></html>";
