<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Models\PostArticleTranslation;

/**
 * Content Freshness (2026-08-01) — nguồn tham khảo blog "Content Freshness in the AEO Era"
 * (tenspeed.io): AI answer engine ưu tiên trích nội dung mới rà soát hơn nội dung để lâu, và
 * khuyến nghị "quarterly freshness sprint" tập trung vào các trang traffic cao nhất — khớp
 * đúng tiêu chí đã có sẵn ở GeoChecklist::groups() nhóm "Liên kết & cập nhật" ("rà soát định
 * kỳ mỗi quý", 90 ngày). Handler này CHỈ liệt kê raw signal (view_count + updated_at) đã có
 * sẵn trên PostArticleTranslation, KHÔNG tính điểm/gắn nhãn "outdated" tự động — giữ đúng
 * quyết định trước đó (2026-07-28) để GeoChecklist luôn là công cụ biên tập viên tự đối chiếu
 * bằng mắt, không tự động hoá đánh giá nội dung.
 */
class ListFreshnessReviewTranslationsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        /** @var ListFreshnessReviewTranslationsQuery $query */
        return PostArticleTranslation::query()
            ->where('status', TranslationStatus::Published)
            ->where('updated_at', '<=', now()->subDays($query->staleAfterDays))
            ->with('article')
            ->orderByDesc('view_count')
            ->limit($query->limit)
            ->get();
    }
}
