<?php /** @var App\Models\Instance[] $instances */ ?>
<div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0"><?= e(__('My instances')) ?></h1>
    <?php if (count($instances) > 1): ?>
        <form method="get" action="<?= base_url() ?>/dashboard" class="d-flex align-items-center gap-2">
            <label class="small text-secondary text-nowrap" for="sort"><?= e(__('Sort by')) ?></label>
            <select class="form-select form-select-sm" id="sort" name="sort" data-autosubmit>
                <?php foreach (['recent' => __('Last started'), 'name' => __('Name'), 'size' => __('Disk usage')] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach ?>
            </select>
        </form>
    <?php endif ?>
</div>

<?= component('quota', compact('used', 'limit', 'available')) ?>

<?php if (!$instances): ?>
    <div class="empty text-center text-secondary py-5">
        <p><?= e(__('You have no instances yet. Create your first DOS machine!')) ?></p>
    </div>
<?php else: ?>
    <?php
    $favorites = array_filter($instances, static fn ($i) => (bool) $i->is_favorite);
    $others = array_filter($instances, static fn ($i) => !$i->is_favorite);
    ?>
    <?php if ($favorites): ?>
        <h2 class="h6 text-secondary mb-3 d-flex align-items-center gap-2"><?= component('icon', ['name' => 'star', 'size' => 16]) ?> <?= e(__('Favourites')) ?></h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 mb-5">
            <?php foreach ($favorites as $instance): ?>
                <?= component('instance-card', compact('instance', 'cpuProfiles')) ?>
            <?php endforeach ?>
        </div>
        <?php if ($others): ?><h2 class="h6 text-secondary mb-3"><?= e(__('Other instances')) ?></h2><?php endif ?>
    <?php endif ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php foreach ($others as $instance): ?>
            <?= component('instance-card', compact('instance', 'cpuProfiles')) ?>
        <?php endforeach ?>
    </div>
<?php endif ?>

<div class="modal fade" id="create-modal" tabindex="-1" aria-labelledby="create-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= base_url() ?>/instances" enctype="multipart/form-data" data-ajax novalidate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="create-title"><?= e(__('New instance')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(__('Close')) ?>"></button>
            </div>
            <div class="modal-body">
                <?= component('field', ['name' => 'name', 'label' => __('Name'), 'required' => true, 'attrs' => 'maxlength="60" placeholder="My DOS box"']) ?>
                <?= component('field', ['name' => 'description', 'type' => 'textarea', 'label' => __('Description (optional)'), 'attrs' => 'maxlength="255"']) ?>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="f-cycles"><?= e(__('CPU speed')) ?></label>
                        <select class="form-select" id="f-cycles" name="cycles">
                            <?php foreach ($cpuProfiles as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $value === 'max' ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <div class="invalid-feedback" data-error-for="cycles"></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="f-keyboard_layout"><?= e(__('DOS keyboard')) ?></label>
                        <select class="form-select" id="f-keyboard_layout" name="keyboard_layout">
                            <?php foreach ($layouts as $code => $label): ?>
                                <option value="<?= e($code) ?>" <?= $code === auth()->keyboard_layout ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <div class="invalid-feedback" data-error-for="keyboard_layout"></div>
                    </div>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="f-show_cursor" name="show_cursor" value="1" checked>
                    <label class="form-check-label" for="f-show_cursor"><?= e(__('Show the system cursor')) ?></label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="f-start_fullscreen" name="start_fullscreen" value="1">
                    <label class="form-check-label" for="f-start_fullscreen"><?= e(__('Start in full screen')) ?></label>
                </div>
                <div class="mb-1">
                    <label class="form-label" for="f-files"><?= e(__('Files for C: (optional)')) ?></label>
                    <input class="form-control" type="file" id="f-files" name="files[]" accept=".zip,application/zip" multiple>
                    <div class="form-text"><?= e(__('One or more .zip archives: their content is extracted into C:. Max :size MB in total.', ['size' => env('DISK_MAX_MB', 64)])) ?></div>
                    <div class="invalid-feedback" data-error-for="files"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal"><?= e(__('Cancel')) ?></button>
                <button type="submit" class="btn btn-light"><?= e(__('Create')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="edit-modal" tabindex="-1" aria-labelledby="edit-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="#" data-ajax novalidate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="edit-title"><?= e(__('Edit instance')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(__('Close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="e-name"><?= e(__('Name')) ?></label>
                    <input class="form-control" id="e-name" name="name" maxlength="60" required>
                    <div class="invalid-feedback" data-error-for="name"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="e-description"><?= e(__('Description (optional)')) ?></label>
                    <textarea class="form-control" id="e-description" name="description" rows="2" maxlength="255"></textarea>
                    <div class="invalid-feedback" data-error-for="description"></div>
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="e-cycles"><?= e(__('CPU speed')) ?></label>
                        <select class="form-select" id="e-cycles" name="cycles">
                            <?php foreach ($cpuProfiles as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <div class="invalid-feedback" data-error-for="cycles"></div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="e-keyboard_layout"><?= e(__('DOS keyboard')) ?></label>
                        <select class="form-select" id="e-keyboard_layout" name="keyboard_layout">
                            <?php foreach ($layouts as $code => $label): ?>
                                <option value="<?= e($code) ?>"><?= e($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <div class="invalid-feedback" data-error-for="keyboard_layout"></div>
                    </div>
                </div>
                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="e-show_cursor" name="show_cursor" value="1">
                    <label class="form-check-label" for="e-show_cursor"><?= e(__('Show the system cursor')) ?></label>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="e-start_fullscreen" name="start_fullscreen" value="1">
                    <label class="form-check-label" for="e-start_fullscreen"><?= e(__('Start in full screen')) ?></label>
                </div>
                <div class="form-text mt-2"><?= e(__('Speed and keyboard apply from the next boot. Close the instance before changing them.')) ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link text-danger me-auto px-0" data-bind="data-action:deleteAction,data-confirm:deleteText"><?= e(__('Delete instance')) ?></button>
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal"><?= e(__('Cancel')) ?></button>
                <button type="submit" class="btn btn-light"><?= e(__('Save')) ?></button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="files-modal" tabindex="-1" aria-labelledby="files-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="#" enctype="multipart/form-data" data-ajax data-reset novalidate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="files-title"><?= e(__('Add files to C:')) ?> <span class="text-secondary mono fs-6" data-bind="text:instanceName"></span></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(__('Close')) ?>"></button>
            </div>
            <div class="modal-body">
                <label class="dropzone" data-dropzone>
                    <input type="file" name="files[]" accept=".zip,application/zip" multiple>
                    <?= component('icon', ['name' => 'upload', 'size' => 28]) ?>
                    <span class="dropzone-text"><?= e(__('Drop .zip files here or click to choose')) ?></span>
                    <span class="dropzone-files mono small" data-dropzone-list></span>
                </label>
                <div class="invalid-feedback" data-error-for="files"></div>
                <p class="form-text mt-3 mb-0"><?= e(__('The content of the zips is added to C:. Files already on the disk are never overwritten. Close the instance before adding files. Max :size MB in total.', ['size' => env('DISK_MAX_MB', 64)])) ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal"><?= e(__('Cancel')) ?></button>
                <button type="submit" class="btn btn-light"><?= e(__('Add files')) ?></button>
            </div>
        </form>
    </div>
</div>
