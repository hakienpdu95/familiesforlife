<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Models\PostArticleTranslation;

class ArchiveArticleAction
{
    use AsAction;
    use LogsPublishingActions;

    public function handle(PostArticleTranslation $translation): PostArticleTranslation
    {
        if (! $translation->status->canTransitionTo(TranslationStatus::Archived)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Archived->value);
        }

        $translation->update(['status' => TranslationStatus::Archived]);

        $this->log($translation, 'archive');

        return $translation;
    }
}
