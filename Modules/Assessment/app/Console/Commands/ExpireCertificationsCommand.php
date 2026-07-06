<?php

namespace Modules\Assessment\Console\Commands;

use Illuminate\Console\Command;
use Modules\Assessment\Models\WorkforceCertification;

class ExpireCertificationsCommand extends Command
{
    protected $signature   = 'certifications:expire';
    protected $description = 'Mark overdue active certifications as expired';

    public function handle(): int
    {
        $count = WorkforceCertification::withoutTenant()
            ->where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} certification(s).");

        return Command::SUCCESS;
    }
}
