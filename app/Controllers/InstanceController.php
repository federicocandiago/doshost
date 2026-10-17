<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Instance;
use App\Models\Setting;
use App\Services\DiskService;

final class InstanceController extends Controller
{
    private DiskService $disks;

    public function __construct()
    {
        $this->disks = new DiskService();
    }

    public function store(Request $request): Response
    {
        $user = auth();
        if (!$user->canCreateInstance()) {
            return $this->fail(__('You have reached your instance limit.'), [], 403);
        }

        $name = (string) $request->input('name', '');
        $description = (string) $request->input('description', '');
        $cycles = (string) $request->input('cycles', 'max');
        $layout = (string) $request->input('keyboard_layout', $user->keyboard_layout ?: 'us');
        $uploads = $request->files('files');

        $errors = [];
        if (mb_strlen($name) < 1 || mb_strlen($name) > 60) {
            $errors['name'] = __('The name is required (max 60 characters).');
        }
        if (mb_strlen($description) > 255) {
            $errors['description'] = __('The description is too long.');
        }
        if (!array_key_exists($cycles, Instance::cpuProfiles())) {
            $errors['cycles'] = __('Invalid CPU profile.');
        }
        if (!array_key_exists($layout, DiskService::KEYBOARD_LAYOUTS)) {
            $errors['keyboard_layout'] = __('Invalid keyboard layout.');
        }
        if ($error = $this->uploadError($uploads)) {
            $errors['files'] = $error;
        }
        if ($errors) {
            return $this->fail(__('Please check the form.'), $errors);
        }

        $uuid = self::uuid();
        try {
            $size = $this->disks->create($uuid, $name, $cycles, $layout, array_column($uploads, 'tmp_name'));
        } catch (\LengthException) {
            $this->disks->destroy($uuid);
            return $this->fail(__('Please check the form.'), [
                'files' => __('The files exceed the maximum disk size of :size MB.', ['size' => env('DISK_MAX_MB', 64)]),
            ]);
        } catch (\InvalidArgumentException) {
            $this->disks->destroy($uuid);
            return $this->fail(__('Please check the form.'), ['files' => __('One of the files is not a valid zip archive.')]);
        }

        Instance::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'name' => $name,
            'description' => $description ?: null,
            'cycles' => $cycles,
            'keyboard_layout' => $layout,
            'show_cursor' => $request->input('show_cursor') ? 1 : 0,
            'start_fullscreen' => $request->input('start_fullscreen') ? 1 : 0,
            'disk_bytes' => $size,
        ]);

        return $this->ok(__('Instance ":name" created.', ['name' => $name]), ['redirect' => '/dashboard']);
    }

    public function update(Request $request, string $uuid): Response
    {
        $instance = $this->owned($uuid);
        if ($instance->isSuspended()) {
            return $this->fail(__('This instance is paused.'), [], 423);
        }

        $name = (string) $request->input('name', '');
        $description = (string) $request->input('description', '');
        $cycles = (string) $request->input('cycles', $instance->cycles);
        $layout = (string) $request->input('keyboard_layout', $instance->keyboard_layout);

        $errors = [];
        if (mb_strlen($name) < 1 || mb_strlen($name) > 60) {
            $errors['name'] = __('The name is required (max 60 characters).');
        }
        if (mb_strlen($description) > 255) {
            $errors['description'] = __('The description is too long.');
        }
        if (!array_key_exists($cycles, Instance::cpuProfiles())) {
            $errors['cycles'] = __('Invalid CPU profile.');
        }
        if (!array_key_exists($layout, DiskService::KEYBOARD_LAYOUTS)) {
            $errors['keyboard_layout'] = __('Invalid keyboard layout.');
        }
        if ($errors) {
            return $this->fail(__('Please check the form.'), $errors);
        }

        $data = [
            'name' => $name,
            'description' => $description ?: null,
            'cycles' => $cycles,
            'keyboard_layout' => $layout,
            'show_cursor' => $request->input('show_cursor') ? 1 : 0,
            'start_fullscreen' => $request->input('start_fullscreen') ? 1 : 0,
        ];
        if ($cycles !== $instance->cycles || $layout !== $instance->keyboard_layout) {
            $data['disk_bytes'] = $this->disks->updateConfig($instance->uuid, $cycles, $layout);
        }
        $instance->update($data);

        return $this->ok(__('Instance ":name" updated.', ['name' => $name]), ['reload' => true]);
    }

    /** Adds the content of one or more zips to C:, keeping everything already there. */
    public function addFiles(Request $request, string $uuid): Response
    {
        $instance = $this->owned($uuid);
        if ($instance->isSuspended()) {
            return $this->fail(__('This instance is paused.'), [], 423);
        }

        $uploads = $request->files('files');
        $error = $uploads ? $this->uploadError($uploads) : __('Choose at least one .zip file.');
        if ($error) {
            return $this->fail(__('Please check the form.'), ['files' => $error]);
        }

        try {
            $result = $this->disks->addFiles($instance->uuid, array_column($uploads, 'tmp_name'));
        } catch (\LengthException) {
            return $this->fail(__('Please check the form.'), [
                'files' => __('The files exceed the maximum disk size of :size MB.', ['size' => env('DISK_MAX_MB', 64)]),
            ]);
        } catch (\InvalidArgumentException) {
            return $this->fail(__('Please check the form.'), ['files' => __('One of the files is not a valid zip archive.')]);
        }

        $instance->update(['disk_bytes' => $result['bytes'], 'disk_full' => 0]);

        $message = $result['added'] === 1
            ? __('1 file added to C:.')
            : __(':n files added to C:.', ['n' => $result['added']]);
        if ($result['skipped'] > 0) {
            $message .= ' ' . ($result['skipped'] === 1
                ? __('1 already on the disk, left untouched.')
                : __(':n already on the disk, left untouched.', ['n' => $result['skipped']]));
        }

        return $this->ok($message, ['reload' => true]);
    }

    /** Star / unstar: favourites get their own row at the top of the dashboard. */
    public function favorite(Request $request, string $uuid): Response
    {
        $instance = $this->owned($uuid);
        $instance->update(['is_favorite' => $instance->is_favorite ? 0 : 1]);

        return $this->ok('', ['reload' => true]);
    }

    public function destroy(Request $request, string $uuid): Response
    {
        $instance = $this->owned($uuid);
        $this->disks->destroy($instance->uuid);
        $instance->delete();

        return $this->ok(__('Instance deleted.'), ['redirect' => '/dashboard']);
    }

    public function play(Request $request, string $uuid): Response
    {
        $instance = $this->owned($uuid);
        if ($instance->isSuspended()) {
            return $this->stopScreen();
        }
        $this->disks->ensureExitLoop($instance->uuid);
        $instance->update(['last_played_at' => date('Y-m-d H:i:s')]);

        return view('pages/play', [
            'title' => $instance->name,
            'instance' => $instance,
            'config' => [
                // versioned URL: a new save means a new URL, never a stale copy
                'bundleUrl' => url("/instances/{$instance->uuid}/bundle.jsdos") . '?v=' . filemtime($this->disks->bundlePath($instance->uuid)),
                'saveUrl' => url("/instances/{$instance->uuid}/disk"),
                'pathPrefix' => url('/vendor/js-dos/emulators/'),
                'backUrl' => url('/dashboard'),
                'csrf' => csrf_token(),
                'autosaveMinutes' => Setting::autosaveMinutes(),
                'maxBytes' => DiskService::maxBytes(),
                'lang' => locale(),
                'keyboardLayout' => $instance->keyboard_layout,
                'showCursor' => (bool) $instance->show_cursor,
                'startFullscreen' => (bool) $instance->start_fullscreen,
                'uuid' => $instance->uuid,
                'exitFlag' => DiskService::EXIT_FLAG,
                'clockFile' => DiskService::CLOCK_FILE,
                'i18n' => [
                    'loading' => __('Booting…'),
                    'ready' => __('Ready'),
                    'saving' => __('Saving disk…'),
                    'saved' => __('Saved at :time'),
                    'nothing' => __('No changes to save'),
                    'error' => __('Save failed!'),
                    'full' => $this->fullMessage(),
                    'leave' => __('Your latest changes may not be saved yet.'),
                    'fullscreenHint' => __('Click or press a key to go full screen'),
                ],
            ],
        ], 'layouts/fullscreen');
    }

    public function bundle(Request $request, string $uuid): Response
    {
        $instance = $this->owned($uuid);
        if ($instance->isSuspended()) {
            return $this->fail(__('This instance is paused.'), [], 423);
        }

        return Response::file($this->disks->bundlePath($instance->uuid), 'application/zip');
    }

    /** Receives the whole C: drive (zip) from the browser. */
    public function saveDisk(Request $request, string $uuid): Response
    {
        $instance = $this->owned($uuid);
        if ($instance->isSuspended()) {
            return $this->fail(__('This instance is paused.'), [], 423);
        }

        try {
            if ($request->contentLength() > DiskService::maxBytes()) {
                throw new \LengthException();
            }
            $bytes = $this->disks->save($instance->uuid, $request->raw());
        } catch (\LengthException) {
            $instance->update(['disk_full' => 1]);
            return $this->fail($this->fullMessage(), [], 413);
        } catch (\InvalidArgumentException) {
            return $this->fail(__('Invalid disk data.'), [], 400);
        }

        $now = date('Y-m-d H:i:s');
        $instance->update(['disk_bytes' => $bytes, 'disk_full' => 0, 'last_saved_at' => $now]);

        return $this->ok('', [
            'bytes' => $bytes,
            'percent' => $instance->usagePercent(),
            'warning' => $instance->diskState() === 'warning',
            'saved_at' => $now,
        ]);
    }

    /** Error message for a list of uploads, or null when all are valid zip files. */
    private function uploadError(array $uploads): ?string
    {
        foreach ($uploads as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return __('Upload of ":file" failed (is it too large?).', ['file' => $file['name']]);
            }
            if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'zip') {
                return __('Only .zip files can be uploaded.');
            }
        }

        return null;
    }

    private function owned(string $uuid): Instance
    {
        $instance = Instance::findByUuid($uuid);
        if ($instance === null || !$instance->belongsTo(auth())) {
            abort(404);
        }

        return $instance;
    }

    private function stopScreen(): Response
    {
        return view('pages/stop', [
            'title' => __('Instance paused'),
            'heading' => __('Instance paused'),
            'text' => __('This instance has been paused by an administrator and cannot be started right now. Its disk is kept safe.'),
        ], 'layouts/minimal', 423);
    }

    private function fullMessage(): string
    {
        return __('Disk full (max :size MB): this instance no longer saves. Delete some files to free space.', ['size' => env('DISK_MAX_MB', 64)]);
    }

    private static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
