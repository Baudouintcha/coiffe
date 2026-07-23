<?php
/**
 * views/components/navbar_coiffeur.php — HeaderCoiffeur (TopNavigation coiffeur)
 * Design System v2.0 | CL §23
 *
 * Ce composant est une extraction de layout/header_coiffeur.php.
 * Il inclut la topbar + bottom nav mobile uniquement (pas le <head> HTML).
 *
 * Variables attendues (toutes optionnelles, lues depuis $_SESSION) :
 *   $prenom_nav       — prénom du coiffeur
 *   $photo_nav        — chemin relatif photo profil ou null
 *   $nb_notifs_nav    — nombre de notifications non lues
 *   $current_page     — basename de la page courante
 *   $page_root        — chemin racine (ex: '/coiffons')
 */

$prenom_nav    = $prenom_nav    ?? htmlspecialchars($_SESSION['prenom'] ?? 'Coiffeur');
$photo_nav     = $photo_nav     ?? ($_SESSION['photo_profil'] ?? null);
$nb_notifs_nav = $nb_notifs_nav ?? 0;
$current_page  = $current_page  ?? basename($_SERVER['PHP_SELF']);
$page_root     = $page_root     ?? '/coiffons';
$initiale_nav  = strtoupper(substr($prenom_nav, 0, 1));

$photo_url_nav = null;
if ($photo_nav && file_exists($_SERVER['DOCUMENT_ROOT'] . $page_root . '/' . $photo_nav)) {
    $photo_url_nav = $page_root . '/' . htmlspecialchars($photo_nav);
}
?>

<!-- ── TOPBAR COIFFEUR ── -->
<nav class="coiffeur-topbar" role="navigation" aria-label="Espace coiffeur">
    <div class="topbar-left">
        <a href="<?= $page_root ?>/index.php?page=dashboard_coiffeur" class="topbar-brand">
            CCT <!-- Logo ici quand disponible -->
        </a>
        <span class="topbar-page">Espace Coiffeur</span>
    </div>
    <div class="topbar-right">
        <a href="<?= $page_root ?>/index.php?page=dashboard_coiffeur"
           class="t-btn" title="Dashboard" aria-label="Retour au dashboard">
            <i class="bi bi-grid-1x2" aria-hidden="true"></i>
        </a>
        <a href="#" class="t-btn" title="Notifications" aria-label="Notifications">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <?php if ($nb_notifs_nav > 0): ?>
                <span class="t-notif-dot" aria-label="<?= (int)$nb_notifs_nav ?> notifications"></span>
            <?php endif; ?>
        </a>
        <a href="<?= $page_root ?>/coiffeurs/profil_coiffeurs.php"
           aria-label="Mon profil">
            <?php if ($photo_url_nav): ?>
                <img src="<?= $photo_url_nav ?>" class="t-avatar-img" alt="Photo de profil">
            <?php else: ?>
                <div class="t-avatar-ph avatar-placeholder avatar-xs"><?= htmlspecialchars($initiale_nav) ?></div>
            <?php endif; ?>
        </a>
    </div>
</nav>

<!-- ── BOTTOM NAV MOBILE COIFFEUR ── -->
<nav class="coiffeur-bottom-nav" role="navigation" aria-label="Navigation mobile coiffeur">
    <div class="cbn-items">
        <?php
        $nav_coiffeur = [
            ['href' => $page_root.'/index.php?page=dashboard_coiffeur', 'icon' => 'bi-grid-1x2',    'label' => 'Dashboard',  'match' => 'index.php'],
            ['href' => $page_root.'/coiffeurs/gestion_catalogue.php',   'icon' => 'bi-scissors',     'label' => 'Catalogue',  'match' => 'gestion_catalogue.php'],
            ['href' => $page_root.'/coiffeurs/agenda_coiffeurs.php',    'icon' => 'bi-calendar3',    'label' => 'Agenda',     'match' => 'agenda_coiffeurs.php'],
            ['href' => $page_root.'/coiffeurs/valider_rendezvous.php',  'icon' => 'bi-check2-circle','label' => 'RDV',        'match' => 'valider_rendezvous.php'],
            ['href' => $page_root.'/deconnexion.php',                   'icon' => 'bi-box-arrow-right','label'=> 'Quitter',   'match' => ''],
        ];
        foreach ($nav_coiffeur as $item):
            $active = ($current_page === $item['match']);
        ?>
        <a href="<?= htmlspecialchars($item['href']) ?>"
           class="cbn-item <?= $active ? 'active' : '' ?>"
           aria-current="<?= $active ? 'page' : 'false' ?>">
            <i class="bi <?= $item['icon'] ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($item['label']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

<style>
/* Topbar coiffeur — DS §10, CL §23 */
.coiffeur-topbar {
    position: sticky; top: 0; z-index: var(--z-bottomnav);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 2rem; height: var(--navbar-height-coiffeur);
    background: rgba(0,0,0,0.92);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(212,175,55,0.12);
}
.topbar-left    { display: flex; align-items: center; gap: 14px; }
.topbar-brand   { font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--gold); text-decoration: none; letter-spacing: 1px; }
.topbar-page    { font-size: 0.8rem; color: var(--text-muted); padding-left: 14px; border-left: 1px solid var(--glass-border); }
.topbar-right   { display: flex; align-items: center; gap: 10px; }
.t-btn          { width: 36px; height: 36px; border-radius: var(--radius-circle); background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.50); cursor: pointer; transition: var(--transition-fast); text-decoration: none; position: relative; }
.t-btn:hover    { background: var(--gold-dim); color: var(--gold); border-color: var(--gold); }
.t-notif-dot    { position: absolute; top: 5px; right: 5px; width: 8px; height: 8px; border-radius: 50%; background: var(--gold); border: 2px solid #000; }
.t-avatar-ph    { width: 36px !important; height: 36px !important; font-size: 0.78rem; }
.t-avatar-img   { width: 36px; height: 36px; border-radius: var(--radius-circle); border: 2px solid var(--gold); object-fit: cover; }
.coiffeur-bottom-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; background: var(--black); border-top: 1px solid rgba(255,255,255,0.06); z-index: var(--z-bottomnav); padding: 6px 0 2px; }
@media (max-width: 768px) {
    .coiffeur-topbar   { padding: 0 1rem; }
    .coiffeur-bottom-nav { display: block; }
    .topbar-page       { display: none; }
}
</style>
<?php unset($prenom_nav, $photo_nav, $nb_notifs_nav, $current_page, $page_root, $initiale_nav, $photo_url_nav, $nav_coiffeur, $item, $active); ?>
