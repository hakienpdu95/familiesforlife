<?php

namespace Modules\Event\Features\EventCategoryManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Event\Features\EventCategoryManagement\Actions\CreateEventCategoryAction;
use Modules\Event\Features\EventCategoryManagement\Actions\DeleteEventCategoryAction;
use Modules\Event\Features\EventCategoryManagement\Actions\ReorderEventCategoriesAction;
use Modules\Event\Features\EventCategoryManagement\Actions\UpdateEventCategoryAction;
use Modules\Event\Features\EventCategoryManagement\Data\EventCategoryData;
use Modules\Event\Features\EventCategoryManagement\Queries\ListEventCategoriesForAdminHandler;
use Modules\Event\Features\EventCategoryManagement\Queries\ListEventCategoriesForAdminQuery;
use Modules\Event\Models\EventCategory;

class EventCategoryAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(EventCategory::class, 'category');
    }

    /** Dữ liệu bảng lấy qua EventCategoryApiController (Tabulator dataTree). */
    public function index(): View
    {
        return view('event::admin.categories.index');
    }

    public function create(ListEventCategoriesForAdminHandler $handler): View
    {
        $categories = $handler->handle(new ListEventCategoriesForAdminQuery());

        return view('event::admin.categories.create', compact('categories'));
    }

    public function store(Request $request, CreateEventCategoryAction $action): RedirectResponse
    {
        $data     = EventCategoryData::from($this->validated($request));
        $category = $action->handle($data);

        return redirect()->route('backend.event.categories.index')
            ->with('success', "Danh mục \"{$category->name}\" đã được tạo.");
    }

    public function edit(EventCategory $category, ListEventCategoriesForAdminHandler $handler): View
    {
        $categories = $handler->handle(new ListEventCategoriesForAdminQuery());

        return view('event::admin.categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, EventCategory $category, UpdateEventCategoryAction $action): RedirectResponse
    {
        $data = EventCategoryData::from($this->validated($request));
        $action->handle($category, $data);

        return redirect()->route('backend.event.categories.index')
            ->with('success', 'Cập nhật danh mục thành công.');
    }

    public function destroy(Request $request, EventCategory $category, DeleteEventCategoryAction $action): RedirectResponse|JsonResponse
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

        return redirect()->route('backend.event.categories.index')
            ->with('success', "Đã xoá danh mục \"{$name}\".");
    }

    public function reorder(Request $request, ReorderEventCategoriesAction $action): RedirectResponse
    {
        $this->authorize('update', EventCategory::class);

        $action->handle($request->array('order'));

        return back()->with('success', 'Đã cập nhật thứ tự danh mục.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'parent_id'  => ['nullable', 'integer', 'exists:event_categories,id'],
            'name'       => ['required', 'string', 'max:100'],
            'icon'       => ['nullable', 'string', 'max:50'],
            'color_hex'  => ['nullable', 'string', 'max:7'],
            'is_active'  => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }
}
