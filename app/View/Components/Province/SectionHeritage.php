<?php

namespace App\View\Components\Province;

use App\Models\Province;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Modules\Heritage\Models\HeritageSite;

/**
 * spec/Heritage_Technical_Specification.md §7.1 — đổi nguồn dữ liệu từ PostArticleTranslation
 * (category di-san-van-hoa) sang HeritageSite có cấu trúc thật. Query trực tiếp trong class
 * component (giống App\View\Components\AddressPicker), không cần Query/Handler CQRS riêng.
 *
 * Không cache — xem lý do ở bản trước của class này (cache store 'database' của môi trường này
 * ném TypeError khi unserialize lại Eloquent Collection).
 */
class SectionHeritage extends Component
{
    public Collection $sites;

    public function __construct(public Province $province)
    {
        $this->sites = HeritageSite::published()
            ->forProvince($province->province_code)
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->limit(config('provinceshowcase.section_limit'))
            ->get();
    }

    public function render(): View
    {
        return view('components.province.section-heritage');
    }
}
