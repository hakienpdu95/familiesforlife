<?php

namespace Modules\JobPosting\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\JobPosting\Models\JpBenefitMaster;
use Modules\JobPosting\Models\JpSkillMaster;

class JobPostingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSkillMasters();
        $this->seedBenefitMasters();
    }

    private function seedSkillMasters(): void
    {
        $skills = [
            // Backend
            ['name' => 'PHP', 'category' => 'Backend'],
            ['name' => 'Laravel', 'category' => 'Backend'],
            ['name' => 'Node.js', 'category' => 'Backend'],
            ['name' => 'Python', 'category' => 'Backend'],
            ['name' => 'Java', 'category' => 'Backend'],
            ['name' => 'Go', 'category' => 'Backend'],
            ['name' => 'Ruby on Rails', 'category' => 'Backend'],
            ['name' => 'MySQL', 'category' => 'Database'],
            ['name' => 'PostgreSQL', 'category' => 'Database'],
            ['name' => 'MongoDB', 'category' => 'Database'],
            ['name' => 'Redis', 'category' => 'Database'],
            // Frontend
            ['name' => 'JavaScript', 'category' => 'Frontend'],
            ['name' => 'TypeScript', 'category' => 'Frontend'],
            ['name' => 'React', 'category' => 'Frontend'],
            ['name' => 'Vue.js', 'category' => 'Frontend'],
            ['name' => 'Angular', 'category' => 'Frontend'],
            ['name' => 'HTML/CSS', 'category' => 'Frontend'],
            ['name' => 'Tailwind CSS', 'category' => 'Frontend'],
            // Mobile
            ['name' => 'React Native', 'category' => 'Mobile'],
            ['name' => 'Flutter', 'category' => 'Mobile'],
            ['name' => 'Swift', 'category' => 'Mobile'],
            ['name' => 'Kotlin', 'category' => 'Mobile'],
            // DevOps
            ['name' => 'Docker', 'category' => 'DevOps'],
            ['name' => 'Kubernetes', 'category' => 'DevOps'],
            ['name' => 'AWS', 'category' => 'DevOps'],
            ['name' => 'CI/CD', 'category' => 'DevOps'],
            ['name' => 'Git', 'category' => 'DevOps'],
            // Soft Skills
            ['name' => 'Giao tiếp', 'category' => 'Soft Skills'],
            ['name' => 'Làm việc nhóm', 'category' => 'Soft Skills'],
            ['name' => 'Giải quyết vấn đề', 'category' => 'Soft Skills'],
            ['name' => 'Quản lý thời gian', 'category' => 'Soft Skills'],
            ['name' => 'Tư duy phân tích', 'category' => 'Soft Skills'],
            // Finance & Accounting
            ['name' => 'Kế toán quản trị', 'category' => 'Finance'],
            ['name' => 'Excel nâng cao', 'category' => 'Finance'],
            ['name' => 'QuickBooks', 'category' => 'Finance'],
            // Sales & Marketing
            ['name' => 'Digital Marketing', 'category' => 'Marketing'],
            ['name' => 'SEO/SEM', 'category' => 'Marketing'],
            ['name' => 'Content Marketing', 'category' => 'Marketing'],
            ['name' => 'Social Media', 'category' => 'Marketing'],
            ['name' => 'B2B Sales', 'category' => 'Sales'],
        ];

        foreach ($skills as $skill) {
            JpSkillMaster::firstOrCreate(
                ['organization_id' => null, 'slug' => Str::slug($skill['name'])],
                [
                    'uuid'            => Str::uuid()->toString(),
                    'organization_id' => null,
                    'name'            => $skill['name'],
                    'slug'            => Str::slug($skill['name']),
                    'category'        => $skill['category'],
                    'is_active'       => true,
                ]
            );
        }
    }

    private function seedBenefitMasters(): void
    {
        $benefits = [
            ['name' => 'Bảo hiểm sức khỏe cao cấp',    'icon' => 'ti-heart',           'category' => 'health'],
            ['name' => 'Khám sức khỏe định kỳ',         'icon' => 'ti-stethoscope',     'category' => 'health'],
            ['name' => 'Thưởng KPI',                    'icon' => 'ti-trophy',          'category' => 'finance'],
            ['name' => 'Thưởng tháng 13',               'icon' => 'ti-cash',            'category' => 'finance'],
            ['name' => 'Cổ phiếu / ESOP',               'icon' => 'ti-chart-bar',       'category' => 'finance'],
            ['name' => 'Phụ cấp ăn trưa',               'icon' => 'ti-tools-kitchen-2', 'category' => 'other'],
            ['name' => 'Phụ cấp đi lại',                'icon' => 'ti-car',             'category' => 'other'],
            ['name' => 'Đào tạo và phát triển',         'icon' => 'ti-school',          'category' => 'learning'],
            ['name' => 'Học bổng / Hỗ trợ học phí',    'icon' => 'ti-books',           'category' => 'learning'],
            ['name' => 'Chứng chỉ nghề nghiệp',         'icon' => 'ti-certificate',     'category' => 'learning'],
            ['name' => 'Làm việc linh hoạt',            'icon' => 'ti-clock',           'category' => 'work_life'],
            ['name' => 'Remote / Hybrid',               'icon' => 'ti-home-2',          'category' => 'work_life'],
            ['name' => 'Nghỉ phép không giới hạn',      'icon' => 'ti-beach',           'category' => 'work_life'],
            ['name' => 'Thể dục thể thao',              'icon' => 'ti-run',             'category' => 'work_life'],
            ['name' => 'MacBook / Laptop cao cấp',      'icon' => 'ti-device-laptop',   'category' => 'equipment'],
            ['name' => 'Màn hình / Thiết bị hỗ trợ',   'icon' => 'ti-device-desktop',  'category' => 'equipment'],
        ];

        foreach ($benefits as $benefit) {
            JpBenefitMaster::firstOrCreate(
                ['organization_id' => null, 'name' => $benefit['name']],
                [
                    'uuid'            => Str::uuid()->toString(),
                    'organization_id' => null,
                    'name'            => $benefit['name'],
                    'icon'            => $benefit['icon'],
                    'category'        => $benefit['category'],
                    'is_active'       => true,
                ]
            );
        }
    }
}
