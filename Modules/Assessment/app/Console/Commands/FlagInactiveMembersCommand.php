<?php

namespace Modules\Assessment\Console\Commands;

use Illuminate\Console\Command;
use Modules\Assessment\Jobs\FlagInactiveMembersJob;

class FlagInactiveMembersCommand extends Command
{
    protected $signature   = 'passport:flag-inactive-members';
    protected $description = 'Gửi báo cáo weekly cho HR về thành viên không hoạt động > 45 ngày';

    public function handle(): int
    {
        FlagInactiveMembersJob::dispatchSync();

        $this->info('Inactive member flag report sent.');

        return Command::SUCCESS;
    }
}
