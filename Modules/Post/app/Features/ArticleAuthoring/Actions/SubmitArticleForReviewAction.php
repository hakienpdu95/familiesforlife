<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Features\ArticleAuthoring\Notifications\ArticleReviewNotification;
use Modules\Post\Models\PostArticleTranslation;

class SubmitArticleForReviewAction
{
    use AsAction;
    use LogsPublishingActions;

    public function __construct(
        private readonly ResolveArticleReviewersAction $reviewers,
    ) {}

    public function handle(PostArticleTranslation $translation, ?User $submitter = null): PostArticleTranslation
    {
        if (! $translation->status->canTransitionTo(TranslationStatus::Submitted)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Submitted->value);
        }

        $skipPreliminary = $submitter !== null && Gate::forUser($submitter)->allows('approve', $translation);

        DB::transaction(function () use ($translation, $submitter, $skipPreliminary) {
            $translation->update(['status' => TranslationStatus::Submitted]);
            $this->log($translation, 'submit');

            if ($skipPreliminary) {
                $translation->update([
                    'status' => TranslationStatus::Approved,
                    'approved_by' => $submitter?->id,
                    'approved_at' => now(),
                ]);
                $this->log($translation, 'approve');
            }
        });

        if ($submitter === null) {
            return $translation;
        }

        $recipients = $skipPreliminary
            ? $this->reviewers->publishers($submitter)
            : $this->reviewers->approvers($translation, $submitter);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new ArticleReviewNotification(
                $translation,
                $skipPreliminary ? 'approved' : 'submitted',
                $submitter->name,
            ));
        }

        return $translation;
    }
}
