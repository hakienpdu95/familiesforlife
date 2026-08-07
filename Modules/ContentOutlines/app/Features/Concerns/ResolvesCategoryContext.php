<?php

namespace Modules\ContentOutlines\Features\Concerns;

use Modules\ContentFoundation\Actions\ListCategoryExistingArticlesAction;
use Modules\ContentFoundation\Models\CategoryContentFoundation;
use Modules\Post\Models\PostCategory;

/**
 * Dùng chung bởi CreateContentOutlineAction/RegenerateContentOutlinePromptAction (Feature
 * OutlineGeneration) và SaveApprovedOutlineAndBuildArticlePromptAction (Feature ArticleDrafting,
 * §4.17 v1.14) — tra ngữ cảnh chuyên mục NGAY TRƯỚC khi gọi Build*PromptAction tương ứng (các
 * Action đó không tự query, cùng nguyên tắc dễ test). Dời từ
 * `Features/OutlineGeneration/Actions/Concerns/` lên `Features/Concerns/` ở v1.14 khi có ĐIỂM DÙNG
 * THỨ 2 ngoài `OutlineGeneration` — cùng lý do tách `BuildsSharedPromptBlocks` (§4.17). Đổi tên từ
 * ResolvesCategoryFoundation (v1.2) — thêm resolveExistingArticleTitles() cho gợi ý internal link
 * THẬT (§4.6, tham khảo piperocket.digital/checklists/content-marketing-checklist).
 */
trait ResolvesCategoryContext
{
    private function resolveFoundation(?int $postCategoryId): ?CategoryContentFoundation
    {
        if ($postCategoryId === null) {
            return null;
        }

        return CategoryContentFoundation::query()
            ->whereHas('categories', fn ($q) => $q->where('post_categories.id', $postCategoryId))
            ->first();
    }

    /**
     * §4.6 (v1.2) — tiêu đề bài ĐÃ PUBLISH trong category, tái dùng NGUYÊN
     * ListCategoryExistingArticlesAction (đã có sẵn, dùng bởi CoreIdeaExtractor §12.8 cho đúng
     * mục đích tránh trùng ý tưởng) — không viết lại query. Giới hạn số lượng thực tế đưa vào
     * prompt do Build*PromptAction tự cắt theo outline_depth, ở đây trả NGUYÊN danh sách đã cắt
     * sẵn bởi Action đó (tối đa `content_foundation.existing_articles.max_titles`).
     */
    private function resolveExistingArticleTitles(?int $postCategoryId): array
    {
        if ($postCategoryId === null) {
            return [];
        }

        $category = PostCategory::find($postCategoryId);
        if (! $category) {
            return [];
        }

        return app(ListCategoryExistingArticlesAction::class)->handle($category);
    }
}
