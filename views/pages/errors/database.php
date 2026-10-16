<p class="mono text-secondary mb-2">Abort, Retry, Fail?</p>
<h1 class="h2 mb-3"><?= e($title) ?></h1>
<p class="text-secondary"><?= e(__('The service cannot reach its database right now. Please try again in a few minutes.')) ?></p>
<?php if (env('APP_DEBUG')): ?>
    <pre class="crt small text-wrap"><?= e($detail) ?></pre>
<?php endif ?>
<a class="btn btn-outline-light mt-3" href="javascript:location.reload()"><?= e(__('Retry')) ?></a>
