<?php /** Small, quiet disk meter. @var App\Models\Instance $instance */
$state = $instance->diskState(); ?>
<div class="disk-usage <?= $state ? 'is-' . $state : '' ?>" title="<?= e(__('Disk usage')) ?>">
    <span class="mono"><?= e(format_bytes((int) $instance->disk_bytes)) ?> / <?= e(format_bytes(App\Services\DiskService::maxBytes())) ?></span>
    <span class="disk-bar"><span style="width: <?= max(1, $instance->usagePercent()) ?>%"></span></span>
    <span class="mono"><?= $instance->usagePercent() ?>%</span>
</div>
