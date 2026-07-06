<?php

namespace Modules\JobPosting\Console\Commands;

use Illuminate\Console\Command;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Notifications\JpJobPostExpiryWarningNotification;

class JpJobPostExpiryWarningCommand extends Command
{
    protected $signature = 'jp:expiry-warning';

    protected $description = 'Gửi thông báo cho owner các tin tuyển dụng sắp hết hạn (D-7, D-3, D-1).';

    private const WARN_DAYS = [7, 3, 1];

    public function handle(): int
    {
        $targetDates = collect(self::WARN_DAYS)
            ->map(fn ($d) => now()->addDays($d)->toDateString())
            ->all();

        $expiringSoon = JpJobPost::withoutTenant()
            ->where('status', JobPostStatus::Published->value)
            ->whereNotNull('expire_at')
            ->where(function ($q) use ($targetDates) {
                foreach ($targetDates as $date) {
                    $q->orWhereBetween('expire_at', [
                        $date . ' 00:00:00',
                        $date . ' 23:59:59',
                    ]);
                }
            })
            ->with('owner')
            ->get();

        if ($expiringSoon->isEmpty()) {
            $this->info('Không có tin tuyển dụng nào sắp hết hạn.');
            return self::SUCCESS;
        }

        $notified = 0;
        foreach ($expiringSoon as $post) {
            if (!$post->owner) {
                $this->warn("  [skip] [{$post->code}] {$post->title} — không có owner");
                continue;
            }

            $daysLeft = (int) now()->startOfDay()->diffInDays($post->expire_at->startOfDay(), false);
            $post->owner->notify(new JpJobPostExpiryWarningNotification($post, max(0, $daysLeft)));

            $this->line("  [notified] [{$post->code}] {$post->title} → {$post->owner->name} ({$daysLeft} ngày)");
            $notified++;
        }

        $this->info("Đã gửi thông báo cho {$notified} tin tuyển dụng sắp hết hạn.");

        return self::SUCCESS;
    }
}
