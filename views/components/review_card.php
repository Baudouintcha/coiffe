<?php
/**
 * views/components/review_card.php — ReviewCard
 * Design System v2.0 | CL §6
 *
 * Variables attendues :
 *   $rv = [
 *     'auteur_nom'    => string,
 *     'auteur_initiale'=> string,
 *     'auteur_photo'  => string|null,
 *     'date'          => string,   // ex: '12/07/2026'
 *     'note'          => int,      // 1–5
 *     'texte'         => string,
 *   ]
 */

$rv = $rv ?? [];
$r = array_merge([
    'auteur_nom'     => 'Client',
    'auteur_initiale'=> 'C',
    'auteur_photo'   => null,
    'date'           => '',
    'note'           => 5,
    'texte'          => '',
], $rv);

$note      = max(1, min(5, (int)$r['note']));
$stars     = str_repeat('★', $note);
$stars_off = str_repeat('★', 5 - $note);
?>

<article class="review-card glass-cct p-3" aria-label="Avis de <?= htmlspecialchars($r['auteur_nom']) ?>">

    <!-- Header -->
    <div class="rv-header">
        <!-- Avatar auteur -->
        <?php if ($r['auteur_photo']): ?>
            <img src="<?= htmlspecialchars($r['auteur_photo']) ?>"
                 alt="<?= htmlspecialchars($r['auteur_nom']) ?>"
                 class="avatar-img"
                 style="width:36px;height:36px;">
        <?php else: ?>
            <div class="avatar-placeholder" style="width:36px;height:36px;font-size:0.75rem;flex-shrink:0;">
                <?= htmlspecialchars(strtoupper(substr($r['auteur_initiale'],0,1))) ?>
            </div>
        <?php endif; ?>

        <div class="rv-meta">
            <span class="rv-name"><?= htmlspecialchars($r['auteur_nom']) ?></span>
            <?php if ($r['date']): ?>
                <span class="rv-date"><?= htmlspecialchars($r['date']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stars -->
    <div class="rv-stars" aria-label="Note: <?= $note ?> sur 5">
        <span class="stars" aria-hidden="true"><?= $stars ?></span>
        <span class="stars-empty" aria-hidden="true"><?= $stars_off ?></span>
    </div>

    <!-- Texte -->
    <?php if ($r['texte']): ?>
        <p class="rv-texte"><?= htmlspecialchars($r['texte']) ?></p>
    <?php endif; ?>

</article>

<style>
/* ReviewCard — CL §6, DS §13 */
.review-card    { margin-bottom: 16px; }
.rv-header      { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.rv-meta        { display:flex; flex-direction:column; }
.rv-name        { font-weight:700; font-size:0.88rem; color:var(--text-primary); }
.rv-date        { font-size:0.68rem; color:var(--text-muted); }
.rv-stars       { margin-bottom:8px; }
.stars          { color:var(--gold); font-size:0.9rem; letter-spacing:2px; }
.stars-empty    { color:rgba(212,175,55,0.30); font-size:0.9rem; letter-spacing:2px; }
.rv-texte       { font-size:0.85rem; color:var(--text-secondary); line-height:1.6; margin:0; }
</style>
<?php unset($rv, $r, $note, $stars, $stars_off); ?>
