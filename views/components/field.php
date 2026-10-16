<?php
/**
 * Form field. Params: name, label, type (text), value, required, help, attrs.
 * The empty .invalid-feedback is filled by app.js with server-side errors.
 */
$type ??= 'text';
$id = 'f-' . $name;
?>
<div class="mb-3">
    <label class="form-label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <?php if ($type === 'textarea'): ?>
        <textarea class="form-control" id="<?= e($id) ?>" name="<?= e($name) ?>" rows="2" <?= $attrs ?? '' ?>><?= e($value ?? '') ?></textarea>
    <?php else: ?>
        <input class="form-control" id="<?= e($id) ?>" type="<?= e($type) ?>" name="<?= e($name) ?>"
               value="<?= e($value ?? '') ?>" <?= !empty($required) ? 'required' : '' ?> <?= $attrs ?? '' ?>>
    <?php endif ?>
    <?php if (!empty($help)): ?><div class="form-text"><?= e($help) ?></div><?php endif ?>
    <div class="invalid-feedback" data-error-for="<?= e($name) ?>"></div>
</div>
