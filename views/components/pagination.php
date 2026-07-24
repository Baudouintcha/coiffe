<?php
/**
 * views/components/pagination.php — Pagination
 * Design System v2.0 | CL §24
 *
 * Variables attendues :
 *   $pg_current  — page courante (int, commence à 1)
 *   $pg_total    — nombre total de pages
 *   $pg_base_url — URL de base sans paramètre page (ex: '/coiffons/filter/annuaire_coiffeurs.php?ville=1')
 *   $pg_param    — nom du paramètre de pagination (défaut: 'page')
 *   $pg_window   — nombre de pages visibles de chaque côté de la page courante (défaut: 2)
 */

$pg_current  = max(1, (int)($pg_current  ?? 1));
$pg_total    = max(1, (int)($pg_total    ?? 1));
$pg_base_url = $pg_base_url ?? '#';
$pg_param    = $pg_param    ?? 'page';
$pg_window   = (int)($pg_window ?? 2);

if ($pg_total <= 1) {
    // Ne rien afficher si une seule page
    unset($pg_current, $pg_total, $pg_base_url, $pg_param, $pg_window);
    return;
}

// Construire l'URL d'une page
$pg_url = function(int $p) use ($pg_base_url, $pg_param): string {
    $sep = str_contains($pg_base_url, '?') ? '&' : '?';
    return htmlspecialchars($pg_base_url . $sep . $pg_param . '=' . $p);
};

$start = max(1, $pg_current - $pg_window);
$end   = min($pg_total, $pg_current + $pg_window);
?>

<nav class="pagination-cct d-flex justify-content-center mt-4"
     aria-label="Pagination">
    <ul class="pagination mb-0">

        <!-- Précédent -->
        <li class="page-item <?= $pg_current <= 1 ? 'disabled' : '' ?>">
            <a class="page-link"
               href="<?= $pg_current > 1 ? $pg_url($pg_current - 1) : '#' ?>"
               aria-label="Page précédente"
               <?= $pg_current <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </a>
        </li>

        <!-- Première page si hors fenêtre -->
        <?php if ($start > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= $pg_url(1) ?>" aria-label="Page 1">1</a>
            </li>
            <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Pages de la fenêtre -->
        <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p === $pg_current ? 'active' : '' ?>">
                <a class="page-link"
                   href="<?= $pg_url($p) ?>"
                   aria-label="Page <?= $p ?>"
                   <?= $p === $pg_current ? 'aria-current="page"' : '' ?>>
                    <?= $p ?>
                </a>
            </li>
        <?php endfor; ?>

        <!-- Dernière page si hors fenêtre -->
        <?php if ($end < $pg_total): ?>
            <?php if ($end < $pg_total - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link"
                   href="<?= $pg_url($pg_total) ?>"
                   aria-label="Page <?= $pg_total ?>">
                    <?= $pg_total ?>
                </a>
            </li>
        <?php endif; ?>

        <!-- Suivant -->
        <li class="page-item <?= $pg_current >= $pg_total ? 'disabled' : '' ?>">
            <a class="page-link"
               href="<?= $pg_current < $pg_total ? $pg_url($pg_current + 1) : '#' ?>"
               aria-label="Page suivante"
               <?= $pg_current >= $pg_total ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </a>
        </li>

    </ul>
</nav>
<?php unset($pg_current, $pg_total, $pg_base_url, $pg_param, $pg_window, $pg_url, $start, $end, $p); ?>
