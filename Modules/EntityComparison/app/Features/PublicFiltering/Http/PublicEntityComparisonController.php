<?php

namespace Modules\EntityComparison\Features\PublicFiltering\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\EntityComparison\Features\PublicFiltering\Queries\ListEntitiesForPublicHandler;
use Modules\EntityComparison\Features\PublicFiltering\Queries\ListEntitiesForPublicQuery;
use Modules\EntityComparison\Models\EntityType;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §7.2 bước 1 — trang lọc + danh sách công khai,
 * không cần đăng nhập (§0 mục 9).
 */
class PublicEntityComparisonController extends Controller
{
    public function index(Request $request, EntityType $entityType): View
    {
        abort_unless($entityType->is_active, 404);

        $criteria = $entityType->criteria()->filterable()->orderBy('sort_order')->get();

        $criterionFilters = [];
        foreach ((array) $request->input('filters', []) as $criterionId => $value) {
            $criterionFilters[(int) $criterionId] = $value;
        }

        $query = new ListEntitiesForPublicQuery(
            entityTypeId: $entityType->id,
            criterionFilters: $criterionFilters,
            page: max(1, $request->integer('page', 1)),
        );

        $entities = app(ListEntitiesForPublicHandler::class)->handle($query);

        return view('entitycomparison::public.index', compact('entityType', 'criteria', 'entities'));
    }
}
