<?php

namespace Modules\Aicem\Listeners;

use Modules\Aicem\Features\ExampleLearning\Actions\CreateExampleCandidateFromArticleAction;
use Modules\Aicem\Models\AicemExampleCandidate;
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;

/**
 * Phase 5 (tuỳ chọn, mục 11/15) — "hiệu suất cao" không thể đánh giá bằng view_count ngay lúc
 * publish (view_count luôn = 0 tại thời điểm này, và repo hiện chưa có cơ chế nào tăng cột đó).
 * Dùng is_featured (tín hiệu biên tập viên tự đánh dấu "bài tốt") làm tiêu chí thay thế — quyết
 * định phạm vi đã chọn khi triển khai Phase 5. Chỉ tạo candidate CHỜ DUYỆT, không ghi thẳng vào
 * knowledge base.
 */
class SuggestExampleGoodFromPublishedArticle
{
    public function handle(ArticlePublished $event): void
    {
        $translation = $event->translation;

        // is_featured nằm ở PostArticle (dùng chung mọi ngôn ngữ), không phải trên translation.
        if (! $translation->article->is_featured) {
            return;
        }

        // subject_id = translation_id (Publishing Engine Phase 13 — subject của post_article
        // giờ là PostArticleTranslation, xem config/aicem_subjects.php).
        $alreadyCandidate = AicemExampleCandidate::query()
            ->where('subject_type', 'post_article')
            ->where('subject_id', $translation->id)
            ->exists();

        if ($alreadyCandidate) {
            return;
        }

        app(CreateExampleCandidateFromArticleAction::class)->handle($translation);
    }
}
