<?php
/**
 * client/catalogue.php — Catalogue global des styles
 * Migration Design System v2.0 — Parcours Client — Page 3
 *
 * ⚠️  LOGIQUE MÉTIER INTACTE — SQL, logique propriétaire, liens inchangés.
 *     Correction : lien profil unifié sur ?id= (était ?prestataire_id= dans les anciennes copies).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../security/config.php';

// Identification du rôle courant — inchangé
$prestataire_id_connecte = (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'coiffeur')
    ? intval($_SESSION['user_id'])
    : null;

// Requête SQL — inchangée
$stmt = $pdo->query("SELECT p.*, u.nom, u.prenom, u.telephone,
                            v.nom_ville AS ville,
                            q.nom_quartier AS quartier
                     FROM services p
                     JOIN users u ON p.id_prestataire = u.id
                     LEFT JOIN villes v ON u.id_ville = v.id
                     LEFT JOIN quartiers q ON u.id_quartier = q.id
                     ORDER BY p.id_service DESC");
$prestations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue des styles — Coiffe Chez Toi</title>

    <!-- Design System v2.0 -->
    <link rel="stylesheet" href="/coiffons/css/variables.css">
    <link rel="stylesheet" href="/coiffons/css/animations.css">
    <link rel="stylesheet" href="/coiffons/css/components.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    /* ── Styles spécifiques au catalogue ── */
    .catalogue-page     { padding-top:calc(var(--navbar-height) + 2rem); padding-bottom:calc(var(--bottomnav-height) + 2rem); min-height:100vh; background:var(--dark); }
    .catalogue-title    { font-family:var(--font-display); font-size:clamp(1.4rem,3vw,2rem); font-weight:700; color:var(--text-primary); text-align:center; letter-spacing:2px; margin-bottom:2rem; }

    /* Catalogue card — utilise gallery-card DS §13 avec couche info */
    .catalogue-card     { background:var(--dark-2); border:1px solid rgba(212,175,55,0.25); border-radius:var(--radius-lg); overflow:hidden; cursor:pointer; transition:var(--transition-spring),border-color .3s; height:100%; display:flex; flex-direction:column; }
    .catalogue-card:hover { transform:translateY(-6px); border-color:rgba(212,175,55,0.50); box-shadow:var(--shadow-lg); }
    .catalogue-card-img { width:100%; height:220px; object-fit:cover; display:block; }
    .catalogue-card-img-placeholder { width:100%; height:220px; background:linear-gradient(135deg,#151515,#252525); display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:2.5rem; }
    .catalogue-card-body{ padding:1.25rem 1.25rem 0; flex:1; }
    .catalogue-card-style{ font-family:var(--font-body); font-size:.95rem; font-weight:700; color:var(--text-primary); margin-bottom:4px; }
    .catalogue-card-prix { font-size:1rem; font-weight:700; color:var(--success-text); margin-bottom:8px; }
    .catalogue-card-desc { font-size:.8rem; color:var(--text-muted); margin-bottom:10px; line-height:1.5; }
    .catalogue-card-sep  { border:none; border-top:1px solid var(--glass-border); margin:10px 0; }
    .catalogue-card-coif { font-size:.8rem; color:var(--text-muted); margin-bottom:3px; }
    .catalogue-card-footer { padding:.75rem 1.25rem 1.25rem; }

    /* Boutons owner (éditer/supprimer) */
    .btn-edit-owner  { background:rgba(25,135,84,0.20); color:var(--success-text); border:1px solid rgba(25,135,84,0.30); border-radius:var(--radius-sm); padding:6px 14px; font-size:.78rem; font-weight:700; cursor:pointer; text-decoration:none; flex:1; text-align:center; transition:var(--transition-fast); }
    .btn-edit-owner:hover  { background:rgba(25,135,84,0.35); }
    .btn-del-owner   { background:rgba(220,53,69,0.15); color:var(--danger-text); border:1px solid var(--danger-border); border-radius:var(--radius-sm); padding:6px 12px; font-size:.78rem; font-weight:700; cursor:pointer; text-decoration:none; transition:var(--transition-fast); }
    .btn-del-owner:hover   { background:rgba(220,53,69,0.30); }
    </style>
</head>
<body>

<!-- Navbar -->
<?php
$page_root = '/coiffons';
include __DIR__ . '/../views/components/navbar_client.php';
?>

<main class="catalogue-page page-transition" id="main-content">
    <div class="container" style="max-width:var(--max-width);">

        <h1 class="catalogue-title">CATALOGUE DES STYLES</h1>

        <?php if (empty($prestations)): ?>
            <!-- Empty State DS §17 -->
            <?php
            $es = [
                'icon'      => 'bi-scissors',
                'message'   => 'Aucun style n\'a encore été publié.',
                'cta_label' => 'Trouver un coiffeur',
                'cta_href'  => '/coiffons/filter/annuaire_coiffeurs.php',
            ];
            include __DIR__ . '/../views/components/empty_state.php';
            ?>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($prestations as $p):
                    $explode_desc         = explode("|||", $p['description']);
                    $description_affichage = $explode_desc[0] ?? '';
                    $est_le_proprietaire  = ($prestataire_id_connecte !== null && $prestataire_id_connecte === intval($p['prestataire_id']));
                    // Lien unifié sur ?id= (conforme à profil_public.php migré)
                    $lien_profil = '/coiffons/coiffeurs/profil_public.php?id=' . $p['prestataire_id'];
                    $photo_url   = (!empty($p['photo']) && file_exists(__DIR__ . '/../' . $p['photo']))
                                   ? '/coiffons/' . $p['photo']
                                   : null;
                ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <article class="catalogue-card catalogue-card-js"
                                 data-href="<?= !$est_le_proprietaire ? htmlspecialchars($lien_profil) : '#' ?>"
                                 aria-label="<?= htmlspecialchars($p['nom_service']) ?> — <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?>">

                            <!-- Image -->
                            <?php if ($photo_url): ?>
                                <img src="<?= htmlspecialchars($photo_url) ?>"
                                     class="catalogue-card-img"
                                     alt="<?= htmlspecialchars($p['nom_service']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="catalogue-card-img-placeholder" aria-hidden="true">
                                    <i class="bi bi-scissors"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Body -->
                            <div class="catalogue-card-body">
                                <div class="catalogue-card-style"><?= htmlspecialchars($p['nom_service']) ?></div>
                                <div class="catalogue-card-prix"><?= number_format($p['prix'], 0, ',', ' ') ?> FCFA</div>
                                <?php if (!empty($description_affichage)): ?>
                                    <p class="catalogue-card-desc"><?= htmlspecialchars($description_affichage) ?></p>
                                <?php endif; ?>
                                <hr class="catalogue-card-sep">
                                <p class="catalogue-card-coif">
                                    <i class="bi bi-person-fill" style="color:var(--gold);" aria-hidden="true"></i>
                                    <strong><?= htmlspecialchars(strtoupper($p['nom'] . ' ' . $p['prenom'])) ?></strong>
                                </p>
                                <p class="catalogue-card-coif">
                                    <i class="bi bi-geo-alt-fill" style="color:var(--gold);" aria-hidden="true"></i>
                                    <?= htmlspecialchars($p['ville'] . ' — ' . $p['quartier']) ?>
                                </p>
                            </div>

                            <!-- Footer -->
                            <div class="catalogue-card-footer">
                                <?php if ($est_le_proprietaire): ?>
                                    <div class="d-flex gap-2">
                                        <a href="/coiffons/coiffeurs/gestion_catalogue.php?ouvrir_modifier=<?= $p['id_service'] ?>"
                                           class="btn-edit-owner action-btn-js"
                                           aria-label="Modifier <?= htmlspecialchars($p['nom_service']) ?>">
                                            <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Éditer
                                        </a>
                                        <a href="/coiffons/coiffeurs/gestion_catalogue.php?supprimer=<?= $p['id_service'] ?>"
                                           class="btn-del-owner action-btn-js"
                                           onclick="return confirm('Supprimer définitivement ce style ?')"
                                           aria-label="Supprimer <?= htmlspecialchars($p['nom_service']) ?>">
                                            <i class="bi bi-trash3-fill" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($lien_profil) ?>&presta_id=<?= $p['id_service'] ?>"
                                       class="btn-gold w-100 action-btn-js"
                                       style="display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;"
                                       aria-label="Voir le profil et réserver <?= htmlspecialchars($p['nom_service']) ?>">
                                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                        Voir le profil &amp; Réserver
                                    </a>
                                <?php endif; ?>
                            </div>

                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</main>

<!-- Footer + Bottom Nav -->
<?php
include __DIR__ . '/../views/components/footer_global.php';
$current_page = 'catalogue.php';
include __DIR__ . '/../views/components/bottom_nav_client.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// JS UI — rend la carte cliquable sans interférer avec les boutons action (inchangé)
document.querySelectorAll('.catalogue-card-js').forEach(card => {
    card.addEventListener('click', function(e) {
        if (e.target.closest('.action-btn-js')) return;
        const url = this.getAttribute('data-href');
        if (url && url !== '#') window.location.href = url;
    });
});
</script>

</body>
</html>
