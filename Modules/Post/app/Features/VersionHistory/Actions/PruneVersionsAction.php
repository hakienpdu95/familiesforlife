<?php

namespace Modules\Post\Features\VersionHistory\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Models\PostArticleVersion;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §10.1 — dọn ngay sau mỗi lần ghi
 * (chỉ khi `max_versions_per_translation` được cấu hình, tắt mặc định = no-op). Không bao giờ
 * xoá version publish/restore (isProtectedFromPruning, §7).
 */
class PruneVersionsAction
{
    use AsAction;

    public function handle(int $translationId): void
    {
        $max = config('post.version_history.max_versions_per_translation');

        if (! $max) {
            return;
        }

        $prunableIds = PostArticleVersion::where('translation_id', $translationId)
            ->where('trigger', VersionTrigger::Save)
            ->orderByDesc('version_number')
            ->skip($max)
            ->pluck('id');

        PostArticleVersion::whereIn('id', $prunableIds)->delete();
    }
}
