<?php

namespace Modules\Lead\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class LeadPipelineStagesSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['code' => 'new',         'label' => 'Mới',              'color' => 'gray',   'sort_order' => 1, 'probability' => 10,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'contacted',   'label' => 'Đã liên hệ',       'color' => 'blue',   'sort_order' => 2, 'probability' => 20,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'qualified',   'label' => 'Đủ điều kiện',     'color' => 'teal',   'sort_order' => 3, 'probability' => 40,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'proposal',    'label' => 'Đã gửi đề xuất',   'color' => 'purple', 'sort_order' => 4, 'probability' => 60,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'negotiation', 'label' => 'Đang đàm phán',    'color' => 'amber',  'sort_order' => 5, 'probability' => 80,  'is_won' => 0, 'is_lost' => 0],
            ['code' => 'won',         'label' => 'Thành công',       'color' => 'green',  'sort_order' => 6, 'probability' => 100, 'is_won' => 1, 'is_lost' => 0],
            ['code' => 'lost',        'label' => 'Thất bại',         'color' => 'red',    'sort_order' => 7, 'probability' => 0,   'is_won' => 0, 'is_lost' => 1],
            ['code' => 'unqualified', 'label' => 'Không phù hợp',    'color' => 'gray',   'sort_order' => 8, 'probability' => 0,   'is_won' => 0, 'is_lost' => 1],
        ];

        foreach ($stages as $stage) {
            LeadPipelineStage::firstOrCreate(
                ['code' => $stage['code'], 'organization_id' => null],
                array_merge($stage, ['organization_id' => null, 'is_global' => 1, 'is_active' => 1])
            );
        }
    }
}
