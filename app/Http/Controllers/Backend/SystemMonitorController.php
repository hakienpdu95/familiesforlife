<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\SystemMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SystemMonitorController extends Controller
{
    public function __construct(
        private readonly SystemMonitorService $monitor,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewSystemMonitor');

        return view('backend.system-monitor.index', [
            'ranges' => array_keys(SystemMonitorService::RANGES),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        Gate::authorize('viewSystemMonitor');

        return response()->json([
            ...$this->monitor->snapshot(),
            'series' => $this->monitor->series($request->integer('minutes', 60)),
            'slow_requests' => $this->monitor->slowRequests(),
            'slow_queries' => $this->monitor->slowQueries(),
        ]);
    }
}
