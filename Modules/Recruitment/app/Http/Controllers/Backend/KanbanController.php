<?php

namespace Modules\Recruitment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Recruitment\Enums\ApplicationStatus;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcPipelineStage;

class KanbanController extends Controller
{
    public function show(Request $request, string $jpJobPostUuid): View
    {
        $this->authorize('viewAny', RcApplication::class);

        $stages = RcPipelineStage::query()
            ->active()
            ->ordered()
            ->withCount([
                'applications as candidate_count' => function ($q) use ($jpJobPostUuid): void {
                    $q->where('jp_job_post_id', $jpJobPostUuid)
                      ->where('status', ApplicationStatus::Active->value);
                },
            ])
            ->get();

        $totalActive = RcApplication::query()
            ->where('jp_job_post_id', $jpJobPostUuid)
            ->where('status', ApplicationStatus::Active->value)
            ->count();

        return view('recruitment::applications.kanban', compact('stages', 'jpJobPostUuid', 'totalActive'));
    }
}
