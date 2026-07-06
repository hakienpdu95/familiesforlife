<?php

namespace Modules\OcopRubric\Features\ProductGroupCatalog\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\OcopRubric\Features\ProductGroupCatalog\Actions\CreateProductGroupAction;
use Modules\OcopRubric\Features\ProductGroupCatalog\Actions\UpdateProductGroupAction;
use Modules\OcopRubric\Features\ProductGroupCatalog\Data\ProductGroupData;
use Modules\OcopRubric\Features\ProductGroupCatalog\Queries\ListProductGroupsHandler;
use Modules\OcopRubric\Features\ProductGroupCatalog\Queries\ListProductGroupsQuery;
use Modules\OcopRubric\Models\OcopProductGroup;

class ProductGroupController extends Controller
{
    public function __construct(private readonly ListProductGroupsHandler $listHandler) {}

    public function index(Request $request): View
    {
        $groups = $this->listHandler->handle(new ListProductGroupsQuery(
            industryCode: $request->string('industry_code')->value() ?: null,
            search:       $request->string('q')->value() ?: null,
        ));

        return view('ocoprubric::admin.product-groups.index', compact('groups'));
    }

    public function create(): View
    {
        return view('ocoprubric::admin.product-groups.create');
    }

    public function store(Request $request, CreateProductGroupAction $action): RedirectResponse
    {
        $data  = ProductGroupData::from($this->validated($request));
        $group = $action->handle($data);

        return redirect()->route('ocop_rubric.admin.product-groups.index')
            ->with('success', "Bộ sản phẩm \"{$group->name}\" đã được tạo.");
    }

    public function edit(OcopProductGroup $productGroup): View
    {
        return view('ocoprubric::admin.product-groups.edit', ['group' => $productGroup]);
    }

    public function update(Request $request, OcopProductGroup $productGroup, UpdateProductGroupAction $action): RedirectResponse
    {
        $data = ProductGroupData::from($this->validated($request, $productGroup->id));
        $action->handle($productGroup, $data);

        return redirect()->route('ocop_rubric.admin.product-groups.index')
            ->with('success', 'Cập nhật bộ sản phẩm thành công.');
    }

    public function destroy(OcopProductGroup $productGroup): RedirectResponse
    {
        if ($productGroup->rubricVersions()->exists()) {
            return back()->withErrors(['product_group' => 'Không thể xóa bộ sản phẩm đã có bộ tiêu chí.']);
        }

        $productGroup->delete();

        return redirect()->route('ocop_rubric.admin.product-groups.index')
            ->with('success', "Đã xóa bộ sản phẩm \"{$productGroup->name}\".");
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $codeRule = 'required|string|max:60|regex:/^[a-z0-9\-]+$/|unique:ocop_product_groups,code'
            . ($ignoreId ? ",{$ignoreId}" : '');

        return $request->validate([
            'code'                     => $codeRule,
            'name'                     => 'required|string|max:255',
            'industry_code'            => 'required|string|max:10',
            'industry_name'            => 'required|string|max:255',
            'group_label'              => 'nullable|string|max:255',
            'managing_agency'          => 'nullable|string|max:255',
            'requires_sample_product'  => 'boolean',
            'is_active'                => 'boolean',
            'sort_order'               => 'integer|min:0',
        ]);
    }
}
