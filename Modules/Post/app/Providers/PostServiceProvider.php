<?php

namespace Modules\Post\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Modules\Post\Console\Commands\BackfillPostTranslationsCommand;
use Modules\Post\Jobs\ExpireSponsoredArticlesJob;
use Modules\Post\Jobs\PublishDueTranslationsJob;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;
use Modules\Post\Policies\PostArticlePolicy;
use Modules\Post\Policies\PostCategoryPolicy;
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
        // viewAny/create không nhận model instance nên áp dụng được qua registration này;
        // view/update/delete/publish/... đăng ký thêm cho PostArticleTranslation::class bên
        // dưới vì các method đó giờ nhận PostArticleTranslation (spec §8).
        Gate::policy(PostArticle::class, PostArticlePolicy::class);
        Gate::policy(PostArticleTranslation::class, PostArticlePolicy::class);

        $this->commands([
            BackfillPostTranslationsCommand::class,
        ]);

        // Phase 14 — tự động publish translation đã tới hạn scheduled_at (§7.3).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(new PublishDueTranslationsJob())->everyMinute()->withoutOverlapping();

            // spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §8 — daily (không phải everyMinute như
            // publish-due) vì hết hạn tài trợ tính theo date, không theo giờ. Queue 'low' truyền
            // qua tham số thứ 2 của Schedule::job() (đã có sẵn trong lệnh worker chuẩn — README)
            // để không tranh tài nguyên với PublishDueTranslationsJob (queue mặc định).
            $schedule->job(new ExpireSponsoredArticlesJob(), 'low')->daily()->withoutOverlapping();
        });
    }
}
