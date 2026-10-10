<?php

namespace Modules\Approval\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Approval\Actions\ListApprovalHistoryAction;
use Modules\Approval\Http\Resources\ApprovalHistoryResource;
use Modules\Approval\Http\Resources\PendingApprovalResource;
use Modules\Approval\Models\ApprovalLog;
use Modules\Approval\Services\ApprovalDashboardService;

class ApprovalApiController extends Controller
{
    public function pending(Request $request, ApprovalDashboardService $service): JsonResponse
    {
        $this->authorize('viewDashboard');

        $items = $service->pendingForUser($request->user())
            ->filter(fn ($s) => $s->subject)
            ->sortByDesc('updated_at')
            ->values();

        return response()->json([
            'data' => PendingApprovalResource::collection($items),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('viewApprovalHistory');

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'subject_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('approval.subjects')))],
            'action' => ['nullable', 'string', 'in:'.implode(',', array_keys(ApprovalLog::ACTION_LABELS))],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $logs = ListApprovalHistoryAction::run(
            $request->user(),
            $validated['subject_type'] ?? null,
            $validated['action'] ?? null,
            $sortDir,
            (int) ($validated['size'] ?? 25),
            (int) ($validated['page'] ?? 1),
        );

        return response()->json([
            'data' => ApprovalHistoryResource::collection($logs->items()),
            'last_page' => $logs->lastPage(),
            'total' => $logs->total(),
        ]);
    }
}
