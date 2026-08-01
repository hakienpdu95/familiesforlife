<?php

namespace Modules\ContentCalendar\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\ContentCalendar\Listeners\MarkLinkedEntryAsDoneListener;
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;

/**
 * spec/ContentCalendar_Technical_Specification.md §5.3.2 — ArticlePublished đã tồn tại và được
 * bắn vô điều kiện bởi Modules\Post\Features\ArticleAuthoring\Actions\PublishArticleAction::handle()
 * (cả publish thủ công lẫn tự động qua PublishDueTranslationsJob), hiện CHƯA có listener nào
 * (Modules/Post/app/Providers/EventServiceProvider.php::$listen = [ArticlePublished::class => []]).
 * Chỉ cần đăng ký listener ở đây — KHÔNG sửa 1 dòng nào trong Post.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ArticlePublished::class => [
            MarkLinkedEntryAsDoneListener::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
