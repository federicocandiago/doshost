<?php if (!empty($paused)): ?>
    <span class="badge text-bg-warning"><?= e(__('Paused')) ?></span>
<?php else: ?>
    <span class="badge text-bg-dark border border-secondary-subtle"><?= e(__('Active')) ?></span>
<?php endif ?>
