<?php

namespace App\View\Components\Province;

use App\Models\Province;
use Illuminate\View\Component;
use Modules\Post\Models\PostArticleTranslation;

/** Cùng SectionHeritage — chỉ khác category slug (am-thuc-vung-mien). Không cache — xem SectionHeritage. */
class SectionCuisine extends Component
{
    public \Illuminate\Support\Collection $translations;

    public function __construct(public Province $province)
    {
        $this->translations = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->whereHas('article', fn ($q) => $q->where('province_code', $province->province_code)
                ->whereHas('categories', fn ($c) => $c->where('slug', 'am-thuc-vung-mien')))
            ->with('article.categories', 'article.createdBy')
            ->orderByDesc('published_at')
            ->limit(config('provinceshowcase.section_limit'))
            ->get();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('components.province.section-cuisine');
    }
}
