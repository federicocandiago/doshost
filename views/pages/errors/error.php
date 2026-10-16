<p class="mono text-secondary mb-2">C:\&gt; ERROR <?= (int) $status ?></p>
<h1 class="h2 mb-3"><?= e($title) ?></h1>
<?php if ($detail): ?><p class="text-secondary mono small text-break"><?= e($detail) ?></p><?php endif ?>
<a class="btn btn-outline-light mt-3" href="<?= base_url() ?>/"><?= e(__('Back to home')) ?></a>
