<?php $user = auth(); ?>
<nav class="navbar navbar-expand-md">
    <div class="container">
        <a class="navbar-brand brand" href="<?= base_url() ?>/"><?= e(env('APP_NAME')) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url() ?>/dashboard"><?= e(__('My instances')) ?></a></li>
                    <?php if ($user->isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= base_url() ?>/admin"><?= e(__('Back-office')) ?></a></li>
                    <?php endif ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= component('avatar', ['user' => $user, 'size' => 28]) ?>
                            <span><?= e($user->name) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= base_url() ?>/account"><?= e(__('My account')) ?></a></li>
                            <?php if ($user->isAdmin()): ?>
                                <li><a class="dropdown-item" href="<?= base_url() ?>/admin"><?= e(__('Back-office')) ?></a></li>
                            <?php endif ?>
                            <li>
                                <form method="post" action="<?= base_url() ?>/logout">
                                    <?= csrf_field() ?>
                                    <button class="dropdown-item" type="submit"><?= e(__('Sign out')) ?></button>
                                </form>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url() ?>/login"><?= e(__('Sign in')) ?></a></li>
                    <?php if (registration_enabled()): ?>
                        <li class="nav-item"><a class="btn btn-light btn-sm px-3" href="<?= base_url() ?>/register"><?= e(__('Create account')) ?></a></li>
                    <?php endif ?>
                <?php endif ?>
            </ul>
        </div>
    </div>
</nav>
