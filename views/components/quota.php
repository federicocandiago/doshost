<?php /** @var int $used @var int $limit @var int $available */
$pct = $limit > 0 ? min(100, (int) round($used / $limit * 100)) : 100;
?>
<div class="quota card mb-4">
    <div class="card-body d-flex flex-column flex-md-row gap-3 align-items-md-center">
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between small mb-2">
                <span><?= e(__(':used of :limit instances in use', ['used' => $used, 'limit' => $limit])) ?></span>
                <span class="mono"><?= e(__(':n available', ['n' => $available])) ?></span>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" style="height: 6px">
                <div class="progress-bar bg-light" style="width: <?= $pct ?>%"></div>
            </div>
        </div>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#create-modal" <?= $available > 0 ? '' : 'disabled' ?>>
            + <?= e(__('New instance')) ?>
        </button>
    </div>
</div>
