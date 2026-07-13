<?php

namespace Modules\Aicem\Support\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Modules\Aicem\Contracts\AicemSubjectResolver;
use Modules\Aicem\Support\PlatformEditorialOrganization;
use Modules\Post\Enums\ContentBlockType;
use Modules\Post\Features\ArticleAuthoring\Actions\UpdateTranslationAction;
use Modules\Post\Features\ArticleAuthoring\Data\TranslationData;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Support\ArticleContentRenderer;

/**
 * Adapter cho subject_type=post_article — xem spec/AICEM_Technical_Specification.md mục 6.2.
 *
 * Từ Publishing Engine Phase 13, title/excerpt/seo_title/seo_description/blocks chuyển sang PostArticleTranslation
 * (per-locale) — subject của AICEM cho post_article giờ là PostArticleTranslation, KHÔNG phải
 * PostArticle (chỉ còn format/categories/tags dùng chung mọi ngôn ngữ, đọc qua $subject->article).
 *
 * LƯU Ý quan trọng (khác giả định ban đầu của spec mục 6.2/11.1): `UpdateTranslationAction` KHÔNG
 * nhận partial update — nó ghi đè toàn bộ field khai trong `TranslationData` (kể cả `blocks`, nếu
 * để mặc định `[]` thì `SyncContentBlocksAction` sẽ XOÁ SẠCH mọi block hiện có). Do đó resolver
 * phải dựng lại ĐỦ `TranslationData` từ bản ghi TƯƠI (đọc trong transaction đã `lockForUpdate` của
 * AcceptSuggestionAction — mục 9.1), chỉ overlay đúng field/block được accept — giống hệt cách xử
 * lý Product ở mục 11.1.
 */
class PostArticleSubjectResolver implements AicemSubjectResolver
{
    public function __construct(
        private readonly ArticleContentRenderer $renderer,
    ) {}

    public function fields(Model $subject): array
    {
        /** @var PostArticleTranslation $subject */
        return [
            'title'            => $subject->title,
            'excerpt'          => $subject->excerpt,
            'seo_title'        => $subject->seo_title,
            'seo_description'  => $subject->seo_description,
        ];
    }

    public function blocks(Model $subject): array
    {
        /** @var PostArticleTranslation $subject */
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
        /** @var PostArticleTranslation $translation */
        $translation = $subject;

        $data = $this->buildTranslationData($translation, [$field => $suggestedText]);

        app(UpdateTranslationAction::class)->handle($translation, $data);
    }

    public function applyBlockSuggestion(Model $subject, int $blockId, string $suggestedText, int $userId): void
    {
        /** @var PostArticleTranslation $translation */
        $translation = $subject;

        $currentBlocks = $translation->contentBlocks()->get();
        $targetIndex   = $currentBlocks->search(fn ($block) => $block->id === $blockId);

        if ($targetIndex === false) {
            throw new \InvalidArgumentException("Block #{$blockId} không tồn tại trên bản dịch này.");
        }

        $blocksPayload = $this->renderer->toComposerPayload($translation);

        if (($blocksPayload[$targetIndex]['type'] ?? null) !== 'text') {
            throw new \InvalidArgumentException("Block #{$blockId} không phải block text — không thể áp dụng gợi ý AI.");
        }

        $blocksPayload[$targetIndex]['html'] = $suggestedText;

        $data = $this->buildTranslationData($translation, [], $blocksPayload);

        app(UpdateTranslationAction::class)->handle($translation, $data);
    }

    public function taxonomy(Model $subject): array
    {
        /** @var PostArticleTranslation $subject */
        $subject->loadMissing('article.categories', 'article.tags');

        return [
            'category_slugs' => $subject->article->categories->pluck('slug')->all(),
            'format'         => [$subject->article->format->value],
            'tag_slugs'      => $subject->article->tags->pluck('slug')->all(),
        ];
    }

    /**
     * Post không còn `organization_id` (spec/Platform_RBAC_Phase2_Specification.md §3.3,
     * v3.0) — bài viết luôn do nhân sự nền tảng viết/duyệt, không thuộc doanh nghiệp nào.
     * Trả về tổ chức biên tập nền tảng cố định (seed riêng, xem
     * PlatformEditorialOrganizationSeeder) để Aicem vẫn có đúng 1 Organization thật cho
     * workflow/knowledge-base/ngân sách AI — bất kể bài có tài trợ hay không (sponsor không
     * liên quan gì tới cấu hình AI dùng để viết bài).
     */
    public function organizationId(Model $subject): int
    {
        return PlatformEditorialOrganization::id();
    }

    /**
     * Dựng đủ TranslationData từ trạng thái hiện tại của $translation, overlay đúng field được
     * accept (hoặc dùng $blocksOverride nếu đang áp block suggestion) — không đổi gì khác.
     *
     * @param array<string, string> $fieldOverrides
     * @param array<int, array>|null $blocksOverride
     */
    private function buildTranslationData(PostArticleTranslation $translation, array $fieldOverrides, ?array $blocksOverride = null): TranslationData
    {
        return new TranslationData(
            title:            $fieldOverrides['title'] ?? $translation->title,
            slug:             $translation->slug,
            excerpt:          $fieldOverrides['excerpt'] ?? $translation->excerpt,
            seo_title:        $fieldOverrides['seo_title'] ?? $translation->seo_title,
            seo_description:  $fieldOverrides['seo_description'] ?? $translation->seo_description,
            blocks:           $blocksOverride ?? $this->renderer->toComposerPayload($translation),
        );
    }
}
