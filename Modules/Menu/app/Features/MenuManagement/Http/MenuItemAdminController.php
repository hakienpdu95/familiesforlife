<?php

namespace Modules\Menu\Features\MenuManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Menu\Enums\MenuLinkType;
use Modules\Menu\Features\MenuManagement\Actions\CreateMenuItemAction;
use Modules\Menu\Features\MenuManagement\Actions\DeleteMenuItemAction;
use Modules\Menu\Features\MenuManagement\Actions\ReorderMenuItemsAction;
use Modules\Menu\Features\MenuManagement\Actions\UpdateMenuItemAction;
use Modules\Menu\Features\MenuManagement\Data\MenuItemData;
use Modules\Menu\Features\MenuManagement\Queries\GetMenuTreeForAdminHandler;
use Modules\Menu\Features\MenuManagement\Queries\GetMenuTreeForAdminQuery;
use Modules\Menu\Models\MenuItem;
use Modules\Post\Models\PostCategory;

class MenuItemAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MenuItem::class, 'menuItem');
    }

    public function index(Request $request, GetMenuTreeForAdminHandler $handler): View
    {
        $tree = $handler->handle(new GetMenuTreeForAdminQuery(
            location: $request->string('location')->value() ?: null,
            search: $request->string('q')->trim()->value() ?: null,
        ));

        return view('menu::admin.menu-items.index', compact('tree'));
    }

    public function create(): View
    {
        return view('menu::admin.menu-items.create', [
            'parentOptions'   => $this->parentOptions(),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request, CreateMenuItemAction $action): RedirectResponse
    {
        $data     = MenuItemData::from($this->validated($request));
        $menuItem = $action->handle($data);

        return redirect()->route('backend.menu.items.index')
            ->with('success', "Mục menu \"{$menuItem->label}\" đã được tạo.");
    }

    public function edit(MenuItem $menuItem): View
    {
        return view('menu::admin.menu-items.edit', [
            'menuItem'        => $menuItem,
            'parentOptions'   => $this->parentOptions($menuItem),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, MenuItem $menuItem, UpdateMenuItemAction $action): RedirectResponse
    {
        $data = MenuItemData::from($this->validated($request, $menuItem));
        $action->handle($menuItem, $data);

        return redirect()->route('backend.menu.items.index')
            ->with('success', 'Cập nhật mục menu thành công.');
    }

    public function destroy(MenuItem $menuItem, DeleteMenuItemAction $action): RedirectResponse
    {
        try {
            $action->handle($menuItem);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['menu_item' => $e->getMessage()]);
        }

        return redirect()->route('backend.menu.items.index')
            ->with('success', "Đã xoá mục menu \"{$menuItem->label}\".");
    }

    public function reorder(Request $request, ReorderMenuItemsAction $action): RedirectResponse
    {
        $this->authorize('update', MenuItem::class);

        $action->handle($request->array('order'));

        return back()->with('success', 'Đã cập nhật thứ tự menu.');
    }

    /**
     * §5.2/§6.2 — UI filter (chỉ liệt kê parent hợp lệ) + §5.3 Action validate là 2 lớp
     * double-check độc lập, lớp này không thay thế lớp kia.
     */
    private function validated(Request $request, ?MenuItem $ignoring = null): array
    {
        return $request->validate([
            'location'        => ['required', Rule::in(array_keys(config('menu.locations')))],
            'parent_id'       => [
                'nullable', 'integer',
                Rule::exists('menu_items', 'id')->using(fn ($query) => $query
                    ->where('depth', '<', config('menu.max_depth'))
                    ->when($ignoring, fn ($q) => $q->whereKeyNot($ignoring->id))),
            ],
            'label'           => ['required', 'string', 'max:150'],
            'icon'            => ['nullable', 'string', 'max:80'],
            'sort_order'      => ['integer', 'min:0'],
            'is_active'       => ['boolean'],
            'open_in_new_tab' => ['boolean'],
            'link_type'       => ['required', Rule::in(array_column(MenuLinkType::cases(), 'value'))],
            'category_id'     => [
                'nullable', 'integer',
                'required_if:link_type,' . MenuLinkType::Category->value,
                'prohibited_unless:link_type,' . MenuLinkType::Category->value,
                Rule::exists('post_categories', 'id')->where('is_active', true),
            ],
            'url'             => [
                'nullable', 'string', 'max:2048',
                'required_if:link_type,' . MenuLinkType::Url->value,
                'prohibited_unless:link_type,' . MenuLinkType::Url->value,
            ],
        ]);
    }

    /** Chỉ liệt kê MenuItem có depth < max_depth — không cho chọn 1 item cấp 2 làm cha (§5.2). */
    private function parentOptions(?MenuItem $editing = null): \Illuminate\Support\Collection
    {
        return MenuItem::query()
            ->where('depth', '<', config('menu.max_depth'))
            ->when($editing, fn ($q) => $q->whereKeyNot($editing->id))
            ->orderBy('location')->orderBy('sort_order')
            ->get(['id', 'label', 'location', 'depth']);
    }

    private function categoryOptions(): \Illuminate\Support\Collection
    {
        return PostCategory::active()->orderBy('name')->get(['id', 'name']);
    }
}
