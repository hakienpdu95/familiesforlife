<?php

namespace Modules\Post\Console\Commands;

use Illuminate\Console\Command;
use Modules\Post\Enums\VersionTrigger;
use Modules\Post\Models\PostArticleVersion;

/**
 * spec/Post_VersionHistory_Technical_Specification.md §10.2 — prune version `trigger=save` cũ
 * hơn `retention_days`, TRỪ version mới nhất của mỗi translation (luôn giữ ít nhất 1 bản) và
 * mọi version `publish`/`restore` (isProtectedFromPruning, §7). Không lên lịch chạy mặc định —
 * chỉ chạy thủ công/cron nếu tổ chức chủ động bật `retention_days`.
 */
class PruneArticleVersionsCommand extends Command
{
    protected $signature = 'post:prune-article-versions';

    protected $description = 'Xoá post_article_versions (trigger=save) cũ hơn config post.version_history.retention_days';

    public function handle(): void
    {
        $retentionDays = config('post.version_history.retention_days');

        if (! $retentionDays) {
            $this->info('post.version_history.retention_days chưa bật (null) — không làm gì.');

            return;
        }

        $cutoff = now()->subDays($retentionDays);
        $count  = 0;

        PostArticleVersion::where('trigger', VersionTrigger::Save)
            ->where('created_at', '<', $cutoff)
            ->whereNotIn('id', function ($query) {
                // Luôn giữ ít nhất 1 bản — version mới nhất của mỗi translation, kể cả khi
                // trigger=save và đã quá hạn retention.
                $query->selectRaw('MAX(id)')
                    ->from('post_article_versions')
                    ->groupBy('translation_id');
            })
            ->chunkById(200, function ($versions) use (&$count) {
                $count += $versions->count();
                PostArticleVersion::whereIn('id', $versions->pluck('id'))->delete();
            });

        $this->info("Đã xoá {$count} version quá hạn {$retentionDays} ngày.");
    }
}
