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
use App\Services\VerificationMail;

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

        $user = User::findByEmail($email);
        if ($user === null || !password_verify($password, (string) $user->password)) {
            usleep(300_000); // slow down brute force a little
            return $this->fail(__('Wrong email or password.'), ['email' => __('Wrong email or password.')]);
        }
        if (email_verification_enabled() && !$user->isVerified()) {
            return $this->fail(__('Please confirm your email first: open the link we sent you (check the spam folder too).'), [
                'email' => __('Email not confirmed yet.'),
            ]);
        }
        Auth::login($user);

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
            'email_verified_at' => email_verification_enabled() ? null : date('Y-m-d H:i:s'),
            'locale' => $locale,
            'keyboard_layout' => $layout,
        ]);

        if (email_verification_enabled()) {
            Translator::setLocale($locale);
            try {
                VerificationMail::send($user);
            } catch (\Throwable $e) {
                error_log('DOSHost: verification email not sent: ' . $e->getMessage());
                return $this->ok(__('Account created, but the confirmation email could not be sent. Try again from «Need a new link?».'), ['redirect' => '/verify/resend']);
            }

            return $this->ok(__('Almost done! We sent a confirmation link to :email. Check the spam folder too.', ['email' => $email]), ['redirect' => '/login']);
        }

        Auth::login($user);
        Translator::setLocale($locale); // the welcome message already in the chosen language

        return $this->ok(__('Welcome aboard, :name!', ['name' => $name]), ['redirect' => '/dashboard']);
    }

    /** Link from the confirmation email. */
    public function verify(Request $request, string $token): Response
    {
        $user = User::findByVerificationToken($token);
        if ($user === null) {
            Session::flash('error', __('This confirmation link is not valid or has already been used.'));
            return redirect('/login');
        }

        $user->update(['email_verified_at' => date('Y-m-d H:i:s'), 'verification_token' => null]);
        Auth::login($user);
        Session::flash('success', __('Email confirmed. Welcome aboard, :name!', ['name' => $user->name]));

        return redirect('/dashboard');
    }

    public function showResend(Request $request): Response
    {
        return view('pages/auth/resend', ['title' => __('Confirm your email')]);
    }

    /** Sends a new link. The answer is the same whether the address exists or not (no account probing). */
    public function resend(Request $request): Response
    {
        $email = mb_strtolower((string) $request->input('email', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fail(__('Please check the form.'), ['email' => __('Please enter a valid email address.')]);
        }

        $user = User::findByEmail($email);
        $recent = $user?->verification_sent_at && strtotime((string) $user->verification_sent_at) > time() - 60;
        if ($user !== null && !$user->isVerified() && !$recent) {
            try {
                VerificationMail::send($user);
            } catch (\Throwable $e) {
                error_log('DOSHost: verification email not sent: ' . $e->getMessage());
                return $this->fail(__('The email could not be sent right now. Please try again later.'));
            }
        }

        return $this->ok(__('If :email is waiting for confirmation, a new link is on its way. Check the spam folder too.', ['email' => $email]));
    }

    public function logout(Request $request): Response
    {
        Auth::logout();

        return redirect('/');
    }
}
