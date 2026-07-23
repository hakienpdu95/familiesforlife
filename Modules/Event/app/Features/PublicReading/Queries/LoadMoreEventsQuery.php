<?php

namespace Modules\Event\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

/**
 * "Xem thêm sự kiện" — cùng nguyên tắc keyset/cursor của
 * Modules\Post\Features\PublicReading\Queries\LoadMoreArticlesQuery, nhưng chiều so sánh
 * NGƯỢC LẠI: Event sort ASC theo start_date (sắp diễn ra gần nhất trước), nên cursor tiếp nối
 * là "start_date SAU thời điểm đã tải" (>), không phải "TRƯỚC" (<) như Post (sort DESC theo
 * published_at, bài mới nhất trước).
 */
class LoadMoreEventsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $afterStartDate = null,
        public readonly ?int $afterId = null,
        /** @var int[] */
        public readonly array $excludeEventIds = [],
        public readonly int $limit = 8,
        public readonly ?int $categoryId = null,
    ) {}
}
