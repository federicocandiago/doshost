<div class="auth-box mx-auto">
    <h1 class="h3 mb-4"><?= e(__('Create account')) ?></h1>
    <form method="post" action="<?= base_url() ?>/register" data-ajax novalidate>
        <?= csrf_field() ?>
        <?= component('field', ['name' => 'name', 'label' => __('Name'), 'required' => true, 'attrs' => 'autocomplete="name" maxlength="100" autofocus']) ?>
        <?= component('field', ['name' => 'email', 'type' => 'email', 'label' => __('Email'), 'required' => true, 'attrs' => 'autocomplete="email"']) ?>
        <?= component('field', ['name' => 'password', 'type' => 'password', 'label' => __('Password'), 'required' => true, 'help' => __('At least 8 characters.'), 'attrs' => 'autocomplete="new-password"']) ?>
        <?= component('field', ['name' => 'password_confirmation', 'type' => 'password', 'label' => __('Repeat password'), 'required' => true, 'attrs' => 'autocomplete="new-password"']) ?>
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
                <label class="form-label" for="f-keyboard_layout"><?= e(__('DOS keyboard')) ?></label>
                <select class="form-select" id="f-keyboard_layout" name="keyboard_layout">
                    <?php foreach ($layouts as $code => $label): ?>
                        <option value="<?= e($code) ?>" <?= $code === $defaultLayout ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach ?>
                </select>
                <div class="invalid-feedback" data-error-for="keyboard_layout"></div>
            </div>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="1" name="terms" id="f-terms">
            <label class="form-check-label small" for="f-terms">
                <?= __('I accept the <a href=":terms" target="_blank">Terms of Service</a> and the <a href=":privacy" target="_blank">Privacy Policy</a>.', ['terms' => url('/terms'), 'privacy' => url('/privacy')]) ?>
            </label>
            <div class="invalid-feedback" data-error-for="terms"></div>
        </div>
        <button class="btn btn-light w-100" type="submit"><?= e(__('Create account')) ?></button>
    </form>
    <p class="small text-secondary mt-4 text-center">
        <?= e(__('Already registered?')) ?> <a href="<?= base_url() ?>/login"><?= e(__('Sign in')) ?></a>
    </p>
</div>
