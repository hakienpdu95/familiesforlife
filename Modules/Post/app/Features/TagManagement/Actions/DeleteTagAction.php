<?php

namespace Modules\Post\Features\TagManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostTag;

class DeleteTagAction
{
    use AsAction;

    /**
     * @throws \RuntimeException Nếu tag còn bài viết gán trực tiếp — theo tiền lệ
     *                           `DeleteCategoryAction` (spec/PostTag_Management_Technical_Specification.md §5.2), không
     *                           xoá thẳng — dẫn người dùng tới tính năng gộp thay vì mất liên
     *                           kết đột ngột.
     */
    public function handle(PostTag $tag): void
    {
        $articleCount = $tag->articles()->count();

        if ($articleCount > 0) {
            throw new \RuntimeException(
                __('Không thể xoá tag đang được sử dụng ở :article_count bài viết. Hãy gộp tag này vào tag khác hoặc gỡ tag khỏi từng bài trước khi xoá.', ['article_count' => $articleCount])
            );
        }

        $tag->delete();
    }
}
