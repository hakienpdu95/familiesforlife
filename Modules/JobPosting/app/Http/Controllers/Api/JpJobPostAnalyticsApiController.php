<?php

namespace Modules\JobPosting\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostStat;

class JpJobPostAnalyticsApiController extends Controller
{
    // GET /backend/api/job-posts/{job_post}/analytics
    public function analytics(JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('view', $jobPost);

        $rows = JpJobPostStat::where('job_post_id', $jobPost->id)
            ->where('stat_date', '>=', now()->subDays(29)->toDateString())
            ->orderByDesc('stat_date')
            ->get(['stat_date', 'source', 'view_count', 'unique_view_count', 'apply_count', 'share_count'])
            ->map(fn ($row) => [
                'stat_date'         => $row->stat_date->toDateString(),
                'source'            => $row->source,
                'view_count'        => $row->view_count,
                'unique_view_count' => $row->unique_view_count,
                'apply_count'       => $row->apply_count,
                'share_count'       => $row->share_count,
            ]);

        $totalViews  = $rows->sum('view_count');
        $totalApplies = $rows->sum('apply_count');

        $totals = [
            'views'          => $totalViews,
            'unique_views'   => $rows->sum('unique_view_count'),
            'applies'        => $totalApplies,
            'conversion_pct' => $totalViews > 0
                ? round($totalApplies * 100 / $totalViews, 1)
                : 0,
        ];

        $bySource = $rows->groupBy('source')->map(fn ($group, $source) => [
            'source'         => $source,
            'views'          => $group->sum('view_count'),
            'applies'        => $group->sum('apply_count'),
            'conversion_pct' => $group->sum('view_count') > 0
                ? round($group->sum('apply_count') * 100 / $group->sum('view_count'), 1)
                : 0,
        ])->values()->sortByDesc('views')->values();

        $daily = $rows->groupBy('stat_date')
            ->map(fn ($group, $date) => [
                'date'    => $date,
                'views'   => $group->sum('view_count'),
                'applies' => $group->sum('apply_count'),
            ])
            ->sortByDesc('date')
            ->values();

        return response()->json([
            'totals'    => $totals,
            'by_source' => $bySource,
            'daily'     => $daily,
        ]);
    }

    // GET /backend/api/job-posts/{job_post}/history
    public function history(JpJobPost $jobPost): JsonResponse
    {
        $this->authorize('view', $jobPost);

        $history = $jobPost->histories()
            ->with('changedBy:id,name')
            ->orderByDesc('created_at')
            ->take(50)
            ->get()
            ->map(fn ($h) => [
                'id'                => $h->id,
                'change_type'       => $h->change_type?->value,
                'change_type_label' => $h->change_type?->label(),
                'old_status'        => $h->old_status?->label(),
                'new_status'        => $h->new_status?->label(),
                'changed_fields'    => $h->changed_fields,
                'note'              => $h->note,
                'changed_by'        => $h->changedBy?->name,
                'created_at'        => $h->created_at?->format('d/m/Y H:i'),
            ]);

        return response()->json(['data' => $history]);
    }
}
