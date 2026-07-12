<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticle;

/**
 * Action riêng cho nút "Gỡ tài trợ" (spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §7.2) — khác
 * UpdateArticleAction ở chỗ đây là 1 thao tác độc lập (nút bấm riêng, không đi qua form save
 * đầy đủ), dùng khi cần gỡ tài trợ ngay mà không sửa các field khác của bài viết.
 */
class RemoveSponsorshipAction
{
    use AsAction;

    public function handle(PostArticle $article): PostArticle
    {
        $article->update([
            'is_sponsored'         => false,
            'sponsor_name'         => null,
            'sponsor_logo_url'     => null,
            'sponsor_label'        => null,
            'campaign_code'        => null,
            'sponsored_start_date' => null,
            'sponsored_end_date'   => null,
            // sponsored_published_at CỐ TÌNH giữ nguyên — lịch sử "đã từng là bài tài trợ",
            // không xoá dấu vết dù đã gỡ (khác các field khác).
        ]);

        // Activity log tự động qua TenantAwareModel (logOnlyDirty) — không cần ghi log thủ công.

        return $article;
    }
}
