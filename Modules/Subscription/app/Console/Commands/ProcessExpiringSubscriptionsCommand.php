<?php

namespace Modules\Subscription\Console\Commands;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Laravelcm\Subscriptions\Models\Subscription;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;
use Modules\Subscription\Features\Subscribe\Events\SubscriptionExpired;
use Modules\Subscription\Notifications\SubscriptionExpiredNotification;

class ProcessExpiringSubscriptionsCommand extends Command
{
    protected $signature   = 'subscription:process-expiring';
    protected $description = 'Kiểm tra và xử lý subscription đã hết hạn, phát event + gửi thông báo';

    public function handle(): int
    {
        $this->info('[subscription:process-expiring] Bắt đầu xử lý...');

        $ended = Subscription::findEndedPeriod()
            ->whereNull('canceled_at')
            ->with(['plan:id,name,slug'])
            ->get();

        $this->line("  Tìm thấy {$ended->count()} subscription đã hết hạn.");

        foreach ($ended as $sub) {
            /** @var Organization|null $org */
            $org = Organization::withoutGlobalScopes()->find($sub->subscriber_id);
            if (!$org) continue;

            TenantContext::runForOrganization($org, function () use ($org, $sub): void {
                SubscriptionContext::flush($org->id);

                SubscriptionExpired::dispatch($org, $sub);

                $owner = $org->owner;
                if ($owner) {
                    $owner->notify(new SubscriptionExpiredNotification($org, $sub));
                }

                $this->line("  [expired] org:{$org->id} ({$org->name}) plan:{$sub->plan?->slug}");
            });

            SubscriptionContext::flushAll();
        }

        $this->info('[subscription:process-expiring] Hoàn thành.');
        return self::SUCCESS;
    }
}
