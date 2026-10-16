<?php /** Stop screen for paused accounts and instances. @var string $heading @var string $text */ ?>
<div class="stop text-center">
    <div class="stop-icon mono mb-4" aria-hidden="true">■</div>
    <p class="mono text-secondary mb-2">C:\&gt; HALT</p>
    <h1 class="h3 mb-3"><?= e($heading) ?></h1>
    <p class="text-secondary"><?= e($text) ?></p>
    <div class="d-flex gap-2 justify-content-center mt-4">
        <?php if (!empty($logout)): ?>
            <form method="post" action="<?= base_url() ?>/logout"><?= csrf_field() ?><button class="btn btn-outline-light"><?= e(__('Sign out')) ?></button></form>
        <?php else: ?>
            <a class="btn btn-outline-light" href="<?= base_url() ?>/dashboard"><?= e(__('Back to my instances')) ?></a>
        <?php endif ?>
    </div>
</div>
