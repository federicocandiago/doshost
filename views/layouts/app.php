<!doctype html>
<html lang="<?= e(locale()) ?>" data-bs-theme="<?= ($theme ?? 'dark') === 'light' ? 'light' : 'dark' ?>">
<head>
    <?= component('head', ['title' => $title ?? null]) ?>
</head>
<body class="d-flex flex-column min-vh-100 theme-<?= e($theme ?? 'dark') ?>">
    <?= component('navbar') ?>
    <main class="flex-grow-1">
        <div class="container">
            <?= component('flash') ?>
            <?= $content ?>
        </div>
    </main>
    <?= component('footer') ?>
    <?= component('confirm-modal') ?>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts"></div>
    <script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>
