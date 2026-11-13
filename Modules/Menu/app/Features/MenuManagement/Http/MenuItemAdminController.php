<?php

namespace Modules\Menu\Features\MenuManagement\Http;

use App\Actions\I18n\SaveModelTranslationsAction;
use App\Http\Controllers\Controller;
use App\Support\I18n\PortalLocale;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Menu\Enums\MenuLinkType;
use Modules\Menu\Features\MenuManagement\Actions\CreateMenuItemAction;
use Modules\Menu\Features\MenuManagement\Actions\DeleteMenuItemAction;
use Modules\Menu\Features\MenuManagement\Actions\ReorderMenuItemsAction;
use Modules\Menu\Features\MenuManagement\Actions\UpdateMenuItemAction;
use Modules\Menu\Features\MenuManagement\Data\MenuItemData;
use Modules\Menu\Models\MenuItem;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeHandler;
use Modules\Post\Features\CategoryManagement\Queries\GetCategoryTreeQuery;
use Modules\Post\Models\PostCategory;

class MenuItemAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MenuItem::class, 'menuItem');
    }

    /** Dữ liệu bảng lấy qua MenuItemApiController (Tabulator dataTree). */
    public function index(): View
    {
        return view('menu::admin.menu-items.index');
    }

    public function create(): View
    {
        return view('menu::admin.menu-items.create', [
            'parentOptions' => $this->parentOptions(),
            'categoryOptions' => $this->categoryOptions(),
            'pageOptions' => $this->pageOptions(),
        ]);
    }

    public function store(Request $request, CreateMenuItemAction $action): RedirectResponse
    {
        $translations = $this->validatedTranslations($request);
        $data = MenuItemData::from($this->validated($request));
        $menuItem = $action->handle($data);
        SaveModelTranslationsAction::run($menuItem, $translations);

        return redirect()->route('backend.menu.items.index')
            ->with('success', __('Mục menu ":menu_item_label" đã được tạo.', ['menu_item_label' => $menuItem->label]));
    }

    public function edit(MenuItem $menuItem): View
    {
        $menuItem->load([
            'category:id,slug,name,is_active',
            'page:id,slug,title,status,published_at',
            'createdBy:id,name',
            'updatedBy:id,name',
        ])->loadCount('children');

        return view('menu::admin.menu-items.edit', [
            'menuItem' => $menuItem,
            'parentOptions' => $this->parentOptions($menuItem),
            'categoryOptions' => $this->categoryOptions(),
            'pageOptions' => $this->pageOptions(),
        ]);
    }

    public function update(Request $request, MenuItem $menuItem, UpdateMenuItemAction $action): RedirectResponse
    {
        $translations = $this->validatedTranslations($request);
        $data = MenuItemData::from($this->validated($request, $menuItem));
        $action->handle($menuItem, $data);
        SaveModelTranslationsAction::run($menuItem, $translations);

        return redirect()->route('backend.menu.items.index')
            ->with('success', __('Cập nhật mục menu thành công.'));
    }

    public function destroy(Request $request, MenuItem $menuItem, DeleteMenuItemAction $action): RedirectResponse|JsonResponse
    {
        $label = $menuItem->label;

        try {
            $action->handle($menuItem);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['menu_item' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Đã xoá mục menu ":label".', ['label' => $label])]);
        }

        return redirect()->route('backend.menu.items.index')
            ->with('success', __('Đã xoá mục menu ":label".', ['label' => $label]));
    }

    public function reorder(Request $request, ReorderMenuItemsAction $action): RedirectResponse|JsonResponse
    {
        $this->authorize('update', MenuItem::class);

        $action->handle($request->array('order'));

        if ($request->expectsJson()) {
            return response()->json(['message' => __('Đã cập nhật thứ tự menu.')]);
        }

        return back()->with('success', __('Đã cập nhật thứ tự menu.'));
    }

    /**
     * §5.2/§6.2 — UI filter (chỉ liệt kê parent hợp lệ) + §5.3 Action validate là 2 lớp
     * double-check độc lập, lớp này không thay thế lớp kia.
     */
    private function validatedTranslations(Request $request): ?array
    {
        return $request->validate(SaveModelTranslationsAction::rules([
            'label' => ['nullable', 'string', 'max:150'],
        ]))['translations'] ?? null;
    }

    private function validated(Request $request, ?MenuItem $ignoring = null): array
    {
        return $request->validate([
            'location' => ['required', Rule::in(array_keys(config('menu.locations')))],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('menu_items', 'id')->using(fn ($query) => $query
                    ->where('depth', '<', config('menu.max_depth'))
                    ->when($ignoring, fn ($q) => $q->where('id', '!=', $ignoring->id))),
            ],
            'label' => ['required', 'string', 'max:150'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'open_in_new_tab' => ['boolean'],
            'link_type' => ['required', Rule::in(array_column(MenuLinkType::cases(), 'value'))],
            'category_id' => [
                'nullable', 'integer',
                'required_if:link_type,'.MenuLinkType::Category->value,
                'prohibited_unless:link_type,'.MenuLinkType::Category->value,
                Rule::exists('post_categories', 'id')->where('is_active', true),
            ],
            'page_id' => [
                'nullable', 'integer',
                'required_if:link_type,'.MenuLinkType::Page->value,
                'prohibited_unless:link_type,'.MenuLinkType::Page->value,
                Rule::exists('pages', 'id')->whereNull('deleted_at'),
            ],
            'url' => [
                'nullable', 'string', 'max:2048',
                'required_if:link_type,'.MenuLinkType::Url->value,
                'prohibited_unless:link_type,'.MenuLinkType::Url->value,
            ],
        ], [
            'page_id.required_if' => __('Vui lòng chọn trang tĩnh.'),
            'page_id.prohibited_unless' => __('Chỉ chọn trang tĩnh khi đích liên kết là "Trang tĩnh".'),
            'page_id.exists' => __('Trang tĩnh được chọn không hợp lệ hoặc đã bị xoá.'),
        ]);
    }

    /** Chỉ liệt kê MenuItem có depth < max_depth — không cho chọn 1 item cấp 2 làm cha (§5.2). */
    private function parentOptions(?MenuItem $editing = null): Collection
    {
        return MenuItem::query()
            ->where('depth', '<', config('menu.max_depth'))
            ->when($editing, fn ($q) => $q->whereKeyNot($editing->id))
            ->orderBy('location')->orderBy('sort_order')
            ->get(['id', 'label', 'location', 'depth']);
    }

    private function categoryOptions(): Collection
    {
        $rows = collect(PostCategory::flatten(
            app(GetCategoryTreeHandler::class)->handle(new GetCategoryTreeQuery(activeOnly: true))
        ));
        (new EloquentCollection($rows->pluck('category')->all()))
            ->load(['translations' => fn ($q) => $q->whereIn('locale', PortalLocale::prefixed())]);

        $paths = [];

        return $rows->map(function (array $row) use (&$paths) {
            $category = $row['category'];
            $paths[$category->id] = [...($category->parent_id ? $paths[$category->parent_id] ?? [] : []), $category->name];

            return (object) [
                'id' => $category->id,
                'name' => $category->name,
                'depth' => $row['depth'],
                'path' => implode(' › ', $paths[$category->id]),
                'translations' => $category->translations->pluck('name', 'locale')->filter()->all(),
            ];
        });
    }

    /** Liệt kê cả trang Nháp — mục menu tự ẩn trên nav công khai cho tới khi trang được xuất bản. */
    private function pageOptions(): Collection
    {
        return Page::query()->orderBy('title')
            ->with(['translations' => fn ($q) => $q->whereIn('locale', PortalLocale::prefixed())])
            ->get(['id', 'title', 'slug', 'status', 'published_at'])
            ->map(fn (Page $page) => (object) [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'is_published' => $page->status === PageStatus::Published && $page->published_at !== null,
                'translations' => $page->translations->pluck('title', 'locale')->filter()->all(),
            ]);
    }
}
