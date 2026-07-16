<?php

namespace App\View\Components\Province;

use App\Models\Province;
use Illuminate\View\Component;
use Modules\Event\Models\Event;

/**
 * spec/Province_Showcase_Technical_Specification.md §7.2 — Event::published()->upcoming(),
 * province_code=... Không cache — xem SectionHeritage.
 */
class SectionEvents extends Component
{
    public \Illuminate\Support\Collection $events;

    public function __construct(public Province $province)
    {
        $this->events = Event::published()
            ->upcoming()
            ->where('province_code', $province->province_code)
            ->with(['category', 'province', 'ward'])
            ->orderBy('start_date')
            ->limit(config('provinceshowcase.section_limit'))
            ->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('components.province.section-events');
    }
}
