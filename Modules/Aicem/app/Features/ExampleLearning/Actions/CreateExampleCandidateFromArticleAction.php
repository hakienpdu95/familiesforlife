<?php

namespace Modules\Aicem\Features\ExampleLearning\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\ExampleCandidateStatus;
use Modules\Aicem\Models\AicemExampleCandidate;
use Modules\Post\Models\PostArticle;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * Dựng nội dung ví dụ mẫu (Markdown) từ 1 bài viết đã published + is_featured=true — chỉ tạo
 * hàng CHỜ DUYỆT (aicem_example_candidates), không ghi thẳng vào knowledge base (mục 11/15).
 */
class CreateExampleCandidateFromArticleAction
{
    use AsAction;

    public function __construct(
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function handle(PostArticle $article): AicemExampleCandidate
    {
        $article->loadMissing(['categories', 'tags']);

        $textContent = collect($this->renderer->toComposerPayload($article))
            ->filter(fn (array $block) => ($block['type'] ?? null) === 'text')
            ->map(fn (array $block) => trim(strip_tags($block['html'] ?? '')))
            ->filter()
            ->implode("\n\n");

        $content = "# {$article->title}\n\n"
            . ($article->excerpt ? "**Mô tả ngắn:** {$article->excerpt}\n\n" : '')
            . $textContent;

        return AicemExampleCandidate::create([
            'subject_type'      => 'post_article',
            'subject_id'        => $article->id,
            'suggested_title'   => "Ví dụ bài viết tốt: {$article->title}",
            'suggested_content' => $content,
            'suggested_scope'   => [
                'category_slugs' => $article->categories->pluck('slug')->all(),
                'format'         => [$article->format->value],
            ],
            'status' => ExampleCandidateStatus::Pending,
        ]);
    }
}
