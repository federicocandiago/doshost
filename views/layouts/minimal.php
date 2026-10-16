<!doctype html>
<html lang="<?= e(locale()) ?>" data-bs-theme="dark">
<head>
    <?= component('head', ['title' => $title ?? null]) ?>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <main class="container py-5" style="max-width: 640px">
        <?= $content ?>
    </main>
</body>
</html>
