<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Models\PostArticleTranslation;

class SubmitArticleForReviewAction
{
    use AsAction;

    public function handle(PostArticleTranslation $translation): PostArticleTranslation
    {
        if (! $translation->status->canTransitionTo(TranslationStatus::Submitted)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Submitted->value);
        }

        $translation->update(['status' => TranslationStatus::Submitted]);

        return $translation;
    }
}
