<?php

namespace Modules\Event\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Features\PublicReading\Queries\ListPublishedEventsHandler;
use Modules\Event\Features\PublicReading\Queries\ListPublishedEventsQuery;
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

        $events = $handler->handle(new ListPublishedEventsQuery(
            page: max(1, $request->integer('page', 1)),
            search: $search,
        ));

        $eventCategories = EventCategory::navTree();

        return view('event::public.index', compact('events', 'eventCategories', 'search'));
    }

    public function category(Request $request, EventCategory $category, ListPublishedEventsHandler $handler): View
    {
        $search = $request->string('q')->trim()->value() ?: null;

        $events = $handler->handle(new ListPublishedEventsQuery(
            page: max(1, $request->integer('page', 1)),
            categoryId: $category->id,
            search: $search,
        ));

        $eventCategories = EventCategory::navTree();

        return view('event::public.category', compact('events', 'category', 'eventCategories', 'search'));
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
