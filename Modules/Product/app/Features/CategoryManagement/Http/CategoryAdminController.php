<?php

namespace Modules\Product\Features\CategoryManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Product\Features\CategoryManagement\Actions\CreateCategoryAction;
use Modules\Product\Features\CategoryManagement\Actions\DeleteCategoryAction;
use Modules\Product\Features\CategoryManagement\Actions\ReorderCategoriesAction;
use Modules\Product\Features\CategoryManagement\Actions\UpdateCategoryAction;
use Modules\Product\Features\CategoryManagement\Data\CategoryData;
use Modules\Product\Features\CategoryManagement\Queries\ListCategoriesForAdminHandler;
use Modules\Product\Features\CategoryManagement\Queries\ListCategoriesForAdminQuery;
use Modules\Product\Models\ProductCategory;

class CategoryAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ProductCategory::class, 'category');
    }

    /** Dữ liệu bảng lấy qua CategoryApiController (Tabulator dataTree). */
    public function index(): View
    {
        return view('product::admin.categories.index');
    }

    public function create(ListCategoriesForAdminHandler $handler): View
    {
        $categories = $handler->handle(new ListCategoriesForAdminQuery());

        return view('product::admin.categories.create', compact('categories'));
    }

    public function store(Request $request, CreateCategoryAction $action): RedirectResponse
    {
        $data     = CategoryData::from($this->validated($request));
        $category = $action->handle($data);

        return redirect()->route('backend.products.categories.index')
            ->with('success', __('Danh mục ":name" đã được tạo.', ['name' => $category->name]));
    }

    public function edit(ProductCategory $category, ListCategoriesForAdminHandler $handler): View
    {
        $categories = $handler->handle(new ListCategoriesForAdminQuery());

        return view('product::admin.categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, ProductCategory $category, UpdateCategoryAction $action): RedirectResponse
    {
        $data = CategoryData::from($this->validated($request, $category->id));
        $action->handle($category, $data);

        return redirect()->route('backend.products.categories.index')
            ->with('success', __('Cập nhật danh mục thành công.'));
    }

    public function destroy(Request $request, ProductCategory $category, DeleteCategoryAction $action): RedirectResponse|JsonResponse
    {
        $name = $category->name;

        try {
            $action->handle($category);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['category' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Đã xoá danh mục ":name".', ['name' => $name])]);
        }

        return redirect()->route('backend.products.categories.index')
            ->with('success', __('Đã xoá danh mục ":name".', ['name' => $name]));
    }

    public function reorder(Request $request, ReorderCategoriesAction $action): RedirectResponse
    {
        $this->authorize('update', ProductCategory::class);

        $action->handle($request->array('order'));

        return back()->with('success', __('Đã cập nhật thứ tự danh mục.'));
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'parent_id'   => ['nullable', 'integer', 'exists:product_categories,id'],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon'        => ['nullable', 'string', 'max:80'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
        ]);
    }
}
