<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Translator;
use App\Models\User;
use App\Services\DiskService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return view('pages/auth/login', ['title' => __('Sign in')]);
    }

    public function login(Request $request): Response
    {
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        if (!Auth::attempt($email, $password)) {
            usleep(300_000); // slow down brute force a little
            return $this->fail(__('Wrong email or password.'), ['email' => __('Wrong email or password.')]);
        }

        return $this->ok('', ['redirect' => '/dashboard']);
    }

    public function showRegister(Request $request): Response
    {
        if (!registration_enabled()) {
            abort(404);
        }

        return view('pages/auth/register', [
            'title' => __('Create account'),
            'locales' => Translator::available(),
            'layouts' => DiskService::KEYBOARD_LAYOUTS,
            // DOS keyboard preselected from the language the visitor is using
            'defaultLayout' => array_key_exists(locale(), DiskService::KEYBOARD_LAYOUTS) ? locale() : 'us',
        ]);
    }

    public function register(Request $request): Response
    {
        if (!registration_enabled()) {
            return $this->fail(__('Registration is currently closed.'), [], 403);
        }

        $name = (string) $request->input('name', '');
        $email = mb_strtolower((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $locale = (string) $request->input('locale', locale());
        $layout = (string) $request->input('keyboard_layout', 'us');
        $errors = [];

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
            $errors['name'] = __('Please enter your name.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __('Please enter a valid email address.');
        } elseif (User::findByEmail($email)) {
            $errors['email'] = __('This email is already registered.');
        }
        if (mb_strlen($password) < 8) {
            $errors['password'] = __('The password must be at least 8 characters.');
        } elseif ($password !== $request->input('password_confirmation')) {
            $errors['password_confirmation'] = __('The passwords do not match.');
        }
        if (!in_array($locale, Translator::available(), true)) {
            $errors['locale'] = __('Invalid language.');
        }
        if (!array_key_exists($layout, DiskService::KEYBOARD_LAYOUTS)) {
            $errors['keyboard_layout'] = __('Invalid keyboard layout.');
        }
        if (!$request->input('terms')) {
            $errors['terms'] = __('You must accept the terms of service.');
        }
        if ($errors) {
            return $this->fail(__('Please check the form.'), $errors);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'locale' => $locale,
            'keyboard_layout' => $layout,
        ]);
        Auth::login($user);
        Translator::setLocale($locale); // the welcome message already in the chosen language

        return $this->ok(__('Welcome aboard, :name!', ['name' => $name]), ['redirect' => '/dashboard']);
    }

    public function logout(Request $request): Response
    {
        Auth::logout();

        return redirect('/');
    }
}
