<?php
/**
 * views/components/avatar.php — Composant Avatar
 * Design System v2.0 | CL §25
 *
 * Usage :
 *   $avatar_src   = '/coiffons/uploads/profil/photo.jpg'; // chemin absolu URL ou null
 *   $avatar_init  = 'A';   // initiale si pas de photo
 *   $avatar_size  = 'sm';  // xs | sm | md | lg
 *   $avatar_alt   = 'Nom'; // attribut alt de l'image
 *
 * Exemple :
 *   <?php $avatar_src = null; $avatar_init = 'J'; $avatar_size = 'lg'; include 'views/components/avatar.php'; ?>
 */

$avatar_src  = $avatar_src  ?? null;
$avatar_init = $avatar_init ?? '?';
$avatar_size = $avatar_size ?? 'sm';
$avatar_alt  = $avatar_alt  ?? 'Avatar';

if ($avatar_src):
?>
    <img src="<?= htmlspecialchars($avatar_src) ?>"
         alt="<?= htmlspecialchars($avatar_alt) ?>"
         class="avatar-img avatar-<?= htmlspecialchars($avatar_size) ?>">
<?php else: ?>
    <div class="avatar-placeholder avatar-<?= htmlspecialchars($avatar_size) ?>"
         aria-label="<?= htmlspecialchars($avatar_alt) ?>">
        <?= htmlspecialchars(strtoupper(substr($avatar_init, 0, 1))) ?>
    </div>
<?php endif;

// Réinitialiser les variables pour éviter les fuites entre inclusions
unset($avatar_src, $avatar_init, $avatar_size, $avatar_alt);
?>
