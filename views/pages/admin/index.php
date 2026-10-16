<?php /** @var array{items: App\Models\User[], page: int, pages: int, total: int} $users @var array<int, App\Models\Instance[]> $byUser @var array $stats */ ?>
<h1 class="h3 mb-4"><?= e(__('Back-office')) ?></h1>

<div class="row row-cols-2 row-cols-lg-4 g-3 mb-5">
    <?php foreach ([
        __('Users') => $stats['users'],
        __('Instances') => $stats['instances'],
        __('Paused') => $stats['paused'],
        __('Disk space used') => format_bytes($stats['disk']),
    ] as $label => $value): ?>
        <div class="col"><div class="card stat"><div class="card-body">
            <div class="small text-secondary"><?= e($label) ?></div><div class="fs-2 mono"><?= e($value) ?></div>
        </div></div></div>
    <?php endforeach ?>
</div>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-baseline mb-3 gap-3">
        <h2 class="h5 mb-0"><?= e(__('Users')) ?> <span class="text-secondary fw-normal small"><?= (int) $users['total'] ?></span></h2>
        <?php if ($byUser): ?>
            <button class="btn btn-link btn-sm text-secondary p-0" type="button" data-toggle-all=".user-instances"><?= e(__('Expand / collapse all')) ?></button>
        <?php endif ?>
    </div>

    <div class="d-flex flex-column gap-2">
    <?php foreach ($users['items'] as $user):
        $list = $byUser[$user->id] ?? [];
        $total = array_sum(array_map(static fn ($i) => (int) $i->disk_bytes, $list));
        $alerts = count(array_filter($list, static fn ($i) => $i->diskState() !== null));
        $isMe = (int) $user->id === (int) auth()->id; ?>
        <div class="user-row surface <?= $user->isSuspended() ? 'is-paused' : '' ?>">
            <div class="user-head">
                <button class="user-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#user-<?= (int) $user->id ?>-instances"
                        aria-expanded="false" aria-controls="user-<?= (int) $user->id ?>-instances">
                    <?= component('avatar', ['user' => $user, 'size' => 36]) ?>
                    <span class="min-w-0 text-start">
                        <span class="d-flex align-items-center gap-2">
                            <span class="fw-semibold text-truncate"><?= e($user->name) ?></span>
                            <span class="chevron" aria-hidden="true">›</span>
                        </span>
                        <span class="d-block small text-secondary">
                            <?= e(count($list) === 1 ? __('1 instance') : __(':n instances', ['n' => count($list)])) ?>
                            <?php if ($list): ?> · <?= e(format_bytes($total)) ?><?php endif ?>
                            <?php if ($alerts): ?> · <span class="text-danger"><?= e(__(':n disk alerts', ['n' => $alerts])) ?></span><?php endif ?>
                        </span>
                    </span>
                </button>

                <div class="user-meta">
                    <span class="mono small text-secondary text-truncate"><?= e($user->email) ?></span>
                    <?= component('status-badge', ['paused' => $user->isSuspended()]) ?>
                </div>

                <form class="user-limit" method="post" action="<?= base_url() ?>/admin/users/<?= (int) $user->id ?>" data-ajax id="user-<?= (int) $user->id ?>">
                    <?= csrf_field() ?>
                    <label class="small text-secondary text-nowrap" for="limit-<?= (int) $user->id ?>"><?= e(__('Limit')) ?></label>
                    <input class="form-control form-control-sm" type="number" min="0" max="1000" name="max_instances" id="limit-<?= (int) $user->id ?>"
                           value="<?= e($user->max_instances) ?>" placeholder="<?= (int) $maxInstances ?>" title="<?= e(__('Empty = default')) ?>">
                    <label class="form-check small mb-0 text-nowrap">
                        <input class="form-check-input" type="checkbox" name="is_admin" value="1" <?= $user->isAdmin() ? 'checked' : '' ?> <?= $isMe ? 'disabled' : '' ?>>
                        <?= e(__('Admin')) ?>
                    </label>
                    <?php if ($isMe): ?><input type="hidden" name="is_admin" value="1"><?php endif ?>
                    <button class="btn btn-sm btn-outline-dark" type="submit"><?= e(__('Save')) ?></button>
                </form>

                <div class="user-actions">
                    <?php if (!$isMe): ?>
                        <button class="btn btn-sm btn-outline-dark" data-post="<?= base_url() ?>/admin/users/<?= (int) $user->id ?>/suspend">
                            <?= e($user->isSuspended() ? __('Resume') : __('Pause')) ?></button>
                        <button class="btn btn-sm btn-outline-danger"
                                data-confirm="<?= e(__('The user :email and all their instances will be deleted.', ['email' => $user->email])) ?>"
                                data-action="<?= base_url() ?>/admin/users/<?= (int) $user->id ?>/delete"><?= e(__('Delete')) ?></button>
                    <?php endif ?>
                </div>
            </div>

            <div class="collapse user-instances" id="user-<?= (int) $user->id ?>-instances" data-remember="admin-user-<?= (int) $user->id ?>">
                <?php if (!$list): ?>
                    <p class="small text-secondary px-3 pb-3 mb-0"><?= e(__('No instances yet.')) ?></p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead><tr>
                            <th><?= e(__('Name')) ?></th><th><?= e(__('Status')) ?></th><th><?= e(__('Disk usage')) ?></th>
                            <th><?= e(__('Last started')) ?></th><th><?= e(__('Last saved')) ?></th><th></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($list as $instance): ?>
                            <tr class="<?= $instance->isSuspended() ? 'is-paused' : '' ?>">
                                <td class="mono"><?= e($instance->name) ?></td>
                                <td><?= component('status-badge', ['paused' => $instance->isSuspended()]) ?></td>
                                <td style="min-width: 220px"><?= component('disk-usage', compact('instance')) ?></td>
                                <td><?= e(format_date($instance->last_played_at)) ?></td>
                                <td><?= e(format_date($instance->last_saved_at)) ?></td>
                                <td class="text-end text-nowrap">
                                    <button class="btn btn-sm btn-outline-dark" data-post="<?= base_url() ?>/admin/instances/<?= e($instance->uuid) ?>/suspend">
                                        <?= e($instance->isSuspended() ? __('Resume') : __('Pause')) ?></button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-confirm="<?= e(__('The instance ":name" and its disk will be deleted forever.', ['name' => $instance->name])) ?>"
                                            data-action="<?= base_url() ?>/admin/instances/<?= e($instance->uuid) ?>/delete"><?= e(__('Delete')) ?></button>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?php endif ?>
            </div>
        </div>
    <?php endforeach ?>
    </div>

    <?= component('pagination', ['page' => $users['page'], 'pages' => $users['pages'], 'url' => url('/admin')]) ?>
