<?php
/**
 * views/components/service_card.php — ServiceCard (Carte coiffeur / prestation)
 * Design System v2.0 | CL §5
 *
 * Variables attendues :
 *   $card = [
 *     'id'           => int,
 *     'nom'          => string,
 *     'photo_cover'  => string|null,  // URL absolue /coiffons/...
 *     'photo_avatar' => string|null,  // URL absolue /coiffons/...
 *     'initiale'     => string,
 *     'ville'        => string,
 *     'quartier'     => string,
 *     'prix'         => string,       // formaté "5 000"
 *     'note'         => float|null,
 *     'nb_avis'      => int,
 *     'is_verified'  => bool,
 *     'tags'         => string[],     // ex: ['Coiffure', 'Barbe']
 *     'href'         => string,       // lien vers profil
 *     'cta_label'    => string,       // texte du bouton
 *   ]
 *
 * Exemple :
 *   $card = ['id'=>1,'nom'=>'Jean D.','initiale'=>'J','prix'=>'3 000','href'=>'/coiffons/...','cta_label'=>'Voir les prestations'];
 *   include 'views/components/service_card.php';
 */

$card = $card ?? [];
$c = array_merge([
    'id'           => 0,
    'nom'          => 'Coiffeur',
    'photo_cover'  => null,
    'photo_avatar' => null,
    'initiale'     => '?',
    'ville'        => '',
    'quartier'     => '',
    'prix'         => '0',
    'note'         => null,
    'nb_avis'      => 0,
    'is_verified'  => false,
    'tags'         => ['Coiffure'],
    'href'         => '#',
    'cta_label'    => 'Voir les prestations',
], $card);
?>
<a href="<?= htmlspecialchars($c['href']) ?>" class="coiffeur-card" aria-label="<?= htmlspecialchars($c['nom']) ?>">

    <!-- Cover -->
    <div class="card-cover">
        <?php if ($c['photo_cover']): ?>
            <img src="<?= htmlspecialchars($c['photo_cover']) ?>" loading="lazy" alt="<?= htmlspecialchars($c['nom']) ?>">
        <?php else: ?>
            <div class="card-cover-placeholder">
                <i class="bi bi-person-badge" aria-hidden="true"></i>
            </div>
        <?php endif; ?>

        <?php if ($c['is_verified']): ?>
            <span class="verified-badge">
                <i class="bi bi-patch-check-fill" aria-hidden="true"></i> Certifié
            </span>
        <?php endif; ?>

        <div class="card-avatar-wrap">
            <?php if ($c['photo_avatar']): ?>
                <img src="<?= htmlspecialchars($c['photo_avatar']) ?>"
                     class="avatar-placeholder avatar-sm"
                     style="border:3px solid var(--dark-2)"
                     alt="">
            <?php else: ?>
                <div class="avatar-placeholder avatar-sm" style="border:3px solid var(--dark-2)">
                    <?= htmlspecialchars(strtoupper(substr($c['initiale'],0,1))) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Body -->
    <div class="card-body">
        <div class="card-name"><?= htmlspecialchars($c['nom']) ?></div>

        <div class="card-rating">
            <?php if ($c['note']): ?>
                <span class="stars" aria-label="Note: <?= number_format($c['note'],1) ?> sur 5">
                    <?= str_repeat('★', round($c['note'])) ?><?= str_repeat('☆', 5 - round($c['note'])) ?>
                </span>
                <?= number_format($c['note'],1) ?> (<?= (int)$c['nb_avis'] ?> avis)
            <?php else: ?>
                <i class="bi bi-geo-alt" style="color:var(--gold)" aria-hidden="true"></i>
                <?= htmlspecialchars($c['ville']) ?><?= $c['quartier'] ? ' · ' . htmlspecialchars($c['quartier']) : '' ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($c['tags'])): ?>
        <div class="card-tags">
            <?php foreach ($c['tags'] as $tag): ?>
                <span class="card-tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card-footer-row">
            <div class="card-price">
                Dès <strong><?= htmlspecialchars($c['prix']) ?> FCFA</strong>
            </div>
            <span class="btn-gold btn-sm"><?= htmlspecialchars($c['cta_label']) ?></span>
        </div>
    </div>
</a>
<?php unset($card, $c); ?>
