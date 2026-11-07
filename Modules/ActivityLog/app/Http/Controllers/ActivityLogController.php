<?php

namespace Modules\ActivityLog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class ActivityLogController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('activitylog::logs.index');
    }

    public function export(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'module'    => 'nullable|string|max:64',
            'action'    => 'nullable|string|max:128',
            'level_min' => 'nullable|integer|min:1|max:5',
            'actor_id'  => 'nullable|integer',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
            'search'    => 'nullable|string|max:100',
        ]);

        $key = (string) Str::uuid();
        \Modules\ActivityLog\Actions\ExportActivityLogsAction::dispatch($filters, $key)
            ->onQueue(config('activitylog_module.queue', 'actlog'));

        return response()->json(['key' => $key]);
    }

    public function downloadExport(string $key): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $path = \Cache::get("actlog:export:{$key}");
        abort_unless($path && file_exists($path), 404, 'File không tồn tại hoặc đã hết hạn.');

        return response()->download($path)->deleteFileAfterSend();
    }
}