</section>

<section>
    <h2 class="h5 mb-3"><?= e(__('Settings')) ?></h2>
    <form class="settings surface" method="post" action="<?= base_url() ?>/admin/settings" data-ajax novalidate>
        <?= csrf_field() ?>
        <div class="setting">
            <div>
                <label class="setting-title" for="f-default_max_instances"><?= e(__('Instances per user')) ?></label>
                <p class="setting-help"><?= e(__('Default limit. Users with a personal limit are not affected.')) ?></p>
            </div>
            <div class="setting-control">
                <input class="form-control" type="number" min="0" max="1000" id="f-default_max_instances" name="default_max_instances" value="<?= (int) $maxInstances ?>">
                <div class="invalid-feedback" data-error-for="default_max_instances"></div>
            </div>
        </div>
        <div class="setting">
            <div>
                <label class="setting-title" for="f-autosave_minutes"><?= e(__('Automatic save')) ?></label>
                <p class="setting-help"><?= e(__('Minutes between automatic saves of a running instance. The disk is also saved when the user leaves it.')) ?></p>
            </div>
            <div class="setting-control">
                <div class="input-group">
                    <input class="form-control" type="number" min="1" max="120" id="f-autosave_minutes" name="autosave_minutes" value="<?= (int) $autosaveMinutes ?>">
                    <span class="input-group-text">min</span>
                </div>
                <div class="invalid-feedback" data-error-for="autosave_minutes"></div>
            </div>
        </div>
        <div class="setting">
            <div>
                <label class="setting-title" for="f-registration_enabled"><?= e(__('Registration')) ?></label>
                <p class="setting-help"><?= e(__('When closed, the sign-up page is hidden and only existing users can sign in.')) ?></p>
            </div>
            <div class="setting-control">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="f-registration_enabled" name="registration_enabled" value="1" <?= $registrationEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="f-registration_enabled"><?= e(__('Open')) ?></label>
                </div>
            </div>
        </div>
        <div class="settings-footer">
            <span class="setting-help m-0"><?= e(__('Initial values come from .env (DEFAULT_MAX_INSTANCES, AUTOSAVE_MINUTES, REGISTRATION_ENABLED).')) ?></span>
            <button class="btn btn-dark" type="submit"><?= e(__('Save settings')) ?></button>
        </div>
    </form>
</section>
