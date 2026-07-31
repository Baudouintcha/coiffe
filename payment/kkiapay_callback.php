<?php
/**
 * payment/kkiapay_callback.php — Webhook Kkiapay (Production uniquement)
 * Ce fichier reçoit les notifications de paiement de Kkiapay
 * À activer après la soutenance avec les vraies clés API
 * 
 * Flux attendu :
 * 1. Kkiapay envoie POST avec {transactionId, status, amount}
 * 2. Vérifier la signature avec KKIAPAY_PRIVATE_KEY
 * 3. Si status = 'SUCCESS' → activer abonnement ou créditer solde
 * 
 * Pour activer :
 * - Définir KKIAPAY_PRIVATE_KEY dans .env
 * - Décommenter le bloc de traitement ci-dessous
 * - Enregistrer l'URL de ce fichier dans le dashboard Kkiapay
 */

// ── MODE SIMULATION : webhook non actif ──
// Supprimer cette ligne en production
http_response_code(200);
exit();

// ── PRODUCTION (à décommenter après soutenance) ──
/*
require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/PaymentService.php';

header('Content-Type: application/json');

$payload = json_decode(file_get_contents('php://input'), true);

if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload invalide']);
    exit();
}

// Vérification signature (à implémenter avec le SDK Kkiapay)
// $signature = $_SERVER['HTTP_X_KKIAPAY_SIGNATURE'] ?? '';
// if (!verifyKkiapaySignature($payload, $signature, $_ENV['KKIAPAY_PRIVATE_KEY'])) {
//     http_response_code(403);
//     exit();
// }

$paymentService = new PaymentService($pdo);
$paymentService->handleKkiapayWebhook($payload);

http_response_code(200);
echo json_encode(['status' => 'ok']);
*/
