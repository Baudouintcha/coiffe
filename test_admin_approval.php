<?php
/**
 * TEST: Vérifier le parcours d'approbation des coiffeurs
 * 
 * À exécuter via: http://localhost/coiffons/test_admin_approval.php
 * 
 * Ce script test:
 * 1. La récupération des données $diplomes_attente
 * 2. La présence de l'action approuver_diplome dans admin_actions.php
 * 3. La mise à jour correcte de is_approved en BD
 * 4. L'affichage dans l'annuaire après approbation
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/security/config.php';

// Récupérer les diplômes en attente
try {
    $diplomes_attente = $pdo->query("
        SELECT id, nom, prenom, telephone, diplome, created_at, is_approved
        FROM users
        WHERE role = 'coiffeur'
          AND diplome IS NOT NULL
          AND diplome != ''
        ORDER BY created_at ASC
        LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $diplomes_attente = [];
}

// Récupérer l'état des coiffeurs avec is_approved
try {
    $coiffeurs_status = $pdo->query("
        SELECT id, nom, prenom, is_approved, abonnement_status
        FROM users
        WHERE role = 'coiffeur'
        ORDER BY id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $coiffeurs_status = [];
}

// Récupérer les coiffeurs approuvés (visible dans annuaire)
try {
    $coiffeurs_approuves = (int)$pdo->query("
        SELECT COUNT(*) FROM users WHERE role = 'coiffeur' AND is_approved = 1
    ")->fetchColumn();
} catch (Exception $e) {
    $coiffeurs_approuves = 0;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Admin Approval Flow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: 'Courier New', monospace;
        }
        .bg-dark-custom {
            background-color: #0c0c0c;
            border: 1px solid #222;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status-ok { color: #2ecc71; font-weight: bold; }
        .status-warning { color: #f39c12; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .code-block {
            background-color: #1a1a1a;
            border-left: 3px solid #D4AF37;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="text-gold mb-4"><i class="bi bi-bug"></i> TEST: Parcours d'Approbation Coiffeurs</h1>

        <!-- TEST 1: Récupération des Diplômes -->
        <div class="bg-dark-custom">
            <h3><i class="bi bi-1-circle-fill"></i> Données Récupérées</h3>
            
            <p><strong>Diplômes en attente:</strong> 
                <span class="<?php echo count($diplomes_attente) > 0 ? 'status-warning' : 'status-ok'; ?>">
                    <?php echo count($diplomes_attente); ?> coiffeur(s)
                </span>
            </p>

            <?php if (count($diplomes_attente) > 0): ?>
                <table class="table table-dark table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th><th>Nom</th><th>is_approved</th><th>Diplôme</th><th>Actions Test</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diplomes_attente as $dip): ?>
                            <tr>
                                <td><?php echo $dip['id']; ?></td>
                                <td><?php echo htmlspecialchars($dip['nom'] . ' ' . $dip['prenom']); ?></td>
                                <td>
                                    <span class="<?php echo $dip['is_approved'] ? 'status-ok' : 'status-error'; ?>">
                                        <?php echo $dip['is_approved'] ? '✅ 1 (Approuvé)' : '❌ 0 (En attente)'; ?>
                                    </span>
                                </td>
                                <td><?php echo $dip['diplome'] ? '✅ Oui' : '❌ Non'; ?></td>
                                <td>
                                    <a href="first/admin_actions.php?action=approuver_diplome&id=<?php echo $dip['id']; ?>" 
                                       class="btn btn-sm btn-success" target="_blank">
                                        Approuver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- TEST 2: État Global des Coiffeurs -->
        <div class="bg-dark-custom">
            <h3><i class="bi bi-2-circle-fill"></i> État de Tous les Coiffeurs</h3>
            
            <p>
                <strong>Coiffeurs approuvés (visible annuaire):</strong> 
                <span class="<?php echo $coiffeurs_approuves > 0 ? 'status-ok' : 'status-error'; ?>">
                    <?php echo $coiffeurs_approuves; ?>/<?php echo count($coiffeurs_status); ?>
                </span>
            </p>

            <table class="table table-dark table-striped table-sm">
                <thead>
                    <tr>
                        <th>ID</th><th>Nom</th><th>is_approved</th><th>abonnement_status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coiffeurs_status as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['nom'] . ' ' . $c['prenom']); ?></td>
                            <td>
                                <span class="<?php echo $c['is_approved'] ? 'status-ok' : 'status-error'; ?>">
                                    <?php echo $c['is_approved'] ? '✅ 1' : '❌ 0'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="<?php echo $c['abonnement_status'] ? 'status-ok' : 'status-warning'; ?>">
                                    <?php echo $c['abonnement_status'] ? '✅ Actif' : '❌ Inactif'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- TEST 3: Vérification du Code -->
        <div class="bg-dark-custom">
            <h3><i class="bi bi-3-circle-fill"></i> Vérification du Code</h3>
            
            <?php
            $admin_dashboard_content = file_get_contents(__DIR__ . '/first/admin_dashboard.php');
            $admin_actions_content = file_get_contents(__DIR__ . '/first/admin_actions.php');
            
            $has_diplomes_section = strpos($admin_dashboard_content, '$diplomes_attente') !== false;
            $has_approuver_action = strpos($admin_actions_content, "case 'approuver_diplome'") !== false;
            $has_valider_abo_action = strpos($admin_actions_content, "case 'valider_abonnement'") !== false;
            $has_valider_retrait_action = strpos($admin_actions_content, "case 'valider_retrait'") !== false;
            ?>

            <table class="table table-dark">
                <thead>
                    <tr><th>Vérification</th><th>Résultat</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Section Diplômes affichée dans dashboard</td>
                        <td>
                            <span class="<?php echo $has_diplomes_section ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $has_diplomes_section ? '✅ Trouvée' : '❌ Manquante'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Action 'approuver_diplome' dans admin_actions.php</td>
                        <td>
                            <span class="<?php echo $has_approuver_action ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $has_approuver_action ? '✅ Implémentée' : '❌ Manquante'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Action 'valider_abonnement' dans admin_actions.php</td>
                        <td>
                            <span class="<?php echo $has_valider_abo_action ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $has_valider_abo_action ? '✅ Implémentée' : '❌ Manquante'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>Action 'valider_retrait' dans admin_actions.php</td>
                        <td>
                            <span class="<?php echo $has_valider_retrait_action ? 'status-ok' : 'status-error'; ?>">
                                <?php echo $has_valider_retrait_action ? '✅ Implémentée' : '❌ Manquante'; ?>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TEST 4: Recommandations -->
        <div class="bg-dark-custom">
            <h3><i class="bi bi-4-circle-fill"></i> Prochaines Étapes</h3>
            
            <p><strong>Pour débloquer l'annuaire:</strong></p>
            <ol>
                <li>Admin se connecte à <code>/first/admin_dashboard.php</code></li>
                <li>Aller à l'onglet "Validations & Flux"</li>
                <li>Section "Diplômes en Attente" affiche les coiffeurs avec diplômes</li>
                <li>Cliquer sur "Approuver" pour chaque coiffeur</li>
                <li>Le coiffeur passe à <code>is_approved = 1</code></li>
                <li>Le coiffeur apparaît dans l'annuaire (`/filter/annuaire_coiffeurs.php`)</li>
            </ol>

            <p class="mt-3"><strong>Commandes SQL de vérification rapide:</strong></p>
            <div class="code-block">
-- Vérifier les coiffeurs en attente
SELECT id, nom, prenom, is_approved FROM users WHERE role = 'coiffeur' AND is_approved = 0;

-- Approuver un coiffeur (remplacer 17 par l'ID)
UPDATE users SET is_approved = 1 WHERE id = 17;

-- Vérifier les approuvés
SELECT COUNT(*) FROM users WHERE role = 'coiffeur' AND is_approved = 1;
            </div>
        </div>

        <div class="mt-4 text-center">
            <a href="first/admin_dashboard.php" class="btn btn-primary btn-lg">
                <i class="bi bi-arrow-right-circle"></i> Aller au Dashboard Admin
            </a>
            <a href="filter/annuaire_coiffeurs.php" class="btn btn-warning btn-lg ms-2">
                <i class="bi bi-search"></i> Tester l'Annuaire
            </a>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
