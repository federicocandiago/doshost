<?php /** @var App\Models\User $user @var int|null $size */
$size ??= 32; ?>
<?php if ($user->avatar): ?>
    <img class="avatar" src="<?= base_url() ?>/avatars/<?= e($user->avatar) ?>" alt="" width="<?= (int) $size ?>" height="<?= (int) $size ?>">
<?php else: ?>
    <span class="avatar avatar-initials" style="width: <?= (int) $size ?>px; height: <?= (int) $size ?>px; font-size: <?= round($size * 0.4) ?>px" aria-hidden="true"><?= e($user->initials()) ?></span>
<?php endif ?>
