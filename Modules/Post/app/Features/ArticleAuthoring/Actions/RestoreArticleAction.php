<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticle;

/**
 * Ngược lại DeleteArticleAction: bỏ xoá mềm + đưa bản dịch đã xuất bản trở lại Meilisearch.
 * restore() gọi save() nên HasQrIdentity tự xếp job làm mới snapshot — mã QR đã in quay về
 * hiển thị trang thật (nếu bài còn bản dịch đang xuất bản) thay cho trang lưu trữ.
 */
class RestoreArticleAction
{
    use AsAction;

    public function handle(PostArticle $article): PostArticle
    {
        DB::transaction(fn () => $article->restore());

        $article->translations()->searchable();

        return $article;
    }
}
