<?php
require_once __DIR__ . '/../security/config.php';

header('Content-Type: application/json');

if (isset($_GET['id_ville'])) {
    $id_ville = intval($_GET['id_ville']);
    
    // 🎯 RECONNAISSANCE AUTOMATIQUE DES ZONES PROCHES
    // Si le mode "regroupement" est activé (espace coiffeur)
    if (isset($_GET['mode']) && $_GET['mode'] === 'regroupement') {
        
        // Logique : Si l'utilisateur choisit la ville 1 ou 2 (Ex: Cotonou/Calavi)
        // Le code détecte le duo et élargit la recherche aux deux IDs d'un coup
        if ($id_ville === 1 || $id_ville === 2) {
            $stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartiers WHERE id_ville IN (1, 2) ORDER BY nom_quartier ASC");
            $stmt->execute();
        } 
        // Si c'est une autre paire de villes proches (Ex: Porto-Novo et Sèmè-Kpodji, imaginons IDs 3 et 4)
        elseif ($id_ville === 3 || $id_ville === 4) {
            $stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartiers WHERE id_ville IN (3, 4) ORDER BY nom_quartier ASC");
            $stmt->execute();
        }
        // Sinon, pour toute autre ville éloignée, on reste strict
        else {
            $stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartiers WHERE id_ville = ? ORDER BY nom_quartier ASC");
            $stmt->execute([$id_ville]);
        }
        
    } else {
        // 🎯 MODE CLASSIQUE (Inscription & Annuaire)
        // On garde ta requête d'origine intacte pour ne rien déréglé ailleurs !
        $stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartiers WHERE id_ville = ? ORDER BY nom_quartier ASC");
        $stmt->execute([$id_ville]);
    }
    
    $quartiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($quartiers);
    exit();
}

echo json_encode([]);
exit();