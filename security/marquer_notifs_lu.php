<?php
/**
 * security/marquer_notifs_lu.php — Endpoint AJAX
 * Marque toutes les notifications non lues de l'utilisateur connecté comme lues.
 * Design System v2.0 — Coiffe Chez Toi V1.0
 */

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../security/config.php';

if (!isset($_SESSION['id_user'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Non autorisé']);
    exit();
}

try {
    $pdo->prepare(
        "UPDATE notifications SET lu = 1 WHERE user_id = ?"
    )->execute([$_SESSION['id_user']]);

    echo json_encode(['status' => 'ok']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur']);
}
