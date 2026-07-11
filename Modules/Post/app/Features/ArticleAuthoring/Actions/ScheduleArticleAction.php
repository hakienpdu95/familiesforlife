<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Models\PostArticleTranslation;

class ScheduleArticleAction
{
    use AsAction;
    use LogsPublishingActions;

    public function handle(PostArticleTranslation $translation, Carbon $publishAt): PostArticleTranslation
    {
        if (! $translation->status->canTransitionTo(TranslationStatus::Scheduled)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Scheduled->value);
        }

        $translation->update([
            'status'       => TranslationStatus::Scheduled,
            'scheduled_at' => $publishAt,
        ]);

        $this->log($translation, 'schedule');

        return $translation;
    }
}
