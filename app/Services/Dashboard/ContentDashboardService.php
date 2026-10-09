<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Queries\ListPendingReviewTranslationsHandler;
use Modules\Post\Features\ArticleAuthoring\Queries\ListPendingReviewTranslationsQuery;
use Modules\Post\Models\PostArticleTranslation;

class ContentDashboardService
{
    public const TREND_RANGES = [7, 14, 30];

    private const TTL_STATS = 600;

    private const TTL_RANKING = 3600;

    private const TTL_QUEUE = 120;

    private const TTL_SYSTEM = 300;

    private const LIST_LIMIT = 8;

    public function __construct(
        private readonly ListPendingReviewTranslationsHandler $pendingHandler,
    ) {}

    public function getData(User $user): array
    {
        $audience = $this->audience($user);

        return [
            'audience' => $audience,
            'content_kpis' => $this->kpis(),
            'review_queue' => $audience['editor'] ? $this->reviewQueue($user) : null,
            'top_authors' => ($audience['editor'] || $audience['admin']) ? $this->topAuthors() : collect(),
            'my_stats' => $audience['creator'] ? $this->myStats($user) : null,
            'moderation' => ($audience['moderator'] || $audience['admin']) ? $this->moderationQueue() : null,
            'system_health' => $audience['admin'] ? $this->systemHealth() : null,
        ];
    }

    public function isContentAudience(User $user): bool
    {
        return $user->organization_id === null;
    }

    public function audience(User $user): array
    {
        $roles = $this->globalRoleNames($user);
        $has = fn (string ...$names) => $roles->intersect($names)->isNotEmpty();

        return [
            'admin' => $has('super-admin', 'platform_ops'),
            'editor' => $has('platform_content_head', 'platform_content_editor', 'platform_section_editor'),
            'creator' => $has('platform_content_creator'),
            'moderator' => $has('platform_content_moderator'),
            'roles' => $roles->values()->all(),
        ];
    }

