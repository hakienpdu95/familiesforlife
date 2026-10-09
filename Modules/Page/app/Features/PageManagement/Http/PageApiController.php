<?php

namespace Modules\Page\Features\PageManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Menu\Enums\MenuLinkType;
use Modules\Menu\Models\MenuItem;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Features\PageManagement\Http\Resources\PageListResource;
use Modules\Page\Features\PageManagement\Queries\ListPagesForAdminHandler;
use Modules\Page\Features\PageManagement\Queries\ListPagesForAdminQuery;
use Modules\Page\Models\Page;

/** JSON backend cho Tabulator ở dashboard/pages/items — cùng pattern ArticleApiController. */
class PageApiController extends Controller
{
    public function index(Request $request, ListPagesForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Page::class);

        $validated = $request->validate([
            'page'   => ['nullable', 'integer', 'min:1'],
            'size'   => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'in:' . implode(',', array_column(PageStatus::cases(), 'value'))],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'updated_at') : 'updated_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListPagesForAdminQuery(
            search:    $validated['search'] ?? null,
            status:    $validated['status'] ?? null,
            page:      max(1, (int) ($validated['page'] ?? 1)),
            perPage:   min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);
        $this->attachMenuPlacements($paginator->getCollection());

        return response()->json([
            'data'      => PageListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    /**
     * Cột "Hiển thị ở" — mục menu (Modules/Menu, link_type=page) đang trỏ tới từng trang, gộp
     * 1 query cho cả trang kết quả thay vì N+1 trong PageListResource.
     *
     * @param  \Illuminate\Support\Collection<int, Page>  $pages
     */
    private function attachMenuPlacements(\Illuminate\Support\Collection $pages): void
    {
        $byPage = MenuItem::query()
            ->where('link_type', MenuLinkType::Page->value)
            ->whereIn('page_id', $pages->pluck('id'))
            ->with('parent:id,label')
            ->orderBy('location')->orderBy('sort_order')
            ->get(['id', 'uuid', 'page_id', 'parent_id', 'location', 'label', 'is_active'])
            ->groupBy('page_id');

        $locations = config('menu.locations');

        $pages->each(fn (Page $page) => $page->setAttribute('menu_placements', $byPage->get($page->id, collect())
            ->map(fn (MenuItem $item) => [
                'path'      => collect([
                    str($locations[$item->location] ?? $item->location)->before(' (')->toString(),
                    $item->parent?->label,
                ])->filter()->implode(' › '),
                'label'     => $item->label,
                'is_active' => (bool) $item->is_active,
                'edit_url'  => route('backend.menu.items.edit', $item),
            ])->values()->all()));
    }
}
