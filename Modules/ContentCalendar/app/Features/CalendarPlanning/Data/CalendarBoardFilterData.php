<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Data;

use Spatie\LaravelData\Data;

/**
 * spec/ContentCalendar_Technical_Specification.md §7.1 — filter cho ListCalendarEntriesAction.
 * `includeDone` mặc định false (board nhìn về TƯƠNG LAI, không phải kho lưu trữ — §7.1).
 */
class CalendarBoardFilterData extends Data
{
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?int $assignedTo = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly bool $includeDone = false,
        public readonly int $perPage = 50,
    ) {}
}
