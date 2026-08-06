<?php

namespace Modules\N8n\Features\LogViewing\Http;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §6/§7.4 — trang log (Tabulator), platform_ops
 * HOẶC platform_viewer. Dữ liệu bảng thật lấy qua N8nLogApiController.
 */
class N8nLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            abort_unless($user?->isPlatformOps() || $user?->isPlatformViewer() || $user?->hasRole('super-admin'), 403);

            return $next($request);
        });
    }

    public function index(): View
    {
        $connections = N8nConnection::withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']);

        return view('n8n::logs.index', compact('connections'));
    }
}
