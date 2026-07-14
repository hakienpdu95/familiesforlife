<?php

namespace Modules\Event\Features\EventModeration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Event\Enums\EventLocationType;
use Modules\Event\Enums\EventPriceType;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Features\EventCategoryManagement\Queries\ListEventCategoriesForAdminHandler;
use Modules\Event\Features\EventCategoryManagement\Queries\ListEventCategoriesForAdminQuery;
use Modules\Event\Features\EventModeration\Actions\ArchiveEventAction;
use Modules\Event\Features\EventModeration\Actions\ApproveEventAction;
use Modules\Event\Features\EventModeration\Actions\CreateEventAction;
use Modules\Event\Features\EventModeration\Actions\NotifyEventApprovalResultAction;
use Modules\Event\Features\EventModeration\Actions\PublishEventAction;
use Modules\Event\Features\EventModeration\Actions\RejectEventAction;
use Modules\Event\Features\EventModeration\Actions\StoreEventPosterAction;
use Modules\Event\Features\EventModeration\Actions\UpdateEventAction;
use Modules\Event\Features\EventModeration\Data\EventData;
use Modules\Event\Features\EventModeration\Queries\ListEventsForAdminHandler;
use Modules\Event\Features\EventModeration\Queries\ListEventsForAdminQuery;
use Modules\Event\Models\Event;

/**
 * spec/Event_Management_Technical_Specification.md §13 — Phase 1 + Phase 2a (create/update,
 * staff tự nhập, không qua form public — EventPolicy::create()/update(), editor/head/ops) +
 * Phase 2b (approve/reject/publish/archive — EventPolicy dùng role-helper trực tiếp).
 */
class EventAdminController extends Controller
{
    public function index(Request $request, ListEventsForAdminHandler $handler): View
    {
        $this->authorize('viewAny', Event::class);

        $events = $handler->handle(new ListEventsForAdminQuery(
            search: $request->string('q')->value() ?: null,
            status: $request->string('status')->value() ?: null,
            page: max(1, $request->integer('page', 1)),
        ));

        $statuses = EventStatus::cases();

        return view('event::admin.events.index', compact('events', 'statuses'));
    }

    public function create(ListEventCategoriesForAdminHandler $handler): View
    {
        $this->authorize('create', Event::class);

        $categories = $handler->handle(new ListEventCategoriesForAdminQuery());

        return view('event::admin.events.create', compact('categories'));
    }

    public function store(Request $request, StoreEventPosterAction $storePoster, CreateEventAction $action): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $validated = $this->validated($request, posterRequired: true);
        unset($validated['poster']);
        $poster = $storePoster->handle($request->file('poster'));

        $data  = EventData::from([...$validated, ...$poster]);
        $event = $action->handle($data);

        return redirect()->route('backend.event.index')
            ->with('success', "Sự kiện \"{$event->title}\" đã được tạo, đang chờ duyệt.");
    }

    public function edit(Event $event, ListEventCategoriesForAdminHandler $handler): View
    {
        $this->authorize('update', $event);

        $categories = $handler->handle(new ListEventCategoriesForAdminQuery());

        return view('event::admin.events.edit', compact('event', 'categories'));
    }

    public function update(Request $request, Event $event, StoreEventPosterAction $storePoster, UpdateEventAction $action): RedirectResponse
    {
        $this->authorize('update', $event);

        $validated = $this->validated($request, posterRequired: false);
        unset($validated['poster']);
        $poster = $request->hasFile('poster')
            ? $storePoster->handle($request->file('poster'))
            : ['poster_path' => null, 'poster_width' => null, 'poster_height' => null, 'poster_size_bytes' => null];

        $data = EventData::from([...$validated, ...$poster]);
        $action->handle($event, $data);

        return redirect()->route('backend.event.index')
            ->with('success', 'Cập nhật sự kiện thành công.');
    }

    public function approve(Event $event, ApproveEventAction $action, NotifyEventApprovalResultAction $notify): RedirectResponse
    {
        $this->authorize('approve', $event);

        $action->handle($event);
        $notify->handle($event);

        return back()->with('success', "Đã duyệt sự kiện \"{$event->title}\" — chờ platform_content_head xuất bản.");
    }

    public function reject(Request $request, Event $event, RejectEventAction $action, NotifyEventApprovalResultAction $notify): RedirectResponse
    {
        $this->authorize('reject', $event);

        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'max:255'],
        ]);

        $action->handle($event, $validated['rejected_reason']);
        $notify->handle($event);

        return redirect()->route('backend.event.index')
            ->with('success', "Đã từ chối sự kiện \"{$event->title}\".");
    }

    public function publish(Event $event, PublishEventAction $action): RedirectResponse
    {
        $this->authorize('publish', $event);

        // Không báo submitter ở bước này — đã báo khi Approve (spec §11.2), độc giả không có
        // tài khoản để "thấy" thêm 1 thông báo khác khi sự kiện thật sự lên cổng thông tin.
        $action->handle($event);

        return back()->with('success', "Đã xuất bản sự kiện \"{$event->title}\".");
    }

    public function archive(Event $event, ArchiveEventAction $action): RedirectResponse
    {
        $this->authorize('archive', $event);

        $action->handle($event);

        return back()->with('success', "Đã lưu trữ sự kiện \"{$event->title}\".");
    }

    private function validated(Request $request, bool $posterRequired): array
    {
        return $request->validate([
            'category_id'    => ['required', 'integer', 'exists:event_categories,id'],
            'title'          => ['required', 'string', 'max:150'],
            'short_title'    => ['required', 'string', 'max:55'],
            'description'    => ['required', 'string', 'not_regex:/(https?:\/\/|www\.)/i'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after_or_equal:start_date'],
            'start_time'     => ['nullable', 'date_format:H:i'],
            'end_time'       => ['nullable', 'date_format:H:i'],
            'location_type'  => ['required', Rule::enum(EventLocationType::class)],
            'venue_name'     => ['required_if:location_type,physical', 'nullable', 'string', 'max:150'],
            'venue_address'  => ['required_if:location_type,physical', 'nullable', 'string', 'max:255'],
            'province_code'  => ['required_if:location_type,physical', 'nullable', 'string', 'max:2'],
            'ward_code'      => ['nullable', 'string', 'max:5'],
            'online_url'     => ['required_if:location_type,online', 'nullable', 'url', 'max:500'],
            'website_url'    => ['required', 'url', 'max:500'],
            'price_type'     => ['required', Rule::enum(EventPriceType::class)],
            'price_amount'   => ['required_if:price_type,single', 'nullable', 'numeric', 'min:0'],
            'price_min'      => ['required_if:price_type,range', 'nullable', 'numeric', 'min:0'],
            'price_max'      => ['required_if:price_type,range', 'nullable', 'numeric', 'min:0', 'gte:price_min'],
            'poster'         => [$posterRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
            'poster_alt'     => ['nullable', 'string', 'max:150'],
            'is_featured'    => ['boolean'],
        ]);
    }
}
