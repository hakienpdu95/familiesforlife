<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions;

use Illuminate\Support\Facades\Validator;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Actions\Concerns\LogsPublishingActions;
use Modules\Post\Features\ArticleAuthoring\Exceptions\InvalidTransitionException;
use Modules\Post\Models\PostArticleTranslation;

class UnpublishArticleTranslationAction
{
    use AsAction;
    use LogsPublishingActions;

    /** Gỡ tạm — có thể publish lại. Giữ nguyên `published_at` (lịch sử); bài không còn hiển thị công khai vì query công khai lọc status=published only. */
    public function handle(PostArticleTranslation $translation, string $reason): PostArticleTranslation
    {
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:10']])->validate();

        if (! $translation->status->canTransitionTo(TranslationStatus::Unpublished)) {
            throw new InvalidTransitionException($translation->status->value, TranslationStatus::Unpublished->value);
        }

        $translation->update([
            'status'           => TranslationStatus::Unpublished,
            'unpublish_reason' => $reason,
        ]);

        $this->log($translation, 'unpublish', $reason);

        return $translation;
    }
}
