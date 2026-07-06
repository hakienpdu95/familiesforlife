<?php

namespace Modules\LeadSource\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\LeadSource\Models\LeadSource;

class LeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['code' => 'website',        'label' => 'Website',          'icon' => 'mdi:web',                  'color' => '#3b82f6', 'sort_order' => 1],
            ['code' => 'referral',        'label' => 'Giới thiệu',       'icon' => 'mdi:account-group',        'color' => '#10b981', 'sort_order' => 2],
            ['code' => 'social_media',    'label' => 'Mạng xã hội',      'icon' => 'mdi:share-variant',        'color' => '#8b5cf6', 'sort_order' => 3],
            ['code' => 'cold_call',       'label' => 'Gọi lạnh',         'icon' => 'mdi:phone-outgoing',       'color' => '#f59e0b', 'sort_order' => 4],
            ['code' => 'email_campaign',  'label' => 'Email Marketing',  'icon' => 'mdi:email-fast',           'color' => '#06b6d4', 'sort_order' => 5],
            ['code' => 'trade_show',      'label' => 'Hội chợ / Sự kiện','icon' => 'mdi:calendar-star',       'color' => '#ec4899', 'sort_order' => 6],
            ['code' => 'survey',          'label' => 'Khảo sát',         'icon' => 'mdi:clipboard-list',      'color' => '#6366f1', 'sort_order' => 7],
            ['code' => 'other',           'label' => 'Khác',             'icon' => 'mdi:dots-horizontal',     'color' => '#9ca3af', 'sort_order' => 8],
        ];

        foreach ($sources as $source) {
            LeadSource::firstOrCreate(
                ['organization_id' => null, 'code' => $source['code']],
                array_merge($source, ['is_global' => true, 'is_active' => true])
            );
        }
    }
}
