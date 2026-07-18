<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticle;

class DeleteArticleAction
{
    use AsAction;

    public function handle(PostArticle $article): void
    {
        // spec/PostSearch_Meilisearch_Technical_Specification.md §6.2 — soft-delete
        // PostArticle KHÔNG cascade DB xuống translations (cascadeOnDelete chỉ chạy khi
        // hard-delete), nên phải tự gỡ khỏi Meilisearch, không thì bài "xoá" vẫn ra trong
        // kết quả search công khai.
        $article->translations()->unsearchable();

        $article->delete();
    }
}
