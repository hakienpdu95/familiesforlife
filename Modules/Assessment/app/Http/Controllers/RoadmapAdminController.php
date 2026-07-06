<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assessment\Models\RoadmapMilestone;
use Modules\Assessment\Models\RoadmapPhase;
use Modules\KcItem\Models\KcItem;

class RoadmapAdminController extends Controller
{
    public function index(): View
    {
        $this->authorize('assessment.config');

        $phases = RoadmapPhase::withCount('milestones')
            ->orderBy('sort_order')
            ->get();

        return view('assessment::roadmap.admin.index', compact('phases'));
    }

    public function phase(RoadmapPhase $phase): View
    {
        $this->authorize('assessment.config');

        $phase->load(['milestones' => fn($q) => $q->withCount('kcItems')]);

        return view('assessment::roadmap.admin.phase', compact('phase'));
    }

    public function milestoneKc(RoadmapPhase $phase, RoadmapMilestone $milestone): View
    {
        $this->authorize('assessment.config');

        $milestone->load('kcItems');

        $orgId       = TenantContext::getOrganizationId();
        $attachedIds = $milestone->kcItems->pluck('id');

        $availableKc = KcItem::approved()
            ->where(fn($q) => $q->where('organization_id', $orgId)->orWhereNull('organization_id'))
            ->whereNotIn('id', $attachedIds)
            ->orderBy('title')
            ->get(['id', 'title', 'type', 'domain_code', 'difficulty']);

        return view('assessment::roadmap.admin.milestone-kc', compact('phase', 'milestone', 'availableKc'));
    }

    public function attachKc(Request $request, RoadmapPhase $phase, RoadmapMilestone $milestone): RedirectResponse
    {
        $this->authorize('assessment.config');

        $request->validate(['kc_item_id' => ['required', 'integer', 'exists:kc_items,id']]);

        $nextOrder = $milestone->kcItems()->max('sort_order') ?? -1;

        $milestone->kcItems()->syncWithoutDetaching([
            $request->kc_item_id => ['sort_order' => $nextOrder + 1],
        ]);

        return back()->with('flash_success', 'Đã gắn tài liệu vào mốc học tập.');
    }

    public function detachKc(RoadmapPhase $phase, RoadmapMilestone $milestone, KcItem $kcItem): RedirectResponse
    {
        $this->authorize('assessment.config');

        $milestone->kcItems()->detach($kcItem->id);

        return back()->with('flash_success', 'Đã gỡ tài liệu.');
    }
}
