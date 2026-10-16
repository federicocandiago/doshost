<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Session;

abstract class Controller
{
    /** Standard JSON answer for fetch() calls; app.js follows `redirect` or reloads the page on `reload`. */
    protected function ok(string $message = '', array $extra = []): Response
    {
        if ($message !== '' && (isset($extra['redirect']) || !empty($extra['reload']))) {
            Session::flash('success', $message);
        }

        if (isset($extra['redirect']) && str_starts_with($extra['redirect'], '/')) {
            $extra['redirect'] = url($extra['redirect']);
        }

        return Response::json(['ok' => true, 'message' => $message, ...$extra]);
    }

    protected function fail(string $message, array $errors = [], int $status = 422): Response
    {
        return Response::json(['ok' => false, 'message' => $message, 'errors' => $errors], $status);
    }
}
