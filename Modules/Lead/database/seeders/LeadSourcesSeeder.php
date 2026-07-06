<?php

namespace Modules\Lead\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\LeadSource\Models\LeadSource;

class LeadSourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['code' => 'manual',   'label' => 'Thủ công',           'color' => 'gray',   'icon' => 'ti-pencil',         'sort_order' => 1],
            ['code' => 'survey',   'label' => 'Survey',             'color' => 'purple', 'icon' => 'ti-clipboard-list', 'sort_order' => 2],
            ['code' => 'import',   'label' => 'Import file',        'color' => 'amber',  'icon' => 'ti-upload',         'sort_order' => 3],
            ['code' => 'api',      'label' => 'API',                'color' => 'blue',   'icon' => 'ti-api',            'sort_order' => 4],
            ['code' => 'workflow', 'label' => 'Tự động (Workflow)', 'color' => 'purple', 'icon' => 'ti-robot',          'sort_order' => 5],
            ['code' => 'referral', 'label' => 'Giới thiệu',        'color' => 'teal',   'icon' => 'ti-users',          'sort_order' => 6],
            ['code' => 'event',    'label' => 'Sự kiện',           'color' => 'teal',   'icon' => 'ti-calendar-event', 'sort_order' => 7],
            ['code' => 'website',  'label' => 'Website',            'color' => 'blue',   'icon' => 'ti-world',          'sort_order' => 8],
        ];

        foreach ($sources as $source) {
            LeadSource::firstOrCreate(
                ['code' => $source['code'], 'organization_id' => null],
                array_merge($source, ['organization_id' => null, 'is_global' => 1, 'is_active' => 1])
            );
        }
    }
}