    public function trend(int $days): array
    {
        $days = in_array($days, self::TREND_RANGES, true) ? $days : 30;

        return Cache::remember("dashboard:content:trend:{$days}", self::TTL_STATS, function () use ($days) {
            $from = now()->subDays($days - 1)->startOfDay();

            $published = DB::table('post_article_translations')
                ->whereNull('deleted_at')
                ->where('status', TranslationStatus::Published->value)
                ->where('published_at', '>=', $from)
                ->selectRaw('DATE(published_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $views = DB::table('post_article_view_events')
                ->where('viewed_at', '>=', $from)
                ->selectRaw('DATE(viewed_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $labels = $publishedSeries = $viewSeries = [];
            for ($i = 0; $i < $days; $i++) {
                $date = $from->copy()->addDays($i);
                $key = $date->toDateString();
                $labels[] = $date->format('d/m');
                $publishedSeries[] = (int) ($published[$key] ?? 0);
                $viewSeries[] = (int) ($views[$key] ?? 0);
            }

            return [
                'days' => $days,
                'labels' => $labels,
                'published' => $publishedSeries,
                'views' => $viewSeries,
            ];
        });
    }

    private function kpis(): array
    {
        return Cache::remember('dashboard:content:kpis', self::TTL_STATS, function () {
            $now = now();
            $today = $now->copy()->startOfDay();

            $published = DB::table('post_article_translations')
                ->whereNull('deleted_at')
                ->where('status', TranslationStatus::Published->value)
                ->selectRaw('SUM(published_at >= ?) as today, SUM(published_at >= ?) as week', [$today, $now->copy()->startOfWeek()])
                ->first();

            $views = DB::table('post_article_view_events')
                ->where('viewed_at', '>=', $now->copy()->subDays(14))
                ->selectRaw('SUM(viewed_at >= ?) as current, SUM(viewed_at < ?) as previous', [$now->copy()->subDays(7), $now->copy()->subDays(7)])
                ->first();

            $pending = DB::table('post_article_translations')
                ->whereNull('deleted_at')
                ->selectRaw('SUM(status = ?) as submitted, SUM(status = ?) as approved', [TranslationStatus::Submitted->value, TranslationStatus::Approved->value])
                ->first();

            $eventsOngoing = DB::table('events')
                ->whereNull('deleted_at')
                ->where('status', 'published')
                ->whereDate('start_date', '<=', $today)
                ->where(fn ($q) => $q->whereDate('end_date', '>=', $today)
                    ->orWhere(fn ($q) => $q->whereNull('end_date')->whereDate('start_date', $today)))
                ->count();

            $breakingActive = DB::table('post_breaking_news')
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                ->count();

            $viewsCurrent = (int) ($views->current ?? 0);
            $viewsPrevious = (int) ($views->previous ?? 0);

            return [
                'published_today' => (int) ($published->today ?? 0),
                'published_week' => (int) ($published->week ?? 0),
                'views_7d' => $viewsCurrent,
                'views_trend' => $viewsPrevious > 0 ? round(($viewsCurrent - $viewsPrevious) / $viewsPrevious * 100) : null,
                'pending_submit' => (int) ($pending->submitted ?? 0),
                'pending_publish' => (int) ($pending->approved ?? 0),
                'events_ongoing' => $eventsOngoing,
                'breaking_active' => $breakingActive,
                'cached_at' => $now->toIso8601String(),
            ];
        });
    }

    private function reviewQueue(User $user): array
    {
        $items = $this->pendingHandler->handle(new ListPendingReviewTranslationsQuery($user));
        $visible = $items->take(self::LIST_LIMIT)->load('article.createdBy:id,name');

        return [
            'total' => $items->count(),
            'items' => $visible,
        ];
    }

    private function moderationQueue(): array
    {
        $counts = Cache::remember('dashboard:content:moderation', self::TTL_QUEUE, fn () => DB::table('approval_subjects')
            ->whereNull('deleted_at')
            ->where('status', 'pending')
            ->selectRaw('subject_type, COUNT(*) as total')
            ->groupBy('subject_type')
            ->pluck('total', 'subject_type')
            ->map(fn ($total) => (int) $total)
            ->all());

        $items = collect(config('approval.subjects', []))
            ->map(fn (array $subject, string $type) => [
                'type' => $type,
                'label' => $subject['label'] ?? $type,
                'total' => $counts[$type] ?? 0,
            ])
            ->values();

        return [
            'total' => $items->sum('total'),
            'items' => $items->all(),
        ];
    }

    private function topAuthors(): Collection
    {
        return collect(Cache::remember('dashboard:content:top-authors', self::TTL_RANKING, fn () => DB::table('post_article_translations as t')
            ->join('post_articles as a', 'a.id', '=', 't.article_id')
            ->join('users as u', 'u.id', '=', 'a.created_by')
            ->whereNull('t.deleted_at')
            ->whereNull('a.deleted_at')
            ->where('t.status', TranslationStatus::Published->value)
            ->where('t.published_at', '>=', now()->subDays(30))
            ->groupBy('u.id', 'u.name')
            ->selectRaw('u.id, u.name, COUNT(DISTINCT a.id) as articles, COALESCE(SUM(t.view_count), 0) as views')
            ->orderByDesc('articles')
            ->orderByDesc('views')
            ->limit(5)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all()));
    }

    private function myStats(User $user): array
    {
        $totals = Cache::remember("dashboard:content:author:{$user->id}", self::TTL_STATS, fn () => (array) DB::table('post_articles as a')
            ->leftJoin('post_article_translations as t', fn ($j) => $j->on('t.article_id', '=', 'a.id')->whereNull('t.deleted_at'))
            ->whereNull('a.deleted_at')
            ->where('a.created_by', $user->id)
            ->selectRaw('COUNT(DISTINCT a.id) as articles, COUNT(DISTINCT CASE WHEN t.status = ? THEN a.id END) as published, COALESCE(SUM(t.view_count), 0) as views', [TranslationStatus::Published->value])
            ->first());

        $inReview = PostArticleTranslation::query()
            ->whereIn('status', [TranslationStatus::Submitted, TranslationStatus::Approved, TranslationStatus::Scheduled])
            ->whereHas('article', fn ($q) => $q->where('created_by', $user->id))
            ->with('article:id,uuid')
            ->latest('updated_at')
            ->limit(self::LIST_LIMIT)
            ->get(['id', 'article_id', 'locale', 'title', 'status', 'updated_at', 'scheduled_at']);

        return [
            'articles' => (int) ($totals['articles'] ?? 0),
            'published' => (int) ($totals['published'] ?? 0),
            'views' => (int) ($totals['views'] ?? 0),
            'in_review' => $inReview,
        ];
    }

    private function systemHealth(): array
    {
        return Cache::remember('dashboard:system-health', self::TTL_SYSTEM, function () {
            $disk = storage_path();
            $diskTotal = @disk_total_space($disk) ?: 0;
            $diskFree = @disk_free_space($disk) ?: 0;

            $online = config('session.driver') === 'database'
                ? DB::table(config('session.table', 'sessions'))
                    ->whereNotNull('user_id')
                    ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
                    ->distinct()
                    ->count('user_id')
                : null;

            return [
                'users_active' => DB::table('users')->where('is_active', true)->count(),
                'users_online' => $online,
                'jobs_pending' => DB::table('jobs')->count(),
                'jobs_failed' => DB::table('failed_jobs')->count(),
                'disk_used_pct' => $diskTotal > 0 ? round(($diskTotal - $diskFree) / $diskTotal * 100) : null,
                'disk_free_gb' => round($diskFree / 1024 ** 3, 1),
                'php_version' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'queue' => config('queue.default'),
                'cache' => config('cache.default'),
                'checked_at' => Carbon::now()->toIso8601String(),
            ];
        });
    }

    private function globalRoleNames(User $user): Collection
    {
        if ($user->organization_id !== null) {
            return collect();
        }

        $pivot = config('permission.table_names.model_has_roles');

        return DB::table($pivot)
            ->join('roles', 'roles.id', '=', "{$pivot}.role_id")
            ->where("{$pivot}.model_id", $user->id)
            ->where("{$pivot}.model_type", $user::class)
            ->pluck('roles.name');
    }
}
