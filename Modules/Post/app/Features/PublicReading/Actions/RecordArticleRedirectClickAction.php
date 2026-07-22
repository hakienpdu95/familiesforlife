<?php

namespace Modules\Post\Features\PublicReading\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleRedirectClick;

/** Ghi 1 dòng / lượt click bài viết format=redirect — phục vụ thống kê xu hướng theo ngày. */
class RecordArticleRedirectClickAction
{
    use AsAction;

    public function handle(PostArticle $article): void
    {
        PostArticleRedirectClick::create([
            'article_id'  => $article->id,
            'referrer'    => request()->header('referer') ? mb_substr(request()->header('referer'), 0, 500) : null,
            'created_at'  => now(),
        ]);
    }
}
