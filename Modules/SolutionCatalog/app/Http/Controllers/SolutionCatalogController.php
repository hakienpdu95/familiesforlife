<?php

namespace Modules\SolutionCatalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\BusinessSolution\Models\BusinessSolution;
use Modules\BusinessSolution\Models\Vertical;
use Modules\OrganizationSolution\Models\OrganizationSolution;
use Modules\SolutionCatalog\Queries\ListPublishedSolutionsHandler;
use Modules\SolutionCatalog\Queries\ListPublishedSolutionsQuery;

class SolutionCatalogController extends Controller
{
    public function __construct(private readonly ListPublishedSolutionsHandler $handler) {}

    public function index(Request $request): View
    {
        $solutions = $this->handler->handle(new ListPublishedSolutionsQuery(
            verticalId: $request->integer('vertical_id') ?: null,
            tag:        $request->string('tag')->value() ?: null,
            search:     $request->string('q')->value() ?: null,
        ));

        $verticals = Vertical::query()->orderBy('name')->get();

        return view('solutioncatalog::index', compact('solutions', 'verticals'));
    }

    public function show(BusinessSolution $businessSolution): View
    {
        if ($businessSolution->status !== 'published' || ! in_array($businessSolution->visibility, ['public', 'marketplace'], true)) {
            abort(404);
        }

        $businessSolution->loadMissing([
            'vertical', 'tags',
            'blueprints' => fn ($q) => $q->where('status', 'published'),
            'blueprints.createdBy',
            'blueprints.currentVersion.outcomes',
            'blueprints.currentVersion.capabilities',
            'blueprints.currentVersion.aiCapabilities',
        ]);

        // "chỉ hiện nút Kích hoạt nếu tổ chức hiện tại chưa có organization_solutions
        // cho solution này" (spec §5.5) — OrganizationSolution đã tự scope theo tenant hiện tại.
        $alreadyActivated = OrganizationSolution::where('business_solution_id', $businessSolution->id)->exists();

        return view('solutioncatalog::show', compact('businessSolution', 'alreadyActivated'));
    }
}
