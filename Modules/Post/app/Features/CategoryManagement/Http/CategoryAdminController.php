<?php

namespace Modules\Post\Features\CategoryManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Post\Features\CategoryManagement\Actions\CreateCategoryAction;
use Modules\Post\Features\CategoryManagement\Actions\DeleteCategoryAction;
use Modules\Post\Features\CategoryManagement\Actions\ReorderCategoriesAction;
use Modules\Post\Features\CategoryManagement\Actions\UpdateCategoryAction;
use Modules\Post\Features\CategoryManagement\Data\CategoryData;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Models\PostCategory;

class CategoryAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PostCategory::class, 'category');
    }

    /** Dữ liệu bảng lấy qua CategoryApiController (Tabulator dataTree, xem §CategoryApiController). */
    public function index(): View
    {
        return view('post::admin.categories.index');
    }

    public function create(GetCategoryTreeHandler $handler): View
    {
        $categoryTree = PostCategory::flatten($handler->handle(new GetCategoryTreeQuery()));

        return view('post::admin.categories.create', compact('categoryTree'));
    }

    public function store(Request $request, CreateCategoryAction $action): RedirectResponse
    {
        $data     = CategoryData::from($this->validated($request));
        $category = $action->handle($data);

        return redirect()->route('backend.post.categories.index')
            ->with('success', "Danh mục \"{$category->name}\" đã được tạo.");
    }

    public function edit(PostCategory $category, GetCategoryTreeHandler $handler): View
    {
        // Loại chính category này VÀ toàn bộ hậu duệ khỏi dropdown "Danh mục cha" — chọn 1 danh
        // mục con/cháu làm cha của chính tổ tiên nó sẽ tạo vòng lặp (cycle) trong cây.
        $categoryTree = PostCategory::flatten($handler->handle(new GetCategoryTreeQuery()), excludeSubtreeRootId: $category->id);

        return view('post::admin.categories.edit', compact('category', 'categoryTree'));
    }

    public function update(Request $request, PostCategory $category, GetCategoryTreeHandler $handler, UpdateCategoryAction $action): RedirectResponse
    {
        $data = CategoryData::from($this->validated($request, $category->id, $handler));
        $action->handle($category, $data);

        return redirect()->route('backend.post.categories.index')
            ->with('success', 'Cập nhật danh mục thành công.');
    }

    public function destroy(Request $request, PostCategory $category, DeleteCategoryAction $action): RedirectResponse|JsonResponse
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
            return response()->json(['message' => "Đã xoá danh mục \"{$name}\"."]);
        }

        return redirect()->route('backend.post.categories.index')
            ->with('success', "Đã xoá danh mục \"{$name}\".");
    }

    public function reorder(Request $request, ReorderCategoriesAction $action): RedirectResponse
    {
        $this->authorize('update', PostCategory::class);

        $action->handle($request->array('order'));

        return back()->with('success', 'Đã cập nhật thứ tự danh mục.');
    }

    private function validated(Request $request, ?int $ignoreId = null, ?GetCategoryTreeHandler $treeHandler = null): array
    {
        return $request->validate([
            'parent_id' => [
                'nullable', 'integer', 'exists:post_categories,id',
                // Chặn cycle: không cho chọn chính danh mục đang sửa hoặc bất kỳ hậu duệ nào của
                // nó làm cha (vd A → B → C, không thể đổi cha của A thành C).
                function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId, $treeHandler): void {
                    if (! $ignoreId || ! $value || ! $treeHandler) {
                        return;
                    }

                    $tree = $treeHandler->handle(new GetCategoryTreeQuery());
                    if (in_array((int) $value, PostCategory::subtreeIds($tree, $ignoreId), true)) {
                        $fail('Không thể chọn chính danh mục này hoặc danh mục con/cháu của nó làm cha (tạo vòng lặp).');
                    }
                },
            ],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon'        => ['nullable', 'string', 'max:80'],
            'color_hex'   => ['nullable', 'string', 'max:7'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['integer', 'min:0'],
        ]);
    }
}
