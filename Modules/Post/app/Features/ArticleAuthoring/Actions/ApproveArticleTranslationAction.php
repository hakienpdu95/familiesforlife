<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Models\PostArticleTranslation;

class ApproveArticleTranslationAction
{
    use AsAction;
    use LogsPublishingActions;

    public function handle(PostArticleTranslation $translation): PostArticleTranslation
    {
        if (! $translation->status->canTransitionTo(TranslationStatus::Approved)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Approved->value);
        }

        $translation->update([
            'status'      => TranslationStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->log($translation, 'approve');

        return $translation;
    }
}
