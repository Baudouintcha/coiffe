<?php
/**
 * views/components/gallery_card.php — GalleryCard (Carte prestation)
 * Design System v2.0 | CL §7
 *
 * Variables attendues :
 *   $gallery = [
 *     'nom_style'    => string,
 *     'prix'         => int|float,
 *     'photo_url'    => string|null,  // URL absolue ou null
 *     'href_reserver'=> string,       // lien reserver.php avec params
 *     'variante'     => 'view'|'edit', // 'view' = lecture seule, 'edit' = actions modifier/supprimer
 *     'id_prestation'=> int,          // pour variante edit
 *     'href_modifier'=> string,       // lien modification
 *     'href_supprimer'=> string,      // lien suppression
 *   ]
 */

$gallery = $gallery ?? [];
$g = array_merge([
    'nom_style'     => 'Style sans nom',
    'prix'          => 0,
    'photo_url'     => null,
    'href_reserver' => '#',
    'variante'      => 'view',
    'id_prestation' => 0,
    'href_modifier' => '#',
    'href_supprimer'=> '#',
], $gallery);
?>
<div class="gallery-card h-100">

    <!-- Image ou placeholder -->
    <div class="gallery-card-img">
        <?php if ($g['photo_url']): ?>
            <img src="<?= htmlspecialchars($g['photo_url']) ?>"
                 alt="<?= htmlspecialchars($g['nom_style']) ?>"
                 loading="lazy">
        <?php else: ?>
            <div class="gallery-card-img-placeholder">
                <i class="bi bi-stars" style="font-size:2rem;color:var(--gold);opacity:0.5;" aria-hidden="true"></i>
                <span style="font-size:0.6rem;letter-spacing:1px;text-transform:uppercase;margin-top:6px;">Création Studio</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Body -->
    <div class="p-4 text-center d-flex flex-column" style="flex:1;">
        <h5 style="font-weight:700;color:#fff;margin-bottom:8px;">
            <?= htmlspecialchars($g['nom_style']) ?>
        </h5>
        <p style="color:var(--gold);font-weight:700;font-size:1.1rem;margin-bottom:16px;">
            <?= number_format($g['prix'], 0, ',', ' ') ?>
            <span style="font-size:0.75rem;">FCFA</span>
        </p>
        <div class="mt-auto">
            <?php if ($g['variante'] === 'view'): ?>
                <a href="<?= htmlspecialchars($g['href_reserver']) ?>"
                   class="btn-outline-gold btn-sm w-100">
                    CHOISIR CE STYLE
                </a>
            <?php else: ?>
                <div class="d-flex gap-2">
                    <a href="<?= htmlspecialchars($g['href_modifier']) ?>"
                       class="btn-outline-gold btn-sm flex-grow-1">
                        ⚙️ Modifier
                    </a>
                    <a href="<?= htmlspecialchars($g['href_supprimer']) ?>"
                       class="btn-danger-cct btn-sm"
                       onclick="return confirm('Supprimer définitivement ce style ?')">
                        ✕
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php unset($gallery, $g); ?>
