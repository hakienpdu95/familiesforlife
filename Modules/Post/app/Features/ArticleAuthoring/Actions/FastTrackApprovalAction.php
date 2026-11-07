<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Models\PostArticleTranslation;

class FastTrackApprovalAction
{
    use AsAction;
    use LogsPublishingActions;

    public function handle(PostArticleTranslation $translation, User $publisher): PostArticleTranslation
    {
        if ($translation->status === TranslationStatus::Draft) {
            $translation->update(['status' => TranslationStatus::Submitted]);
            $this->log($translation, 'submit');
        }

        if ($translation->status === TranslationStatus::Submitted) {
            $translation->update([
                'status' => TranslationStatus::Approved,
                'approved_by' => $publisher->id,
                'approved_at' => now(),
            ]);
            $this->log($translation, 'approve');
        }

        return $translation;
    }
}
