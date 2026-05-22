<?php
require_once __DIR__ . '/../security/config.php';

header('Content-Type: application/json');

if (isset($_GET['id_ville'])) {
    $id_ville = intval($_GET['id_ville']);
    
    $stmt = $pdo->prepare("SELECT id, nom_quartier FROM quartiers WHERE id_ville = ? ORDER BY nom_quartier ASC");
    $stmt->execute([$id_ville]);
    $quartiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($quartiers);
    exit();
}

echo json_encode([]);
exit();