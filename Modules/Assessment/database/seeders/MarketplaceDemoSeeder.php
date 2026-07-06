<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seed demo data cho Assessment Marketplace:
 *  1. 3 tài khoản tự do (free, no org) — email đã xác minh
 *  2. 5 chiến dịch đánh giá mở từ các tổ chức demo
 *
 * Chạy sau AssessmentDatabaseSeeder (cần sandbox_tasks và organizations tồn tại).
 *
 * Tài khoản demo:
 *   free.user01@demo.local  /  Demo@123!  — trust_level=2
 *   free.user02@demo.local  /  Demo@123!  — trust_level=2
 *   free.user03@demo.local  /  Demo@123!  — trust_level=1 (email only)
 */
class MarketplaceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFreeUsers();
        $this->seedCampaigns();
    }

    // ── 1. Tài khoản tự do ────────────────────────────────────────────────────

    private function seedFreeUsers(): void
    {
        $now = now();

        $users = [
            [
                'name'              => 'Nguyễn Thị Lan',
                'email'             => 'free.user01@demo.local',
                'email_verified_at' => $now,
                'trust_level'       => 2,
            ],
            [
                'name'              => 'Trần Văn Minh',
                'email'             => 'free.user02@demo.local',
                'email_verified_at' => $now,
                'trust_level'       => 2,
            ],
            [
                'name'              => 'Lê Thị Hương',
                'email'             => 'free.user03@demo.local',
                'email_verified_at' => $now,
                'trust_level'       => 1,   // chỉ email — để test eligibility Lv1
            ],
        ];

        $created = 0;
        foreach ($users as $data) {
            $exists = DB::table('users')->where('email', $data['email'])->exists();
            if ($exists) continue;

            DB::table('users')->insert([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => Hash::make('Demo@123!'),
                'account_type'      => 'free',
                'organization_id'   => null,
                'email_verified_at' => $data['email_verified_at'],
                'trust_level'       => $data['trust_level'],
                'is_active'         => true,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
            $created++;
        }

        $this->command->info("  ✓ Free demo users: {$created} created (skipped existing)");
        $this->command->table(
            ['Email', 'Trust Level', 'Có thể join campaign'],
            [
                ['free.user01@demo.local', 'Lv2', 'Tất cả (min_trust ≤ 2)'],
                ['free.user02@demo.local', 'Lv2', 'Tất cả (min_trust ≤ 2)'],
                ['free.user03@demo.local', 'Lv1 — email only', 'Campaign min_trust_level=1'],
            ]
        );
    }

    // ── 2. Demo campaigns ─────────────────────────────────────────────────────

    private function seedCampaigns(): void
    {
        // Lấy org_id từ slug để không hardcode id dễ vỡ khi fresh seed
        $orgMap = DB::table('organizations')->pluck('id', 'slug');

        $demoOrgId = $orgMap['demo-organization']
            ?? DB::table('organizations')->where('name', 'like', '%Demo%')->value('id');

        $htxAvsa  = $orgMap['htx-nong-nghiep-avsa']
            ?? DB::table('organizations')->where('name', 'like', '%AVSA%')->value('id');

        $htxTra   = $orgMap['htx-tra-hoa-vang-hoa-son']
            ?? DB::table('organizations')->where('name', 'like', '%Trà hoa%')->value('id');

        $htxDuoc  = $orgMap['htx-duoc-lieu-ba-che']
            ?? DB::table('organizations')->where('name', 'like', '%Dược liệu%')->value('id');

        $htxOcop  = $orgMap['htx-ocop-dam-ha']
            ?? DB::table('organizations')->where('name', 'like', '%OCOP%')->value('id');

        // Task ids: 1=email, 2=bảng tổng hợp, 3=data clean, 4=phân tích khách hàng,
        //           5=viết JD, 6=SOP onboarding, 7=báo cáo kinh doanh
        $now = now();

        $campaigns = [

            // ── Campaign 1: Kinh doanh AI ─────────────────────────────────────
            [
                'org_id'       => $demoOrgId,
                'title'        => 'Tuyển Chuyên viên Kinh doanh AI',
                'description'  => "Demo Organization đang tìm kiếm ứng viên có khả năng ứng dụng AI vào quy trình kinh doanh.\n\nBạn sẽ hoàn thành 2 bài đánh giá thực tế: phân tích profile khách hàng và soạn email chuyên nghiệp bằng AI.",
                'job_title'    => 'Chuyên viên Kinh doanh',
                'department'   => 'Phòng Kinh doanh',
                'min_trust'    => 2,
                'min_score'    => null,
                'open_until'   => $now->copy()->addDays(30),
                'max_part'     => 10,
                'domains'      => [
                    ['code' => 'D3', 'min_score' => 3.0, 'required' => true],
                    ['code' => 'D1', 'min_score' => null, 'required' => false],
                ],
                'tasks'        => [
                    ['task_id' => 4, 'required' => true,  'sort' => 1],
                    ['task_id' => 1, 'required' => false, 'sort' => 2],
                ],
            ],

            // ── Campaign 2: Dữ liệu & Phân tích ──────────────────────────────
            [
                'org_id'       => $htxAvsa ?? $demoOrgId,
                'title'        => 'Nhân viên Dữ liệu & Phân tích',
                'description'  => "HTX Nông nghiệp AVSA cần người xử lý và phân tích dữ liệu vận hành bằng công cụ AI.\n\nBài đánh giá gồm 2 task thực tế về làm sạch dữ liệu và tạo báo cáo tổng hợp.",
                'job_title'    => 'Chuyên viên Dữ liệu',
                'department'   => 'Phòng Vận hành',
                'min_trust'    => 2,
                'min_score'    => null,
                'open_until'   => $now->copy()->addDays(21),
                'max_part'     => 5,
                'domains'      => [
                    ['code' => 'D2', 'min_score' => 3.5, 'required' => true],
                    ['code' => 'D4', 'min_score' => null, 'required' => false],
                ],
                'tasks'        => [
                    ['task_id' => 3, 'required' => true,  'sort' => 1],
                    ['task_id' => 2, 'required' => false, 'sort' => 2],
                ],
            ],

            // ── Campaign 3: Nhân sự ───────────────────────────────────────────
            [
                'org_id'       => $htxTra ?? $demoOrgId,
                'title'        => 'Chuyên viên Nhân sự (AI-augmented)',
                'description'  => "HTX Trà hoa vàng Hoa Sơn tìm ứng viên HR có khả năng dùng AI để tối ưu quy trình tuyển dụng và onboarding.\n\nBài đánh giá: Viết JD & câu hỏi phỏng vấn bằng AI + thiết kế SOP onboarding.",
                'job_title'    => 'Chuyên viên Nhân sự',
                'department'   => 'Phòng Nhân sự',
                'min_trust'    => 2,
                'min_score'    => null,
                'open_until'   => $now->copy()->addDays(14),
                'max_part'     => null,
                'domains'      => [
                    ['code' => 'D4', 'min_score' => 3.0, 'required' => true],
                    ['code' => 'D1', 'min_score' => null, 'required' => false],
                ],
                'tasks'        => [
                    ['task_id' => 5, 'required' => true,  'sort' => 1],
                    ['task_id' => 6, 'required' => true,  'sort' => 2],
                ],
            ],

            // ── Campaign 4: Lãnh đạo AI — min_trust=1 ────────────────────────
            [
                'org_id'       => $htxDuoc ?? $demoOrgId,
                'title'        => 'Trợ lý Ban Giám đốc — AI Strategy',
                'description'  => "HTX Dược liệu Ba Chẽ tìm ứng viên cấp cao có tư duy chiến lược và khả năng phân tích báo cáo bằng AI.\n\n**Yêu cầu tối thiểu: Trust Level 1** (chỉ cần xác minh email).\n\nBài đánh giá: Phân tích báo cáo kinh doanh và đề xuất chiến lược AI.",
                'job_title'    => 'Trợ lý Giám đốc',
                'department'   => 'Ban Giám đốc',
                'min_trust'    => 1,
                'min_score'    => null,
                'open_until'   => $now->copy()->addDays(45),
                'max_part'     => 3,
                'domains'      => [
                    ['code' => 'D6', 'min_score' => 4.0, 'required' => true],
                    ['code' => 'D5', 'min_score' => null, 'required' => false],
                ],
                'tasks'        => [
                    ['task_id' => 7, 'required' => true, 'sort' => 1],
                ],
            ],

            // ── Campaign 5: Vận hành ──────────────────────────────────────────
            [
                'org_id'       => $htxOcop ?? $demoOrgId,
                'title'        => 'Chuyên viên Vận hành & Quy trình',
                'description'  => "HTX OCOP Đầm Hà cần ứng viên xây dựng và tối ưu quy trình vận hành bằng AI.\n\nBài đánh giá gồm 2 task: thiết kế SOP và phân tích/làm sạch dữ liệu vận hành.",
                'job_title'    => 'Chuyên viên Vận hành',
                'department'   => 'Phòng Vận hành',
                'min_trust'    => 2,
                'min_score'    => 3.0,
                'open_until'   => $now->copy()->addDays(28),
                'max_part'     => 8,
                'domains'      => [
                    ['code' => 'D4', 'min_score' => 3.0, 'required' => true],
                    ['code' => 'D2', 'min_score' => null, 'required' => false],
                ],
                'tasks'        => [
                    ['task_id' => 6, 'required' => true,  'sort' => 1],
                    ['task_id' => 3, 'required' => false, 'sort' => 2],
                ],
            ],
        ];

        $created = 0;

        foreach ($campaigns as $data) {
            if (! $data['org_id']) continue;

            // Idempotent: bỏ qua nếu tổ chức đó đã có campaign cùng title
            $exists = DB::table('open_assessment_campaigns')
                ->where('organization_id', $data['org_id'])
                ->where('title', $data['title'])
                ->exists();

            if ($exists) continue;

            $campaignId = DB::table('open_assessment_campaigns')->insertGetId([
                'uuid'                  => (string) Str::uuid(),
                'order_column'          => 0,
                'organization_id'       => $data['org_id'],
                'title'                 => $data['title'],
                'description'           => $data['description'],
                'target_job_title_label'=> $data['job_title'],
                'target_department_label' => $data['department'],
                'min_trust_level'       => $data['min_trust'],
                'min_tdwcf_score'       => $data['min_score'],
                'status'                => 'open',
                'open_from'             => $now,
                'open_until'            => $data['open_until'],
                'max_participants'      => $data['max_part'],
                'is_anonymous_to_org'   => true,
                'participants_count'    => 0,
                'completed_count'       => 0,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            // Domain requirements
            foreach ($data['domains'] as $domain) {
                DB::table('campaign_domain_requirements')->insert([
                    'campaign_id' => $campaignId,
                    'domain_code' => $domain['code'],
                    'min_score'   => $domain['min_score'] ?? 0.0,
                    'is_required' => $domain['required'],
                ]);
            }

            // Sandbox tasks
            foreach ($data['tasks'] as $taskData) {
                DB::table('campaign_sandbox_tasks')->insert([
                    'campaign_id'     => $campaignId,
                    'sandbox_task_id' => $taskData['task_id'],
                    'is_required'     => $taskData['required'],
                    'sort_order'      => $taskData['sort'],
                ]);
            }

            $created++;
        }

        $this->command->info("  ✓ Demo campaigns: {$created} created (skipped existing)");
        $this->command->table(
            ['Campaign', 'Org', 'Min Trust', 'Deadline', 'Slots'],
            collect($campaigns)
                ->filter(fn ($c) => (bool) $c['org_id'])
                ->map(fn ($c) => [
                    $c['title'],
                    DB::table('organizations')->where('id', $c['org_id'])->value('name') ?? '—',
                    'Lv' . $c['min_trust'],
                    $c['open_until']->format('d/m/Y'),
                    $c['max_part'] ? $c['max_part'] . ' slots' : 'Không giới hạn',
                ])
                ->values()->toArray()
        );
    }
}
