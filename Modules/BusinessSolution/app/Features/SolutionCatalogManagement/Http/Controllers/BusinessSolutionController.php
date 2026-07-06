<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\BusinessSolution\Enums\BusinessSolutionVisibility;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Actions\ArchiveBusinessSolutionAction;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Actions\CreateBusinessSolutionAction;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Actions\PublishBusinessSolutionAction;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Actions\UpdateBusinessSolutionAction;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Data\BusinessSolutionData;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Exceptions\BusinessSolutionNotPublishableException;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Queries\ListBusinessSolutionsHandler;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Queries\ListBusinessSolutionsQuery;
use Modules\BusinessSolution\Models\BusinessSolution;
use Modules\BusinessSolution\Models\Vertical;

class BusinessSolutionController extends Controller
{
    public function __construct(private readonly ListBusinessSolutionsHandler $listHandler) {}

    public function index(Request $request): View
    {
        $solutions = $this->listHandler->handle(new ListBusinessSolutionsQuery(
            verticalId: $request->integer('vertical_id') ?: null,
            status:     $request->string('status')->value() ?: null,
            visibility: $request->string('visibility')->value() ?: null,
            search:     $request->string('q')->value() ?: null,
        ));

        $verticals = Vertical::query()->orderBy('name')->get();

        return view('businesssolution::admin.business-solutions.index', compact('solutions', 'verticals'));
    }

    public function create(): View
    {
        $verticals = Vertical::query()->orderBy('name')->get();

        return view('businesssolution::admin.business-solutions.create', compact('verticals'));
    }

    public function store(Request $request, CreateBusinessSolutionAction $action): RedirectResponse
    {
        $data     = BusinessSolutionData::from($this->validated($request));
        $solution = $action->handle($data);

        return redirect()->route('business_solutions.admin.index')
            ->with('success', "Business Solution \"{$solution->name}\" đã được tạo (draft).");
    }

    public function edit(BusinessSolution $businessSolution): View
    {
        $verticals = Vertical::query()->orderBy('name')->get();

        return view('businesssolution::admin.business-solutions.edit', [
            'solution'  => $businessSolution,
            'verticals' => $verticals,
        ]);
    }

    public function update(Request $request, BusinessSolution $businessSolution, UpdateBusinessSolutionAction $action): RedirectResponse
    {
        $data = BusinessSolutionData::from($this->validated($request, $businessSolution->id));
        $action->handle($businessSolution, $data);

        return redirect()->route('business_solutions.admin.index')
            ->with('success', 'Cập nhật Business Solution thành công.');
    }

    public function destroy(BusinessSolution $businessSolution): RedirectResponse
    {
        if ($businessSolution->versions()->exists()) {
            return back()->withErrors(['business_solution' => 'Không thể xóa Business Solution đã có version.']);
        }

        $businessSolution->delete();

        return redirect()->route('business_solutions.admin.index')
            ->with('success', "Đã xóa Business Solution \"{$businessSolution->name}\".");
    }

    public function publish(BusinessSolution $businessSolution, PublishBusinessSolutionAction $action): RedirectResponse
    {
        try {
            $action->handle($businessSolution);
        } catch (BusinessSolutionNotPublishableException $e) {
            return back()->withErrors(['business_solution' => $e->getMessage()]);
        }

        return redirect()->route('business_solutions.admin.index')
            ->with('success', "Đã phát hành Business Solution \"{$businessSolution->name}\".");
    }

    public function archive(BusinessSolution $businessSolution, ArchiveBusinessSolutionAction $action): RedirectResponse
    {
        $action->handle($businessSolution);

        return redirect()->route('business_solutions.admin.index')
            ->with('success', "Đã lưu trữ Business Solution \"{$businessSolution->name}\".");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = 'required|string|max:50|regex:/^[A-Z0-9\-]+$/|unique:business_solutions,code'
            . ($ignoreId ? ",{$ignoreId}" : '');

        // Input hiển thị chữ hoa qua CSS (class="uppercase") nhưng giá trị submit vẫn
        // giữ nguyên chữ người dùng gõ — chuẩn hoá về chữ hoa thật trước khi validate
        // để khớp với những gì người dùng NHÌN THẤY trên form.
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper((string) $request->string('code'))]);
        }

        if ($request->filled('target_customers_raw')) {
            $request->merge([
                'target_customers' => array_values(array_filter(array_map(
                    'trim',
                    explode(',', (string) $request->string('target_customers_raw'))
                ))),
            ]);
        }

        return $request->validate([
            'code'               => $codeRule,
            'name'               => 'required|string|max:255',
            'vertical_id'        => 'required|integer|exists:verticals,id',
            'short_description'  => 'nullable|string',
            'description'        => 'nullable|string',
            'target_customers'   => 'nullable|array',
            'target_customers.*'  => 'string',
            'visibility'         => 'required|in:' . implode(',', array_column(BusinessSolutionVisibility::cases(), 'value')),
            'thumbnail_url'      => 'nullable|string|max:500',
        ]);
    }
}
