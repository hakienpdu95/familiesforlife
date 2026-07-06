<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Recruitment\Enums\PipelineStageType;
use Modules\Recruitment\Models\RcPipelineStage;

class PipelineStageController extends Controller
{
    public function index(): View
    {
        $this->authorize('manage', RcPipelineStage::class);

        $stages = RcPipelineStage::query()
            ->ordered()
            ->get();

        $stageTypes = collect(PipelineStageType::cases())
            ->map(fn ($t) => ['value' => $t->value, 'text' => $t->label()])
            ->all();

        return view('recruitment::pipeline-stages.index', compact('stages', 'stageTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage', RcPipelineStage::class);

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:100'],
            'stage_type'        => ['required', 'string'],
            'color_hex'         => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'require_score'     => ['boolean'],
            'send_notification' => ['boolean'],
        ]);

        $maxOrder = RcPipelineStage::max('sort_order') ?? 0;

        RcPipelineStage::create(array_merge($validated, [
            'org_id'     => TenantContext::getOrganizationId(),
            'sort_order' => $maxOrder + 10,
        ]));

        return redirect()
            ->route('backend.recruitment.pipeline-stages.index')
            ->with('success', 'Đã thêm stage mới');
    }

    public function update(Request $request, RcPipelineStage $pipelineStage): RedirectResponse
    {
        $this->authorize('manage', RcPipelineStage::class);

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:100'],
            'color_hex'         => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'require_score'     => ['boolean'],
            'send_notification' => ['boolean'],
            'is_active'         => ['boolean'],
        ]);

        $pipelineStage->update($validated);

        return redirect()
            ->route('backend.recruitment.pipeline-stages.index')
            ->with('success', 'Đã cập nhật stage');
    }
}
