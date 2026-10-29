<?php
/**
 * Labelled form input with inline validation error.
 * Params: name, label, type='text', value='', required=true, autocomplete='', hint='', extra='' (raw, trusted attributes).
 */
$type ??= 'text';
$value ??= '';
$required ??= true;
$autocomplete ??= '';
$hint ??= '';
$extra ??= '';
$hasError = isset(\App\Core\Session::errors()[$name]);
?>
<div class="field<?= $hasError ? ' has-error' : '' ?>">
    <label for="<?= e($name) ?>"><?= e($label) ?><?php if (!$required): ?> <span class="muted">(optional)</span><?php endif ?></label>
    <input id="<?= e($name) ?>" type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>"
        <?= $required ? 'required' : '' ?>
        <?= $autocomplete ? 'autocomplete="' . e($autocomplete) . '"' : '' ?>
        <?= $hasError ? 'aria-invalid="true" aria-describedby="' . e($name) . '-error"' : '' ?>
        <?= $extra ?>>
    <?php if ($hint): ?><p class="hint"><?= e($hint) ?></p><?php endif ?>
    <?= error_for($name) ?>
</div>
