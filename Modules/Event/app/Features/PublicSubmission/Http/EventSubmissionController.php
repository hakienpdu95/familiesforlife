<?php

namespace Modules\Event\Features\PublicSubmission\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Event\Enums\EventLocationType;
use Modules\Event\Enums\EventPriceType;
use Modules\Event\Features\EventCategoryManagement\Queries\ListEventCategoriesForAdminHandler;
use Modules\Event\Features\EventCategoryManagement\Queries\ListEventCategoriesForAdminQuery;
use Modules\Event\Features\EventModeration\Actions\StoreEventPosterAction;
use Modules\Event\Features\EventModeration\Data\EventData;
use Modules\Event\Features\PublicSubmission\Actions\SubmitEventAction;
use Modules\Event\Features\PublicSubmission\Data\EventSubmitterData;
use Modules\Event\Models\EventCategory;

/**
 * spec/Event_Management_Technical_Specification.md §8 — form public không {locale}, cùng
 * quyết định đã áp dụng cho Post (bỏ hẳn locale khỏi URL công khai).
 */
class EventSubmissionController extends Controller
{
    public function create(ListEventCategoriesForAdminHandler $handler): View
    {
        // navTree() (root+children) đủ cho <select> — dùng lại đúng query danh mục sẵn có
        // (EventCategory::navTree()) thay vì ListEventCategoriesForAdminHandler (bảng phẳng
        // kèm parent/đếm sự kiện, dư thông tin không cần cho form public).
        //
        // Đặt tên $eventCategories (KHÔNG phải $categories) — layouts.frontend include sẵn
        // frontend-nav/frontend-footer/promo-bar, các partial đó đọc biến $categories mong đợi
        // là CÂY DANH MỤC POST (điều hướng toàn site) — trùng tên sẽ khiến nav site hiển thị
        // nhầm danh mục Event thay vì danh mục Post thật (bug đã gặp khi test).
        $eventCategories = EventCategory::navTree();

        return view('event::public.submit-form', compact('eventCategories'));
    }

    public function store(Request $request, StoreEventPosterAction $storePoster, SubmitEventAction $action): RedirectResponse
    {
        $validated = $this->validated($request);
        unset($validated['poster'], $validated['first_name'], $validated['last_name'], $validated['email'], $validated['newsletter_consent']);
        $poster = $storePoster->handle($request->file('poster'));

        $data      = EventData::from([...$validated, ...$poster]);
        $submitter = EventSubmitterData::from([
            'first_name'         => $request->string('first_name')->trim()->value(),
            'last_name'          => $request->string('last_name')->trim()->value(),
            'email'              => $request->string('email')->trim()->value(),
            'newsletter_consent' => $request->boolean('newsletter_consent'),
        ]);

        $action->handle($data, $submitter, $request->ip(), $request->userAgent());

        return redirect()->route('event.public.submit.success')
            ->with('success', 'Cảm ơn bạn đã gửi sự kiện! Đội ngũ biên tập sẽ xem xét và phản hồi qua email trong thời gian sớm nhất.');
    }

    public function success(): View
    {
        return view('event::public.submit-success');
    }

    private function validated(Request $request): array
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
            'poster'         => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
            'poster_alt'     => ['nullable', 'string', 'max:150'],
            'is_featured'    => ['boolean'],
            'first_name'     => ['required', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email', 'max:255'],
            'newsletter_consent' => ['accepted'],
        ]);
    }
}
