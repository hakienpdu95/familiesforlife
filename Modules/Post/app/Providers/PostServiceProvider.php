<?php

namespace Modules\Post\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Post\Console\Commands\BackfillPostTranslationsCommand;
use Modules\Post\Console\Commands\MonitorScoutFailedJobsCommand;
use Modules\Post\Console\Commands\PruneArticleVersionsCommand;
use Modules\Post\Console\Commands\SyncGoogleAnalyticsStatsCommand;
use Modules\Post\Jobs\ExpireSponsoredArticlesJob;
use Modules\Post\Jobs\PruneArticleViewEventsJob;
use Modules\Post\Jobs\PublishDueTranslationsJob;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostBreakingNews;
use Modules\Post\Models\PostCategory;
use Modules\Post\Models\PostTag;
use Modules\Post\Policies\PostArticlePolicy;
use Modules\Post\Policies\PostBreakingNewsPolicy;
use Modules\Post\Policies\PostCategoryPolicy;
use Modules\Post\Policies\PostTagPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PostServiceProvider extends ModuleServiceProvider
{
    protected string $name      = 'Post';
    protected string $nameLower = 'post';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(PostCategory::class, PostCategoryPolicy::class);
        Gate::policy(PostTag::class, PostTagPolicy::class);
        // viewAny/create không nhận model instance nên áp dụng được qua registration này;
        // view/update/delete/publish/... đăng ký thêm cho PostArticleTranslation::class bên
        // dưới vì các method đó giờ nhận PostArticleTranslation (spec §8).
        Gate::policy(PostArticle::class, PostArticlePolicy::class);
        Gate::policy(PostArticleTranslation::class, PostArticlePolicy::class);
        // spec/Breaking_News_Ticker_Technical_Specification.md §6.3 — KHÔNG có job dọn dẹp nào
        // (tin hết hạn tự biến mất qua scopeActive()/isCurrentlyBreaking(), cùng nguyên tắc
        // Banner — xem §0 "Job dọn dẹp định kỳ").
        Gate::policy(PostBreakingNews::class, PostBreakingNewsPolicy::class);

        $this->commands([
            BackfillPostTranslationsCommand::class,
            PruneArticleVersionsCommand::class,
            MonitorScoutFailedJobsCommand::class,
            SyncGoogleAnalyticsStatsCommand::class,
        ]);

        // Phase 14 — tự động publish translation đã tới hạn scheduled_at (§7.3).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(new PublishDueTranslationsJob())->everyMinute()->withoutOverlapping();

            // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §8 — daily (không phải everyMinute như
            // publish-due) vì hết hạn tài trợ tính theo date, không theo giờ. Queue 'low' truyền
            // qua tham số thứ 2 của Schedule::job() (đã có sẵn trong lệnh worker chuẩn — README)
            // để không tranh tài nguyên với PublishDueTranslationsJob (queue mặc định).
            $schedule->job(new ExpireSponsoredArticlesJob(), 'low')->daily()->withoutOverlapping();

            // spec/SiteSearch_Activation_Expansion_Technical_Specification.md §4.3 — giám sát
            // failed_jobs cho job Scout (MakeSearchable/RemoveFromSearch), 15 phút/lần.
            $schedule->command(MonitorScoutFailedJobsCommand::class)->everyFifteenMinutes()->withoutOverlapping();

            // spec/Related_Posts_Engine_Technical_Specification.md §6.2 — dọn post_article_view_events
            // cũ hơn behavior_lookback_days, daily cùng nguyên tắc ExpireSponsoredArticlesJob.
            $schedule->job(new PruneArticleViewEventsJob(), 'low')->daily()->withoutOverlapping();

            // spec/ga-dashboard-statistics.md §3.1 — đồng bộ lượt xem GA4 (30 ngày) về
            // post_article_translations.ga_views_30d, phục vụ cột "Lượt xem GA" + "Top nội dung".
            $schedule->command(SyncGoogleAnalyticsStatsCommand::class)->hourly()->withoutOverlapping();
        });
    }
}
