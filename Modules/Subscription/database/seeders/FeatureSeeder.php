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
     *
     * Chỉ liệt kê feature-slug ĐANG THỰC SỰ ĐƯỢC ENFORCE trong codebase — mọi slug từng có
     * ở đây (`module.task/sop/hr/recruitment/project/kc/marketplace/ai`, `limit.employees`,
     * `limit.workflows`, `limit.projects`, `limit.storage_gb`, `limit.ai_agents`,
     * `flag.api_access/audit_log/advanced_reports/sso/white_label/custom_domain`,
     * `quota.ai_requests/ai_tokens/workflow_runs/email_notifications`) đã bị bỏ vì hoặc (a)
     * không có module/khái niệm tương ứng nào tồn tại trong `Modules/`, hoặc (b) có hạ tầng
     * thật (Workflow, Media storage, ActivityLog, token tracking trên Aicem) nhưng CHƯA từng
     * được dây nối để Subscription enforce — đổi giá trị các slug đó giữa các gói không tạo
     * ra khác biệt hành vi nào trên thực tế.
     *
     * `module.*` — 4 module đang được `feature:module.x` middleware gate:
     * `Modules/Lead/routes/web.php`, `Modules/Customer/routes/web.php`,
     * `Modules/Assessment/routes/web.php`, `Modules/WorkflowAutomation/routes/web.php`.
     *
     * `limit.members` — giới hạn số User/organization_id, enforce tại
     * `Modules/User/app/Http/Controllers/UserController.php`.
     */
    private array $featureMatrix = [
        'starter' => [
            'module.lead'       => '0',
            'module.customer'   => '0',
            'module.workflow'   => '0',
            'module.assessment' => '0',
            'limit.members'     => '3',
        ],
        'growth' => [
            'module.lead'       => '1',
            'module.customer'   => '1',
            'module.workflow'   => '1',
            'module.assessment' => '1',
            'limit.members'     => '15',
        ],
        'scale' => [
            'module.lead'       => '1',
            'module.customer'   => '1',
            'module.workflow'   => '1',
            'module.assessment' => '1',
            'limit.members'     => '50',
        ],
        'enterprise' => [
            'module.lead'       => '1',
            'module.customer'   => '1',
            'module.workflow'   => '1',
            'module.assessment' => '1',
            'limit.members'     => '0',
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
