<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Features\ArticleAuthoring\Notifications\ArticleReviewNotification;
use Modules\Post\Models\PostArticleTranslation;

class ApproveArticleTranslationAction
{
    use AsAction;
    use LogsPublishingActions;

    public function __construct(
        private readonly ResolveArticleReviewersAction $reviewers,
    ) {}

    public function handle(PostArticleTranslation $translation, ?User $approver = null): PostArticleTranslation
    {
        if (! $translation->status->canTransitionTo(TranslationStatus::Approved)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Approved->value);
        }

        $translation->update([
            'status' => TranslationStatus::Approved,
            'approved_by' => $approver?->id ?? auth()->id(),
            'approved_at' => now(),
        ]);

        $this->log($translation, 'approve');

        if ($approver !== null) {
            $recipients = $this->reviewers->publishers($approver);

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new ArticleReviewNotification($translation, 'approved', $approver->name));
            }
        }

        return $translation;
    }
}
