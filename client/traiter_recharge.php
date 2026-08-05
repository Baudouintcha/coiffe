<?php
/**
 * client/traiter_recharge.php — Recharge du portefeuille client
 * Utilise PaymentService (couche abstraction paiement)
 * Architecture prête pour Kkiapay
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../security/config.php';
require_once __DIR__ . '/../security/PaymentService.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

$id_client = (int)$_SESSION['id_user'];
$montant   = intval($_POST['montant'] ?? 0);

$payment = new PaymentService($pdo);
$result  = $payment->rechargerPortefeuille($id_client, (float)$montant, 'simulation');

if ($result['success']) {
    // Notification in-app
    require_once __DIR__ . '/../security/notifications.php';
    notifier($pdo, $id_client,
        number_format($montant, 0, ',', ' ') . ' FCFA rechargés sur votre portefeuille.',
        'success'
    );
    $_SESSION['flash_success'] = $result['message'];
} else {
    $_SESSION['flash_danger'] = $result['message'];
}

header('Location: /coiffons/profil.php');
exit();
