<?php

namespace Modules\Post\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\Jobs\MakeSearchable;
use Laravel\Scout\Jobs\RemoveFromSearch;

/**
 * spec/SiteSearch_Activation_Expansion_Technical_Specification.md §4.3/§4.3.1 — giám sát
 * failed_jobs cho 2 job Scout (MakeSearchable/RemoveFromSearch). Đăng ký chạy 15 phút/lần qua
 * PostServiceProvider::boot() (cùng pattern PublishDueTranslationsJob/ExpireSponsoredArticlesJob),
 * KHÔNG đặt trong routes/console.php gốc — nhất quán với cách các job định kỳ khác của module
 * này tự đăng ký lịch của mình.
 *
 * Dùng field JSON `displayName` (Laravel queue framework tự set cho MỌI job, ổn định hơn `LIKE`
 * trên payload serialize — đổi định dạng nội bộ của job không ảnh hưởng field này).
 *
 * Cảnh báo qua `Log::critical()`/`Log::warning()` — kênh log mặc định của app (`LOG_STACK`,
 * hiện là `single`) đã hỗ trợ sẵn driver `slack` (config/logging.php) nếu sau này cần đẩy qua
 * Slack: chỉ cần set `LOG_SLACK_WEBHOOK_URL` và thêm `slack` vào `LOG_STACK` trong `.env`,
 * không cần sửa code ở đây.
 */
class MonitorScoutFailedJobsCommand extends Command
{
    protected $signature = 'post:monitor-scout-failed-jobs';

    protected $description = 'Giám sát failed_jobs cho MakeSearchable/RemoveFromSearch (Scout) — cảnh báo theo ngưỡng §4.3';

    private const SCOUT_JOB_CLASSES = [
        MakeSearchable::class,
        RemoveFromSearch::class,
    ];

    public function handle(): int
    {
        $recentCount = $this->scoutFailedJobsQuery()
            ->where('failed_at', '>=', now()->subMinutes(30))
            ->count();

        // Drift kéo dài: job Scout thất bại VẪN CÒN trong failed_jobs (chưa ai retry/xử lý) và
        // bản ghi cũ nhất trong số đó đã quá 2 giờ — nghĩa là lỗi đã tồn tại liên tục ít nhất
        // 2 giờ mà không ai xử lý, bất kể trong 30 phút gần nhất có bao nhiêu lỗi mới.
        $oldestUnresolved = $this->scoutFailedJobsQuery()->min('failed_at');
        $isDrifting        = $oldestUnresolved !== null
            && Carbon::parse($oldestUnresolved)->lte(now()->subHours(2));

        if ($isDrifting) {
            Log::critical('[Scout] failed_jobs drift kéo dài > 2 giờ — index Post có thể đang lệch (drift) tích luỹ, xem §4.3.1.', [
                'oldest_unresolved_failure' => (string) $oldestUnresolved,
                'count_last_30min'          => $recentCount,
            ]);
            $this->error("CRITICAL: job Scout thất bại liên tục từ {$oldestUnresolved} (> 2 giờ, chưa xử lý). Xem §4.3.1 để khắc phục.");

            return self::FAILURE;
        }

        if ($recentCount >= 3) {
            Log::critical('[Scout] failed_jobs MakeSearchable/RemoveFromSearch vượt ngưỡng Critical trong 30 phút.', [
                'count_last_30min' => $recentCount,
            ]);
            $this->error("CRITICAL: {$recentCount} job Scout thất bại trong 30 phút qua (ngưỡng Critical là 3).");

            return self::FAILURE;
        }

        if ($recentCount >= 1) {
            Log::warning('[Scout] failed_jobs MakeSearchable/RemoveFromSearch — mức Warning.', [
                'count_last_30min' => $recentCount,
            ]);
            $this->warn("WARNING: {$recentCount} job Scout thất bại trong 30 phút qua.");

            return self::SUCCESS;
        }

        $this->info('OK — không có job Scout (MakeSearchable/RemoveFromSearch) thất bại trong 30 phút qua.');

        return self::SUCCESS;
    }

    private function scoutFailedJobsQuery(): \Illuminate\Database\Query\Builder
    {
        $placeholders = implode(',', array_fill(0, count(self::SCOUT_JOB_CLASSES), '?'));

        return DB::table('failed_jobs')
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(payload, '$.displayName')) in ({$placeholders})",
                self::SCOUT_JOB_CLASSES
            );
    }
}
