<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentOutlines\Models\ContentOutline;

/**
 * spec/ContentOutlines_Technical_Specification.md §1/§2.2 — liên kết THAM CHIẾU thủ công, KHÔNG
 * sinh nội dung/copy dữ liệu sang PostArticle. $postArticleId = null để GỠ liên kết.
 */
class LinkContentOutlineToArticleAction
{
    use AsAction;

    public function handle(ContentOutline $outline, ?int $postArticleId, int $updatedBy): ContentOutline
    {
        $outline->update([
            'linked_post_article_id' => $postArticleId,
            'updated_by' => $updatedBy,
        ]);

        return $outline;
    }
}
