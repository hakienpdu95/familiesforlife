<?php

namespace Modules\Event\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPublishedEventsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 12,
        public readonly ?int $categoryId = null,
        public readonly ?string $search = null,
        public readonly bool $upcomingOnly = true,
        /** @var int[] Loại bỏ (vd sự kiện đã dùng làm "tin to" size=lg, tránh trùng lặp trong lưới). */
        public readonly array $excludeEventIds = [],
    ) {}
}
