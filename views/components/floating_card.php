<?php
/**
 * views/components/floating_card.php — FloatingCard (Profile Glass Card)
 * Design System v2.0 | CL §4
 *
 * Carte glassmorphisme flottante positionnée en overlay sur un fond image.
 * Utilisée dans HeroCapsule (profil client) et profil_public.php.
 *
 * Variables attendues :
 *   $fc_greeting   — texte de salutation (défaut: 'Bonjour 👋')
 *   $fc_name       — nom de l'utilisateur
 *   $fc_stat       — statistique secondaire (ex: '3 RDV terminés')
 *   $fc_photo_url  — URL de la photo de profil (ou null)
 *   $fc_initiale   — initiale de secours
 *   $fc_position   — style de positionnement CSS (défaut: 'top:84px;right:24px;')
 */

$fc_greeting  = $fc_greeting  ?? 'Bonjour 👋';
$fc_name      = $fc_name      ?? '';
$fc_stat      = $fc_stat      ?? '';
$fc_photo_url = $fc_photo_url ?? null;
$fc_initiale  = $fc_initiale  ?? '?';
$fc_position  = $fc_position  ?? 'top:84px;right:24px;';
?>

<div class="floating-card"
     style="<?= htmlspecialchars($fc_position) ?>"
     aria-label="Profil de <?= htmlspecialchars($fc_name) ?>">

    <!-- Avatar -->
    <?php if ($fc_photo_url): ?>
        <img src="<?= htmlspecialchars($fc_photo_url) ?>"
             alt="Photo de profil"
             class="avatar-img"
             style="width:44px;height:44px;flex-shrink:0;">
    <?php else: ?>
        <div class="avatar-placeholder avatar-sm" style="flex-shrink:0;">
            <?= htmlspecialchars(strtoupper(substr($fc_initiale, 0, 1))) ?>
        </div>
    <?php endif; ?>

    <!-- Info -->
    <div class="fc-info">
        <?php if ($fc_greeting): ?>
            <span class="fc-greeting"><?= htmlspecialchars($fc_greeting) ?></span>
        <?php endif; ?>
        <?php if ($fc_name): ?>
            <span class="fc-name"><?= htmlspecialchars($fc_name) ?></span>
        <?php endif; ?>
        <?php if ($fc_stat): ?>
            <span class="fc-stat"><?= htmlspecialchars($fc_stat) ?></span>
        <?php endif; ?>
    </div>

</div>

<style>
/* FloatingCard — CL §4, DS §7 */
.floating-card {
    position:absolute;
    background:rgba(255,255,255,0.08);
    backdrop-filter:blur(20px);
    -webkit-backdrop-filter:blur(20px);
    border:1px solid var(--glass-border-md);
    border-radius:var(--radius-lg);
    padding:14px 18px;
    display:flex;
    align-items:center;
    gap:12px;
    min-width:220px;
    box-shadow:0 4px 20px rgba(0,0,0,0.40);
    z-index:3;
}
.fc-info     { display:flex; flex-direction:column; min-width:0; }
.fc-greeting { font-size:0.72rem; color:var(--text-muted); }
.fc-name     { font-weight:700; font-size:0.9rem; color:var(--text-primary); }
.fc-stat     { font-size:0.7rem; color:var(--gold); margin-top:2px; }
@media (max-width:768px) { .floating-card { display:none; } }
</style>
<?php unset($fc_greeting, $fc_name, $fc_stat, $fc_photo_url, $fc_initiale, $fc_position); ?>
