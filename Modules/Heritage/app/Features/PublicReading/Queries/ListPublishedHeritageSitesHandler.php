<?php

namespace Modules\Heritage\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Heritage\Models\HeritageSite;

/**
 * spec/Heritage_Technical_Specification.md §5.2 — index() v1 CHỈ liệt kê HeritageSite::published()
 * phân trang, sắp theo is_featured rồi sort_order, lọc tuỳ chọn theo ?province=. KHÔNG có bộ lọc
 * loại hình/xếp hạng (số lượng di tích demo còn quá ít để bộ lọc có giá trị thật).
 */
class ListPublishedHeritageSitesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublishedHeritageSitesQuery $query */
        return HeritageSite::published()
            ->when($query->provinceCode, fn ($q) => $q->where('province_code', $query->provinceCode))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
