<?php

namespace Modules\Post\Features\ArticleAuthoring\Actions\Concerns;

use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostPublishingLog;

trait LogsPublishingActions
{
    private function log(PostArticleTranslation $translation, string $action, ?string $reason = null): void
    {
        PostPublishingLog::create([
            'organization_id' => $translation->organization_id,
            'translation_id'  => $translation->id,
            'action'          => $action,
            'reason'          => $reason,
            'performed_by'    => auth()->id(), // null nếu chạy từ Job hệ thống
        ]);
    }
}
