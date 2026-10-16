<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Instance;
use App\Models\Setting;
use App\Models\User;
use App\Services\DiskService;

final class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $db = Database::connection();
        $users = User::paginate((int) $request->input('page', 1), 10);

        // Instances of the users on this page, grouped by owner
        $byUser = [];
        $ids = array_map(static fn (User $u): int => (int) $u->id, $users['items']);
        if ($ids) {
            $instances = Instance::where('user_id IN (' . implode(',', $ids) . ')', [], 'COALESCE(last_played_at, created_at) DESC');
            foreach ($instances as $instance) {
                $byUser[$instance->user_id][] = $instance;
            }
        }

        return view('pages/admin/index', [
            'title' => __('Back-office'),
            'theme' => 'light',
            'users' => $users,
            'byUser' => $byUser,
            'stats' => [
                'users' => $users['total'],
                'instances' => Instance::count(),
                'paused' => Instance::count('suspended_at IS NOT NULL') + User::count('suspended_at IS NOT NULL'),
                'disk' => (int) $db->scalar('SELECT COALESCE(SUM(disk_bytes), 0) FROM instances'),
            ],
            'maxInstances' => Setting::maxInstances(),
            'autosaveMinutes' => Setting::autosaveMinutes(),
            'registrationEnabled' => Setting::registrationEnabled(),
        ]);
    }

    public function settings(Request $request): Response
    {
        $max = filter_var($request->input('default_max_instances'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000]]);
        $autosave = filter_var($request->input('autosave_minutes'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 120]]);

        $errors = [];
        if ($max === false) {
            $errors['default_max_instances'] = __('Enter a number between 0 and 1000.');
        }
        if ($autosave === false) {
            $errors['autosave_minutes'] = __('Enter a number of minutes between 1 and 120.');
        }
        if ($errors) {
            return $this->fail(__('Please check the form.'), $errors);
        }

        Setting::set('default_max_instances', (string) $max);
        Setting::set('autosave_minutes', (string) $autosave);
        Setting::set('registration_enabled', $request->input('registration_enabled') ? '1' : '0');

        return $this->ok(__('Settings saved.'), ['reload' => true]);
    }

    public function updateUser(Request $request, string $id): Response
    {
        $user = User::find((int) $id) ?? abort(404);
        $raw = $request->input('max_instances', '');

        $max = null;
        if ($raw !== '' && $raw !== null) {
            $max = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000]]);
            if ($max === false) {
                return $this->fail(__('Enter a number between 0 and 1000.'));
            }
        }

        $isAdmin = (int) (bool) $request->input('is_admin');
        if ((int) $user->id === (int) auth()->id && !$isAdmin) {
            return $this->fail(__('You cannot remove your own admin role.'));
        }

        $user->update(['max_instances' => $max, 'is_admin' => $isAdmin]);

        return $this->ok(__('User updated.'));
    }

    public function deleteUser(Request $request, string $id): Response
    {
        $user = User::find((int) $id) ?? abort(404);
        if ((int) $user->id === (int) auth()->id) {
            return $this->fail(__('You cannot delete yourself.'));
        }

        $user->deleteWithData();

        return $this->ok(__('User deleted.'), ['reload' => true]);
    }

    public function toggleUser(Request $request, string $id): Response
    {
        $user = User::find((int) $id) ?? abort(404);
        if ((int) $user->id === (int) auth()->id) {
            return $this->fail(__('You cannot pause yourself.'));
        }
        $user->update(['suspended_at' => $user->isSuspended() ? null : date('Y-m-d H:i:s')]);

        return $this->ok($user->isSuspended() ? __('User paused.') : __('User reactivated.'), ['reload' => true]);
    }

    public function toggleInstance(Request $request, string $uuid): Response
    {
        $instance = Instance::findByUuid($uuid) ?? abort(404);
        $instance->update(['suspended_at' => $instance->isSuspended() ? null : date('Y-m-d H:i:s')]);

        return $this->ok($instance->isSuspended() ? __('Instance paused.') : __('Instance reactivated.'), ['reload' => true]);
    }

    public function deleteInstance(Request $request, string $uuid): Response
    {
        $instance = Instance::findByUuid($uuid) ?? abort(404);
        (new DiskService())->destroy($instance->uuid);
        $instance->delete();

        return $this->ok(__('Instance deleted.'), ['reload' => true]);
    }
}
