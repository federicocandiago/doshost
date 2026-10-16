<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Translator;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\DiskService;

final class AccountController extends Controller
{
    public function show(Request $request): Response
    {
        return view('pages/account', [
            'title' => __('My account'),
            'user' => auth(),
            'layouts' => DiskService::KEYBOARD_LAYOUTS,
            'locales' => Translator::available(),
        ]);
    }

    public function profile(Request $request): Response
    {
        $name = (string) $request->input('name', '');
        $locale = (string) $request->input('locale', '');
        $layout = (string) $request->input('keyboard_layout', 'us');

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = __('Please enter your name.');
        }
        if (!in_array($locale, Translator::available(), true)) {
            $errors['locale'] = __('Invalid language.');
        }
        if (!array_key_exists($layout, DiskService::KEYBOARD_LAYOUTS)) {
            $errors['keyboard_layout'] = __('Invalid keyboard layout.');
        }
        if ($errors) {
            return $this->fail(__('Please check the form.'), $errors);
        }

        auth()->update(['name' => $name, 'locale' => $locale, 'keyboard_layout' => $layout]);
        Session::set('locale', $locale);

        return $this->ok(__('Profile updated.'), ['redirect' => '/account']);
    }

    public function avatar(Request $request): Response
    {
        $file = $request->files('avatar')[0] ?? null;
        if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->fail(__('Please choose an image.'), ['avatar' => __('Please choose an image.')]);
        }

        $avatars = new AvatarService();
        try {
            $name = $avatars->store((int) auth()->id, $file['tmp_name']);
        } catch (\LengthException) {
            return $this->fail(__('The image is too large (max :size KB).', ['size' => env('AVATAR_MAX_KB', 4096)]));
        } catch (\InvalidArgumentException) {
            return $this->fail(__('Use a JPG, PNG, GIF or WebP image.'));
        }

        $avatars->delete(auth()->avatar);
        auth()->update(['avatar' => $name]);

        return $this->ok(__('Profile picture updated.'), ['redirect' => '/account']);
    }

    public function deleteAvatar(Request $request): Response
    {
        (new AvatarService())->delete(auth()->avatar);
        auth()->update(['avatar' => null]);

        return $this->ok(__('Profile picture removed.'), ['redirect' => '/account']);
    }

    public function avatarFile(Request $request, string $file): Response
    {
        $path = (new AvatarService())->path($file) ?? abort(404);

        return Response::file($path, 'image/png')->withHeader('Cache-Control', 'private, max-age=31536000, immutable');
    }

    public function password(Request $request): Response
    {
        $user = auth();
        $new = (string) $request->input('password', '');

        $errors = [];
        if (!password_verify((string) $request->input('current_password', ''), (string) $user->password)) {
            $errors['current_password'] = __('The current password is wrong.');
        }
        if (mb_strlen($new) < 8) {
            $errors['password'] = __('The password must be at least 8 characters.');
        } elseif ($new !== $request->input('password_confirmation')) {
            $errors['password_confirmation'] = __('The passwords do not match.');
        }
        if ($errors) {
            return $this->fail(__('Please check the form.'), $errors);
        }

        $user->update(['password' => password_hash($new, PASSWORD_DEFAULT)]);
        Session::regenerate();

        return $this->ok(__('Password changed.'));
    }

    public function destroy(Request $request): Response
    {
        $user = auth();
        if (!password_verify((string) $request->input('confirm_password', ''), (string) $user->password)) {
            return $this->fail(__('Please check the form.'), ['confirm_password' => __('The current password is wrong.')]);
        }
        if ($user->isAdmin() && User::count('is_admin = 1') <= 1) {
            return $this->fail(__('You are the only administrator: promote another user before deleting your account.'));
        }

        $user->deleteWithData();
        Auth::logout();
        Session::start();
        Session::flash('success', __('Your account has been deleted. Goodbye!'));

        return $this->ok('', ['redirect' => '/']);
    }
}
