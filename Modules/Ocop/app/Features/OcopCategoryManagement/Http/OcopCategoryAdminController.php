<?php

namespace Modules\Ocop\Features\OcopCategoryManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ocop\Features\OcopCategoryManagement\Actions\CreateOcopCategoryAction;
use Modules\Ocop\Features\OcopCategoryManagement\Actions\DeleteOcopCategoryAction;
use Modules\Ocop\Features\OcopCategoryManagement\Actions\UpdateOcopCategoryAction;
use Modules\Ocop\Features\OcopCategoryManagement\Data\OcopCategoryData;
use Modules\Ocop\Features\OcopCategoryManagement\Queries\ListOcopCategoriesForAdminHandler;
use Modules\Ocop\Features\OcopCategoryManagement\Queries\ListOcopCategoriesForAdminQuery;
use Modules\Ocop\Models\OcopCategory;

class OcopCategoryAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OcopCategory::class, 'category');
    }

    public function index(Request $request, ListOcopCategoriesForAdminHandler $handler): View
    {
        $categories = $handler->handle(new ListOcopCategoriesForAdminQuery(
            search: $request->string('q')->value() ?: null,
        ));

        return view('ocop::admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('ocop::admin.categories.create');
    }

    public function store(Request $request, CreateOcopCategoryAction $action): RedirectResponse
    {
        $data     = OcopCategoryData::from($this->validated($request));
        $category = $action->handle($data);

        return redirect()->route('backend.ocop.categories.index')
            ->with('success', "Danh mục \"{$category->name}\" đã được tạo.");
    }

    public function edit(OcopCategory $category): View
    {
        return view('ocop::admin.categories.edit', compact('category'));
    }

    public function update(Request $request, OcopCategory $category, UpdateOcopCategoryAction $action): RedirectResponse
    {
        $data = OcopCategoryData::from($this->validated($request));
        $action->handle($category, $data);

        return redirect()->route('backend.ocop.categories.index')
            ->with('success', 'Cập nhật danh mục thành công.');
    }

    public function destroy(OcopCategory $category, DeleteOcopCategoryAction $action): RedirectResponse
    {
        try {
            $action->handle($category);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['category' => $e->getMessage()]);
        }

        return redirect()->route('backend.ocop.categories.index')
            ->with('success', "Đã xoá danh mục \"{$category->name}\".");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'       => ['required', 'string', 'max:150'],
            'icon'       => ['nullable', 'string', 'max:80'],
            'is_active'  => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }
}
