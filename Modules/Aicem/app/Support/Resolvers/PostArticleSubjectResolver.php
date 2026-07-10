<?php

namespace Modules\Aicem\Support\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Modules\Aicem\Contracts\AicemSubjectResolver;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Features\ArticleAuthoring\Actions\UpdateArticleAction;
use Modules\Post\Features\ArticleAuthoring\Data\ArticleData;
use Modules\Post\Models\PostArticle;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * Adapter cho subject_type=post_article — xem spec/AICEM_Technical_Specification.md mục 6.2.
 *
 * LƯU Ý quan trọng (khác giả định ban đầu của spec mục 6.2/11.1): `UpdateArticleAction` KHÔNG
 * nhận partial update — nó ghi đè toàn bộ field khai trong `ArticleData` (kể cả `blocks`, nếu để
 * mặc định `[]` thì `SyncContentBlocksAction` sẽ XOÁ SẠCH mọi block hiện có) và
 * `SyncsArticleRelations` sync lại toàn bộ categories/tags theo đúng những gì `ArticleData` khai.
 * Do đó resolver phải dựng lại ĐỦ `ArticleData` từ bản ghi TƯƠI (đọc trong transaction đã
 * `lockForUpdate` của AcceptSuggestionAction — mục 9.1), chỉ overlay đúng field/block được accept
 * — giống hệt cách xử lý Product ở mục 11.1, dù Post không có vấn đề validate toàn khối vì
 * `name`-tương-đương (`title`) luôn có sẵn giá trị hợp lệ khi đọc từ bản ghi tươi.
 */
class PostArticleSubjectResolver implements AicemSubjectResolver
{
    public function __construct(
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function fields(Model $subject): array
    {
        /** @var PostArticle $subject */
        return [
            'title'            => $subject->title,
            'excerpt'          => $subject->excerpt,
            'seo_title'        => $subject->seo_title,
            'seo_description'  => $subject->seo_description,
        ];
    }

    public function blocks(Model $subject): array
    {
        /** @var PostArticle $subject */
        return $subject->contentBlocks()
            ->where('type', ContentBlockType::Text)
            ->get()
            ->map(fn ($block) => [
                'block_id' => $block->id,
                'type'     => $block->type->value,
                'body'     => (string) $block->text_html,
            ])
            ->all();
    }

    public function applyFieldSuggestion(Model $subject, string $field, string $suggestedText, int $userId): void
    {
        /** @var PostArticle $article */
        $article = $subject;

        $data = $this->buildArticleData($article, [$field => $suggestedText]);

        app(UpdateArticleAction::class)->handle($article, $data);
    }

    public function applyBlockSuggestion(Model $subject, int $blockId, string $suggestedText, int $userId): void
    {
        /** @var PostArticle $article */
        $article = $subject;

        $currentBlocks = $article->contentBlocks()->get();
        $targetIndex   = $currentBlocks->search(fn ($block) => $block->id === $blockId);

        if ($targetIndex === false) {
            throw new \InvalidArgumentException("Block #{$blockId} không tồn tại trên bài viết này.");
        }

        $blocksPayload = $this->renderer->toComposerPayload($article);

        if (($blocksPayload[$targetIndex]['type'] ?? null) !== 'text') {
            throw new \InvalidArgumentException("Block #{$blockId} không phải block text — không thể áp dụng gợi ý AI.");
        }

        $blocksPayload[$targetIndex]['html'] = $suggestedText;

        $data = $this->buildArticleData($article, [], $blocksPayload);

        app(UpdateArticleAction::class)->handle($article, $data);
    }

    public function taxonomy(Model $subject): array
    {
        /** @var PostArticle $subject */
        return [
            'category_slugs' => $subject->categories->pluck('slug')->all(),
            'format'         => [$subject->format->value],
            'tag_slugs'      => $subject->tags->pluck('slug')->all(),
        ];
    }

    /**
     * Dựng đủ ArticleData từ trạng thái hiện tại của $article, overlay đúng field được accept
     * (hoặc dùng $blocksOverride nếu đang áp block suggestion) — không đổi gì khác.
     *
     * @param array<string, string> $fieldOverrides
     * @param array<int, array>|null $blocksOverride
     */
    private function buildArticleData(PostArticle $article, array $fieldOverrides, ?array $blocksOverride = null): ArticleData
    {
        $article->loadMissing(['categories', 'tags']);

        return new ArticleData(
            title:                  $fieldOverrides['title'] ?? $article->title,
            format:                 $article->format,
            excerpt:                $fieldOverrides['excerpt'] ?? $article->excerpt,
            blocks:                 $blocksOverride ?? $this->renderer->toComposerPayload($article),
            cover_image_url:        $article->cover_image_url,
            seo_title:              $fieldOverrides['seo_title'] ?? $article->seo_title,
            seo_description:        $fieldOverrides['seo_description'] ?? $article->seo_description,
            is_featured:            $article->is_featured,
            category_ids:           $article->categories->pluck('id')->all(),
            is_primary_category_id: $article->primaryCategory()->first()?->id,
            tags:                   $article->tags->pluck('name')->implode(','),
        );
    }
}
