<?php

namespace App\View\Components\Province;

use App\Models\Province;
use Illuminate\View\Component;
use Modules\Post\Models\PostArticleTranslation;

/**
 * spec/Province_Showcase_Technical_Specification.md §3.2.2/§7.2 — bài viết category=di-san-van-hoa
 * của đúng 1 tỉnh (trang đang đứng) — where(), không phải whereIn(). Query trực tiếp trong class
 * component (giống App\View\Components\AddressPicker), không cần Query/Handler CQRS riêng vì đây
 * là hiển thị thuần, không có logic nghiệp vụ phức tạp.
 *
 * §4.3 gợi ý bọc Cache::remember() — KHÔNG áp dụng ở đây: cache store 'database' của môi trường
 * này ném TypeError ("Cannot assign __PHP_Incomplete_Class") khi unserialize lại Eloquent
 * Collection đã cache (xác nhận qua Cache::put()/Cache::get() thuần, không liên quan gì đến
 * query/component này) — đây là gợi ý hiệu năng (§4.3 "nên"), không phải business rule bắt
 * buộc (§5), nên bỏ qua caching thay vì DEBUG sâu vào Cache driver nội bộ ngoài phạm vi spec.
 */
class SectionHeritage extends Component
{
    public \Illuminate\Support\Collection $translations;

    public function __construct(public Province $province)
    {
        $this->translations = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->whereHas('article', fn ($q) => $q->where('province_code', $province->province_code)
                ->whereHas('categories', fn ($c) => $c->where('slug', 'di-san-van-hoa')))
            ->with('article.categories', 'article.createdBy')
            ->orderByDesc('published_at')
            ->limit(config('provinceshowcase.section_limit'))
            ->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('components.province.section-heritage');
    }
}
