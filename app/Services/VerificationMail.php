<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Translator;
use App\Core\View;
use App\Models\User;

/** The "confirm your email" message, written in the user's language. */
final class VerificationMail
{
    public static function send(User $user): void
    {
        $previous = Translator::locale();
        Translator::setLocale((string) ($user->locale ?: $previous));
        try {
            $data = [
                'name' => (string) $user->name,
                'link' => absolute_url('/verify/' . $user->newVerificationToken()),
                'app' => (string) env('APP_NAME', 'DOSHost'),
            ];
            Mailer::send(
                (string) $user->email,
                __('Confirm your email for :app', ['app' => $data['app']]),
                View::partial('emails/verify-text', $data),
                View::partial('emails/verify-html', $data)
            );
        } finally {
            Translator::setLocale($previous);
        }
    }
}
