<?php

namespace Modules\ContentCalendar\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\ContentCalendar\Models\ContentCalendarEntry;
use Modules\ContentCalendar\Policies\ContentCalendarEntryPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ContentCalendarServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'ContentCalendar';
    protected string $nameLower = 'contentcalendar';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // nwidart đăng ký config module dưới key lồng `contentcalendar.content_calendar` (cùng
        // pattern CoreIdeaExtractorServiceProvider — xem docblock ở đó) — expose lại ở key
        // top-level `content_calendar` để code đọc gọn (config('content_calendar.dedup...')).
        $this->mergeConfigFrom(
            __DIR__.'/../../config/content_calendar.php',
            'content_calendar'
        );
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(ContentCalendarEntry::class, ContentCalendarEntryPolicy::class);
    }
}
