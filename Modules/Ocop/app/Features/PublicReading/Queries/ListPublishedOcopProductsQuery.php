<?php

namespace Modules\Ocop\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPublishedOcopProductsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $provinceCode = null,
        public readonly ?int $categoryId = null,
        /** @var int[] Danh mục đã chọn + toàn bộ danh mục con — ưu tiên hơn $categoryId khi có */
        public readonly array $categoryIds = [],
        public readonly ?string $search = null,
        /** @var string[] Lọc nhiều phường/xã (chỉ có nghĩa khi đã chọn tỉnh) */
        public readonly array $wardCodes = [],
        public readonly ?int $stars = null,
        public readonly int $page = 1,
        public readonly int $perPage = 12,
    ) {}
}
