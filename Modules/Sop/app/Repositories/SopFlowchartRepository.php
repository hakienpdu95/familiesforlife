<?php

namespace Modules\Sop\Repositories;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SopFlowchartRepository
{
    private const CACHE_TTL = 1800; // 30 minutes
    private const CACHE_KEY = 'sop-flowchart';

    public function getFlowchartData(int $sopId): array
    {
        return $this->store($sopId)->remember(
            self::CACHE_KEY . ":{$sopId}",
            self::CACHE_TTL,
            fn () => $this->fetchFlowchartData($sopId)
        );
    }

    public function invalidate(int $sopId): void
    {
        if ($this->supportsTagging()) {
            Cache::tags(["sop:{$sopId}"])->flush();
        } else {
            Cache::forget(self::CACHE_KEY . ":{$sopId}");
        }
    }

    private function store(int $sopId): \Illuminate\Contracts\Cache\Repository
    {
        if ($this->supportsTagging()) {
            return Cache::tags(["sop:{$sopId}"]);
        }
        return Cache::store();
    }

    private function supportsTagging(): bool
    {
        try {
            return Cache::getStore() instanceof TaggableStore;
        } catch (\Throwable) {
            return false;
        }
    }

    private function fetchFlowchartData(int $sopId): array
    {
        // Query 1: Steps (only active) — left join ref SOP for sub_sop display
        $steps = DB::table('sop_steps as s')
            ->leftJoin('sop_processes as ref', 'ref.id', '=', 's.ref_sop_id')
            ->where('s.sop_id', $sopId)
            ->where('s.is_active', true)
            ->orderBy('s.position')
            ->select([
                's.id', 's.uuid', 's.position', 's.title', 's.description',
                's.expected_output', 's.warning_note',
                's.step_type', 's.duration_minutes', 's.is_mandatory',
                's.branch_yes_position', 's.branch_no_position',
                'ref.code as ref_sop_code',
                'ref.title as ref_sop_title',
            ])
            ->get();

        // Query 2: Connectors
        $connectors = DB::table('sop_step_connectors')
            ->where('sop_id', $sopId)
            ->orderBy('sort_order')
            ->select(['id', 'uuid', 'from_step_id', 'to_step_id',
                      'connector_type', 'label', 'color_hex'])
            ->get();

        // Query 3: RACI — single query, no N+1
        $stepIds = $steps->pluck('id');
        $raci = collect();
        if ($stepIds->isNotEmpty()) {
            $raci = DB::table('sop_step_raci as r')
                ->whereIn('r.step_id', $stepIds)
                ->leftJoin('users as u', function ($j) {
                    $j->on('u.id', '=', 'r.assignee_id')
                      ->where('r.assignee_type', 'user');
                })
                ->leftJoin('roles as ro', function ($j) {
                    $j->on('ro.id', '=', 'r.assignee_id')
                      ->where('r.assignee_type', 'role');
                })
                ->select([
                    'r.step_id', 'r.raci_type', 'r.assignee_type',
                    DB::raw("COALESCE(u.name, ro.name) as assignee_name"),
                ])
                ->get()
                ->groupBy('step_id');
        }

        // Query 4: Attachment count per step
        $attachmentCounts = collect();
        if ($stepIds->isNotEmpty()) {
            $attachmentCounts = DB::table('sop_step_attachments')
                ->whereIn('step_id', $stepIds)
                ->select('step_id', DB::raw('COUNT(*) as count'))
                ->groupBy('step_id')
                ->pluck('count', 'step_id');
        }

        $shapeMap = [
            'start'        => ['shape' => 'oval',          'color' => '#1D9E75', 'fill' => '#E1F5EE'],
            'end'          => ['shape' => 'oval_double',    'color' => '#1D9E75', 'fill' => '#E1F5EE'],
            'action'       => ['shape' => 'rect',           'color' => '#378ADD', 'fill' => '#E6F1FB'],
            'decision'     => ['shape' => 'diamond',        'color' => '#EF9F27', 'fill' => '#FAEEDA'],
            'sub_sop'      => ['shape' => 'rect_double',    'color' => '#1D9E75', 'fill' => '#E1F5EE'],
            'notification' => ['shape' => 'parallelogram',  'color' => '#7F77DD', 'fill' => '#EEEDFE'],
            'wait'         => ['shape' => 'rounded_rect',   'color' => '#888780', 'fill' => '#F1EFE8'],
        ];

        $stepsWithData = $steps->map(function ($step) use ($raci, $attachmentCounts, $shapeMap) {
            $shape = $shapeMap[$step->step_type] ?? $shapeMap['action'];
            return array_merge((array) $step, $shape, [
                'raci'             => $raci->get($step->id, collect())->values(),
                'attachment_count' => $attachmentCounts->get($step->id, 0),
            ]);
        });

        return [
            'steps'          => $stepsWithData,
            'connectors'     => $connectors,
            'total_duration' => $steps->sum('duration_minutes'),
            'mandatory_count' => $steps->where('is_mandatory', true)->count(),
        ];
    }
}
