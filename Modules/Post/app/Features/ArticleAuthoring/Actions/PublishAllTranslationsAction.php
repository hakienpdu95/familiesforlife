<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Models\PostArticle;

/**
 * Lặp qua mọi translation của 1 article đang isPublishable(), gọi PublishArticleAction cho
 * từng translation bên trong 1 transaction bao ngoài toàn bộ vòng lặp (all-or-nothing).
 * PublishArticleAction tự ghi post_publishing_logs mỗi lần gọi → mỗi translation có 1 dòng
 * log riêng, không cần code ghi log gộp thêm.
 */
class PublishAllTranslationsAction
{
    use AsAction;

    public function __construct(
        private readonly PublishArticleAction $publishArticle,
    ) {}

    public function handle(PostArticle $article): void
    {
        DB::transaction(function () use ($article) {
            $translations = $article->translations()->get()->filter->isPublishable();

            foreach ($translations as $translation) {
                $this->publishArticle->handle($translation);
            }
        });
    }
}
