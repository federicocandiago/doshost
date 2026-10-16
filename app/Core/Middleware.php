<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Route guards. Each returns a Response to stop the request, or null to continue.
 */
final class Middleware
{
    public static function handle(string $name, Request $request): ?Response
    {
        return match ($name) {
            'auth' => self::auth($request),
            'guest' => Auth::check() ? Response::redirect('/dashboard') : null,
            'admin' => Auth::user()?->isAdmin() ? null : self::deny($request, 403, '/'),
            default => throw new \LogicException("Unknown middleware: {$name}"),
        };
    }

    private static function auth(Request $request): ?Response
    {
        $user = Auth::user();
        if ($user === null) {
            return self::deny($request, 401, '/login');
        }
        if ($user->isSuspended()) {
            return $request->wantsJson()
                ? Response::json(['ok' => false, 'message' => __('Your account is suspended.')], 403)
                : view('pages/stop', [
                    'title' => __('Account suspended'),
                    'heading' => __('Account suspended'),
                    'text' => __('Your account has been paused by an administrator. Your data is safe; contact the administrator to know more.'),
                    'logout' => true,
                ], 'layouts/minimal', 403);
        }

        return null;
    }

    private static function deny(Request $request, int $status, string $redirect): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['ok' => false, 'message' => __('You are not allowed to do this.')], $status);
        }
        if ($status === 403) {
            throw new HttpException(403);
        }

        return Response::redirect($redirect);
    }
}
