<?php

namespace App\View\Components\Province;

use App\Models\Province;
use Illuminate\View\Component;
use Modules\Ocop\Models\OcopProduct;

/**
 * spec/Province_Showcase_Technical_Specification.md §7.2 — OcopProduct::published(), top N +
 * link "Xem tất cả OCOP {tỉnh}". §3.4.1 gợi ý ưu tiên sản phẩm đã được nhắc trong bài viết của
 * tỉnh — chỉ là gợi ý mở rộng Phase 2, KHÔNG bắt buộc ở v1 (giữ đơn giản: published + theo
 * tỉnh + sort_order). Không cache — xem SectionHeritage.
 */
class SectionOcop extends Component
{
    public \Illuminate\Support\Collection $products;

    public function __construct(public Province $province)
    {
        $this->products = OcopProduct::published()
            ->forProvince($province->province_code)
            ->with('category')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(config('provinceshowcase.section_limit'))
            ->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('components.province.section-ocop');
    }
}
