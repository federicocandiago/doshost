<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Translator;
use App\Core\View;
use App\Models\User;

function env(string $key, mixed $default = null): mixed
{
    return Env::get($key, $default);
}

/** Translate an English source string; ":name" placeholders are replaced. */
function __(string $message, array $replace = []): string
{
    return Translator::get($message, $replace);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function view(string $name, array $data = [], ?string $layout = 'layouts/app', int $status = 200): Response
{
    return Response::html(View::render($name, $data, $layout), $status);
}

function component(string $name, array $data = []): string
{
    return View::partial('components/' . $name, $data);
}

function redirect(string $to): Response
{
    return Response::redirect($to);
}

function abort(int $status, string $message = ''): never
{
    throw new HttpException($status, $message);
}

function auth(): ?User
{
    return Auth::user();
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/**
 * Private data folder (disks, avatars, logs): STORAGE_PATH in .env, or storage/ in the project.
 * A relative STORAGE_PATH is resolved from the project folder (e.g. ../doshost-data).
 * Keep it outside the web root on shared hosting.
 */
function storage_path(string $path = ''): string
{
    $root = (string) env('STORAGE_PATH', 'storage');
    if (!str_starts_with($root, '/') && !preg_match('#^[A-Za-z]:[\\\\/]#', $root)) {
        $root = BASE_PATH . '/' . $root;
    }
    $root = rtrim($root, '/');

    return $path === '' ? $root : $root . '/' . ltrim($path, '/');
}

/**
 * Path the site lives under, taken from APP_URL: "" for https://example.com,
 * "/doshost" for https://example.com/doshost.
 */
function base_url(): string
{
    static $base = null;

    return $base ??= rtrim((string) parse_url((string) env('APP_URL', ''), PHP_URL_PATH), '/');
}

/** Site-relative URL: url('/login') → /doshost/login */
function url(string $path = '/'): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = (defined('PUBLIC_PATH') ? PUBLIC_PATH : BASE_PATH . '/public') . '/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';

    return url($path) . $version;
}

function locale(): string
{
    return Translator::locale();
}

function format_bytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $size = (float) $bytes;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }

    return ($i === 0 ? (string) $bytes : number_format($size, 1)) . ' ' . $units[$i];
}

function format_date(?string $datetime): string
{
    return $datetime ? date(locale() === 'it' ? 'd/m/Y H:i' : 'Y-m-d H:i', strtotime($datetime)) : '—';
}

/** Legal details from .env, used by the Privacy and Cookie policies. */
function legal(string $key): string
{
    return (string) match ($key) {
        'controller' => env('LEGAL_CONTROLLER_NAME', env('APP_NAME')),
        'address' => env('LEGAL_CONTROLLER_ADDRESS', ''),
        'email' => env('LEGAL_CONTACT_EMAIL', ''),
        'location' => ($l = (string) env('LEGAL_DATA_LOCATION', 'the European Union')) === 'the European Union' ? __('the European Union') : $l,
        'log_days' => env('LEGAL_LOG_RETENTION_DAYS', 30),
        'date' => date(locale() === 'it' ? 'd/m/Y' : 'F j, Y', strtotime((string) env('LEGAL_POLICY_DATE', '2026-10-16'))),
        default => '',
    };
}

/** Back-office setting, falling back to REGISTRATION_ENABLED in .env (also when the DB is down). */
function registration_enabled(): bool
{
    try {
        return App\Models\Setting::registrationEnabled();
    } catch (App\Core\DatabaseException) { // a missing table must reach App: it triggers the auto-install
        return (bool) env('REGISTRATION_ENABLED', true);
    }
}
