<?php

namespace Modules\Subscription\Console\Commands;

use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Modules\Subscription\Features\FeatureGate\Support\SubscriptionContext;

class SubscriptionStatusCommand extends Command
{
    protected $signature   = 'subscription:status {org_id : ID của tổ chức}';
    protected $description = 'Hiển thị trạng thái subscription và feature map của một tổ chức';

    public function handle(): int
    {
        $orgId = (int) $this->argument('org_id');
        $org   = Organization::withoutGlobalScopes()->find($orgId);

        if (!$org) {
            $this->error("Không tìm thấy tổ chức ID={$orgId}");
            return self::FAILURE;
        }

        TenantContext::set($org);

        try {
            $sub = $org->planSubscription('main');
            $ctx = SubscriptionContext::boot($org);

            // ── Subscription summary ──────────────────────────────────────
            $this->info("\n📋 Organization: {$org->name} (ID={$org->id})");
            $this->line(str_repeat('─', 50));

            if (!$sub) {
                $this->warn('  Chưa có subscription nào.');
                return self::SUCCESS;
            }

            $this->line("  Plan     : " . ($sub->plan->name ?? '?') . " [{$sub->plan->slug}]");
            $this->line("  Active   : " . ($sub->active()   ? '<fg=green>YES</>' : '<fg=red>NO</>'));
            $this->line("  Trial    : " . ($sub->onTrial()  ? '<fg=cyan>YES</>' : 'NO'));
            $this->line("  Canceled : " . ($sub->canceled() ? '<fg=yellow>YES</>' : 'NO'));
            $this->line("  Ended    : " . ($sub->ended()    ? '<fg=red>YES</>' : 'NO'));

            if ($sub->trial_ends_at) {
                $this->line("  Trial ends : {$sub->trial_ends_at->toDateTimeString()}");
            }
            if ($sub->ends_at) {
                $this->line("  Ends at  : {$sub->ends_at->toDateTimeString()}");
            }
            if ($sub->canceled_at) {
                $this->line("  Canceled at: {$sub->canceled_at->toDateTimeString()}");
            }

            // ── Feature map ──────────────────────────────────────────────
            $this->line("\n  Feature Map:");
            $this->line(str_repeat('─', 50));

            $sub->loadMissing('plan.features');
            $features = $sub->plan->features ?? collect();

            if ($features->isEmpty()) {
                $this->warn('  (no features on this plan)');
            } else {
                $rows = $features->map(fn ($f) => [
                    $f->slug,
                    $f->value,
                    $ctx->canUse($f->slug) ? '<fg=green>✓</>' : '<fg=red>✗</>',
                ])->toArray();

                $this->table(['Slug', 'Plan Value', 'Can Use'], $rows);
            }

            return self::SUCCESS;
        } finally {
            TenantContext::flush();
            SubscriptionContext::flush($orgId);
        }
    }
}
