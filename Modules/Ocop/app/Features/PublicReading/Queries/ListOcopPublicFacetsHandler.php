<?php

namespace Modules\Ocop\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Builder;
use Modules\Ocop\Models\OcopProduct;

/**
 * Facet kiểu "loại trừ chính nó": số lượng phường/xã tính theo MỌI bộ lọc khác trừ phường/xã,
 * số lượng hạng sao tính theo mọi bộ lọc khác trừ hạng sao — để người dùng thấy chọn/đổi lựa
 * chọn sẽ ra bao nhiêu kết quả (không có bộ lọc nào khác thì chính là số lượng toàn tỉnh).
 * Liệt kê TẤT CẢ phường/xã đang hoạt động của tỉnh, nơi chưa có sản phẩm hiện số 0.
 * Hạng sao cố định 5/4/3 (thang OCOP thực tế), cộng thêm mức đang chọn nếu nằm ngoài.
 */
class ListOcopPublicFacetsHandler implements QueryHandlerInterface
{
    public const STAR_LEVELS = [5, 4, 3];

    /** @return array{wards: \Illuminate\Support\Collection, wards_total: int, stars: array<int, int>} */
    public function handle(QueryInterface $query): array
    {
        /** @var ListOcopPublicFacetsQuery $query */
        $wards = collect();

        if ($query->provinceCode) {
            $wardCounts = $this->base($query)
                ->when($query->stars, fn ($q) => $q->where('star_rating', $query->stars))
                ->whereNotNull('ward_code')
                ->selectRaw('ward_code, COUNT(*) as total')
                ->groupBy('ward_code')
                ->pluck('total', 'ward_code');

            $wards = Ward::where('province_code', $query->provinceCode)
                ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('ward_code', $query->wardCodes))
                ->orderBy('name')
                ->get(['ward_code', 'name'])
                ->map(fn (Ward $w) => (object) [
                    'ward_code' => $w->ward_code,
                    'ward_name' => $w->name,
                    'total'     => (int) ($wardCounts[$w->ward_code] ?? 0),
                ])
                // Nhiều sản phẩm lên đầu (cao → thấp), bằng nhau thì theo tên; nơi 0 sản phẩm xuống
                // cuối theo tên (get() đã orderBy name, sortBy ổn định nên giữ được thứ tự đó).
                ->sortBy([['total', 'desc'], ['ward_name', 'asc']])
                ->values();
        }

        $starCounts = $this->base($query)
            ->when($query->wardCodes, fn ($q) => $q->whereIn('ward_code', $query->wardCodes))
            ->selectRaw('star_rating, COUNT(*) as total')
            ->groupBy('star_rating')
            ->pluck('total', 'star_rating');

        $stars = collect(self::STAR_LEVELS)
            ->when($query->stars, fn ($c) => $c->push($query->stars))
            ->unique()->sortDesc()->values()
            ->mapWithKeys(fn (int $s) => [$s => (int) ($starCounts[$s] ?? 0)])
            ->all();

        // "Tất cả địa phương" — gồm cả sản phẩm chưa gắn phường/xã (không nằm trong tổng các dòng).
        $wardsTotal = $this->base($query)
            ->when($query->stars, fn ($q) => $q->where('star_rating', $query->stars))
            ->count();

        return ['wards' => $wards, 'wards_total' => $wardsTotal, 'stars' => $stars];
    }

    private function base(ListOcopPublicFacetsQuery $query): Builder
    {
        return OcopProduct::published()
            ->when($query->provinceCode, fn ($q) => $q->where('province_code', $query->provinceCode))
            ->when($query->categoryIds, fn ($q) => $q->whereIn('category_id', $query->categoryIds))
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"));
    }
}
