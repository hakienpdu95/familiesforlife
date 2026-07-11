<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Features\ArticleAuthoring\Notifications\ArticleTakenDownNotification;
use Modules\Post\Models\PostArticleTranslation;

/**
 * "Hard take down" (spec/PublishingEngine_Technical_Specification.md §7.2) — đích thẳng
 * archived (khác Unpublish chỉ gỡ tạm), bắt buộc reason. Gửi notification tới ceo/ai_operator
 * + người publish gần nhất trong cùng tổ chức (§7.6), dispatch sau khi transaction DB commit.
 */
class TakeDownArticleTranslationAction
{
    use AsAction;
    use LogsPublishingActions;

    public function handle(PostArticleTranslation $translation, string $reason): PostArticleTranslation
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:10']])->validate();

        if (! $translation->status->canTransitionTo(TranslationStatus::Archived)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Archived->value);
        }

        $translation->update([
            'status'           => TranslationStatus::Archived,
            'unpublish_reason' => $reason,
        ]);

        $this->log($translation, 'takedown', $reason);

        DB::afterCommit(fn () => $this->notifyTakedown($translation, $reason));

        return $translation;
    }

    private function notifyTakedown(PostArticleTranslation $translation, string $reason): void
    {
        $recipients = User::where('organization_id', $translation->organization_id)
            ->role(['ceo', 'ai_operator'])
            ->get();

        $lastPublisherId = $translation->latestPublishLog()?->performed_by;

        if ($lastPublisherId && ! $recipients->contains('id', $lastPublisherId)) {
            $publisher = User::where('organization_id', $translation->organization_id)->find($lastPublisherId);

            if ($publisher) {
                $recipients->push($publisher);
            }
        }

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ArticleTakenDownNotification($translation, $reason));
        }
    }
}
