<?php
/**
 * debug_annuaire.php — Diagnostic temporaire
 * À SUPPRIMER après correction
 */
require_once __DIR__ . '/security/config.php';

echo '<pre style="background:#111;color:#eee;padding:20px;font-family:monospace;">';

// 1. Tous les coiffeurs, sans filtre
$stmt = $pdo->query("SELECT id, nom, prenom, role, is_approved, abonnement_status FROM users WHERE role = 'coiffeur'");
$rows = $stmt->fetchAll();

echo "=== COIFFEURS EN BDD ===\n";
if (empty($rows)) {
    echo "⚠️  Aucun utilisateur avec role='coiffeur' trouvé.\n";
} else {
    foreach ($rows as $r) {
        $approved = $r['is_approved'] ? '✅ approved=1' : '❌ approved=0';
        echo "ID:{$r['id']} | {$r['nom']} {$r['prenom']} | {$approved} | abo_status:{$r['abonnement_status']}\n";
    }
}

// 2. Coiffeurs avec is_approved = 1
echo "\n=== COIFFEURS APPROUVÉS (is_approved=1) ===\n";
$stmt2 = $pdo->query("SELECT id, nom, prenom FROM users WHERE role='coiffeur' AND is_approved=1");
$rows2 = $stmt2->fetchAll();
if (empty($rows2)) {
    echo "⚠️  Aucun coiffeur avec is_approved=1 — c'est la cause du problème.\n";
} else {
    foreach ($rows2 as $r) {
        echo "ID:{$r['id']} | {$r['nom']} {$r['prenom']}\n";
    }
}

// 3. Simulation de la requête annuaire avec un fragment
echo "\n=== TEST REQUÊTE ANNUAIRE (sans filtre ville/quartier) ===\n";
$sql = "SELECT DISTINCT u.id, u.nom, u.prenom, u.is_approved
        FROM users u
        WHERE u.role = 'coiffeur' AND u.is_approved = 1";
$stmt3 = $pdo->query($sql);
$rows3 = $stmt3->fetchAll();
echo count($rows3) . " coiffeur(s) retourné(s) par la requête principale.\n";

echo '</pre>';
echo '<p style="color:red;font-family:monospace;padding:10px;">⚠️ Supprimer ce fichier après diagnostic : /coiffons/debug_annuaire.php</p>';
