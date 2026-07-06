<?php

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Laravelcm\Subscriptions\Models\Feature;
use Laravelcm\Subscriptions\Models\Plan;

class FeatureSeeder extends Seeder
{
    /**
     * Feature slug taxonomy per plan tier.
     * value: '1'/'0' for bool, numeric string for limits/quotas.
     * 0 = unlimited.
     */
    private array $featureMatrix = [
        'starter' => [
            // Modules
            'module.task'         => '1',
            'module.sop'          => '1',
            'module.hr'           => '1',
            'module.lead'         => '0',
            'module.customer'     => '0',
            'module.workflow'     => '0',
            'module.ai'           => '0',
            'module.recruitment'  => '0',
            'module.assessment'   => '0',
            'module.project'      => '0',
            'module.kc'           => '0',
            'module.marketplace'  => '0',
            // Limits
            'limit.employees'     => '5',
            'limit.members'       => '3',
            'limit.workflows'     => '2',
            'limit.projects'      => '0',
            'limit.storage_gb'    => '1',
            // Flags
            'flag.api_access'     => '0',
            'flag.audit_log'      => '0',
            'flag.advanced_reports' => '0',
            'flag.sso'            => '0',
            'flag.white_label'    => '0',
            'flag.custom_domain'  => '0',
            // Quotas
            'quota.ai_requests'   => '20',
            'quota.ai_tokens'     => '0',
            'quota.workflow_runs' => '50',
            // AI limits
            'limit.ai_agents'     => '0',
        ],
        'growth' => [
            'module.task'         => '1',
            'module.sop'          => '1',
            'module.hr'           => '1',
            'module.lead'         => '1',
            'module.customer'     => '1',
            'module.workflow'     => '1',
            'module.ai'           => '1',
            'module.recruitment'  => '1',
            'module.assessment'   => '1',
            'module.project'      => '0',
            'module.kc'           => '0',
            'module.marketplace'  => '0',
            'limit.employees'     => '50',
            'limit.members'       => '15',
            'limit.workflows'     => '20',
            'limit.projects'      => '0',
            'limit.storage_gb'    => '10',
            'flag.api_access'     => '0',
            'flag.audit_log'      => '1',
            'flag.advanced_reports' => '1',
            'flag.sso'            => '0',
            'flag.white_label'    => '0',
            'flag.custom_domain'  => '0',
            'quota.ai_requests'   => '500',
            'quota.ai_tokens'     => '500000',
            'quota.workflow_runs' => '2000',
            'limit.ai_agents'     => '5',
        ],
        'scale' => [
            'module.task'         => '1',
            'module.sop'          => '1',
            'module.hr'           => '1',
            'module.lead'         => '1',
            'module.customer'     => '1',
            'module.workflow'     => '1',
            'module.ai'           => '1',
            'module.recruitment'  => '1',
            'module.assessment'   => '1',
            'module.project'      => '1',
            'module.kc'           => '1',
            'module.marketplace'  => '1',
            'limit.employees'     => '200',
            'limit.members'       => '50',
            'limit.workflows'     => '0',
            'limit.projects'      => '0',
            'limit.storage_gb'    => '50',
            'flag.api_access'     => '1',
            'flag.audit_log'      => '1',
            'flag.advanced_reports' => '1',
            'flag.sso'            => '0',
            'flag.white_label'    => '0',
            'flag.custom_domain'  => '0',
            'quota.ai_requests'   => '5000',
            'quota.ai_tokens'     => '5000000',
            'quota.workflow_runs' => '0',
            'limit.ai_agents'     => '20',
        ],
        'enterprise' => [
            'module.task'         => '1',
            'module.sop'          => '1',
            'module.hr'           => '1',
            'module.lead'         => '1',
            'module.customer'     => '1',
            'module.workflow'     => '1',
            'module.ai'           => '1',
            'module.recruitment'  => '1',
            'module.assessment'   => '1',
            'module.project'      => '1',
            'module.kc'           => '1',
            'module.marketplace'  => '1',
            'limit.employees'     => '0',
            'limit.members'       => '0',
            'limit.workflows'     => '0',
            'limit.projects'      => '0',
            'limit.storage_gb'    => '0',
            'flag.api_access'     => '1',
            'flag.audit_log'      => '1',
            'flag.advanced_reports' => '1',
            'flag.sso'            => '1',
            'flag.white_label'    => '1',
            'flag.custom_domain'  => '1',
            'quota.ai_requests'   => '0',
            'quota.ai_tokens'     => '0',
            'quota.workflow_runs' => '0',
            'limit.ai_agents'     => '0',
        ],
    ];

    public function run(): void
    {
        foreach ($this->featureMatrix as $planSlug => $features) {
            $plan = Plan::where('slug', $planSlug)->first();
            if (!$plan) continue;

            Feature::where('plan_id', $plan->id)->forceDelete();

            $sortOrder = 0;
            foreach ($features as $slug => $value) {
                Feature::create([
                    'plan_id'             => $plan->id,
                    'slug'                => $slug,
                    'name'                => ['vi' => $slug],
                    'value'               => $value,
                    'resettable_period'   => str_starts_with($slug, 'quota.') ? 1 : 0,
                    'resettable_interval' => str_starts_with($slug, 'quota.') ? 'month' : 'month',
                    'sort_order'          => $sortOrder++,
                ]);
            }
        }
    }
}
