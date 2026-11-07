<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Features\ArticleAuthoring\Notifications\ArticleReviewNotification;
use Modules\Post\Models\PostArticleTranslation;

class ReturnArticleToDraftAction
{
    use AsAction;
    use LogsPublishingActions;

    public function handle(PostArticleTranslation $translation, User $reviewer, string $reason): PostArticleTranslation
    {
        if (! in_array($translation->status, [TranslationStatus::Submitted, TranslationStatus::Approved], true)
            || ! $translation->status->canTransitionTo(TranslationStatus::Draft)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Draft->value);
        }

        DB::transaction(function () use ($translation, $reason) {
            $translation->update([
                'status' => TranslationStatus::Draft,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            $this->log($translation, 'reject', $reason);
        });

        $translation->loadMissing('article');

        $recipientIds = collect([
            $translation->article->created_by,
            $translation->publishingLogs()->where('action', 'submit')->latest('id')->value('performed_by'),
        ])->filter()->unique()->reject(fn ($id) => $id === $reviewer->id);

        $recipients = User::whereIn('id', $recipientIds)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ArticleReviewNotification($translation, 'returned', $reviewer->name, $reason));
        }

        return $translation;
    }
}
