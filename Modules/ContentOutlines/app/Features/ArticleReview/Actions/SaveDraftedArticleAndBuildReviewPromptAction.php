<?php

namespace Modules\ContentOutlines\Features\ArticleReview\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentOutlines\Features\Concerns\ResolvesCategoryContext;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;
use Modules\ContentOutlines\Models\ContentOutline;

/**
 * spec/ContentOutlines_Technical_Specification.md §4.20 (v1.16) — lưu bài viết đã dán tay vào
 * `ContentOutline::drafted_article`, rồi sinh + lưu `review_prompt` qua
 * `BuildArticleReviewPromptAction`. CÙNG hành vi "ghi đè, không versioning" với
 * `SaveApprovedOutlineAndBuildArticlePromptAction` (§4.17) — gọi lại Action này GHI ĐÈ
 * `review_prompt` cũ, KHÔNG giữ lịch sử.
 *
 * KHÔNG đụng `generated_prompt`/`approved_outline`/`article_draft_prompt`/`linked_post_article_id`
 * — đây là bước RIÊNG, độc lập với "Bước 1"/"Bước 2".
 */
class SaveDraftedArticleAndBuildReviewPromptAction
{
    use AsAction;
    use ResolvesCategoryContext;

    public function __construct(private readonly BuildArticleReviewPromptAction $buildReviewPrompt) {}

    public function handle(ContentOutline $outline, string $draftedArticle, int $updatedBy): ContentOutline
    {
        $foundation = $this->resolveFoundation($outline->post_category_id);
        $input = ContentOutlineInputData::from($outline);

        $prompt = $this->buildReviewPrompt->handle($input, $foundation, $draftedArticle);

        $outline->update([
            'drafted_article' => $draftedArticle,
            'review_prompt' => $prompt,
            'updated_by' => $updatedBy,
        ]);

        return $outline;
    }
}
