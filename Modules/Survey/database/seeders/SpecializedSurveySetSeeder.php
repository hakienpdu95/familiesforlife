<?php

namespace Modules\Survey\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Models\Survey;

class SpecializedSurveySetSeeder extends Seeder
{
    public function run(): void
    {
        $sets = [
            [
                'specialized_set_code' => 'B1_SALES',
                'slug'                 => 'tdwcf-b1-sales',
                'title'                => 'TDWCF — Bộ câu hỏi Sales / Kinh doanh',
                'focus'                => 'D3_AI_LITERACY, D4_WORKFLOW, D6_PERFORMANCE',
            ],
            [
                'specialized_set_code' => 'B2_HR',
                'slug'                 => 'tdwcf-b2-hr',
                'title'                => 'TDWCF — Bộ câu hỏi Nhân sự (HR)',
                'focus'                => 'D1_DIGITAL_LITERACY, D5_INNOVATION, D6_PERFORMANCE',
            ],
            [
                'specialized_set_code' => 'B3_FINANCE',
                'slug'                 => 'tdwcf-b3-finance',
                'title'                => 'TDWCF — Bộ câu hỏi Kế toán / Tài chính',
                'focus'                => 'D2_DATA_LITERACY, D4_WORKFLOW, D6_PERFORMANCE',
            ],
            [
                'specialized_set_code' => 'B4_OPS',
                'slug'                 => 'tdwcf-b4-ops',
                'title'                => 'TDWCF — Bộ câu hỏi Vận hành / Operations',
                'focus'                => 'D2_DATA_LITERACY, D4_WORKFLOW, D6_PERFORMANCE',
            ],
            [
                'specialized_set_code' => 'B5_IT',
                'slug'                 => 'tdwcf-b5-it',
                'title'                => 'TDWCF — Bộ câu hỏi IT / Kỹ thuật',
                'focus'                => 'D3_AI_LITERACY, D4_WORKFLOW, D5_INNOVATION',
            ],
            [
                'specialized_set_code' => 'B6_LEADERSHIP',
                'slug'                 => 'tdwcf-b6-leadership',
                'title'                => 'TDWCF — Bộ câu hỏi Lãnh đạo / Quản lý',
                'focus'                => 'D3_AI_LITERACY, D5_INNOVATION, D6_PERFORMANCE',
            ],
            [
                'specialized_set_code' => 'B7_EDUCATION',
                'slug'                 => 'tdwcf-b7-education',
                'title'                => 'TDWCF — Bộ câu hỏi Giáo dục / Đào tạo',
                'focus'                => 'D1_DIGITAL_LITERACY, D2_DATA_LITERACY, D5_INNOVATION',
            ],
        ];

        foreach ($sets as $i => $set) {
            if (Survey::where('slug', $set['slug'])->exists()) {
                $this->command->info('Survey "' . $set['slug'] . '" đã tồn tại, bỏ qua.');
                continue;
            }

            Survey::create([
                'title'                => $set['title'],
                'slug'                 => $set['slug'],
                'assessment_code'      => 'TDWCF',
                'specialized_set_code' => $set['specialized_set_code'],
                'status'               => SurveyStatus::Draft,
                'version'              => 1,
                'allow_multiple_responses' => false,
            ]);

            $this->command->info('Created specialized survey: ' . $set['slug']);
        }
    }
}
