<?php

namespace Modules\Subscription\Database\Seeders;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Seeder;
use Laravelcm\Subscriptions\Models\Plan;
use Modules\Subscription\Features\Subscribe\Actions\SubscribeOrganizationAction;
use Modules\Subscription\Features\Subscribe\Data\SubscribeData;

class SeedExistingOrgsWithStarterPlan extends Seeder
{
    public function run(): void
    {
        $starter = Plan::where('slug', 'starter')->where('is_active', true)->first();
        if (!$starter) {
            $this->command->warn('Starter plan not found. Run PlanSeeder first.');
            return;
        }

        $orgs = Organization::whereDoesntHave('planSubscriptions')->get();

        foreach ($orgs as $org) {
            SubscribeOrganizationAction::run($org, new SubscribeData(
                planId:        $starter->id,
                idempotentKey: 'seed-starter-' . $org->id,
            ));
            $this->command->line("  subscribed org:{$org->id} ({$org->name}) → starter");
        }
    }
}
