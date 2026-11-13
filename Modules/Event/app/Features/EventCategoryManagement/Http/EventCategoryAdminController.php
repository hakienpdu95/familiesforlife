<?php

namespace Modules\Event\Features\EventCategoryManagement\Http;

use App\Actions\I18n\SaveModelTranslationsAction;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\Rule;
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
        return view('event::admin.categories.create', [
            'parentOptions' => $this->parentOptions($handler->handle(new ListEventCategoriesForAdminQuery)),
        ]);
    }

    public function store(Request $request, CreateEventCategoryAction $action): RedirectResponse
    {
        $translations = $this->validatedTranslations($request);
        $data = EventCategoryData::from($this->validated($request));
        $category = $action->handle($data);
        SaveModelTranslationsAction::run($category, $translations);

        return redirect()->route('backend.event.categories.index')
            ->with('success', __('Danh mục ":name" đã được tạo.', ['name' => $category->name]));
    }

    public function edit(EventCategory $category, ListEventCategoriesForAdminHandler $handler): View
    {
        $category->load(['createdBy:id,name', 'updatedBy:id,name'])->loadCount(['events', 'children']);

        return view('event::admin.categories.edit', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($handler->handle(new ListEventCategoriesForAdminQuery), $category),
        ]);
    }

    public function update(Request $request, EventCategory $category, UpdateEventCategoryAction $action): RedirectResponse
    {
        $translations = $this->validatedTranslations($request);
        $data = EventCategoryData::from($this->validated($request, $category));
        $action->handle($category, $data);
        SaveModelTranslationsAction::run($category, $translations);

        return redirect()->route('backend.event.categories.index')
            ->with('success', __('Cập nhật danh mục thành công.'));
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
            return response()->json(['message' => __('Đã xoá danh mục ":name".', ['name' => $name])]);
        }

        return redirect()->route('backend.event.categories.index')
            ->with('success', __('Đã xoá danh mục ":name".', ['name' => $name]));
    }

    public function reorder(Request $request, ReorderEventCategoriesAction $action): RedirectResponse
    {
        $this->authorize('update', EventCategory::class);

        $action->handle($request->array('order'));

        return back()->with('success', __('Đã cập nhật thứ tự danh mục.'));
    }

    private function validatedTranslations(Request $request): ?array
    {
        return $request->validate(SaveModelTranslationsAction::rules([
            'name' => ['nullable', 'string', 'max:100'],
        ]))['translations'] ?? null;
    }

    private function validated(Request $request, ?EventCategory $ignoring = null): array
    {
        $blockedParentIds = $ignoring
            ? $this->subtreeIds(EventCategory::query()->get(['id', 'parent_id']), $ignoring->id)
            : [];

        return $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:event_categories,id', Rule::notIn($blockedParentIds)],
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }

    private function parentOptions(Collection $categories, ?EventCategory $ignoring = null): SupportCollection
    {
        $blocked = $ignoring ? $this->subtreeIds($categories, $ignoring->id) : [];
        $byParent = $categories->groupBy(fn (EventCategory $c) => $c->parent_id ?? 0);
        $rows = collect();

        $walk = function (int $parentId, int $depth, array $path) use (&$walk, $byParent, $blocked, $rows): void {
            foreach ($byParent->get($parentId, []) as $category) {
                if (in_array($category->id, $blocked, true)) {
                    continue;
                }
                $trail = [...$path, $category->name];
                $rows->push((object) [
                    'id' => $category->id,
                    'name' => $category->name,
                    'depth' => $depth,
                    'path' => implode(' › ', $trail),
                    'is_active' => $category->is_active,
                ]);
                $walk($category->id, $depth + 1, $trail);
            }
        };
        $walk(0, 0, []);

        return $rows;
    }

    private function subtreeIds(Collection $categories, int $rootId): array
    {
        $byParent = $categories->groupBy(fn (EventCategory $c) => $c->parent_id ?? 0);
        $ids = [];
        $stack = [$rootId];

        while ($stack) {
            $id = array_pop($stack);
            $ids[] = $id;
            foreach ($byParent->get($id, []) as $child) {
                $stack[] = $child->id;
            }
        }

        return $ids;
    }
}
