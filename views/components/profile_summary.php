<?php
/**
 * views/components/profile_summary.php — ProfileSummary
 * Design System v2.0 | CL §28
 *
 * Variables attendues :
 *   $ps = [
 *     'nom'          => string,
 *     'initiale'     => string,
 *     'photo_url'    => string|null,
 *     'ville'        => string,
 *     'quartier'     => string,
 *     'note'         => float|null,
 *     'nb_avis'      => int,
 *     'bio'          => string,
 *   ]
 */

$ps = $ps ?? [];
$p = array_merge([
    'nom'       => 'Coiffeur',
    'initiale'  => '?',
    'photo_url' => null,
    'ville'     => 'Non spécifiée',
    'quartier'  => 'Général',
    'note'      => null,
    'nb_avis'   => 0,
    'bio'       => 'Artisan coiffeur professionnel sélectionné par notre plateforme.',
], $ps);
?>
<section class="profile-summary-section" aria-label="Profil du coiffeur">
    <div class="container text-center">

        <!-- Avatar -->
        <div class="mb-3">
            <?php if ($p['photo_url']): ?>
                <img src="<?= htmlspecialchars($p['photo_url']) ?>"
                     alt="Photo de profil"
                     class="avatar-placeholder avatar-lg">
            <?php else: ?>
                <div class="avatar-placeholder avatar-lg d-inline-flex" aria-hidden="true">
                    <?= htmlspecialchars(strtoupper(substr($p['initiale'],0,1))) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Nom -->
        <h1 style="font-family:var(--font-display);font-size:clamp(1.4rem,3vw,2rem);font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:2px;margin-bottom:0.4rem;">
            <?= htmlspecialchars(strtoupper($p['nom'])) ?>
        </h1>

        <!-- Localisation -->
        <p class="mb-3" style="color:var(--text-muted);font-size:0.85rem;">
            <i class="bi bi-geo-alt-fill" style="color:var(--gold);" aria-hidden="true"></i>
            <?= htmlspecialchars($p['ville']) ?> — <?= htmlspecialchars($p['quartier']) ?>
        </p>

        <!-- Badge note -->
        <div class="mb-3">
            <?php if ($p['note']): ?>
                <span class="badge-gold">
                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                    <?= number_format($p['note'],1) ?> / 5
                    (<?= (int)$p['nb_avis'] ?> avis)
                </span>
            <?php else: ?>
                <span class="badge-muted">
                    <i class="bi bi-star" aria-hidden="true"></i>
                    Aucun avis pour le moment
                </span>
            <?php endif; ?>
        </div>

        <!-- Bio -->
        <p style="color:var(--text-secondary);font-size:0.9rem;line-height:1.6;max-width:600px;margin:0 auto;">
            <?= htmlspecialchars($p['bio']) ?>
        </p>

    </div>
</section>

<style>
/* badge-muted — variante grisée de badge-gold */
.badge-muted { background:rgba(255,255,255,0.06); border:1px solid var(--glass-border); color:var(--text-muted); font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:20px; display:inline-flex; align-items:center; gap:5px; }
</style>
<?php unset($ps, $p); ?>
