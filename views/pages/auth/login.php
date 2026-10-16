<div class="auth-box mx-auto">
    <h1 class="h3 mb-4"><?= e(__('Sign in')) ?></h1>
    <form method="post" action="<?= base_url() ?>/login" data-ajax novalidate>
        <?= csrf_field() ?>
        <?= component('field', ['name' => 'email', 'type' => 'email', 'label' => __('Email'), 'required' => true, 'attrs' => 'autocomplete="email" autofocus']) ?>
        <?= component('field', ['name' => 'password', 'type' => 'password', 'label' => __('Password'), 'required' => true, 'attrs' => 'autocomplete="current-password"']) ?>
        <button class="btn btn-light w-100 mt-2" type="submit"><?= e(__('Sign in')) ?></button>
    </form>
    <?php if (registration_enabled()): ?>
        <p class="small text-secondary mt-4 text-center">
            <?= e(__('No account yet?')) ?> <a href="<?= base_url() ?>/register"><?= e(__('Create one')) ?></a>
        </p>
    <?php endif ?>
</div>
