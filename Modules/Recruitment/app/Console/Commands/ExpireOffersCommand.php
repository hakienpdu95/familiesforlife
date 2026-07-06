<?php

namespace Modules\Recruitment\Console\Commands;

use Illuminate\Console\Command;
use Modules\Recruitment\Models\RcOffer;

class ExpireOffersCommand extends Command
{
    protected $signature   = 'recruitment:expire-offers';
    protected $description = 'Đánh dấu expired các offer đã hết hạn';

    public function handle(): int
    {
        $expired = RcOffer::query()
            ->withoutGlobalScope('tenant')
            ->where('status', 'sent')
            ->whereNotNull('expire_at')
            ->whereDate('expire_at', '<', now()->toDateString())
            ->get();

        foreach ($expired as $offer) {
            $offer->update(['status' => 'expired']);
        }

        $this->info("Đã expire {$expired->count()} offer(s).");

        return self::SUCCESS;
    }
}
