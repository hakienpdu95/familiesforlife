<?php

namespace Modules\Post\Features\CategoryManagement\Actions;

use Modules\Post\Models\PostCategory;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteCategoryAction
{
    use AsAction;

    /**
     * @throws \RuntimeException Nếu danh mục còn danh mục con hoặc còn bài viết gán trực tiếp
     * (docs/post-module-spec.md §11.1) — khác `Product`, vì xoá ở đây sẽ cascade xoá luôn dòng
     * pivot `post_article_categories`, có thể âm thầm làm bài viết mất hết phân loại.
     */
    public function handle(PostCategory $category): void
    {
        if ($category->children()->exists()) {
            throw new \RuntimeException('Không thể xoá danh mục còn danh mục con — hãy chuyển/xoá danh mục con trước.');
        }

        if ($category->articles()->exists()) {
            throw new \RuntimeException('Không thể xoá danh mục còn bài viết gán trực tiếp — hãy gỡ bài khỏi danh mục này trước.');
        }

        $category->delete();
    }
}
