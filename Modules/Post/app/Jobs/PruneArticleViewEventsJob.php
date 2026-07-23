<?php

namespace Modules\Post\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Post\Models\PostArticleViewEvent;

/**
 * spec/Related_Posts_Engine_Technical_Specification.md §6.2 — dọn post_article_view_events cũ
 * hơn behavior_lookback_days (đúng bằng cửa sổ tính đồng-xem, §5.3 — dữ liệu cũ hơn không còn
 * dùng vào việc gì). Cùng nguyên tắc ExpireSponsoredArticlesJob (queue 'low', daily(),
 * withoutOverlapping()).
 */
class PruneArticleViewEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $lookbackDays = (int) config('post.related_posts.behavior_lookback_days', 90);

        PostArticleViewEvent::where('viewed_at', '<', now()->subDays($lookbackDays))
            ->chunkById(1000, function ($events) {
                PostArticleViewEvent::destroy($events->pluck('id'));
            });
    }
}
