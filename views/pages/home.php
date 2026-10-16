<section class="hero">
    <div class="row">
        <div class="col-lg-8 col-xl-7">
            <h1 class="display-3 fw-bold mb-4"><?= e(env('APP_NAME')) ?></h1>
            <p class="lead text-secondary mb-5"><?= e(__((string) env('APP_DESCRIPTION', ''))) ?></p>
            <div class="d-flex flex-wrap gap-2">
                <?php if (auth()): ?>
                    <a href="<?= base_url() ?>/dashboard" class="btn btn-light btn-lg px-4"><?= e(__('Go to my instances')) ?></a>
                <?php else: ?>
                    <a href="<?= base_url() ?>/login" class="btn btn-light btn-lg px-4"><?= e(__('Sign in')) ?></a>
                    <?php if (registration_enabled()): ?>
                        <a href="<?= base_url() ?>/register" class="btn btn-outline-light btn-lg px-4"><?= e(__('Create account')) ?></a>
                    <?php endif ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>

<section class="features row row-cols-1 row-cols-md-3 g-4">
    <div class="col">
        <h2 class="h6 mono">01. <?= e(__('Create')) ?></h2>
        <p class="text-secondary small"><?= e(__('Spin up your own DOS machines, each with its own C: drive.')) ?></p>
    </div>
    <div class="col">
        <h2 class="h6 mono">02. <?= e(__('Boot')) ?></h2>
        <p class="text-secondary small"><?= e(__('DOSBox runs right in your browser, full screen. Nothing to install.')) ?></p>
    </div>
    <div class="col">
        <h2 class="h6 mono">03. <?= e(__('Keep')) ?></h2>
        <p class="text-secondary small"><?= e(__('Your disk is saved on the server automatically and when you leave.')) ?></p>
    </div>
</section>
