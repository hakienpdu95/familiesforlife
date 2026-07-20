<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Features\VersionHistory\Actions\CreateArticleVersionAction;
use Modules\Post\Models\PostArticleTranslation;

class PublishArticleAction
{
    use AsAction;
    use LogsPublishingActions;

    public function __construct(
        private readonly CreateArticleVersionAction $createVersion,
    ) {}

    public function handle(PostArticleTranslation $translation): PostArticleTranslation
    {
        // Idempotent no-op nếu đã published — bảo vệ PublishDueTranslationsJob chạy trùng lặp
        // (2 worker cùng lúc / job retry), tránh ghi log trùng (spec §14).
        if ($translation->status === TranslationStatus::Published) {
            return $translation;
        }

        if (! $translation->status->canTransitionTo(TranslationStatus::Published)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Published->value);
        }

        $translation->update([
            'status'       => TranslationStatus::Published,
            'published_at' => $translation->published_at ?? now(),
            'approved_by'  => $translation->approved_by ?? auth()->id(),
            'approved_at'  => $translation->approved_at ?? now(),
        ]);

        // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §7.3 — set 1 LẦN duy nhất trên PostArticle
        // (không phải translation), lần đầu bài được publish trong lúc is_sponsored=true. Nằm
        // SAU đoạn early-return "đã published thì no-op" ở đầu method, nên publish trùng lặp
        // (job retry, PublishAllTranslationsAction gọi 2 lần...) không set lại/ghi đè giá trị.
        $article = $translation->article;
        if ($article->is_sponsored && ! $article->sponsored_published_at) {
            $article->update(['sponsored_published_at' => now()]);
        }

        $this->log($translation, 'publish');

        // spec/Post_VersionHistory_Technical_Specification.md §9.4 — chốt "bản đang public",
        // LUÔN ghi (không dedup theo content_hash như trigger=save, xem PersistArticleVersionJob).
        $this->createVersion->handle($translation, VersionTrigger::Publish, auth()->id());

        event(new ArticlePublished($translation));

        return $translation;
    }
}
