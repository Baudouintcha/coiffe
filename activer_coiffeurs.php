<?php
/**
 * Script pour activer tous les coiffeurs
 * À utiliser uniquement en développement
 */

require_once __DIR__ . '/security/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>
body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
.section { background: #2a2a2a; padding: 15px; margin: 10px 0; border-radius: 8px; }
.success { color: #4caf50; }
.error { color: #f44336; }
.warning { color: #ff9800; }
</style></head><body>";

echo "<h1>⚙️ Activation des Coiffeurs</h1>";

if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    echo "<div class='section'>";
    echo "<h2>Activation en cours...</h2>";
    
    try {
        // Activer tous les coiffeurs
        $stmt = $pdo->exec("
            UPDATE users 
            SET is_approved = 1,
                statut = 'actif',
                abonnement_status = 'actif',
                date_expiration_abo = DATE_ADD(NOW(), INTERVAL 1 MONTH)
            WHERE role = 'coiffeur'
        ");
        
        echo "<p class='success'>✓ $stmt coiffeur(s) activé(s) avec succès</p>";
        echo "<p>Les coiffeurs ont maintenant :</p>";
        echo "<ul>";
        echo "<li>is_approved = 1</li>";
        echo "<li>statut = 'actif'</li>";
        echo "<li>abonnement_status = 'actif'</li>";
        echo "<li>date_expiration_abo = +1 mois</li>";
        echo "</ul>";
        
        // Afficher les coiffeurs activés
        $coiffeurs = $pdo->query("
            SELECT u.id, u.nom, u.prenom, v.nom_ville, q.nom_quartier
            FROM users u
            LEFT JOIN villes v ON u.ville = v.id
            LEFT JOIN quartiers q ON u.id_quartier = q.id
            WHERE u.role = 'coiffeur'
            ORDER BY u.id
        ")->fetchAll();
        
        if (!empty($coiffeurs)) {
            echo "<h3>Liste des coiffeurs :</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%; color: #fff;'>";
            echo "<tr><th>ID</th><th>Nom</th><th>Ville</th><th>Quartier</th></tr>";
            foreach ($coiffeurs as $c) {
                echo "<tr>";
                echo "<td>{$c['id']}</td>";
                echo "<td>{$c['prenom']} {$c['nom']}</td>";
                echo "<td>{$c['nom_ville']}</td>";
                echo "<td>{$c['nom_quartier']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<p style='margin-top: 20px;'><a href='/coiffons/index.php?page=dashboard' style='color: #d4af37;'>→ Aller au Dashboard Client</a></p>";
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Erreur : " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
} else {
    // Formulaire de confirmation
    echo "<div class='section'>";
    echo "<h2>⚠️ Confirmation Requise</h2>";
    echo "<p>Ce script va activer TOUS les coiffeurs de la base de données :</p>";
    echo "<ul>";
    echo "<li>Approuver tous les comptes (is_approved = 1)</li>";
    echo "<li>Définir le statut sur 'actif'</li>";
    echo "<li>Activer l'abonnement (abonnement_status = 'actif')</li>";
    echo "<li>Définir une date d'expiration à +1 mois</li>";
    echo "</ul>";
    
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coiffeur'")->fetchColumn();
        echo "<p class='warning'><strong>$count coiffeur(s) sera(ont) affecté(s)</strong></p>";
    } catch (Exception $e) {
        echo "<p class='error'>Erreur : " . $e->getMessage() . "</p>";
    }
    
    echo "<form method='POST'>";
    echo "<input type='hidden' name='confirm' value='yes'>";
    echo "<button type='submit' style='background: #4caf50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;'>Confirmer l'activation</button>";
    echo "</form>";
    
    echo "<p style='margin-top: 20px;'><a href='/coiffons/test_dashboard_client.php' style='color: #d4af37;'>← Retour au test</a></p>";
    echo "</div>";
}

echo "</body></html>";
