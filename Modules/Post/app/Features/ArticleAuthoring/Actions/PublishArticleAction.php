<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Models\PostArticleTranslation;

class PublishArticleAction
{
    use AsAction;
    use LogsPublishingActions;

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

        $this->log($translation, 'publish');

        event(new ArticlePublished($translation));

        return $translation;
    }
}
