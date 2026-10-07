<?php

namespace Modules\Ocop\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryInterface;

/** Số lượng sản phẩm theo từng lựa chọn lọc (phường/xã, hạng sao) ở trang /ocop — cùng tham số ListPublishedOcopProductsQuery. */
class ListOcopPublicFacetsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $provinceCode = null,
        /** @var int[] Danh mục đã chọn + danh mục con */
        public readonly array $categoryIds = [],
        public readonly ?string $search = null,
        public readonly array $wardCodes = [],
        public readonly ?int $stars = null,
    ) {}
}
