<footer class="site-footer small text-secondary">
    <div class="container d-flex flex-column flex-md-row gap-2 justify-content-between">
        <div>&copy; <?= date('Y') ?> <?= e(env('APP_NAME')) ?></div>
        <nav class="d-flex flex-wrap gap-3">
            <a href="<?= base_url() ?>/about"><?= e(__('About')) ?></a>
            <a href="<?= base_url() ?>/terms"><?= e(__('Terms of Service')) ?></a>
            <a href="<?= base_url() ?>/privacy"><?= e(__('Privacy Policy')) ?></a>
            <a href="<?= base_url() ?>/cookies"><?= e(__('Cookie Policy')) ?></a>
            <span class="text-secondary-emphasis">
                <?php foreach (App\Core\Translator::available() as $i => $code): ?>
                    <?= $i ? '/' : '' ?>
                    <a href="<?= base_url() ?>/locale/<?= e($code) ?>" class="<?= $code === locale() ? 'text-white' : '' ?>"><?= e(strtoupper($code)) ?></a>
                <?php endforeach ?>
            </span>
        </nav>
    </div>
</footer>
