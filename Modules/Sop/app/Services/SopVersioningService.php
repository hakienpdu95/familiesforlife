<?php

namespace Modules\Sop\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\Sop\Enums\ChangeType;
use Modules\Sop\Models\SopApprovalFlow;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopStep;
use Modules\Sop\Models\SopStepConnector;
use Modules\Sop\Models\SopStepRaci;
use Modules\Sop\Models\SopStepConnectorVersion;
use Modules\Sop\Models\SopStepRaciVersion;
use Modules\Sop\Models\SopStepVersion;
use Modules\Sop\Models\SopVersion;

class SopVersioningService
{
    /**
     * Tạo snapshot toàn bộ live-layer vào version tables (immutable).
     * Gọi khi approval flow hoàn tất (step cuối approved).
     */
    public function createSnapshot(SopVersion $version, int $approverId): void
    {
        DB::transaction(function () use ($version, $approverId) {
            $sopId = $version->sop_id;

            $steps = SopStep::where('sop_id', $sopId)
                ->where('is_active', true)
                ->orderBy('position')
                ->with(['raci'])
                ->get();

            $connectors = SopStepConnector::where('sop_id', $sopId)->get();

            // Load previous approved version for diff
            $prevVersion = SopVersion::where('sop_id', $sopId)
                ->where('status', 'approved')
                ->orderByDesc('version_number')
                ->first();

            $prevStepsByPos = $prevVersion
                ? SopStepVersion::where('sop_version_id', $prevVersion->id)
                    ->get()->keyBy('position')
                : collect();

            // Snapshot steps
            $stepVersionMap = []; // step BIGINT id => SopStepVersion id
            $stepPositionMap = $steps->pluck('position', 'id'); // step id => position

            foreach ($steps as $step) {
                $prev = $prevStepsByPos->get($step->position);
                $changeType = $this->detectChangeType($step, $prev);

                $sv = SopStepVersion::create([
                    'uuid'                => Str::uuid(),
                    'sop_version_id'      => $version->id,
                    'original_step_id'    => $step->id,
                    'position'            => $step->position,
                    'title'               => $step->title,
                    'description'         => $step->description,
                    'expected_output'     => $step->expected_output,
                    'warning_note'        => $step->warning_note,
                    'step_type'           => $step->step_type->value,
                    'ref_sop_id'          => $step->ref_sop_id,
                    'ref_sop_code'        => $step->refSop?->code,
                    'branch_yes_position' => $step->branch_yes_position,
                    'branch_no_position'  => $step->branch_no_position,
                    'duration_minutes'    => $step->duration_minutes,
                    'is_mandatory'        => $step->is_mandatory,
                    'change_type'         => $changeType,
                ]);

                $stepVersionMap[$step->id] = $sv->id;

                // Snapshot RACI
                foreach ($step->raci as $r) {
                    SopStepRaciVersion::create([
                        'uuid'            => Str::uuid(),
                        'sop_version_id'  => $version->id,
                        'step_version_id' => $sv->id,
                        'step_position'   => $step->position,
                        'assignee_type'   => $r->assignee_type,
                        'assignee_id'     => $r->assignee_id,
                        'assignee_name'   => $r->assigneeName(),
                        'raci_type'       => $r->raci_type,
                    ]);
                }
            }

            // Snapshot connectors — dùng position, không step id
            foreach ($connectors as $conn) {
                $fromPos = $stepPositionMap[$conn->from_step_id] ?? null;
                $toPos   = $stepPositionMap[$conn->to_step_id] ?? null;
                if ($fromPos === null || $toPos === null) {
                    continue;
                }

                SopStepConnectorVersion::create([
                    'uuid'            => Str::uuid(),
                    'sop_version_id'  => $version->id,
                    'from_position'   => $fromPos,
                    'to_position'     => $toPos,
                    'connector_type'  => $conn->connector_type->value,
                    'label'           => $conn->label,
                    'color_hex'       => $conn->color_hex,
                ]);
            }

            // Finalize version header
            $version->update([
                'status'                 => 'approved',
                'total_steps'            => $steps->count(),
                'total_duration_minutes' => $steps->sum('duration_minutes'),
                'approved_by'            => $approverId,
                'approved_at'            => now(),
            ]);

            // Promote SOP
            SopProcess::where('id', $sopId)->update([
                'status'      => 'approved',
                'version'     => $version->version_number,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Rebuild live-layer từ snapshot version cũ (tạo draft mới từ history).
     */
    public function rollbackToVersion(int $sopId, int $targetVersionNumber): void
    {
        DB::transaction(function () use ($sopId, $targetVersionNumber) {
            $version = SopVersion::where('sop_id', $sopId)
                ->where('version_number', $targetVersionNumber)
                ->where('status', 'approved')
                ->firstOrFail();

            $stepVersions = SopStepVersion::where('sop_version_id', $version->id)
                ->orderBy('position')
                ->get();

            // 1. Soft delete active steps
            SopStep::where('sop_id', $sopId)
                ->where('is_active', true)
                ->update(['is_active' => false, 'updated_at' => now()]);

            // 2. Remove current connectors
            SopStepConnector::where('sop_id', $sopId)->delete();

            // 3. Rebuild steps from snapshot
            $newStepMap = []; // position => new step BIGINT id
            foreach ($stepVersions as $sv) {
                $newStep = SopStep::create([
                    'uuid'                => Str::uuid(),
                    'sop_id'              => $sopId,
                    'position'            => $sv->position,
                    'title'               => $sv->title,
                    'description'         => $sv->description,
                    'expected_output'     => $sv->expected_output,
                    'warning_note'        => $sv->warning_note,
                    'step_type'           => $sv->step_type->value,
                    'ref_sop_id'          => $sv->ref_sop_id,
                    'branch_yes_position' => $sv->branch_yes_position,
                    'branch_no_position'  => $sv->branch_no_position,
                    'duration_minutes'    => $sv->duration_minutes,
                    'is_mandatory'        => $sv->is_mandatory,
                    'is_active'           => true,
                    'created_by'          => auth()->id(),
                ]);
                $newStepMap[$sv->position] = $newStep->id;
            }

            // 4. Rebuild connectors
            $connVersions = SopStepConnectorVersion::where('sop_version_id', $version->id)->get();
            foreach ($connVersions as $cv) {
                $fromId = $newStepMap[$cv->from_position] ?? null;
                $toId   = $newStepMap[$cv->to_position] ?? null;
                if (!$fromId || !$toId) {
                    continue;
                }
                SopStepConnector::create([
                    'uuid'           => Str::uuid(),
                    'sop_id'         => $sopId,
                    'from_step_id'   => $fromId,
                    'to_step_id'     => $toId,
                    'connector_type' => $cv->connector_type->value,
                    'label'          => $cv->label,
                    'color_hex'      => $cv->color_hex,
                ]);
            }

            // 5. Rebuild RACI
            $raciVersions = SopStepRaciVersion::where('sop_version_id', $version->id)->get();
            foreach ($raciVersions as $rv) {
                $newStepId = $newStepMap[$rv->step_position] ?? null;
                if (!$newStepId) {
                    continue;
                }
                SopStepRaci::create([
                    'uuid'          => Str::uuid(),
                    'step_id'       => $newStepId,
                    'assignee_type' => $rv->assignee_type,
                    'assignee_id'   => $rv->assignee_id,
                    'raci_type'     => $rv->raci_type,
                ]);
            }

            // 6. Reset SOP to draft for re-approval
            SopProcess::where('id', $sopId)->update([
                'status'     => 'draft',
                'updated_at' => now(),
            ]);
        });
    }

    public function detectChangeType(SopStep $step, ?SopStepVersion $prev): string
    {
        if (!$prev) {
            return ChangeType::Added->value;
        }

        $fields = [
            'title', 'description', 'expected_output', 'warning_note',
            'step_type', 'duration_minutes', 'is_mandatory',
        ];

        foreach ($fields as $f) {
            $stepVal = $step->$f instanceof \BackedEnum ? $step->$f->value : $step->$f;
            $prevVal = $prev->$f instanceof \BackedEnum ? $prev->$f->value : $prev->$f;
            if ($stepVal !== $prevVal) {
                return ChangeType::Modified->value;
            }
        }

        return ChangeType::Unchanged->value;
    }

    /**
     * Build snapshot flowchart data from a specific version (for diff view).
     */
    public function getVersionFlowchartData(SopVersion $version): array
    {
        $stepVersions = SopStepVersion::where('sop_version_id', $version->id)
            ->orderBy('position')
            ->get();

        $connVersions = SopStepConnectorVersion::where('sop_version_id', $version->id)->get();

        $raciByPos = SopStepRaciVersion::where('sop_version_id', $version->id)
            ->get()
            ->groupBy('step_position');

        $changeColors = [
            'added'     => '#639922',
            'modified'  => '#EF9F27',
            'deleted'   => '#E24B4A',
            'unchanged' => null,
        ];

        $shapeMap = [
            'start'        => ['shape' => 'oval',          'color' => '#1D9E75', 'fill' => '#E1F5EE'],
            'end'          => ['shape' => 'oval_double',    'color' => '#1D9E75', 'fill' => '#E1F5EE'],
            'action'       => ['shape' => 'rect',           'color' => '#378ADD', 'fill' => '#E6F1FB'],
            'decision'     => ['shape' => 'diamond',        'color' => '#EF9F27', 'fill' => '#FAEEDA'],
            'sub_sop'      => ['shape' => 'rect_double',    'color' => '#1D9E75', 'fill' => '#E1F5EE'],
            'notification' => ['shape' => 'parallelogram',  'color' => '#7F77DD', 'fill' => '#EEEDFE'],
            'wait'         => ['shape' => 'rounded_rect',   'color' => '#888780', 'fill' => '#F1EFE8'],
        ];

        $steps = $stepVersions->map(function ($sv) use ($shapeMap, $changeColors, $raciByPos) {
            $shape = $shapeMap[$sv->step_type->value] ?? $shapeMap['action'];
            $diffColor = $changeColors[$sv->change_type->value] ?? null;

            return array_merge((array) $sv->toArray(), $shape, [
                'change_color' => $diffColor,
                'raci'         => $raciByPos->get($sv->position, collect())->values(),
            ]);
        });

        // Connectors — build a position map first
        $posToStepId = $stepVersions->pluck('id', 'position');
        $connectors = $connVersions->map(fn ($cv) => [
            'id'             => $cv->id,
            'from_step_id'   => $posToStepId->get($cv->from_position),
            'to_step_id'     => $posToStepId->get($cv->to_position),
            'connector_type' => $cv->connector_type->value,
            'label'          => $cv->label,
            'color_hex'      => $cv->color_hex,
        ]);

        return [
            'steps'          => $steps->values(),
            'connectors'     => $connectors->values(),
            'total_duration' => $stepVersions->sum('duration_minutes'),
        ];
    }
}
