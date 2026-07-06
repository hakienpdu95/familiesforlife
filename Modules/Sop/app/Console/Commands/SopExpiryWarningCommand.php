<?php

namespace Modules\Sop\Console\Commands;

use Illuminate\Console\Command;
use Modules\Sop\Enums\SopStatus;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Notifications\SopExpiryWarningNotification;

class SopExpiryWarningCommand extends Command
{
    protected $signature = 'sop:expiry-warning';

    protected $description = 'Gửi cảnh báo cho owner các SOP sắp hết hạn trong 7 ngày.';

    public function handle(): int
    {
        $warningDate = now()->addDays(7)->toDateString();

        $expiringSoon = SopProcess::withoutTenant()
            ->where('status', SopStatus::Approved->value)
            ->whereNotNull('expired_date')
            ->whereBetween('expired_date', [now()->toDateString(), $warningDate])
            ->with('owner')
            ->get();

        if ($expiringSoon->isEmpty()) {
            $this->info('Không có SOP nào sắp hết hạn trong 7 ngày tới.');
            return self::SUCCESS;
        }

        $notified = 0;
        foreach ($expiringSoon as $sop) {
            if (!$sop->owner) {
                $this->warn("  [skip] [{$sop->code}] {$sop->title} — không có owner");
                continue;
            }

            $daysLeft = (int) now()->diffInDays($sop->expired_date, false);
            $sop->owner->notify(new SopExpiryWarningNotification($sop, max(0, $daysLeft)));

            $this->line("  [notified] [{$sop->code}] {$sop->title} → {$sop->owner->name} ({$daysLeft} ngày)");
            $notified++;
        }

        $this->info("Đã gửi cảnh báo cho {$notified} SOP sắp hết hạn.");

        return self::SUCCESS;
    }
}
