<?php
/**
 * test_notifications.php — Test du système de notifications
 * 
 * À appeler depuis le terminal ou navigateur pour vérifier les notifications
 * Usage : http://localhost/coiffons/test_notifications.php
 */

session_start();
require_once __DIR__ . '/security/config.php';

$test_results = [];

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>Test Notifications — Coiffe Chez Toi</title>
<style>
body { background: #0a0a0a; color: #fff; font-family: 'Inter', sans-serif; padding: 2rem; }
.container { max-width: 600px; margin: 0 auto; }
h1 { color: #D4AF37; margin-bottom: 1.5rem; }
.test-group { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
.test-item { display: flex; align-items: center; gap: 10px; padding: 0.75rem 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
.test-item:last-child { border-bottom: none; }
.test-status { 
    display: inline-flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 50%;
    font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
}
.status-ok { background: #6ee7b7; color: #000; }
.status-error { background: #ffb3b3; color: #000; }
.status-warning { background: #ffd60a; color: #000; }
.test-message { flex: 1; font-size: 0.9rem; }
.code { background: rgba(255,255,255,0.08); border-left: 2px solid #D4AF37; padding: 0.75rem; border-radius: 4px; font-family: 'Courier', monospace; font-size: 0.85rem; margin-top: 1rem; overflow-x: auto; }
</style>
</head>
<body>
<div class='container'>
<h1>🔔 Test du système de notifications</h1>
";

// ── TEST 1 : Vérifier la connexion à la BD ──
echo "<div class='test-group'>
<h2 style='margin-bottom: 1rem; font-size: 1rem; color: #D4AF37;'>Test 1 : Connexion à la base de données</h2>";

try {
    $test = $pdo->query("SELECT 1");
    echo "<div class='test-item'>
    <div class='test-status status-ok'>✓</div>
    <div class='test-message'>Connexion PDO établie</div>
    </div>";
} catch (Exception $e) {
    echo "<div class='test-item'>
    <div class='test-status status-error'>✗</div>
    <div class='test-message'>Erreur de connexion : " . $e->getMessage() . "</div>
    </div>";
}

// ── TEST 2 : Vérifier la table notifications ──
echo "<h2 style='margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1rem; color: #D4AF37;'>Test 2 : Table notifications</h2>";

try {
    $result = $pdo->query("DESCRIBE notifications");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN, 0);
    
    if (in_array('id_notification', $columns)) {
        echo "<div class='test-item'>
        <div class='test-status status-ok'>✓</div>
        <div class='test-message'>Table notifications existe</div>
        </div>";
    } else {
        echo "<div class='test-item'>
        <div class='test-status status-error'>✗</div>
        <div class='test-message'>Table notifications existe mais structure invalide</div>
        </div>";
    }
    
    echo "<div class='code'><strong>Colonnes trouvées :</strong><br>" . implode(", ", $columns) . "</div>";
} catch (Exception $e) {
    echo "<div class='test-item'>
    <div class='test-status status-error'>✗</div>
    <div class='test-message'>Table notifications n'existe pas : " . $e->getMessage() . "</div>
    </div>";
}

// ── TEST 3 : Vérifier les notifications non lues ──
echo "</div><div class='test-group'>
<h2 style='margin-bottom: 1rem; font-size: 1rem; color: #D4AF37;'>Test 3 : Notifications non lues</h2>";

try {
    $total = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    $nonlues = $pdo->query("SELECT COUNT(*) FROM notifications WHERE statut_lecture = 'non_lu'")->fetchColumn();
    
    echo "<div class='test-item'>
    <div class='test-status status-ok'>✓</div>
    <div class='test-message'>Total notifications : $total | Non lues : $nonlues</div>
    </div>";
    
    if ($nonlues > 0) {
        echo "<div class='code'><strong>Dernières notifications non lues :</strong><br>";
        $derniers = $pdo->query("
            SELECT n.*, u.prenom, u.nom, u.role 
            FROM notifications n
            LEFT JOIN users u ON n.id_user = u.id
            WHERE n.statut_lecture = 'non_lu'
            ORDER BY n.date_notification DESC
            LIMIT 5
        ")->fetchAll();
        
        foreach ($derniers as $n) {
            echo htmlspecialchars($n['prenom'] . ' ' . $n['nom'] . ' (' . $n['role'] . ')') . "<br>";
            echo "Message : " . htmlspecialchars(substr($n['message'], 0, 60) . '...') . "<br>";
            echo "Date : " . $n['date_notification'] . "<br><br>";
        }
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item'>
    <div class='test-status status-error'>✗</div>
    <div class='test-message'>Erreur lors de la requête : " . $e->getMessage() . "</div>
    </div>";
}

// ── TEST 4 : Vérifier la fonction notifier ──
echo "</div><div class='test-group'>
<h2 style='margin-bottom: 1rem; font-size: 1rem; color: #D4AF37;'>Test 4 : Fonction notifier</h2>";

require_once __DIR__ . '/security/notifications.php';

if (function_exists('notifier')) {
    echo "<div class='test-item'>
    <div class='test-status status-ok'>✓</div>
    <div class='test-message'>Fonction notifier() disponible</div>
    </div>";
} else {
    echo "<div class='test-item'>
    <div class='test-status status-error'>✗</div>
    <div class='test-message'>Fonction notifier() n'existe pas</div>
    </div>";
}

// ── TEST 5 : Compter les coiffeurs avec demandes en attente ──
echo "</div><div class='test-group'>
<h2 style='margin-bottom: 1rem; font-size: 1rem; color: #D4AF37;'>Test 5 : Demandes de RDV en attente</h2>";

try {
    $demandes = $pdo->query("
        SELECT COUNT(*) as total,
               COUNT(DISTINCT r.coiffeur_id) as coiffeurs_affectes
        FROM rendez_vous r
        WHERE r.statut_rdv = 'en_attente'
    ")->fetch();
    
    echo "<div class='test-item'>
    <div class='test-status status-ok'>✓</div>
    <div class='test-message'>Demandes en attente : " . $demandes['total'] . " | Coiffeurs affectés : " . $demandes['coiffeurs_affectes'] . "</div>
    </div>";
    
    if ($demandes['total'] > 0) {
        echo "<div class='code'><strong>Dernières demandes :</strong><br>";
        $derniers_rdv = $pdo->query("
            SELECT r.*, u.prenom, u.nom, p.nom_style, c.prenom as coiffeur_prenom, c.nom as coiffeur_nom
            FROM rendez_vous r
            JOIN users u ON r.client_id = u.id
            JOIN prestations p ON r.coiffure_id = p.id_prestation
            JOIN users c ON r.coiffeur_id = c.id
            WHERE r.statut_rdv = 'en_attente'
            ORDER BY r.date_demande DESC
            LIMIT 3
        ")->fetchAll();
        
        foreach ($derniers_rdv as $rdv) {
            echo "Client : " . htmlspecialchars($rdv['prenom'] . ' ' . $rdv['nom']) . "<br>";
            echo "Coiffeur : " . htmlspecialchars($rdv['coiffeur_prenom'] . ' ' . $rdv['coiffeur_nom']) . "<br>";
            echo "Service : " . htmlspecialchars($rdv['nom_style']) . "<br>";
            echo "Date demande : " . $rdv['date_demande'] . "<br><br>";
        }
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='test-item'>
    <div class='test-status status-warning'>⚠</div>
    <div class='test-message'>Erreur : " . $e->getMessage() . "</div>
    </div>";
}

// ── TEST 6 : Vérifier le session utilisateur ──
echo "</div><div class='test-group'>
<h2 style='margin-bottom: 1rem; font-size: 1rem; color: #D4AF37;'>Test 6 : Session utilisateur</h2>";

if (isset($_SESSION['id_user'])) {
    $user_role = $_SESSION['role'] ?? 'unknown';
    echo "<div class='test-item'>
    <div class='test-status status-ok'>✓</div>
    <div class='test-message'>Utilisateur connecté : ID=" . $_SESSION['id_user'] . " | Rôle=$user_role</div>
    </div>";
} else {
    echo "<div class='test-item'>
    <div class='test-status status-warning'>⚠</div>
    <div class='test-message'>Aucun utilisateur connecté (vous êtes en mode invité)</div>
    </div>";
}

echo "</div></div></body></html>";
