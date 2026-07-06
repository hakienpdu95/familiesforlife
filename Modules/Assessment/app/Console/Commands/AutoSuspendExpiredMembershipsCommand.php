<?php

namespace Modules\Assessment\Console\Commands;

use Illuminate\Console\Command;
use Modules\Assessment\Jobs\AutoSuspendExpiredMembershipsJob;

class AutoSuspendExpiredMembershipsCommand extends Command
{
    protected $signature   = 'passport:auto-suspend-expired';
    protected $description = 'Suspend membership đã quá contract_end_date và gửi thông báo HR';

    public function handle(): int
    {
        AutoSuspendExpiredMembershipsJob::dispatchSync();

        $this->info('Auto-suspend expired memberships completed.');

        return Command::SUCCESS;
    }
}
