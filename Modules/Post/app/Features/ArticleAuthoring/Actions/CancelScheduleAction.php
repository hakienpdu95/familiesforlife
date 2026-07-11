<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Models\PostArticleTranslation;

/**
 * scheduled → draft. Kiểm tra chặt FROM=Scheduled (không dùng canTransitionTo(Draft) chung
 * chung — Submitted cũng map sang Draft nhưng đó là "reject submission", khác ngữ nghĩa
 * "cancel schedule"; reject chưa có action riêng trong spec này).
 */
class CancelScheduleAction
{
    use AsAction;
    use LogsPublishingActions;

    public function handle(PostArticleTranslation $translation): PostArticleTranslation
    {
        if ($translation->status !== TranslationStatus::Scheduled) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Draft->value);
        }

        $translation->update([
            'status'       => TranslationStatus::Draft,
            'scheduled_at' => null,
        ]);

        $this->log($translation, 'cancel_schedule');

        return $translation;
    }
}
