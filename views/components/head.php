<?php /** @var string|null $title */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, interactive-widget=resizes-content">
<title><?= e(isset($title) ? $title . ' · ' . env('APP_NAME') : env('APP_NAME')) ?></title>
<meta name="description" content="<?= e(__((string) env('APP_DESCRIPTION', ''))) ?>">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<?php /* "Add to Home screen": the site opens like an app, full screen (iOS: standalone, dark status bar) */ ?>
<link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
<meta name="theme-color" content="#000000">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e(env('APP_NAME')) ?>">
<link rel="apple-touch-icon" href="<?= asset('assets/icons/apple-touch-icon.png') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><circle cx='16' cy='16' r='16' fill='black'/><path d='M13 10l6 6-6 6' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/></svg>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
