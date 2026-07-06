<?php

namespace Modules\JobTitle\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobTitleDatabaseSeeder extends Seeder
{
    /**
     * Seed bộ chức danh chuẩn (is_system=1, is_locked=1) cho mọi org.
     * Chỉ seed vào org đầu tiên (id=1) theo convention của project.
     */
    public function run(): void
    {
        $orgId = DB::table('organizations')->value('id');
        if (! $orgId) {
            return;
        }

        $now = now();

        $jobTitles = [
            // Executive (level 18-20)
            ['code' => 'CEO',   'name' => 'Tổng Giám đốc',          'category' => 'executive',  'level' => 20],
            ['code' => 'COO',   'name' => 'Giám đốc Điều hành',     'category' => 'executive',  'level' => 19],
            ['code' => 'CFO',   'name' => 'Giám đốc Tài chính',     'category' => 'executive',  'level' => 18],
            ['code' => 'CTO',   'name' => 'Giám đốc Công nghệ',     'category' => 'executive',  'level' => 18],
            // Manager (level 13-17)
            ['code' => 'DIR',   'name' => 'Giám đốc',               'category' => 'manager',    'level' => 17],
            ['code' => 'VP',    'name' => 'Phó Giám đốc',           'category' => 'manager',    'level' => 16],
            ['code' => 'MGR',   'name' => 'Trưởng phòng',           'category' => 'manager',    'level' => 14],
            ['code' => 'DMGR',  'name' => 'Phó trưởng phòng',       'category' => 'manager',    'level' => 13],
            // Supervisor (level 9-12)
            ['code' => 'TEAM_L','name' => 'Trưởng nhóm',            'category' => 'supervisor', 'level' => 12],
            ['code' => 'SPV',   'name' => 'Giám sát viên',          'category' => 'supervisor', 'level' => 10],
            ['code' => 'SR_SPV','name' => 'Giám sát viên cấp cao',  'category' => 'supervisor', 'level' => 11],
            // Staff (level 5-8)
            ['code' => 'SR_STF','name' => 'Chuyên viên cấp cao',    'category' => 'staff',      'level' => 8],
            ['code' => 'STF',   'name' => 'Chuyên viên',            'category' => 'staff',      'level' => 6],
            ['code' => 'STAFF', 'name' => 'Nhân viên',              'category' => 'staff',      'level' => 5],
            ['code' => 'JR_STF','name' => 'Nhân viên mới',          'category' => 'staff',      'level' => 5],
            // Intern (level 1-2)
            ['code' => 'INTERN','name' => 'Thực tập sinh',          'category' => 'intern',     'level' => 1],
            ['code' => 'PROBAT','name' => 'Nhân viên thử việc',     'category' => 'intern',     'level' => 2],
            // Consultant (level 10-15)
            ['code' => 'CONS',  'name' => 'Tư vấn viên',            'category' => 'consultant', 'level' => 10],
            ['code' => 'SR_CONS','name' => 'Tư vấn viên cấp cao',   'category' => 'consultant', 'level' => 13],
        ];

        foreach ($jobTitles as $jt) {
            $exists = DB::table('job_titles')
                ->where('organization_id', $orgId)
                ->where('code', $jt['code'])
                ->exists();

            if (! $exists) {
                DB::table('job_titles')->insert([
                    'uuid'            => Str::uuid(),
                    'organization_id' => $orgId,
                    'code'            => $jt['code'],
                    'name'            => $jt['name'],
                    'category'        => $jt['category'],
                    'level'           => $jt['level'],
                    'description'     => null,
                    'is_system'       => 1,
                    'is_locked'       => 1,
                    'is_active'       => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }
}
