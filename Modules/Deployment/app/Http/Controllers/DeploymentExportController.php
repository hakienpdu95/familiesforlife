<?php

namespace Modules\Deployment\Http\Controllers;

use App\Foundation\Export\SurveyExportBuilder;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Project\Models\Project;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeploymentExportController extends Controller
{
    // ── Field picker form (single target) ─────────────────────────────────────

    public function index(Request $request, DeploymentTarget $target): View
    {
        $this->authorize('view', $target);

        $vertical = $request->attributes->get('_vertical');
        $target->load(['targetOrganization', 'dataCollectionResponse', 'project']);

        $builder = new SurveyExportBuilder;
        $catalog = $builder->fieldCatalog($target);
        $orgName = $target->targetOrganization?->name ?? "Target #{$target->id}";

        return view('deployment::export.index', compact(
            'vertical', 'target', 'catalog', 'orgName'
        ));
    }

    // ── Export single target ──────────────────────────────────────────────────

    public function exportTarget(Request $request, DeploymentTarget $target): StreamedResponse
    {
        $this->authorize('view', $target);

        $target->load(['targetOrganization', 'dataCollectionResponse']);

        $builder  = new SurveyExportBuilder;
        $catalog  = $builder->fieldCatalog($target);
        $labelMap = $builder->flatLabelMap($catalog);

        $selected = $this->resolveSelected($request, $labelMap);

        $rows     = $builder->buildRows(collect([$target]), $selected, $labelMap);
        $filename = $this->filename($target->vertical_code, 'target');

        return (new FastExcel($rows))->download($filename);
    }

    // ── Field picker form (project — all targets) ─────────────────────────────

    public function projectIndex(Request $request, Project $project): View
    {
        $this->authorize('view', $project);

        $vertical = $request->attributes->get('_vertical');

        // Use first target with a survey response as template for the catalog
        $anchor = DeploymentTarget::where('project_id', $project->id)
            ->whereNotNull('data_collection_response_id')
            ->with(['targetOrganization', 'dataCollectionResponse'])
            ->first();

        $builder = new SurveyExportBuilder;
        $catalog = $anchor ? $builder->fieldCatalog($anchor) : [];

        $targetCount = DeploymentTarget::where('project_id', $project->id)
            ->whereNotIn('current_phase', ['draft'])
            ->count();

        return view('deployment::export.project', compact(
            'vertical', 'project', 'catalog', 'targetCount', 'anchor'
        ));
    }

    // ── Export all targets in project ─────────────────────────────────────────

    public function exportProject(Request $request, Project $project): StreamedResponse
    {
        $this->authorize('view', $project);

        $targets = DeploymentTarget::where('project_id', $project->id)
            ->whereNotIn('current_phase', ['draft'])
            ->whereNotNull('data_collection_response_id')
            ->with(['targetOrganization', 'dataCollectionResponse'])
            ->get();

        // Build catalog from first available target
        $anchor = $targets->first();
        if (! $anchor) {
            abort(422, 'Chưa có target nào hoàn thành thu thập dữ liệu.');
        }

        $builder  = new SurveyExportBuilder;
        $catalog  = $builder->fieldCatalog($anchor);
        $labelMap = $builder->flatLabelMap($catalog);

        $selected = $this->resolveSelected($request, $labelMap);
        $rows     = $builder->buildRows($targets, $selected, $labelMap);
        $filename = $this->filename($anchor->vertical_code, 'project_' . $project->id);

        return (new FastExcel($rows))->download($filename);
    }

    // ── Template download (blank Excel for upload-based data) ─────────────────

    public function template(Request $request, string $type): mixed
    {
        $columns = match ($type) {
            'donvi'  => ['MA_CHUTHE', 'MA_LO', 'MA_DV', 'LOAI_DV'],
            'nhatky' => ['MA_CHUTHE', 'MA_LO', 'NGAY', 'LOAI_HD', 'SO_LUONG', 'DON_VI', 'NGUOI_TH'],
            default  => null,
        };

        if (! $columns) {
            abort(404, "Template '{$type}' không tồn tại.");
        }

        $tmpPath  = sys_get_temp_dir() . "/template_{$type}.xlsx";
        $filename = "template_{$type}.xlsx";

        (new FastExcel(collect([array_fill_keys($columns, '')])))->export($tmpPath);

        return response()->download($tmpPath, $filename);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Resolve selected field sources from POST data.
     * Falls back to ALL available fields if nothing posted (e.g. direct link).
     *
     * @return array<int, string>  ordered source strings
     */
    private function resolveSelected(Request $request, array $labelMap): array
    {
        $posted = $request->input('fields', []);

        if (empty($posted)) {
            return array_keys($labelMap);
        }

        // Only allow sources that exist in the label map (security: no arbitrary source injection)
        return array_values(array_filter($posted, fn ($s) => isset($labelMap[$s])));
    }

    private function filename(string $verticalCode, string $suffix): string
    {
        return "{$verticalCode}_export_{$suffix}_" . now()->format('Ymd_His') . '.xlsx';
    }
}
