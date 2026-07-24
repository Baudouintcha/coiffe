<?php
/**
 * views/components/data_table.php — DataTable
 * Design System v2.0 | CL §36
 *
 * Tableau de données dark theme avec wrapper responsive.
 *
 * Variables attendues :
 *   $dt_headers  — array de libellés de colonnes (strings)
 *   $dt_rows     — array de lignes, chaque ligne = array de cellules HTML
 *   $dt_caption  — légende accessible du tableau (optionnel)
 *   $dt_empty    — message si tableau vide (défaut: 'Aucune donnée')
 *   $dt_classes  — classes CSS supplémentaires pour le tableau
 *
 * Chaque cellule peut être une string HTML ou un array ['content'=>str, 'attrs'=>str]
 *
 * Exemple :
 *   $dt_headers = ['Date', 'Style', 'Statut', 'Actions'];
 *   $dt_rows    = [
 *     ['12/07/2026', 'Nattes', render_status_badge('en_attente'), '<button>Gérer</button>'],
 *   ];
 *   include 'data_table.php';
 */

$dt_headers = $dt_headers ?? [];
$dt_rows    = $dt_rows    ?? [];
$dt_caption = $dt_caption ?? '';
$dt_empty   = $dt_empty   ?? 'Aucune donnée à afficher.';
$dt_classes = $dt_classes ?? '';
?>

<div class="table-responsive" role="region" aria-label="<?= htmlspecialchars($dt_caption ?: 'Tableau de données') ?>">
    <table class="table-dark-cct <?= htmlspecialchars($dt_classes) ?>"
           style="width:100%;border-collapse:collapse;">

        <?php if ($dt_caption): ?>
            <caption class="visually-hidden"><?= htmlspecialchars($dt_caption) ?></caption>
        <?php endif; ?>

        <?php if (!empty($dt_headers)): ?>
        <thead>
            <tr>
                <?php foreach ($dt_headers as $header): ?>
                    <th scope="col"><?= htmlspecialchars($header) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <?php endif; ?>

        <tbody>
            <?php if (empty($dt_rows)): ?>
                <tr>
                    <td colspan="<?= count($dt_headers) ?: 1 ?>"
                        style="text-align:center;color:var(--text-muted);padding:2rem;">
                        <?= htmlspecialchars($dt_empty) ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($dt_rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <?php if (is_array($cell)): ?>
                                <td <?= $cell['attrs'] ?? '' ?>><?= $cell['content'] ?? '' ?></td>
                            <?php else: ?>
                                <td><?= $cell ?></td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>

    </table>
</div>
<?php unset($dt_headers, $dt_rows, $dt_caption, $dt_empty, $dt_classes, $row, $cell, $header); ?>
