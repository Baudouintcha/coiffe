<?php
session_start();

// 1. Sécurité : Vérification stricte du rôle d'administrateur
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: connexion.php");
    exit();
}

// 2. Connexion à la base de données
try {
    $bdd = new PDO('mysql:host=localhost;dbname=cft;charset=utf8', 'root', '');
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}

// 3. Traitement : Validation de l'abonnement d'un coiffeur (1500 FCFA)
if (isset($_GET['action']) && $_GET['action'] === 'valider_abonnement' && isset($_GET['id'])) {
    $id_abonnement = intval($_GET['id']);
    
    // On passe le statut de l'abonnement à 'actif'
    $req = $bdd->prepare("UPDATE abonnements SET statut_abonnement = 'actif' WHERE id_abonnement = ?");
    $req->execute([$id_abonnement]);
    
    // Redirection vers le dashboard avec un message de succès
    header("Location: admin_dashboard.php?success=abonnement_valide");
    exit();
}

// 4. Traitement : Validation du retrait d'un coiffeur (Mobile Money)
if (isset($_GET['action']) && $_GET['action'] === 'valider_retrait' && isset($_GET['id'])) {
    $id_transaction = intval($_GET['id']);
    
    // On récupère les détails de la transaction pour connaître le montant et le portefeuille lié
    $reqTx = $bdd->prepare("SELECT id_portefeuille, montant_net FROM transactions WHERE id_transaction = ? AND type_transaction = 'retrait'");
    $reqTx->execute([$id_transaction]);
    $tx = $reqTx->fetch();
    
    if ($tx) {
        $id_portefeuille = $tx['id_portefeuille'];
        $montant_retire = abs($tx['montant_net']); // On s'assure d'avoir une valeur positive
        
        // A. On valide le paiement de la transaction en la passant à 'complete'
        $updateTx = $bdd->prepare("UPDATE transactions SET statut_paiement = 'complete' WHERE id_transaction = ?");
        $updateTx->execute([$id_transaction]);
        
        // B. On déduit définitivement l'argent du solde réel (solde_total) du coiffeur
        $updatePortefeuille = $bdd->prepare("UPDATE portefeuille SET solde_total = solde_total - ? WHERE id_portefeuille = ?");
        $updatePortefeuille->execute([$montant_retire, $id_portefeuille]);
    }
    
    // Redirection vers le dashboard avec un message de succès
    header("Location: admin_dashboard.php?success=retrait_valide");
    exit();
}

// Si quelqu'un atterrit ici sans action précise, on le renvoie au dashboard
header("Location: admin_dashboard.php");
exit();