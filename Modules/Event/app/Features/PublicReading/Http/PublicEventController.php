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
use Modules\Post\Models\PostCategory;

/**
 * spec/Event_Management_Technical_Specification.md §8/§13 Phase 3 — không {locale}, cùng quyết
 * định đã áp dụng cho Post. `$categories` truyền cho layouts.frontend là CÂY DANH MỤC POST
 * (điều hướng toàn site, xem frontend-nav.blade.php) — KHÔNG phải EventCategory, tránh đúng bug
 * trùng tên biến đã gặp ở PublicSubmission (EventSubmissionController dùng $eventCategories).
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
        $categories      = PostCategory::navTree();

        return view('event::public.index', compact('events', 'eventCategories', 'categories', 'search'));
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
        $categories      = PostCategory::navTree();

        return view('event::public.category', compact('events', 'category', 'eventCategories', 'categories', 'search'));
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

        $categories = PostCategory::navTree();

        return view('event::public.show', compact('event', 'categories'));
    }
}
