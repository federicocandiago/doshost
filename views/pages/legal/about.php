<article class="legal mx-auto">
    <h1 class="h3 mb-4"><?= e(__('About')) ?></h1>
    <p class="lead"><?= e(__((string) env('APP_DESCRIPTION', ''))) ?></p>
    <p><?= e(__(':app is a small self-hosted platform. Every instance is a DOSBox machine compiled to WebAssembly (js-dos) that runs inside your browser: the server only stores your disk.', ['app' => env('APP_NAME')])) ?></p>
    <h2 class="h6 mono mt-4"><?= e(__('How saving works')) ?></h2>
    <p><?= e(__('While you use an instance, the changes to drive C: are sent to the server every :min minutes, when you press Save and when you leave the page.', ['min' => App\Models\Setting::autosaveMinutes()])) ?></p>
    <h2 class="h6 mono mt-4"><?= e(__('Credits')) ?></h2>
    <p><?= e(__('Built with PHP, MySQL, Bootstrap and js-dos (DOSBox).')) ?></p>
</article>
