<?php
/**
 * views/components/bottom_nav_client.php — BottomNavigation client
 * Design System v2.0 | CL §20
 *
 * Variable attendue :
 *   $current_page — basename de la page courante (ex: 'dashboard', 'mes_rendezvous.php')
 *   $page_root    — chemin racine (ex: '/coiffons')
 */

$current_page = $current_page ?? basename($_SERVER['PHP_SELF']);
$page_root    = $page_root    ?? '/coiffons';

$nav_items = [
    ['icon' => 'bi-house',        'label' => 'Accueil',    'href' => $page_root . '/index.php',                          'match' => ['index.php', 'dashboard']],
    ['icon' => 'bi-search',       'label' => 'Rechercher', 'href' => $page_root . '/filter/annuaire_coiffeurs.php',       'match' => ['annuaire_coiffeurs.php'], 'role' => 'client'],
    ['icon' => 'bi-calendar3',    'label' => 'Mes RDV',    'href' => $page_root . '/client/mes_rendezvous.php',           'match' => ['mes_rendezvous.php']],
    ['icon' => 'bi-person-circle','label' => 'Profil',     'href' => $page_root . '/profil.php',                          'match' => ['profil.php', 'modifier_profil.php']],
];
?>
<nav class="client-bottom-nav" role="navigation" aria-label="Navigation mobile">
    <div class="cbn-items">
        <?php foreach ($nav_items as $item):
            // Skip items that have a role restriction and user doesn't match
            if (isset($item['role']) && ($item['role'] !== ($_SESSION['role'] ?? null))) {
                continue;
            }
            $is_active = in_array($current_page, $item['match']);
        ?>
        <a href="<?= htmlspecialchars($item['href']) ?>"
           class="cbn-item <?= $is_active ? 'active' : '' ?>"
           aria-current="<?= $is_active ? 'page' : 'false' ?>">
            <i class="bi <?= $item['icon'] ?>" aria-hidden="true"></i>
            <?= htmlspecialchars($item['label']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</nav>

<style>
/* BottomNavigation client — DS §10, CL §20 */
.client-bottom-nav {
    display: none;
    position: fixed; bottom: 0; left: 0; right: 0;
    background: var(--black);
    border-top: 1px solid rgba(255,255,255,0.06);
    z-index: var(--z-bottomnav);
    padding: 6px 0 2px;
}
.cbn-items  { display: flex; justify-content: space-around; }
.cbn-item   { display: flex; flex-direction: column; align-items: center; gap: 2px; color: rgba(255,255,255,0.30); text-decoration: none; font-size: 0.58rem; padding: 4px 10px; border-radius: var(--radius-sm); transition: color 0.2s; }
.cbn-item i { font-size: 1.15rem; }
.cbn-item.active, .cbn-item:hover { color: var(--gold); }
@media (max-width: 768px) { .client-bottom-nav { display: block; } }
</style>
<?php unset($current_page, $page_root, $nav_items, $item, $is_active); ?>
