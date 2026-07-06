<?php

namespace Modules\Marketplace\Console\Commands;

use Illuminate\Console\Command;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Models\MktListing;

class ExpireListingsCommand extends Command
{
    protected $signature   = 'marketplace:expire-listings';
    protected $description = 'Close marketplace listings that have passed their expire_at date.';

    public function handle(): int
    {
        $count = MktListing::withoutGlobalScope('tenant')
            ->where('status', ListingStatus::ACTIVE->value)
            ->whereNotNull('expire_at')
            ->where('expire_at', '<', now())
            ->update([
                'status'    => ListingStatus::EXPIRED->value,
                'closed_at' => now(),
            ]);

        $this->info("Expired {$count} listing(s).");

        return Command::SUCCESS;
    }
}
