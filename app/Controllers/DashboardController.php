<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Instance;
use App\Services\DiskService;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = auth();
        $sort = (string) $request->input('sort', 'recent');
        $sort = array_key_exists($sort, Instance::SORTS) ? $sort : 'recent';
        $instances = $user->instances($sort);
        $limit = $user->instanceLimit();

        return view('pages/dashboard', [
            'title' => __('My instances'),
            'instances' => $instances,
            'sort' => $sort,
            'used' => count($instances),
            'limit' => $limit,
            'available' => max(0, $limit - count($instances)),
            'cpuProfiles' => Instance::cpuProfiles(),
            'layouts' => DiskService::KEYBOARD_LAYOUTS,
            'maxBytes' => DiskService::maxBytes(),
        ]);
    }
}
