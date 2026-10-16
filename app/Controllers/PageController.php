<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Translator;

final class PageController extends Controller
{
    public function terms(Request $request): Response
    {
        return view('pages/legal/terms', ['title' => __('Terms of Service')]);
    }

    public function privacy(Request $request): Response
    {
        return view('pages/legal/privacy', ['title' => __('Privacy Policy')]);
    }

    public function cookies(Request $request): Response
    {
        return view('pages/legal/cookies', ['title' => __('Cookie Policy')]);
    }

    public function about(Request $request): Response
    {
        return view('pages/legal/about', ['title' => __('About')]);
    }

    /** Web app manifest: "Add to Home screen" opens the site full screen, without browser bars. */
    public function manifest(Request $request): Response
    {
        $icon = static fn (string $file, int $size, string $purpose = 'any'): array => [
            'src' => url('/assets/icons/' . $file), 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => $purpose,
        ];

        return Response::json([
            'name' => (string) env('APP_NAME'),
            'short_name' => (string) env('APP_NAME'),
            'description' => __((string) env('APP_DESCRIPTION', '')),
            'lang' => locale(),
            'start_url' => url('/dashboard'),
            'scope' => url('/'),
            'display' => 'fullscreen',
            'display_override' => ['fullscreen', 'standalone'],
            'orientation' => 'any',
            'background_color' => '#000000',
            'theme_color' => '#000000',
            'icons' => [
                $icon('icon-192.png', 192),
                $icon('icon-512.png', 512),
                $icon('icon-maskable-512.png', 512, 'maskable'),
            ],
        ])->withHeader('Content-Type', 'application/manifest+json');
    }

    public function locale(Request $request, string $code): Response
    {
        if (in_array($code, Translator::available(), true)) {
            Session::set('locale', $code);
            Auth::user()?->update(['locale' => $code]);
        }
        $back = (string) ($request->header('Referer') ?? '/');
        $sameHost = parse_url($back, PHP_URL_HOST) === parse_url('//' . $request->header('Host'), PHP_URL_HOST);

        return redirect($sameHost ? $back : '/');
    }
}
