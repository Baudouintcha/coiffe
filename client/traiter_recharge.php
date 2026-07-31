<?php
/**
 * client/traiter_recharge.php — Traitement de la recharge simulée du portefeuille client
 * Simulation de paiement — Architecture prête pour Kkiapay
 * ⚠️ LOGIQUE MÉTIER RÉELLE — SQL inchangeable
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../security/config.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'client') {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

$id_client = $_SESSION['id_user'];
$montant = intval($_POST['montant'] ?? 0);

if ($montant < 500) {
    $_SESSION['flash_danger'] = 'Montant minimum : 500 FCFA.';
    header('Location: /coiffons/profil.php');
    exit();
}

if ($montant > 500000) {
    $_SESSION['flash_danger'] = 'Montant maximum : 500 000 FCFA par recharge.';
    header('Location: /coiffons/profil.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Mise à jour du solde
    $stmt = $pdo->prepare("UPDATE users SET solde = solde + ? WHERE id = ?");
    $stmt->execute([$montant, $id_client]);
    
    // Historique de transaction
    $ins = $pdo->prepare("INSERT INTO transactions_portefeuille (user_id, type_transaction, montant, motif, date_creation) VALUES (?, 'recharge', ?, ?, NOW())");
    $ins->execute([$id_client, $montant, 'Recharge portefeuille (simulation)']);
    
    $pdo->commit();
    
    $_SESSION['flash_success'] = number_format($montant, 0, ',', ' ') . ' FCFA rechargés avec succès.';
    header('Location: /coiffons/profil.php');
    exit();
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_danger'] = 'Erreur lors de la recharge. Veuillez réessayer.';
    header('Location: /coiffons/profil.php');
    exit();
}
