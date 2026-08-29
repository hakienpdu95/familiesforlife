<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentOutlines\Features\Concerns\ResolvesCategoryContext;
use Modules\ContentOutlines\Features\OutlineGeneration\Data\ContentOutlineInputData;
use Modules\ContentOutlines\Models\ContentOutline;

/**
 * spec/ContentOutlines_Technical_Specification.md §0/§4.2 — sửa input rồi "Sinh lại" GHI ĐÈ
 * generated_prompt, không versioning (khác việc tạo mới — cùng bản ghi, đọc lại ContentFoundation
 * tại thời điểm sinh lại, không tự đồng bộ theo foundation nếu không bấm nút này).
 *
 * §4.2 — 3 điểm hành vi chốt rõ:
 * - `linked_post_article_id` GIỮ NGUYÊN — CỐ Ý không có trong mảng update() dưới đây, vì sinh lại
 *   prompt là thao tác trên NỘI DUNG nghiên cứu, không liên quan gì tới liên kết bài viết đã gắn.
 * - `updated_by`/`updated_at` LUÔN cập nhật — `updated_by` set thủ công trong update(), `updated_at`
 *   tự động qua Eloquent timestamps ở MỌI lần gọi update() (kể cả khi nội dung field không đổi).
 * - Xác nhận (confirm dialog) trước khi submit thuộc trách nhiệm UI (edit.blade.php +
 *   content-outlines.js `confirm()` trước khi submit form) — Action này KHÔNG tự chặn, vì Action
 *   không biết gì về việc người dùng đã xác nhận hay chưa ở tầng trình duyệt.
 *
 * §4.24 (v1.20/v1.21, rà soát rủi ro nội bộ) — TUYỆT ĐỐI KHÔNG đụng `approved_outline`/
 * `article_draft_prompt` (§4.17, Feature `ArticleDrafting` — "Bước 2") lẫn `drafted_article`/
 * `review_prompt` (§4.20, Feature `ArticleReview` — "Bước 3") — CỐ Ý không đưa 4 field này vào
 * mảng update() dưới đây, CÙNG lý do/CÙNG cơ chế với `linked_post_article_id` ở trên (sinh lại
 * outline là thao tác trên NỘI DUNG NGHIÊN CỨU, không tự động lan sang nội dung của 2 bước sau đã
 * được sinh dựa trên outline CŨ). Hệ quả CHỦ Ý: outline mới có thể không còn khớp với Bước 2/3 đã
 * có — module KHÔNG tự xoá/cảnh báo ngầm trong Action này (biên tập viên có thể có lý do chính
 * đáng giữ lại); cảnh báo thuộc trách nhiệm UI, xem `edit.blade.php`/`show.blade.php` (§4.24).
 */
class RegenerateContentOutlinePromptAction
{
    use AsAction;
    use ResolvesCategoryContext;

    public function __construct(private readonly BuildContentOutlinePromptAction $buildPrompt) {}

    public function handle(ContentOutline $outline, ContentOutlineInputData $input, int $updatedBy): ContentOutline
    {
        $foundation = $this->resolveFoundation($input->post_category_id);
        $existingArticleTitles = $this->resolveExistingArticleTitles($input->post_category_id);
        $prompt = $this->buildPrompt->handle($input, $foundation, $existingArticleTitles);

        $outline->update([
            'label' => $input->label ?: $input->topic,
            'topic' => $input->topic,
            'target_keyword' => $input->target_keyword,
            'secondary_keywords' => $input->secondary_keywords,
            'search_intent' => $input->search_intent,
            'post_category_id' => $input->post_category_id,
            'target_audience' => $input->target_audience,
            'job_to_be_done' => $input->job_to_be_done,
            'reader_emotional_state' => $input->reader_emotional_state,
            'content_goal' => $input->content_goal,
            'cta_url' => $input->cta_url,
            'tone_style' => $input->tone_style,
            'competitor_urls' => $input->competitor_urls,
            'desired_word_count' => $input->desired_word_count,
            'language' => $input->language,
            'outline_depth' => $input->outline_depth,
            'content_role' => $input->content_role,
            'additional_notes' => $input->additional_notes,
            'generated_prompt' => $prompt,
            'updated_by' => $updatedBy,
            // KHÔNG có 'linked_post_article_id' ở đây — xem docblock lớp.
        ]);

        return $outline;
    }
}
