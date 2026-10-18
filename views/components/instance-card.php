<?php /** @var App\Models\Instance $instance @var array $cpuProfiles */
$state = $instance->diskState(); ?>
<div class="col">
    <div class="card instance h-100 <?= $instance->isSuspended() ? 'is-paused' : '' ?>">
        <div class="card-body d-flex flex-column">
            <?= component('disk-usage', compact('instance')) ?>
            <div class="d-flex justify-content-between align-items-start gap-2 mt-2">
                <h3 class="h5 mono mb-1 text-break d-flex align-items-center gap-2">
                    <button class="star-toggle <?= $instance->is_favorite ? 'is-on' : '' ?>" type="button"
                            data-post="<?= e(url("/instances/{$instance->uuid}/favorite")) ?>"
                            aria-pressed="<?= $instance->is_favorite ? 'true' : 'false' ?>"
                            title="<?= e($instance->is_favorite ? __('Remove from favourites') : __('Add to favourites')) ?>"
                            aria-label="<?= e($instance->is_favorite ? __('Remove from favourites') : __('Add to favourites')) ?>"><?= component('icon', ['name' => 'star', 'size' => 18]) ?></button>
                    <span><?= e($instance->name) ?></span>
                </h3>
                <?php if ($instance->isSuspended()): ?>
                    <span class="badge text-bg-warning"><?= e(__('Paused')) ?></span>
                <?php else: ?>
                    <span class="badge text-bg-dark border border-secondary-subtle mono"><?= e($cpuProfiles[$instance->cycles] ?? $instance->cycles) ?></span>
                <?php endif ?>
            </div>
            <?php if ($instance->description): ?>
                <p class="text-secondary small mb-3"><?= e($instance->description) ?></p>
            <?php endif ?>
            <dl class="small text-secondary mb-3 meta">
                <dt><?= e(__('Last started')) ?></dt><dd><?= e(format_date($instance->last_played_at)) ?></dd>
                <dt><?= e(__('Last saved')) ?></dt><dd><?= e(format_date($instance->last_saved_at)) ?></dd>
            </dl>

            <?php if ($state === 'full'): ?>
                <div class="disk-alert is-full small mb-3"><?= e(__('Disk full: this instance no longer saves. Delete some files inside DOS to free space.')) ?></div>
            <?php elseif ($state === 'warning'): ?>
                <div class="disk-alert small mb-3"><?= e(__('The disk is almost full (:pct%). When it reaches 100% the instance stops saving.', ['pct' => $instance->usagePercent()])) ?></div>
            <?php endif ?>
            <?php if ($instance->isSuspended()): ?>
                <div class="disk-alert small mb-3"><?= e(__('Paused by an administrator.')) ?></div>
            <?php endif ?>

            <div class="mt-auto d-flex gap-2">
                <?php if ($instance->isSuspended()): ?>
                    <button class="btn btn-outline-secondary flex-grow-1" disabled>■ <?= e(__('Paused')) ?></button>
                <?php else: ?>
                    <a class="btn btn-light flex-grow-1" href="<?= base_url() ?>/play/<?= e($instance->uuid) ?>">▶ <?= e(__('Boot')) ?></a>
                    <button class="btn btn-outline-light btn-icon" type="button" data-bs-toggle="modal" data-bs-target="#files-modal"
                            title="<?= e(__('Add files to C:')) ?>" aria-label="<?= e(__('Add files to C:')) ?>"
                            data-fill="<?= e(json_encode([
                                'action' => url("/instances/{$instance->uuid}/files"),
                                'instanceName' => $instance->name,
                            ], JSON_UNESCAPED_UNICODE)) ?>"><?= component('icon', ['name' => 'file-plus']) ?></button>
                    <button class="btn btn-outline-light btn-icon" type="button" data-bs-toggle="modal" data-bs-target="#edit-modal"
                            title="<?= e(__('Settings')) ?>" aria-label="<?= e(__('Settings')) ?>"
                            data-fill="<?= e(json_encode([
                                'action' => url("/instances/{$instance->uuid}/update"),
                                'name' => $instance->name,
                                'description' => $instance->description,
                                'cycles' => $instance->cycles,
                                'keyboard_layout' => $instance->keyboard_layout,
                                'show_cursor' => (bool) $instance->show_cursor,
                                'start_fullscreen' => (bool) $instance->start_fullscreen,
                                'deleteAction' => url("/instances/{$instance->uuid}/delete"),
                                'backupUrl' => url("/instances/{$instance->uuid}/backup"),
                                'deleteText' => __('The instance ":name" and its disk will be deleted forever.', ['name' => $instance->name]),
                            ], JSON_UNESCAPED_UNICODE)) ?>"><?= component('icon', ['name' => 'gear']) ?></button>
                <?php endif ?>
                <?php if ($instance->isSuspended()): ?>
                    <a class="btn btn-outline-light btn-icon" href="<?= e(url("/instances/{$instance->uuid}/backup")) ?>" download
                       title="<?= e(__('Download backup')) ?>" aria-label="<?= e(__('Download backup')) ?>"><?= component('icon', ['name' => 'download']) ?></a>
                    <button class="btn btn-outline-danger"
                            data-confirm="<?= e(__('The instance ":name" and its disk will be deleted forever.', ['name' => $instance->name])) ?>"
                            data-action="<?= base_url() ?>/instances/<?= e($instance->uuid) ?>/delete"><?= e(__('Delete')) ?></button>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
