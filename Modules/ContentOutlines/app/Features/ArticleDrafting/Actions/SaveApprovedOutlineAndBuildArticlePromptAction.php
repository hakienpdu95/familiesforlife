<?php

namespace Modules\ContentOutlines\Features\ArticleDrafting\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentOutlines\Features\Concerns\ResolvesCategoryContext;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;
use Modules\ContentOutlines\Models\ContentOutline;

/**
 * spec/ContentOutlines_Technical_Specification.md §4.17/§5 (v1.14) — lưu outline đã duyệt (dán
 * tay từ AI ngoài) vào `ContentOutline::approved_outline`, rồi sinh + lưu
 * `article_draft_prompt` qua `BuildArticleDraftPromptAction`. CÙNG hành vi "ghi đè, không
 * versioning" với `RegenerateContentOutlinePromptAction` (§4.2) — gọi lại Action này (VD sau khi
 * biên tập viên sửa outline đã dán) GHI ĐÈ `article_draft_prompt` cũ, KHÔNG giữ lịch sử.
 *
 * KHÔNG đụng `generated_prompt`/`linked_post_article_id` — đây là bước RIÊNG, độc lập với vòng
 * "sinh/sinh lại prompt outline" (§4.2), cùng nguyên tắc `linked_post_article_id` giữ nguyên khi
 * regenerate outline.
 */
class SaveApprovedOutlineAndBuildArticlePromptAction
{
    use AsAction;
    use ResolvesCategoryContext;

    public function __construct(private readonly BuildArticleDraftPromptAction $buildArticlePrompt) {}

    public function handle(ContentOutline $outline, string $approvedOutline, int $updatedBy): ContentOutline
    {
        $foundation = $this->resolveFoundation($outline->post_category_id);

        // §4.17 — hydrate ContentOutlineInputData TRỰC TIẾP từ model đã lưu (Spatie Laravel Data
        // tự map theo tên property/cột trùng nhau) — Action Build* không tự query, cùng nguyên
        // tắc BuildContentOutlinePromptAction (§3.1).
        $input = ContentOutlineInputData::from($outline);

        $prompt = $this->buildArticlePrompt->handle($input, $foundation, $approvedOutline);

        $outline->update([
            'approved_outline' => $approvedOutline,
            'article_draft_prompt' => $prompt,
            'updated_by' => $updatedBy,
        ]);

        return $outline;
    }
}
