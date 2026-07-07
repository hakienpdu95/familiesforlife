<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\ArticleStatus;
use Modules\Post\Models\PostArticle;

class ScheduleArticleAction
{
    use AsAction;

    /**
     * Đặt `published_at` trong tương lai + `status = scheduled`. Chưa có command
     * `post:publish-due` chạy Laravel Scheduler tự động chuyển `published` khi đến giờ —
     * việc này để ở Phase 9 (docs/post-module-spec.md §17), phải publish tay khi đến hạn.
     */
    public function handle(PostArticle $article, Carbon $publishAt): PostArticle
    {
        $article->update([
            'status'       => ArticleStatus::Scheduled,
            'published_at' => $publishAt,
        ]);

        return $article;
    }
}
