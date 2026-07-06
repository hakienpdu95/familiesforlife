<?php

namespace Modules\ActivityLog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\ActivityLog\Models\ActivityLogContext;
use Modules\ActivityLog\Models\ActivityLogHttp;
use App\Shared\Tenancy\TenantContext;

class ActivityLogController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('activitylog::logs.index');
    }

    public function show(ActivityLog $log): \Illuminate\View\View
    {
        if (TenantContext::isSet() && $log->organization_id !== TenantContext::getOrganizationId()) {
            abort(403);
        }

        $contexts = ActivityLogContext::where('log_id', $log->id)->orderBy('key_name')->get();
        $http     = ActivityLogHttp::where('log_id', $log->id)->first();

        $sameRequest = $log->request_id
            ? ActivityLog::where('request_id', $log->request_id)->where('id', '!=', $log->id)
                         ->orderBy('created_at')
                         ->get(['id', 'module', 'action', 'level', 'created_at'])
            : collect();

        $subjectHistory = ($log->subject_type && $log->subject_id)
            ? ActivityLog::where('subject_type', $log->subject_type)
                         ->where('subject_id', $log->subject_id)
                         ->where('id', '!=', $log->id)
                         ->orderByDesc('created_at')->limit(10)
                         ->get(['id', 'module', 'action', 'level', 'created_at'])
            : collect();

        return view('activitylog::logs.show',
            compact('log', 'contexts', 'http', 'sameRequest', 'subjectHistory'));
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

        if (TenantContext::isSet()) {
            $filters['organization_id'] = TenantContext::getOrganizationId();
        }

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
