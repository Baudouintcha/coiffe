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
$_nav_initiale = strtoupper(substr($prenom_client, 0, 1));

// Vérifier si la photo existe physiquement
$photo_url = null;
if ($photo_profil && file_exists($_SERVER['DOCUMENT_ROOT'] . $page_root . '/' . $photo_profil)) {
    $photo_url = $page_root . '/' . htmlspecialchars($photo_profil);
}

// Charger les 5 dernières notifications non lues pour le dropdown
$_navbar_notifs = [];
if (isset($pdo) && isset($_SESSION['id_user'])) {
    try {
        $stmt_nav = $pdo->prepare(
            "SELECT id_notif, message, type, date_notification
             FROM notifications
             WHERE id_user = ? AND statut_lecture = 'non_lu'
             ORDER BY date_notification DESC
             LIMIT 5"
        );
        $stmt_nav->execute([(int)$_SESSION['id_user']]);
        foreach ($stmt_nav->fetchAll() as $row) {
            $type_n = $row['type'];
            $icon_map = [
                'info'    => 'bi-info-circle',
                'success' => 'bi-check-circle',
                'danger'  => 'bi-exclamation-circle',
                'warning' => 'bi-exclamation-triangle',
            ];
            $_navbar_notifs[] = [
                'id'      => $row['id_notif'],
                'type'    => $type_n,
                'message' => $row['message'],
                'icone'   => $icon_map[$type_n] ?? 'bi-bell',
                'lien'    => $page_root . '/client/notifications.php',
                'date'    => date('d/m H:i', strtotime($row['date_notification'])),
            ];
        }
        // Mettre à jour le compteur avec la vraie valeur BDD
        if ((int)$nb_notifs === 0 && !empty($_navbar_notifs)) {
            $nb_notifs = count($_navbar_notifs);
        }
    } catch (Exception $e) {
        // Non-bloquant
    }
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
            <i class="bi bi-calendar3" aria-hidden="true"></i>
        </a>

        <!-- ── Cloche notifications (dropdown CL §17) ── -->
        <?php
        $nd_count         = (int)$nb_notifs;
        $nd_notifications = $_navbar_notifs;
        $nd_mark_read_url = $page_root . '/security/marquer_notifs_lu.php';
        $nd_trigger_id    = 'navbarNotifDropdown';
        include __DIR__ . '/notification_dropdown.php';
        ?>

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
                    <?= htmlspecialchars($_nav_initiale) ?>
                </div>
            <?php endif; ?>
        </a>
    </div>
</nav>

<!-- AJAX — marquer comme lu au clic sur le bouton du dropdown -->
<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        // Lien "Tout lire" du dropdown (ancre a.text-decoration-none dans nd-panel)
        const markReadLinks = document.querySelectorAll('.nd-panel a[href$="marquer_notifs_lu.php"]');
        markReadLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                fetch(link.getAttribute('href'), { method: 'GET', credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.status === 'ok') {
                            // Masquer le badge
                            const dots = document.querySelectorAll('.t-notif-dot.animate-pulse');
                            dots.forEach(function (d) { d.style.display = 'none'; });
                            // Vider la liste
                            const items = document.querySelectorAll('.nd-panel li.p-2');
                            items.forEach(function (i) { i.remove(); });
                            // Mettre "Rien à signaler"
                            const list = document.querySelector('.nd-panel ul, .nd-panel');
                            if (list) {
                                const empty = document.createElement('li');
                                empty.className = 'text-center py-2 small';
                                empty.style.color = 'var(--text-muted)';
                                empty.textContent = 'Rien à signaler';
                                list.appendChild(empty);
                            }
                        }
                    })
                    .catch(function () {});
            });
        });
    });
})();
</script>

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
<?php unset($prenom_client, $photo_profil, $nb_notifs, $page_root, $_nav_initiale, $photo_url, $_navbar_notifs, $nd_count, $nd_notifications, $nd_mark_read_url, $nd_trigger_id, $stmt_nav, $row, $type_n, $icon_map); ?>
