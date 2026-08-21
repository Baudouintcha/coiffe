<?php
/**
 * coiffeurs/mes_avis.php — Historique des avis reçus par le coiffeur
 * Design System v2.0 — Parcours Coiffeur
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL inchangeable
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'prestataire') {
    header('Location: /coiffons/index.php?page=login');
    exit();
}

$id_coiffeur = $_SESSION['user_id'];

// Note moyenne + total avis
try {
    $stmt_stats = $pdo->prepare(
        "SELECT ROUND(AVG(note), 1) AS moy, COUNT(*) AS total
         FROM commentaires
         WHERE id_coiffeur = ? AND type_commentaire = 'public'"
    );
    $stmt_stats->execute([$id_coiffeur]);
    $stats = $stmt_stats->fetch();
    $note_moyenne = floatval($stats['moy'] ?? 0);
    $total_avis   = intval($stats['total'] ?? 0);
} catch (Exception $e) {
    $note_moyenne = 0;
    $total_avis   = 0;
}

// Distribution des notes (1 à 5)
$distribution = array_fill(1, 5, 0);
try {
    $stmt_dist = $pdo->prepare(
        "SELECT note, COUNT(*) AS nb FROM commentaires
         WHERE id_coiffeur = ? AND type_commentaire = 'public'
         GROUP BY note ORDER BY note DESC"
    );
    $stmt_dist->execute([$id_coiffeur]);
    foreach ($stmt_dist->fetchAll() as $row) {
        $distribution[(int)$row['note']] = (int)$row['nb'];
    }
} catch (Exception $e) {}

// Liste des avis publics — 20 derniers
try {
    $stmt_avis = $pdo->prepare(
        "SELECT c.*, u.nom AS client_nom, u.prenom AS client_prenom
         FROM commentaires c
         JOIN users u ON c.id_client = u.id
         WHERE c.id_coiffeur = ?
           AND c.type_commentaire = 'public'
           AND c.statut_moderation != 'rejete'
         ORDER BY c.date_demande DESC
         LIMIT 20"
    );
    $stmt_avis->execute([$id_coiffeur]);
    $avis_liste = $stmt_avis->fetchAll();
} catch (Exception $e) {
    $avis_liste = [];
}

include __DIR__ . '/../layout/header_coiffeur.php';
?>

<!-- ActionBar -->
<?php
$ab = [
    'icon'     => 'bi-star-half',
    'title'    => 'Mes Avis',
    'subtitle' => 'Ce que vos clients pensent de vous',
];
include __DIR__ . '/../views/components/action_bar.php';
?>

<!-- ── Résumé note globale ── -->
<div class="glass-cct p-4 mb-4">
    <div class="row g-4 align-items-center">

        <!-- Score global -->
        <div class="col-12 col-md-3 text-center">
            <div style="font-size:3.5rem;font-weight:800;color:var(--gold);line-height:1;">
                <?= $total_avis > 0 ? $note_moyenne : '—' ?>
            </div>
            <div style="font-size:1.2rem;color:var(--gold);letter-spacing:2px;margin:6px 0;">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span style="color:<?= $s <= round($note_moyenne) ? 'var(--gold)' : 'rgba(255,255,255,0.15)' ?>">★</span>
                <?php endfor; ?>
            </div>
            <div style="font-size:.78rem;color:var(--text-muted);">
                <?= $total_avis ?> avis client<?= $total_avis > 1 ? 's' : '' ?>
            </div>
        </div>

        <!-- Distribution barres -->
        <div class="col-12 col-md-9">
            <?php for ($n = 5; $n >= 1; $n--):
                $nb    = $distribution[$n];
                $pct   = $total_avis > 0 ? round(($nb / $total_avis) * 100) : 0;
            ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <span style="font-size:.75rem;color:var(--gold);min-width:20px;text-align:right;"><?= $n ?>★</span>
                <div style="flex:1;background:var(--dark-3);border-radius:20px;height:8px;overflow:hidden;">
                    <div style="width:<?= $pct ?>%;height:100%;background:<?= $n >= 4 ? 'var(--gold)' : ($n === 3 ? 'var(--warning-color)' : 'var(--danger-icon)') ?>;border-radius:20px;transition:width .6s ease;"></div>
                </div>
                <span style="font-size:.72rem;color:var(--text-muted);min-width:32px;"><?= $nb ?></span>
            </div>
            <?php endfor; ?>
        </div>

    </div>
</div>

<!-- ── Liste des avis ── -->
<?php if (empty($avis_liste)): ?>
    <?php
    $es = [
        'icon'    => 'bi-chat-square-text',
        'message' => 'Aucun avis reçu pour le moment. Les avis apparaîtront ici après chaque prestation.',
    ];
    include __DIR__ . '/../views/components/empty_state.php';
    ?>
<?php else: ?>
    <div class="glass-cct" style="overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid var(--glass-border);">
            <span style="font-size:.88rem;font-weight:700;color:var(--text-primary);">
                Derniers avis reçus
            </span>
        </div>

        <?php foreach ($avis_liste as $avis):
            $note_avis = intval($avis['note'] ?? 0);
            $initiale  = strtoupper(substr($avis['client_prenom'] ?? 'C', 0, 1));
            $prenom_masque = strtoupper(substr($avis['client_prenom'] ?? 'C', 0, 1)) . '***';
            $nom_masque    = strtoupper(substr($avis['client_nom'] ?? 'C', 0, 1)) . '.';

            // Date relative
            $date_obj = new DateTime($avis['date_demande'] ?? 'now');
            $now_obj  = new DateTime();
            $diff_d   = (int)$now_obj->diff($date_obj)->days;
            if ($diff_d === 0)       $date_rel = "Aujourd'hui";
            elseif ($diff_d === 1)   $date_rel = "Hier";
            elseif ($diff_d < 7)     $date_rel = "Il y a $diff_d jours";
            elseif ($diff_d < 30)    $date_rel = "Il y a " . floor($diff_d / 7) . " sem.";
            else                     $date_rel = date('d/m/Y', strtotime($avis['date_demande']));
        ?>
            <div style="padding:16px 20px;border-bottom:1px solid rgba(255,255,255,0.04);display:flex;gap:14px;align-items:flex-start;">
                <!-- Initiale avatar -->
                <div style="width:38px;height:38px;border-radius:50%;background:rgba(212,175,55,0.12);border:1px solid rgba(212,175,55,0.25);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--gold);font-size:.85rem;flex-shrink:0;">
                    <?= htmlspecialchars($initiale) ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <!-- Header avis -->
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;flex-wrap:wrap;gap:6px;">
                        <div>
                            <span style="font-size:.82rem;font-weight:600;color:var(--text-primary);">
                                <?= htmlspecialchars($prenom_masque . ' ' . $nom_masque) ?>
                            </span>
                            <span style="font-size:.7rem;color:var(--text-muted);margin-left:8px;">
                                <?= htmlspecialchars($date_rel) ?>
                            </span>
                        </div>
                        <!-- Étoiles -->
                        <div style="font-size:.85rem;" aria-label="Note : <?= $note_avis ?>/5">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <span style="color:<?= $s <= $note_avis ? '#D4AF37' : 'rgba(255,255,255,0.15)' ?>;">★</span>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <!-- Commentaire -->
                    <?php if (!empty($avis['message'])): ?>
                        <p style="color:var(--text-secondary);font-size:.82rem;line-height:1.6;margin:0;">
                            "<?= htmlspecialchars($avis['message']) ?>"
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div><!-- /.glass-cct -->
<?php endif; ?>

<?php include __DIR__ . '/../layout/footer_coiffeur.php'; ?>
