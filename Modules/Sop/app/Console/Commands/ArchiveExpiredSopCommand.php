<?php

namespace Modules\Sop\Console\Commands;

use Illuminate\Console\Command;
use Modules\Sop\Enums\SopStatus;
use Modules\Sop\Models\SopProcess;

class ArchiveExpiredSopCommand extends Command
{
    protected $signature = 'sop:archive-expired';

    protected $description = 'Chuyển các SOP đã hết hạn (expired_date < today AND status = approved) sang trạng thái archived.';

    public function handle(): int
    {
        $expired = SopProcess::withoutTenant()
            ->where('status', SopStatus::Approved->value)
            ->whereNotNull('expired_date')
            ->where('expired_date', '<', now()->toDateString())
            ->with('owner')
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Không có SOP nào cần lưu trữ.');
            return self::SUCCESS;
        }

        foreach ($expired as $sop) {
            $sop->update(['status' => SopStatus::Archived->value]);
            $this->line("  [archived] [{$sop->code}] {$sop->title} (org: {$sop->organization_id})");
        }

        $this->info("Đã lưu trữ {$expired->count()} SOP hết hạn.");

        return self::SUCCESS;
    }
}
