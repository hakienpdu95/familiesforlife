<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\SyncsArticleRelations;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Models\PostArticle;

/** Chỉ cập nhật field cấp PostArticle (dùng chung mọi ngôn ngữ) — title/excerpt/seo_title/seo_description/blocks xem UpdateTranslationAction. */
class UpdateArticleAction
{
    use AsAction;
    use SyncsArticleRelations;

    public function handle(PostArticle $article, ArticleData $data): PostArticle
    {
        return DB::transaction(function () use ($article, $data) {
            $article->update([
                'format'                 => $data->format,
                'cover_image_url'        => $data->cover_image_url,
                'is_featured'            => $data->is_featured,
                'province_code'          => $data->province_code,
                'ward_code'              => $data->ward_code,
                'updated_by'             => auth()->id(),
                // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §7.1 — khi is_sponsored=false, mọi
                // field sponsor phải được clear ngay tại đây (không cần Action riêng cho trường
                // hợp save thường; RemoveSponsorshipAction chỉ dùng cho nút bấm độc lập).
                'is_sponsored'           => $data->is_sponsored,
                'sponsor_name'           => $data->is_sponsored ? $data->sponsor_name : null,
                'sponsor_logo_url'       => $data->is_sponsored ? $data->sponsor_logo_url : null,
                'sponsor_label'          => $data->is_sponsored ? $data->sponsor_label : null,
                'campaign_code'          => $data->is_sponsored ? $data->campaign_code : null,
                'sponsored_start_date'   => $data->is_sponsored ? $data->sponsored_start_date : null,
                'sponsored_end_date'     => $data->is_sponsored ? $data->sponsored_end_date : null,
            ]);

            $this->syncCategories($article, $data);
            $this->syncTags($article, $data);
            $this->syncOcopProducts($article, $data);

            return $article;
        });
    }
}
