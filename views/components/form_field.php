<?php
/**
 * views/components/form_field.php — FormField
 * Design System v2.0 | CL §33
 *
 * Champ de formulaire dark glassmorphisme.
 * Toutes les variantes en un seul composant.
 *
 * Variables attendues :
 *   $ff = [
 *     'type'       => 'text'|'email'|'password'|'tel'|'number'|'time'|'file'|'select'|'textarea',
 *     'name'       => string,
 *     'label'      => string,        // libellé du label (optionnel)
 *     'value'      => string,        // valeur pré-remplie
 *     'placeholder'=> string,
 *     'required'   => bool,
 *     'disabled'   => bool,
 *     'id'         => string,        // ID HTML (défaut: name)
 *     'extra_class'=> string,        // classes CSS supplémentaires
 *     'attrs'      => string,        // attributs HTML supplémentaires (ex: 'min="0" max="100"')
 *     'options'    => array,         // pour type='select': [['value'=>v,'label'=>l,'selected'=>bool]]
 *     'rows'       => int,           // pour type='textarea' (défaut: 3)
 *     'icon_prefix'=> string,        // classe bi-* pour icône gauche (input group)
 *     'toggle_pw'  => bool,          // afficher bouton toggle password
 *     'note'       => string,        // texte d'aide sous le champ
 *   ]
 */

$ff = $ff ?? [];
$f = array_merge([
    'type'        => 'text',
    'name'        => 'field',
    'label'       => '',
    'value'       => '',
    'placeholder' => '',
    'required'    => false,
    'disabled'    => false,
    'id'          => '',
    'extra_class' => '',
    'attrs'       => '',
    'options'     => [],
    'rows'        => 3,
    'icon_prefix' => '',
    'toggle_pw'   => false,
    'note'        => '',
], $ff);

$f['id'] = $f['id'] ?: $f['name'];
$req_attr = $f['required'] ? 'required' : '';
$dis_attr = $f['disabled'] ? 'disabled' : '';
$has_group = $f['icon_prefix'] || $f['toggle_pw'];

// Génère un ID unique pour le toggle
$toggle_id = 'toggle-' . $f['id'];
?>

<div class="ff-wrapper mb-3">

    <?php if ($f['label']): ?>
        <label for="<?= htmlspecialchars($f['id']) ?>" class="form-label-cct">
            <?= htmlspecialchars($f['label']) ?>
            <?php if ($f['required']): ?>
                <span style="color:var(--danger-icon);" aria-hidden="true"> *</span>
            <?php endif; ?>
        </label>
    <?php endif; ?>

    <?php if ($has_group): ?>
    <div class="input-group-cct">
        <?php if ($f['icon_prefix']): ?>
            <span class="ig-prefix" aria-hidden="true">
                <i class="bi <?= htmlspecialchars($f['icon_prefix']) ?>"></i>
            </span>
        <?php endif; ?>
    <?php endif; ?>

        <?php if ($f['type'] === 'select'): ?>
            <select name="<?= htmlspecialchars($f['name']) ?>"
                    id="<?= htmlspecialchars($f['id']) ?>"
                    class="fc-dark <?= htmlspecialchars($f['extra_class']) ?>"
                    <?= $req_attr ?> <?= $dis_attr ?>
                    <?= $f['attrs'] ?>>
                <?php foreach ($f['options'] as $opt): ?>
                    <option value="<?= htmlspecialchars($opt['value'] ?? '') ?>"
                            <?= !empty($opt['selected']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($opt['label'] ?? '') ?>
                    </option>
                <?php endforeach; ?>
            </select>

        <?php elseif ($f['type'] === 'textarea'): ?>
            <textarea name="<?= htmlspecialchars($f['name']) ?>"
                      id="<?= htmlspecialchars($f['id']) ?>"
                      class="fc-dark <?= htmlspecialchars($f['extra_class']) ?>"
                      rows="<?= (int)$f['rows'] ?>"
                      placeholder="<?= htmlspecialchars($f['placeholder']) ?>"
                      <?= $req_attr ?> <?= $dis_attr ?>
                      <?= $f['attrs'] ?>><?= htmlspecialchars($f['value']) ?></textarea>

        <?php else: ?>
            <input type="<?= htmlspecialchars($f['type']) ?>"
                   name="<?= htmlspecialchars($f['name']) ?>"
                   id="<?= htmlspecialchars($f['id']) ?>"
                   value="<?= htmlspecialchars($f['value']) ?>"
                   placeholder="<?= htmlspecialchars($f['placeholder']) ?>"
                   class="fc-dark <?= htmlspecialchars($f['extra_class']) ?>"
                   <?= $req_attr ?> <?= $dis_attr ?>
                   <?= $f['attrs'] ?>>
        <?php endif; ?>

    <?php if ($has_group): ?>
        <?php if ($f['toggle_pw']): ?>
            <button type="button"
                    class="ig-suffix"
                    id="<?= htmlspecialchars($toggle_id) ?>"
                    onclick="(function(btn,input){input=document.getElementById('<?= htmlspecialchars($f['id']) ?>');var icon=btn.querySelector('i');input.type=input.type==='password'?'text':'password';icon.className=input.type==='password'?'bi bi-eye':'bi bi-eye-slash';})(this)"
                    aria-label="Afficher/masquer le mot de passe">
                <i class="bi bi-eye" aria-hidden="true"></i>
            </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($f['note']): ?>
        <small style="color:var(--text-muted);font-size:0.70rem;display:block;margin-top:4px;">
            <?= htmlspecialchars($f['note']) ?>
        </small>
    <?php endif; ?>

</div>
<?php unset($ff, $f, $req_attr, $dis_attr, $has_group, $toggle_id); ?>
