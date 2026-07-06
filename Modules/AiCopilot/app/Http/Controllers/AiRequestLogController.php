<?php

namespace Modules\AiCopilot\Http\Controllers;

use App\Enums\PermissionEnum as P;
use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\AiCopilot\Actions\RetryAiRequestAction;
use Modules\AiCopilot\Exceptions\QuotaExceededException;
use Modules\AiCopilot\Models\AiAgent;
use Modules\AiCopilot\Models\AiRequest;

class AiRequestLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(P::AI_LOGS_FULL->value);

        $orgId  = TenantContext::getOrganizationId();
        $status = $request->get('status');
        $agentId = $request->integer('agent_id', 0);

        $query = AiRequest::where('organization_id', $orgId)
            ->with('agent:id,name,slug')
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['pending', 'processing', 'done', 'failed'])) {
            $query->where('status', $status);
        }
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        $requests = $query->paginate(30)->withQueryString();

        $agents = AiAgent::withoutTenant()
            ->withoutGlobalScope('active')
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('ai_copilot::logs.index', compact('requests', 'agents', 'status', 'agentId'));
    }

    public function show(AiRequest $aiRequest)
    {
        $this->authorize(P::AI_LOGS_FULL->value);

        $orgId = TenantContext::getOrganizationId();
        abort_unless($aiRequest->organization_id === $orgId, 404);

        $aiRequest->load(['agent', 'prompt', 'user']);

        return view('ai_copilot::logs.show', compact('aiRequest'));
    }

    public function retry(Request $request, AiRequest $aiRequest, RetryAiRequestAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize(P::AI_LOGS_FULL->value);

        $orgId = TenantContext::getOrganizationId();
        abort_unless($aiRequest->organization_id === $orgId, 404);

        try {
            $retried = $action->handle($aiRequest);

            if ($request->expectsJson()) {
                return response()->json(['uuid' => $retried->uuid, 'status' => $retried->status]);
            }
            return back()->with('success', "Đã tạo retry request: {$retried->uuid}");

        } catch (QuotaExceededException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 429);
            }
            return back()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
