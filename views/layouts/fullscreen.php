<!doctype html>
<html lang="<?= e(locale()) ?>" data-bs-theme="dark" class="h-100">
<head>
    <?= component('head', ['title' => $title ?? null]) ?>
    <link rel="stylesheet" href="<?= asset('vendor/js-dos/js-dos.css') ?>">
</head>
<body class="player h-100 overflow-hidden">
    <?= $content ?>
</body>
</html>
