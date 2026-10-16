<?php /** @var App\Models\User $user @var array $layouts @var array $locales */ ?>
<div class="account mx-auto">
    <div class="d-flex align-items-center gap-3 mb-5">
        <?= component('avatar', ['user' => $user, 'size' => 64]) ?>
        <div>
            <h1 class="h3 mb-0"><?= e(__('My account')) ?></h1>
            <div class="text-secondary small mono"><?= e($user->email) ?></div>
        </div>
    </div>

    <section class="mb-5">
        <h2 class="h6 mono mb-3"><?= e(__('Profile')) ?></h2>
        <form class="card" method="post" action="<?= base_url() ?>/account/profile" data-ajax novalidate>
            <div class="card-body">
                <?= csrf_field() ?>
                <?= component('field', ['name' => 'name', 'label' => __('Name'), 'value' => $user->name, 'required' => true, 'attrs' => 'maxlength="100"']) ?>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="f-locale"><?= e(__('Site language')) ?></label>
                        <select class="form-select" id="f-locale" name="locale">
                            <?php foreach ($locales as $code): ?>
                                <option value="<?= e($code) ?>" <?= $code === locale() ? 'selected' : '' ?>><?= e($code === 'it' ? 'Italiano' : ($code === 'en' ? 'English' : strtoupper($code))) ?></option>
                            <?php endforeach ?>
                        </select>
                        <div class="invalid-feedback" data-error-for="locale"></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="f-keyboard_layout"><?= e(__('Default DOS keyboard')) ?></label>
                        <select class="form-select" id="f-keyboard_layout" name="keyboard_layout">
                            <?php foreach ($layouts as $code => $label): ?>
                                <option value="<?= e($code) ?>" <?= $code === $user->keyboard_layout ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <div class="form-text"><?= e(__('Used for new instances.')) ?></div>
                        <div class="invalid-feedback" data-error-for="keyboard_layout"></div>
                    </div>
                </div>
                <button class="btn btn-light" type="submit"><?= e(__('Save')) ?></button>
            </div>
        </form>
    </section>

    <section class="mb-5">
        <h2 class="h6 mono mb-3"><?= e(__('Profile picture')) ?></h2>
        <div class="card"><div class="card-body d-flex flex-column flex-sm-row gap-3 align-items-sm-center">
            <?= component('avatar', ['user' => $user, 'size' => 72]) ?>
            <form class="flex-grow-1 d-flex gap-2 align-items-start" method="post" action="<?= base_url() ?>/account/avatar" enctype="multipart/form-data" data-ajax novalidate>
                <?= csrf_field() ?>
                <div class="flex-grow-1">
                    <input class="form-control" type="file" name="avatar" accept="image/png,image/jpeg,image/gif,image/webp" aria-label="<?= e(__('Profile picture')) ?>">
                    <div class="form-text"><?= e(__('JPG, PNG, GIF or WebP. It will be cropped to a square.')) ?></div>
                    <div class="invalid-feedback" data-error-for="avatar"></div>
                </div>
                <button class="btn btn-outline-light" type="submit"><?= e(__('Upload')) ?></button>
            </form>
            <?php if ($user->avatar): ?>
                <form method="post" action="<?= base_url() ?>/account/avatar/delete" data-ajax>
                    <?= csrf_field() ?>
                    <button class="btn btn-link text-secondary" type="submit"><?= e(__('Remove')) ?></button>
                </form>
            <?php endif ?>
        </div></div>
    </section>

    <section class="mb-5">
        <h2 class="h6 mono mb-3"><?= e(__('Change password')) ?></h2>
        <form class="card" method="post" action="<?= base_url() ?>/account/password" data-ajax data-reset novalidate>
            <div class="card-body">
                <?= csrf_field() ?>
                <?= component('field', ['name' => 'current_password', 'type' => 'password', 'label' => __('Current password'), 'attrs' => 'autocomplete="current-password"']) ?>
                <div class="row g-3">
                    <div class="col-sm-6"><?= component('field', ['name' => 'password', 'type' => 'password', 'label' => __('New password'), 'help' => __('At least 8 characters.'), 'attrs' => 'autocomplete="new-password"']) ?></div>
                    <div class="col-sm-6"><?= component('field', ['name' => 'password_confirmation', 'type' => 'password', 'label' => __('Repeat password'), 'attrs' => 'autocomplete="new-password"']) ?></div>
                </div>
                <button class="btn btn-light" type="submit"><?= e(__('Change password')) ?></button>
            </div>
        </form>
    </section>

    <section class="mb-5">
        <h2 class="h6 mono mb-3 text-danger"><?= e(__('Delete account')) ?></h2>
        <form class="card border-danger-subtle" method="post" action="<?= base_url() ?>/account/delete" data-ajax
              data-ask="<?= e(__('Your account, all your instances and their disks will be deleted forever. Continue?')) ?>" novalidate>
            <div class="card-body">
                <?= csrf_field() ?>
                <p class="small text-secondary"><?= e(__('This deletes your account, all your instances and their disks. It cannot be undone.')) ?></p>
                <?= component('field', ['name' => 'confirm_password', 'type' => 'password', 'label' => __('Confirm with your password'), 'attrs' => 'autocomplete="current-password"']) ?>
                <button class="btn btn-outline-danger" type="submit"><?= e(__('Delete my account')) ?></button>
            </div>
        </form>
    </section>
</div>
