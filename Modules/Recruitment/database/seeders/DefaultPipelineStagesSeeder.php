<?php

namespace Modules\Recruitment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Recruitment\Models\RcPipelineStage;

class DefaultPipelineStagesSeeder extends Seeder
{
    private const DEFAULTS = [
        ['name' => 'Sàng lọc hồ sơ',    'stage_type' => 'screening',   'sort_order' => 10, 'color_hex' => '#6366f1'],
        ['name' => 'Phỏng vấn HR',       'stage_type' => 'interview',   'sort_order' => 20, 'color_hex' => '#f59e0b'],
        ['name' => 'Phỏng vấn chuyên môn','stage_type' => 'interview',  'sort_order' => 30, 'color_hex' => '#3b82f6'],
        ['name' => 'Offer',              'stage_type' => 'offer',       'sort_order' => 40, 'color_hex' => '#10b981'],
        ['name' => 'Đã nhận việc',       'stage_type' => 'hired',       'sort_order' => 50, 'color_hex' => '#22c55e'],
        ['name' => 'Từ chối',            'stage_type' => 'rejected',    'sort_order' => 60, 'color_hex' => '#ef4444'],
    ];

    public function run(): void
    {
        $orgs = \App\Shared\Tenancy\Models\Organization::all('id');

        foreach ($orgs as $org) {
            $hasStages = RcPipelineStage::withoutTenant()
                ->where('org_id', $org->id)
                ->exists();

            if ($hasStages) {
                continue;
            }

            foreach (self::DEFAULTS as $stage) {
                RcPipelineStage::withoutTenant()->create(array_merge($stage, [
                    'org_id'            => $org->id,
                    'require_score'     => false,
                    'send_notification' => true,
                    'is_active'         => true,
                ]));
            }
        }
    }
}
