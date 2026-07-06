<?php

namespace Modules\AiCopilot\Http\Controllers;

use App\Enums\PermissionEnum as P;
use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\AiCopilot\Actions\SetDefaultPromptAction;
use Modules\AiCopilot\Actions\StoreAiPromptAction;
use Modules\AiCopilot\Actions\UpdateAiPromptAction;
use Modules\AiCopilot\Data\Requests\StoreAiPromptData;
use Modules\AiCopilot\Models\AiAgent;
use Modules\AiCopilot\Models\AiPrompt;
use Modules\Organization\Models\Organization;

class AiPromptController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(P::PROMPT_FULL->value);

        $orgId   = TenantContext::getOrganizationId();
        $agentId = $request->integer('agent_id', 0);

        $agents = AiAgent::withoutTenant()
            ->withoutGlobalScope('active')
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'task_type', 'is_system']);

        $promptsQuery = AiPrompt::withoutTenant()
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->with('agent:id,name,slug,task_type,is_system')
            ->orderByDesc('is_default')
            ->orderBy('agent_id')
            ->orderByDesc('version');

        if ($agentId) {
            $promptsQuery->where('agent_id', $agentId);
        }

        $prompts = $promptsQuery->get();

        return view('ai_copilot::prompts.index', compact('prompts', 'agents', 'agentId'));
    }

    public function create(Request $request)
    {
        $this->authorize(P::PROMPT_FULL->value);

        [$organizations, $defaultOrgId, $orgLocked] = $this->_resolveOrganizations();

        $orgId  = $defaultOrgId ?? TenantContext::getOrganizationId();
        $agents = AiAgent::withoutTenant()
            ->withoutGlobalScope('active')
            ->where(fn ($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'task_type', 'is_system']);

        $selectedAgentId = $request->integer('agent_id', 0) ?: null;

        return view('ai_copilot::prompts.create', compact(
            'agents', 'selectedAgentId',
            'organizations', 'defaultOrgId', 'orgLocked'
        ));
    }

    public function store(Request $request, StoreAiPromptAction $action): RedirectResponse
    {
        $this->authorize(P::PROMPT_FULL->value);

        $data   = StoreAiPromptData::validateAndCreate($request->all());
        $prompt = $action->handle($data);

        return redirect()->route('ai.prompts.index', ['agent_id' => $prompt->agent_id])
            ->with('success', "Prompt \"{$prompt->name}\" đã được tạo.");
    }

    public function edit(AiPrompt $prompt)
    {
        $this->authorize(P::PROMPT_FULL->value);

        $prompt->load('agent:id,name,slug,is_system');
        $orgName = Organization::withoutTenant()->find($prompt->organization_id)?->name ?? '(system)';

        return view('ai_copilot::prompts.edit', compact('prompt', 'orgName'));
    }

    public function update(Request $request, AiPrompt $prompt, UpdateAiPromptAction $action): RedirectResponse
    {
        $this->authorize(P::PROMPT_FULL->value);

        $data = StoreAiPromptData::validateAndCreate($request->all());
        $action->handle($prompt, $data);

        return redirect()->route('ai.prompts.index', ['agent_id' => $prompt->agent_id])
            ->with('success', 'Đã cập nhật prompt.');
    }

    public function setDefault(AiPrompt $prompt, SetDefaultPromptAction $action): JsonResponse|RedirectResponse
    {
        $this->authorize(P::PROMPT_FULL->value);

        $action->handle($prompt);

        if (request()->expectsJson()) {
            return response()->json(['message' => "Đã đặt \"{$prompt->name}\" làm prompt mặc định."]);
        }

        return back()->with('success', "Đã đặt \"{$prompt->name}\" làm prompt mặc định.");
    }

    /** @return array{Collection, ?int, bool} [$organizations, $defaultOrgId, $orgLocked] */
    private function _resolveOrganizations(): array
    {
        $userOrgId = auth()->user()->organization_id;
        if ($userOrgId) {
            return [Organization::withoutTenant()->where('id', $userOrgId)->get(['id', 'name']), $userOrgId, true];
        }
        return [Organization::withoutTenant()->active()->orderBy('name')->get(['id', 'name']), null, false];
    }

    public function destroy(Request $request, AiPrompt $prompt): RedirectResponse|JsonResponse
    {
        $this->authorize(P::PROMPT_FULL->value);

        if ($prompt->is_default && is_null($prompt->organization_id)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Không thể xóa system prompt mặc định.'], 403);
            }
            return back()->with('error', 'Không thể xóa system prompt mặc định.');
        }

        $name = $prompt->name;
        $agentId = $prompt->agent_id;
        $prompt->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => "Đã xóa prompt \"{$name}\"."]);
        }

        return redirect()->route('ai.prompts.index', ['agent_id' => $agentId])
            ->with('success', "Đã xóa prompt \"{$name}\".");
    }
}
