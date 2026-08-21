<?php
/**
 * debug_approve_coiffeurs.php — Prépare les coiffeurs de test pour validation admin
 *
 * Étape 1 : Active abonnement + assigne diplôme fictif → apparaissent dans la liste admin
 * Étape 2 : Tu vas sur admin_dashboard → Validations → Approuver
 *
 * À SUPPRIMER après utilisation
 */
require_once __DIR__ . '/security/config.php';
require_once __DIR__ . '/security/notifications.php';

echo '<style>
body { background:#0a0a0a; color:#eee; font-family:monospace; padding:30px; line-height:2; }
h2 { color:#D4AF37; }
.ok  { color:#6EE7B7; }
.err { color:#FF6B6B; }
a { color:#D4AF37; }
</style>';

echo '<h2>🔧 Préparation des coiffeurs de test</h2>';

// Récupérer les coiffeurs
$coiffeurs = $pdo->query(
    "SELECT id, nom, prenom, abonnement_status, is_approved, diplome FROM users WHERE role='coiffeur'"
)->fetchAll();

echo '<h3 style="color:#aaa;">État actuel</h3><pre>';
foreach ($coiffeurs as $c) {
    $abo = $c['abonnement_status'] ? '✅ abo' : '❌ abo';
    $app = $c['is_approved']       ? '✅ approved' : '❌ approved';
    $dip = $c['diplome']           ? '📄 diplôme: ' . $c['diplome'] : '⚠️ pas de diplôme';
    echo "ID:{$c['id']} | {$c['nom']} {$c['prenom']} | {$abo} | {$app} | {$dip}\n";
}
echo '</pre>';

// Étape 1 : activer abonnement + assigner diplôme fictif pour ceux qui n'en ont pas
$expiration = date('Y-m-d', strtotime('+30 days'));
$diplome_fictif = 'uploads/diplomes/test_diplome.jpg'; // chemin fictif pour la démo

// Mise à jour dans profils_prestataires (pour abonnement_status)
$stmt_profil = $pdo->prepare("
    UPDATE profils_prestataires pp
    INNER JOIN users u ON u.id = pp.user_id
    SET pp.abonnement_status = 'actif',
        pp.date_expiration_abo = ?,
        pp.diplome = CASE
            WHEN (pp.diplome IS NULL OR pp.diplome = '') THEN ?
            ELSE pp.diplome
        END
    WHERE u.role = 'coiffeur' AND u.is_approved = 0
");
$stmt_profil->execute([$expiration, $diplome_fictif]);
$nb = $stmt_profil->rowCount();

echo "<p class='ok'>✅ {$nb} coiffeur(s) préparés : abonnement activé + diplôme enregistré.</p>";
echo "<p style='color:#aaa;font-size:.85em;'>Les coiffeurs sans diplôme réel ont reçu un diplôme fictif pour la démo.</p>";

// Notifier les admins qu'il y a des diplômes à valider
try {
    $admins = $pdo->query("SELECT id FROM users WHERE role='admin'")->fetchAll();
    if (!empty($admins)) {
        $msg = '📋 ' . $nb . ' coiffeur(s) ont payé leur abonnement et soumis un diplôme. Veuillez valider leurs profils dans l\'onglet Validations.';
        $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, type, lu, date_demande) VALUES (?, ?, 'info', 0, NOW())");
        foreach ($admins as $admin) {
            $stmt_notif->execute([$admin['id'], $msg]);
        }
        echo "<p class='ok'>✅ Admins notifiés.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:#aaa;'>ℹ️ Pas d'admin en BDD ou notifications non disponibles.</p>";
}

echo '<hr style="border-color:#333;margin:20px 0;">';
echo '<h3 style="color:#aaa;">Prochaines étapes</h3>';
echo '<ol style="line-height:2.2;">';
echo '<li>👉 <a href="/coiffons/first/admin_dashboard.php">Aller au Dashboard Admin</a></li>';
echo '<li>Cliquer sur l\'onglet <strong style="color:#D4AF37;">3. Validations &amp; Flux</strong></li>';
echo '<li>Dans la section <strong>"Diplômes en attente"</strong>, cliquer <strong>"Approuver"</strong> pour chaque coiffeur</li>';
echo '<li>Chaque approbation : is_approved = 1 + notification coiffeur automatique</li>';
echo '<li>✅ Les coiffeurs seront alors visibles dans l\'annuaire</li>';
echo '</ol>';

echo '<p class="err">⚠️ Supprime ce fichier : /coiffons/debug_approve_coiffeurs.php</p>';
