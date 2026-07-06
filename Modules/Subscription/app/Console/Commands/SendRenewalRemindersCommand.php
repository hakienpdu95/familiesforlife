<?php

namespace Modules\Subscription\Console\Commands;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Laravelcm\Subscriptions\Models\Subscription;
use Modules\Subscription\Features\Subscribe\Events\SubscriptionExpiring;
use Modules\Subscription\Notifications\RenewalReminderNotification;

class SendRenewalRemindersCommand extends Command
{
    protected $signature   = 'subscription:send-reminders';
    protected $description = 'Gửi email nhắc gia hạn subscription (7, 3, 1 ngày trước khi hết hạn)';

    public function handle(): int
    {
        $this->info('[subscription:send-reminders] Bắt đầu gửi nhắc nhở...');

        $reminderDays = config('subscription.renewal_reminder_days', [7, 3, 1]);
        $total        = 0;

        foreach ($reminderDays as $days) {
            $subscriptions = Subscription::findEndingPeriod($days)
                ->with(['plan:id,name,slug'])
                ->get();

            foreach ($subscriptions as $sub) {
                /** @var Organization|null $org */
                $org = Organization::withoutGlobalScopes()->find($sub->subscriber_id);
                if (!$org) continue;

                TenantContext::runForOrganization($org, function () use ($org, $sub, $days): void {
                    SubscriptionExpiring::dispatch($org, $sub, $days);

                    $owner = $org->owner;
                    if ($owner) {
                        $owner->notify(new RenewalReminderNotification($org, $sub, $days));
                        $this->line("  [reminder {$days}d] org:{$org->id} ({$org->name}) → {$owner->email}");
                    }
                });

                $total++;
            }
        }

        $this->info("[subscription:send-reminders] Đã gửi {$total} nhắc nhở.");
        return self::SUCCESS;
    }
}
