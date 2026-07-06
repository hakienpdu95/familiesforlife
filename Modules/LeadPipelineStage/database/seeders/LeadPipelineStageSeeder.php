<?php

namespace Modules\LeadPipelineStage\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class LeadPipelineStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['code' => 'new',         'label' => 'Mới',             'color' => '#6b7280', 'sort_order' => 1,  'probability' => 10,  'is_won' => false, 'is_lost' => false],
            ['code' => 'contacted',   'label' => 'Đã liên hệ',      'color' => '#3b82f6', 'sort_order' => 2,  'probability' => 20,  'is_won' => false, 'is_lost' => false],
            ['code' => 'qualified',   'label' => 'Đủ điều kiện',     'color' => '#06b6d4', 'sort_order' => 3,  'probability' => 30,  'is_won' => false, 'is_lost' => false],
            ['code' => 'proposal',    'label' => 'Đã gửi đề xuất',   'color' => '#8b5cf6', 'sort_order' => 4,  'probability' => 60,  'is_won' => false, 'is_lost' => false],
            ['code' => 'negotiation', 'label' => 'Đang đàm phán',    'color' => '#f59e0b', 'sort_order' => 5,  'probability' => 80,  'is_won' => false, 'is_lost' => false],
            ['code' => 'won',         'label' => 'Thành công',       'color' => '#10b981', 'sort_order' => 6,  'probability' => 100, 'is_won' => true,  'is_lost' => false],
            ['code' => 'lost',        'label' => 'Thất bại',         'color' => '#ef4444', 'sort_order' => 7,  'probability' => 0,   'is_won' => false, 'is_lost' => true],
            ['code' => 'unsuitable',  'label' => 'Không phù hợp',    'color' => '#9ca3af', 'sort_order' => 8,  'probability' => 0,   'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            LeadPipelineStage::firstOrCreate(
                ['organization_id' => null, 'code' => $stage['code']],
                array_merge($stage, ['is_global' => true, 'is_active' => true])
            );
        }
    }
}
