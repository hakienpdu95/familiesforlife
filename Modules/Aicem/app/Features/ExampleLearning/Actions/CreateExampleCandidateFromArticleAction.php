<?php

namespace Modules\Aicem\Features\ExampleLearning\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\ExampleCandidateStatus;
use Modules\Aicem\Models\AicemExampleCandidate;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * Dựng nội dung ví dụ mẫu (Markdown) từ 1 bản dịch bài viết đã published + is_featured=true
 * (flag ở PostArticle, dùng chung mọi ngôn ngữ) — chỉ tạo hàng CHỜ DUYỆT (aicem_example_candidates),
 * không ghi thẳng vào knowledge base (mục 11/15).
 *
 * Subject = PostArticleTranslation (Publishing Engine Phase 13) — title/excerpt/blocks giờ
 * per-locale, subject_id lưu ở đây là translation_id (khớp config('aicem_subjects.post_article.model')).
 */
class CreateExampleCandidateFromArticleAction
{
    use AsAction;

    public function __construct(
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function handle(PostArticleTranslation $translation): AicemExampleCandidate
    {
        $translation->loadMissing('article.categories');
        $article = $translation->article;

        $textContent = collect($this->renderer->toComposerPayload($translation))
            ->filter(fn (array $block) => ($block['type'] ?? null) === 'text')
            ->map(fn (array $block) => trim(strip_tags($block['html'] ?? '')))
            ->filter()
            ->implode("\n\n");

        $content = "# {$translation->title}\n\n"
            . ($translation->excerpt ? "**Mô tả ngắn:** {$translation->excerpt}\n\n" : '')
            . $textContent;

        return AicemExampleCandidate::create([
            'subject_type'      => 'post_article',
            'subject_id'        => $translation->id,
            'suggested_title'   => "Ví dụ bài viết tốt: {$translation->title}",
            'suggested_content' => $content,
            'suggested_scope'   => [
                'category_slugs' => $article->categories->pluck('slug')->all(),
                'format'         => [$article->format->value],
            ],
            'status' => ExampleCandidateStatus::Pending,
        ]);
    }
}
