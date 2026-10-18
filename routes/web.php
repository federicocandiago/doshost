<?php

declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\InstanceController;
use App\Controllers\PageController;
use App\Core\Router;

return static function (Router $r): void {
    // Public
    $r->get('/', [HomeController::class, 'index']);
    $r->get('/terms', [PageController::class, 'terms']);
    $r->get('/privacy', [PageController::class, 'privacy']);
    $r->get('/cookies', [PageController::class, 'cookies']);
    $r->get('/about', [PageController::class, 'about']);
    $r->get('/locale/{code}', [PageController::class, 'locale']);
    $r->get('/manifest.webmanifest', [PageController::class, 'manifest']);

    // Auth
    $r->get('/login', [AuthController::class, 'showLogin'], ['guest']);
    $r->post('/login', [AuthController::class, 'login'], ['guest']);
    $r->get('/register', [AuthController::class, 'showRegister'], ['guest']);
    $r->post('/register', [AuthController::class, 'register'], ['guest']);
    $r->post('/logout', [AuthController::class, 'logout']);
    $r->get('/verify/resend', [AuthController::class, 'showResend'], ['guest']);
    $r->post('/verify/resend', [AuthController::class, 'resend'], ['guest']);
    $r->get('/verify/{token}', [AuthController::class, 'verify']);

    // Front-office: instances
    $r->get('/dashboard', [DashboardController::class, 'index'], ['auth']);
    $r->post('/instances', [InstanceController::class, 'store'], ['auth']);
    $r->post('/instances/{uuid}/update', [InstanceController::class, 'update'], ['auth']);
    $r->post('/instances/{uuid}/files', [InstanceController::class, 'addFiles'], ['auth']);
    $r->post('/instances/{uuid}/favorite', [InstanceController::class, 'favorite'], ['auth']);
    $r->post('/instances/{uuid}/delete', [InstanceController::class, 'destroy'], ['auth']);
    $r->get('/play/{uuid}', [InstanceController::class, 'play'], ['auth']);
    $r->get('/instances/{uuid}/bundle.jsdos', [InstanceController::class, 'bundle'], ['auth']);
    $r->get('/instances/{uuid}/backup', [InstanceController::class, 'backup'], ['auth']);
    $r->post('/instances/{uuid}/disk', [InstanceController::class, 'saveDisk'], ['auth']);
    $r->post('/instances/{uuid}/disk/delta', [InstanceController::class, 'saveDelta'], ['auth']);

    // Front-office: account
    $r->get('/account', [AccountController::class, 'show'], ['auth']);
    $r->post('/account/profile', [AccountController::class, 'profile'], ['auth']);
    $r->post('/account/avatar', [AccountController::class, 'avatar'], ['auth']);
    $r->post('/account/avatar/delete', [AccountController::class, 'deleteAvatar'], ['auth']);
    $r->post('/account/password', [AccountController::class, 'password'], ['auth']);
    $r->post('/account/delete', [AccountController::class, 'destroy'], ['auth']);
    $r->get('/avatars/{file}', [AccountController::class, 'avatarFile'], ['auth']);

    // Back-office
    $r->get('/admin', [AdminController::class, 'index'], ['auth', 'admin']);
    $r->post('/admin/settings', [AdminController::class, 'settings'], ['auth', 'admin']);
    $r->post('/admin/users/{id}', [AdminController::class, 'updateUser'], ['auth', 'admin']);
    $r->post('/admin/users/{id}/suspend', [AdminController::class, 'toggleUser'], ['auth', 'admin']);
    $r->post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser'], ['auth', 'admin']);
    $r->post('/admin/instances/{uuid}/suspend', [AdminController::class, 'toggleInstance'], ['auth', 'admin']);
    $r->post('/admin/instances/{uuid}/delete', [AdminController::class, 'deleteInstance'], ['auth', 'admin']);
};
