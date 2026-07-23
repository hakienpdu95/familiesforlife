<?php

namespace Modules\Event\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Features\PublicReading\Queries\ListPublishedEventsHandler;
use Modules\Event\Features\PublicReading\Queries\ListPublishedEventsQuery;
use Modules\Event\Features\PublicReading\Queries\LoadMoreEventsHandler;
use Modules\Event\Features\PublicReading\Queries\LoadMoreEventsQuery;
use Modules\Event\Models\Event;
use Modules\Event\Models\EventCategory;

/**
 * spec/Event_Management_Technical_Specification.md §8/§13 Phase 3 — không {locale}, cùng quyết
 * định đã áp dụng cho Post.
 *
 * Trước đây còn truyền `$categories = PostCategory::navTree()` cho layouts.frontend (nav toàn
 * site đọc qua biến kế thừa từ view cha) — từ spec/Menu_Navigation_Technical_Specification.md
 * Phase 3, nav chuyển sang MenuItem::tree() qua View Composer (MenuServiceProvider), không còn
 * phụ thuộc biến do controller truyền xuống nữa — đã bỏ ở Phase 4 (§8).
 */
class PublicEventController extends Controller
{
    public function index(Request $request, ListPublishedEventsHandler $handler): View
    {
        $search = $request->string('q')->trim()->value() ?: null;
        $page   = max(1, $request->integer('page', 1));
        $isMagazine = ! $search && $page === 1;

        // "Tin to" (size=lg, cùng bố cục danh-muc/{slug} của Post) — ưu tiên sự kiện
        // is_featured=true (mới ĐANG SẮP DIỄN RA nhất trong số đó), fallback sự kiện sắp diễn
        // ra gần nhất nếu không có bài nào được ghim. Chỉ áp dụng trang 1/không tìm kiếm.
        $lead = $isMagazine ? $this->leadEvent() : null;

        $events = $handler->handle(new ListPublishedEventsQuery(
            page: $page,
            search: $search,
            excludeEventIds: $lead ? [$lead->id] : [],
        ));

        $eventCategories = EventCategory::navTree();

        return view('event::public.index', compact('events', 'eventCategories', 'search', 'lead'));
    }

    public function category(Request $request, EventCategory $category, ListPublishedEventsHandler $handler): View
    {
        $search = $request->string('q')->trim()->value() ?: null;
        $page   = max(1, $request->integer('page', 1));
        $isMagazine = ! $search && $page === 1;

        $lead = $isMagazine ? $this->leadEvent($category->id) : null;

        $events = $handler->handle(new ListPublishedEventsQuery(
            page: $page,
            categoryId: $category->id,
            search: $search,
            excludeEventIds: $lead ? [$lead->id] : [],
        ));

        $eventCategories = EventCategory::navTree();

        return view('event::public.category', compact('events', 'category', 'eventCategories', 'search', 'lead'));
    }

    /**
     * "Xem thêm sự kiện" — dùng chung cho khối lưới trang su-kien VÀ su-kien/danh-muc/{slug}
     * (Modules/Event/resources/views/public/{index,category}.blade.php), gọi qua Alpine
     * (resources/js/frontend.js loadMoreEvents). Cùng cấu trúc
     * Modules\Post\Features\PublicReading\Http\PublicCategoryController::loadMore().
     */
    public function loadMore(Request $request, LoadMoreEventsHandler $handler): JsonResponse
    {
        $maxTotal  = (int) config('event.load_more_max_total');
        $loaded    = max(0, $request->integer('loaded', 0));
        $remaining = $maxTotal - $loaded;

        if ($remaining <= 0) {
            return response()->json(['html' => '', 'has_more' => false]);
        }

        $excludeEventIds = collect(explode(',', (string) $request->string('exclude')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $requestedLimit = $request->filled('limit') ? $request->integer('limit') : 8;
        $limit          = min(24, max(1, $requestedLimit), $remaining);

        $result = $handler->handle(new LoadMoreEventsQuery(
            afterStartDate: $request->string('after_start_date')->value() ?: null,
            afterId: $request->filled('after_id') ? $request->integer('after_id') : null,
            excludeEventIds: $excludeEventIds,
            limit: $limit,
            categoryId: $request->filled('category_id') ? $request->integer('category_id') : null,
        ));

        $events = $result['events'];
        $last   = $events->last();

        return response()->json([
            'html'        => view('event::public.partials.event-grid-items', ['events' => $events])->render(),
            'count'       => $events->count(),
            'next_cursor' => $last ? ['start_date' => $last->start_date?->toDateString(), 'id' => $last->id] : null,
            'has_more'    => $result['has_more'] && ($loaded + $events->count()) < $maxTotal,
        ]);
    }

    /**
     * "Tin to" — ưu tiên sự kiện is_featured=true (sắp diễn ra gần nhất trong số đó); không có
     * bài nào được ghim thì fallback sự kiện sắp diễn ra gần nhất, cùng tiêu chí
     * Modules\Post\Features\PublicReading\Http\PublicCategoryController::leadArticleForCategory().
     */
    private function leadEvent(?int $categoryId = null): ?Event
    {
        $featured = Event::published()
            ->upcoming()
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->where('is_featured', true)
            ->with(['category', 'province', 'ward'])
            ->orderBy('start_date')
            ->orderBy('id')
            ->first();

        if ($featured) {
            return $featured;
        }

        return Event::published()
            ->upcoming()
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->with(['category', 'province', 'ward'])
            ->orderBy('start_date')
            ->orderBy('id')
            ->first();
    }

    /** Slug không phải route key (getRouteKeyName()='uuid', cùng lý do PostArticleTranslation
     *  — xem §3.4) — resolve thủ công, không implicit binding. */
    public function show(string $slug): View
    {
        $event = Event::where('status', EventStatus::Published)
            ->where('slug', $slug)
            ->with('category')
            ->first();

        abort_unless($event, 404);

        return view('event::public.show', compact('event'));
    }
}
