<?php
/**
 * views/components/navbar_client.php — TopNavigation client
 * Design System v2.0 | CL §21
 *
 * Variables attendues dans la page appelante (optionnelles) :
 *   $prenom_client  — prénom affiché
 *   $photo_profil   — chemin relatif vers la photo (ex: 'uploads/profil/xxx.jpg')
 *   $nb_notifs      — nombre de notifications non lues (int, défaut 0)
 *   $page_root      — chemin racine du projet (ex: '/coiffons') ou '' si à la racine
 */

$prenom_client = $prenom_client ?? ($_SESSION['prenom'] ?? 'Client');
$photo_profil  = $photo_profil  ?? ($_SESSION['photo_profil'] ?? null);
$nb_notifs     = $nb_notifs     ?? 0;
$page_root     = $page_root     ?? '/coiffons';
$initiale      = strtoupper(substr($prenom_client, 0, 1));

// Vérifier si la photo existe physiquement
$photo_url = null;
if ($photo_profil && file_exists($_SERVER['DOCUMENT_ROOT'] . $page_root . '/' . $photo_profil)) {
    $photo_url = $page_root . '/' . htmlspecialchars($photo_profil);
}
?>
<nav class="cct-nav" role="navigation" aria-label="Navigation principale">
    <a href="<?= $page_root ?>/index.php" class="cct-brand">
        <!-- LOGO : remplacer ce texte par <img src="..."> quand disponible -->
        Coiffe Chez Toi
    </a>
    <div class="cct-nav-actions">
        <a href="<?= $page_root ?>/filter/annuaire_coiffeurs.php"
           class="nav-icon-btn"
           title="Rechercher un coiffeur"
           aria-label="Rechercher">
            <i class="bi bi-search" aria-hidden="true"></i>
        </a>
        <a href="<?= $page_root ?>/client/mes_rendezvous.php"
           class="nav-icon-btn"
           title="Mes rendez-vous"
           aria-label="Mes rendez-vous">
            <?php if ($nb_notifs > 0): ?>
                <span class="position-relative">
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                    <span class="t-notif-dot" aria-hidden="true"></span>
                </span>
            <?php else: ?>
                <i class="bi bi-calendar3" aria-hidden="true"></i>
            <?php endif; ?>
        </a>
        <a href="<?= $page_root ?>/profil.php"
           class="nav-avatar-link"
           title="Mon profil"
           aria-label="Mon profil">
            <?php if ($photo_url): ?>
                <img src="<?= $photo_url ?>"
                     alt="Photo de profil"
                     class="nav-avatar">
            <?php else: ?>
                <div class="nav-avatar-placeholder avatar-placeholder avatar-xs"
                     aria-hidden="true">
                    <?= htmlspecialchars($initiale) ?>
                </div>
            <?php endif; ?>
        </a>
    </div>
</nav>

<style>
/* TopNavigation client — DS §10, CL §21 */
.cct-nav {
    position: fixed; top: 0; left: 0; right: 0;
    z-index: var(--z-navbar);
    padding: 0 2rem;
    height: var(--navbar-height);
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(10,10,10,0.85);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.cct-brand {
    font-family: var(--font-display);
    font-size: 1.15rem; font-weight: 700;
    color: var(--gold); text-decoration: none; letter-spacing: 1px;
}
.cct-nav-actions { display: flex; align-items: center; gap: 1rem; }
.nav-icon-btn {
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-circle);
    width: 38px; height: 38px;
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,0.60);
    cursor: pointer; transition: var(--transition-fast); text-decoration: none;
    font-size: 1rem; position: relative;
}
.nav-icon-btn:hover { background: var(--gold-dim); color: var(--gold); border-color: var(--gold); }
.nav-avatar       { width: 38px; height: 38px; border-radius: var(--radius-circle); border: 2px solid var(--gold); object-fit: cover; cursor: pointer; }
.nav-avatar-link  { text-decoration: none; }
.nav-avatar-placeholder { cursor: pointer; width: 38px !important; height: 38px !important; }
.t-notif-dot      { position: absolute; top: 3px; right: 3px; width: 8px; height: 8px; border-radius: 50%; background: var(--gold); border: 2px solid #000; }
@media (max-width: 768px) { .cct-nav { padding: 0 1rem; } }
</style>
<?php unset($prenom_client, $photo_profil, $nb_notifs, $page_root, $initiale, $photo_url); ?>
