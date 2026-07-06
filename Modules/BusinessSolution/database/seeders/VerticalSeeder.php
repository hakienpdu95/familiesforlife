<?php

namespace Modules\BusinessSolution\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\BusinessSolution\Models\Vertical;

/**
 * Danh mục ngành dọc (vertical) tối thiểu để đăng ký 3 Business Solution bespoke
 * hiện có (AI-TXNG, AI-OCOP → agriculture; AI-WORKFORCE → workforce).
 */
class VerticalSeeder extends Seeder
{
    private array $verticals = [
        ['code' => 'agriculture', 'name' => 'Nông nghiệp',        'icon' => 'leaf'],
        ['code' => 'workforce',   'name' => 'Nhân sự & Tuyển dụng', 'icon' => 'users'],
    ];

    public function run(): void
    {
        foreach ($this->verticals as $vertical) {
            Vertical::updateOrCreate(
                ['code' => $vertical['code']],
                array_merge(['status' => 'active'], $vertical),
            );
        }

        $this->command?->info('  ✓ Verticals seeded.');
    }
}
