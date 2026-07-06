<?php

namespace Modules\Assessment\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Employee\Models\Employee;

class WorkforceProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (WorkforceProfile::withoutTenant()->count() > 0) {
            $this->command->info('WorkforceProfiles already exist, skipping.');
            return;
        }

        $org = DB::table('organizations')->first();
        if (! $org) {
            $this->command->warn('No organization found. Cannot seed workforce profiles.');
            return;
        }
        $orgId = $org->id;

        // Load job titles keyed by code
        $jobTitles = DB::table('job_titles')
            ->where('organization_id', $orgId)
            ->get()
            ->keyBy('code');

        // Ensure at least one branch exists (required FK on employees)
        $branchId = DB::table('branches')->where('organization_id', $orgId)->value('id');
        if (! $branchId) {
            $branchId = DB::table('branches')->insertGetId([
                'uuid'            => Str::uuid(),
                'organization_id' => $orgId,
                'parent_id'       => null,
                'path'            => '/',
                'depth'           => 0,
                'name'            => 'Trụ sở chính',
                'code'            => 'HQ',
                'type'            => 'headquarters',
                'status'          => 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Ensure at least one department exists (required FK on employees)
        $deptId = DB::table('departments')->where('organization_id', $orgId)->value('id');
        if (! $deptId) {
            $deptId = DB::table('departments')->insertGetId([
                'uuid'            => Str::uuid(),
                'organization_id' => $orgId,
                'branch_id'       => $branchId,
                'parent_id'       => null,
                'path'            => '/',
                'depth'           => 0,
                'name'            => 'Phòng Tổng hợp',
                'code'            => 'GEN',
                'function'        => 'general',
                'status'          => 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // Load a sandbox task for sandbox sessions
        $sandboxTaskId = DB::table('sandbox_tasks')->value('id');

        // Load cert definitions for seeding certifications
        $certDefPractitioner = DB::table('certification_definitions')
            ->whereNull('organization_id')
            ->where('level_code', 'PRACTITIONER')
            ->first();
        $certDefProfessional = DB::table('certification_definitions')
            ->whereNull('organization_id')
            ->where('level_code', 'PROFESSIONAL')
            ->first();
        $certDefLeader = DB::table('certification_definitions')
            ->whereNull('organization_id')
            ->where('level_code', 'LEADER')
            ->first();

        // Get an admin user id for evaluator_user_id
        $adminUserId = DB::table('users')->first()?->id;

        /**
         * Profile definitions:
         * Each entry: name, email, job_code, maturity_level, career_goal,
         *             d1..d6 scores, tdwcf, employment_type, status,
         *             sandbox (bool), cert_level (null|PRACTITIONER|PROFESSIONAL|LEADER),
         *             impact (bool)
         */
        $profiles = [
            [
                'name'            => 'Nguyễn Thị Lan',
                'email'           => 'demo.emp01@thuchoc.vn',
                'job_code'        => 'INTERN',
                'maturity_level'  => 'BEGINNER',
                'career_goal'     => 'Trở thành nhân viên chính thức và nâng cao kỹ năng số',
                'd1' => 22, 'd2' => 18, 'd3' => 12, 'd4' => 18, 'd5' => 15, 'd6' => 20,
                'tdwcf'           => 17.2,
                'employment_type' => 'intern',
                'status'          => 'probation',
                'kpi_avg'         => 65.0,
                'sandbox'         => false,
                'cert_level'      => null,
                'impact'          => false,
            ],
            [
                'name'            => 'Trần Văn Minh',
                'email'           => 'demo.emp02@thuchoc.vn',
                'job_code'        => 'STAFF',
                'maturity_level'  => 'AWARE',
                'career_goal'     => 'Nâng cao năng lực dữ liệu và quy trình số hóa',
                'd1' => 38, 'd2' => 32, 'd3' => 28, 'd4' => 35, 'd5' => 28, 'd6' => 34,
                'tdwcf'           => 33.2,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 72.0,
                'sandbox'         => false,
                'cert_level'      => null,
                'impact'          => false,
            ],
            [
                'name'            => 'Lê Thị Hoa',
                'email'           => 'demo.emp03@thuchoc.vn',
                'job_code'        => 'STAFF',
                'maturity_level'  => 'AWARE',
                'career_goal'     => 'Ứng dụng AI vào công việc hành chính và báo cáo',
                'd1' => 42, 'd2' => 38, 'd3' => 35, 'd4' => 40, 'd5' => 32, 'd6' => 38,
                'tdwcf'           => 38.5,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 75.0,
                'sandbox'         => false,
                'cert_level'      => null,
                'impact'          => false,
            ],
            [
                'name'            => 'Phạm Văn Đức',
                'email'           => 'demo.emp04@thuchoc.vn',
                'job_code'        => 'STF',
                'maturity_level'  => 'PRACTITIONER',
                'career_goal'     => 'Đạt chứng nhận AI Practitioner trong 6 tháng',
                'd1' => 55, 'd2' => 50, 'd3' => 52, 'd4' => 58, 'd5' => 45, 'd6' => 54,
                'tdwcf'           => 53.2,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 80.0,
                'sandbox'         => true,
                'cert_level'      => 'PRACTITIONER',
                'impact'          => true,
            ],
            [
                'name'            => 'Hoàng Thị Mai',
                'email'           => 'demo.emp05@thuchoc.vn',
                'job_code'        => 'STF',
                'maturity_level'  => 'PRACTITIONER',
                'career_goal'     => 'Trở thành chuyên viên cấp cao về chuyển đổi số',
                'd1' => 60, 'd2' => 58, 'd3' => 62, 'd4' => 60, 'd5' => 52, 'd6' => 58,
                'tdwcf'           => 58.8,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 82.0,
                'sandbox'         => true,
                'cert_level'      => 'PRACTITIONER',
                'impact'          => true,
            ],
            [
                'name'            => 'Vũ Minh Tuấn',
                'email'           => 'demo.emp06@thuchoc.vn',
                'job_code'        => 'SR_STF',
                'maturity_level'  => 'PRACTITIONER',
                'career_goal'     => 'Xây dựng bộ quy trình AI cho phòng ban',
                'd1' => 65, 'd2' => 62, 'd3' => 68, 'd4' => 65, 'd5' => 58, 'd6' => 62,
                'tdwcf'           => 63.8,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 85.0,
                'sandbox'         => true,
                'cert_level'      => 'PRACTITIONER',
                'impact'          => true,
            ],
            [
                'name'            => 'Đỗ Thị Thanh',
                'email'           => 'demo.emp07@thuchoc.vn',
                'job_code'        => 'SR_STF',
                'maturity_level'  => 'PROFESSIONAL',
                'career_goal'     => 'Lên Senior Manager và chuyên sâu về AI Strategy',
                'd1' => 70, 'd2' => 68, 'd3' => 72, 'd4' => 70, 'd5' => 65, 'd6' => 68,
                'tdwcf'           => 70.0,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 88.0,
                'sandbox'         => true,
                'cert_level'      => 'PROFESSIONAL',
                'impact'          => true,
            ],
            [
                'name'            => 'Bùi Văn Hùng',
                'email'           => 'demo.emp08@thuchoc.vn',
                'job_code'        => 'TEAM_L',
                'maturity_level'  => 'PROFESSIONAL',
                'career_goal'     => 'Đào tạo nhóm về AI và nâng cao năng suất tập thể',
                'd1' => 74, 'd2' => 72, 'd3' => 75, 'd4' => 74, 'd5' => 70, 'd6' => 73,
                'tdwcf'           => 73.4,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 88.0,
                'sandbox'         => true,
                'cert_level'      => 'PROFESSIONAL',
                'impact'          => true,
            ],
            [
                'name'            => 'Nguyễn Văn Sơn',
                'email'           => 'demo.emp09@thuchoc.vn',
                'job_code'        => 'MGR',
                'maturity_level'  => 'PROFESSIONAL',
                'career_goal'     => 'Dẫn dắt chuyển đổi số toàn phòng trong năm nay',
                'd1' => 78, 'd2' => 76, 'd3' => 78, 'd4' => 78, 'd5' => 74, 'd6' => 76,
                'tdwcf'           => 76.6,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 90.0,
                'sandbox'         => true,
                'cert_level'      => 'PROFESSIONAL',
                'impact'          => true,
            ],
            [
                'name'            => 'Trần Thị Thu',
                'email'           => 'demo.emp10@thuchoc.vn',
                'job_code'        => 'MGR',
                'maturity_level'  => 'LEADER',
                'career_goal'     => 'Xây dựng chiến lược nhân sự số cho toàn tổ chức',
                'd1' => 84, 'd2' => 82, 'd3' => 85, 'd4' => 84, 'd5' => 80, 'd6' => 82,
                'tdwcf'           => 83.0,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 92.0,
                'sandbox'         => true,
                'cert_level'      => 'LEADER',
                'impact'          => true,
            ],
            [
                'name'            => 'Lê Văn Khoa',
                'email'           => 'demo.emp11@thuchoc.vn',
                'job_code'        => 'DIR',
                'maturity_level'  => 'LEADER',
                'career_goal'     => 'Chuyển đổi toàn bộ năng lực tổ chức sang mô hình AI-First',
                'd1' => 88, 'd2' => 86, 'd3' => 90, 'd4' => 88, 'd5' => 85, 'd6' => 87,
                'tdwcf'           => 87.3,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 94.0,
                'sandbox'         => true,
                'cert_level'      => 'LEADER',
                'impact'          => true,
            ],
            [
                'name'            => 'Phạm Thị Nga',
                'email'           => 'demo.emp12@thuchoc.vn',
                'job_code'        => 'CEO',
                'maturity_level'  => 'LEADER',
                'career_goal'     => 'Định hướng chiến lược số hóa toàn diện doanh nghiệp 2026-2030',
                'd1' => 92, 'd2' => 90, 'd3' => 93, 'd4' => 92, 'd5' => 90, 'd6' => 91,
                'tdwcf'           => 91.5,
                'employment_type' => 'full_time',
                'status'          => 'active',
                'kpi_avg'         => 96.0,
                'sandbox'         => true,
                'cert_level'      => 'LEADER',
                'impact'          => true,
            ],
        ];

        $now    = Carbon::now();
        $seeded = 0;

        foreach ($profiles as $index => $p) {
            $empCode = sprintf('DEMO%02d', $index + 1);

            // 1. Create or find User
            $user = DB::table('users')->where('email', $p['email'])->first();
            if (! $user) {
                $userId = DB::table('users')->insertGetId([
                    'name'              => $p['name'],
                    'email'             => $p['email'],
                    'password'          => Hash::make('password'),
                    'organization_id'   => $orgId,
                    'email_verified_at' => $now,
                    'is_active'         => true,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ]);
            } else {
                $userId = $user->id;
            }

            // 2. Resolve job title
            $jobTitleRow = $jobTitles->get($p['job_code']) ?? $jobTitles->first();
            $jobTitleId  = $jobTitleRow->id;
            $jobLevel    = $jobTitleRow->level;

            // 3. Create Employee
            $employee = Employee::withoutTenant()
                ->where('organization_id', $orgId)
                ->where('employee_code', $empCode)
                ->first();

            if (! $employee) {
                // Use DB::table to bypass observer (ActivityLog) which requires all fillable attrs
                $employeeId = DB::table('employees')->insertGetId([
                    'uuid'            => Str::uuid(),
                    'organization_id' => $orgId,
                    'user_id'         => $userId,
                    'branch_id'       => $branchId,
                    'department_id'   => $deptId,
                    'job_title_id'    => $jobTitleId,
                    'employee_code'   => $empCode,
                    'full_name'       => $p['name'],
                    'email'           => $p['email'],
                    'status'          => $p['status'],
                    'employment_type' => $p['employment_type'],
                    'hired_at'        => $now->copy()->subMonths(rand(3, 36))->toDateString(),
                    'snap_job_title'  => $jobTitleRow->name,
                    'snap_job_level'  => $jobLevel,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
                $employee = Employee::withoutTenant()->find($employeeId);
            }

            // 4. Compute derived scores
            $d1 = $p['d1'];
            $d2 = $p['d2'];
            $d3 = $p['d3'];
            $d4 = $p['d4'];
            $d5 = $p['d5'];
            $d6 = $p['d6'];

            $aiReadiness     = round(($d3 + $d4) / 2, 2);
            $digitalScore    = round(($d1 + $d2 + $d3) / 3, 2);
            $productivityScore = round(($d4 + $d6) / 2, 2);
            $innovationScore = round($d5 * 1.0, 2);
            $aiScore         = round($d3 * 1.0, 2);

            // Cert score for trust calc
            $certScore = match ($p['cert_level']) {
                'LEADER'       => 100.0,
                'PROFESSIONAL' => 75.0,
                'PRACTITIONER' => 50.0,
                default        => 0.0,
            };

            // Sandbox avg estimate for profiles that have sandbox sessions
            $sandboxAvg = $p['sandbox'] ? round(($p['tdwcf'] * 0.9 + rand(0, 10)), 2) : null;

            // workforce_trust_score = TDWCF×30% + Cert×25% + KPI×20% + Sandbox×15% + Portfolio×10%
            $trustScore = round(
                $p['tdwcf']     * 0.30 +
                $certScore      * 0.25 +
                $p['kpi_avg']   * 0.20 +
                ($sandboxAvg ?? 0) * 0.15 +
                0               * 0.10,
                2
            );

            $certCount      = $p['cert_level'] ? 1 : 0;
            $completeness   = $this->computeCompleteness($p);

            $assessedAt  = $now->copy()->subDays(rand(7, 90));
            $assessedAt2 = $assessedAt->copy()->subMonths(rand(2, 5));

            // 5. Create WorkforceProfile
            $profileRow = DB::table('workforce_profiles')
                ->where('organization_id', $orgId)
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->first();

            if (! $profileRow) {
                $profileId = DB::table('workforce_profiles')->insertGetId([
                    'uuid'                      => Str::uuid(),
                    'organization_id'           => $orgId,
                    'user_id'                   => $userId,
                    'employee_id'               => $employee->id,
                    'tdwcf_score'               => $p['tdwcf'],
                    'tdwcf_maturity_level'      => $p['maturity_level'],
                    'tdwcf_assessed_at'         => $assessedAt,
                    'score_d1_digital_literacy' => $d1,
                    'score_d2_data_literacy'    => $d2,
                    'score_d3_ai_literacy'      => $d3,
                    'score_d4_workflow'         => $d4,
                    'score_d5_innovation'       => $d5,
                    'score_d6_performance'      => $d6,
                    'digital_score'             => $digitalScore,
                    'ai_score'                  => $aiScore,
                    'productivity_score'        => $productivityScore,
                    'innovation_score'          => $innovationScore,
                    'growth_score'              => round(rand(1, 8) * 0.5, 2),
                    'ai_readiness_score'        => $aiReadiness,
                    'workforce_trust_score'     => $trustScore,
                    'sandbox_sessions_total'    => $p['sandbox'] ? rand(1, 4) : 0,
                    'sandbox_hours_total'       => $p['sandbox'] ? rand(2, 12) : 0,
                    'sandbox_score_avg'         => $sandboxAvg,
                    'sandbox_last_completed_at' => $p['sandbox'] ? $assessedAt->copy()->subDays(rand(1, 30)) : null,
                    'certifications_count'      => $certCount,
                    'highest_cert_level'        => $p['cert_level'],
                    'highest_cert_issued_at'    => $certCount > 0 ? $assessedAt->copy()->subDays(rand(5, 20)) : null,
                    'highest_cert_expires_at'   => $certCount > 0 ? $assessedAt->copy()->addMonths(24) : null,
                    'kpi_achievement_avg'       => $p['kpi_avg'],
                    'impact_score'              => $p['impact'] ? round($p['tdwcf'] * 0.85 + rand(0, 8), 2) : null,
                    'career_goal'               => $p['career_goal'],
                    'current_learning_path'     => $this->inferLearningPath($p['maturity_level']),
                    'profile_completeness_pct'  => $completeness,
                    'created_at'                => $now,
                    'updated_at'                => $now,
                ]);
                $profileRow = DB::table('workforce_profiles')->where('id', $profileId)->first();
            }
            $profileId = $profileRow->id;

            // 6. WorkforceProfileHistory — 2 records (initial + recent)
            $scoreBefore = round($p['tdwcf'] - rand(3, 12), 2);
            $maturityBefore = $this->inferPreviousMaturity($p['maturity_level']);

            DB::table('workforce_profile_histories')->insert([
                [
                    'workforce_profile_id'  => $profileId,
                    'event_type'            => 'assessment',
                    'source_id'             => null,
                    'source_type'           => null,
                    'tdwcf_score_before'    => null,
                    'tdwcf_score_after'     => $scoreBefore,
                    'maturity_level_before' => null,
                    'maturity_level_after'  => $maturityBefore,
                    'change_delta'          => null,
                    'notes'                 => 'Đánh giá lần đầu — nhập hệ thống',
                    'recorded_at'           => $assessedAt2,
                    'created_at'            => $assessedAt2,
                    'updated_at'            => $assessedAt2,
                ],
                [
                    'workforce_profile_id'  => $profileId,
                    'event_type'            => 'assessment',
                    'source_id'             => null,
                    'source_type'           => null,
                    'tdwcf_score_before'    => $scoreBefore,
                    'tdwcf_score_after'     => $p['tdwcf'],
                    'maturity_level_before' => $maturityBefore,
                    'maturity_level_after'  => $p['maturity_level'],
                    'change_delta'          => round($p['tdwcf'] - $scoreBefore, 2),
                    'notes'                 => 'Đánh giá định kỳ — cập nhật hồ sơ Digital Twin',
                    'recorded_at'           => $assessedAt,
                    'created_at'            => $assessedAt,
                    'updated_at'            => $assessedAt,
                ],
            ]);

            // 7. SandboxSession for profiles #4+ (index >= 3)
            if ($p['sandbox'] && $sandboxTaskId) {
                $sessStarted = $assessedAt->copy()->subDays(rand(5, 25));
                $duration    = rand(12, 20);
                $qScore      = round($p['tdwcf'] * 1.0 + rand(-5, 5), 2);
                $pScore      = round($p['tdwcf'] * 0.95 + rand(-3, 5), 2);
                $aScore      = round($p['tdwcf'] * 0.90 + rand(-5, 8), 2);
                $finalScore  = round($qScore * 0.40 + $pScore * 0.35 + $aScore * 0.25, 2);

                DB::table('sandbox_sessions')->insert([
                    'uuid'                  => Str::uuid(),
                    'organization_id'       => $orgId,
                    'sandbox_task_id'       => $sandboxTaskId,
                    'workforce_profile_id'  => $profileId,
                    'user_id'               => $userId,
                    'status'                => 'completed',
                    'started_at'            => $sessStarted,
                    'submitted_at'          => $sessStarted->copy()->addMinutes($duration),
                    'completed_at'          => $sessStarted->copy()->addMinutes($duration + 5),
                    'duration_minutes'      => $duration,
                    'quality_score'         => $qScore,
                    'productivity_score'    => $pScore,
                    'ai_adoption_score'     => $aScore,
                    'final_score'           => $finalScore,
                    'passed'                => $finalScore >= 60 ? 1 : 0,
                    'evaluator_user_id'     => $adminUserId,
                    'evaluated_at'          => $sessStarted->copy()->addMinutes($duration + 30),
                    'feedback'              => 'Hoàn thành tốt bài tập thực hành AI. Cần cải thiện tốc độ xử lý.',
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ]);
            }

            // 8. WorkforceCertification for profiles #7+ (PROFESSIONAL or LEADER)
            if ($p['cert_level'] && in_array($p['cert_level'], ['PROFESSIONAL', 'LEADER'])) {
                $certDefId = match ($p['cert_level']) {
                    'LEADER'       => $certDefLeader?->id,
                    'PROFESSIONAL' => $certDefProfessional?->id,
                    default        => null,
                };

                if ($certDefId) {
                    $issuedAt  = $assessedAt->copy()->subDays(rand(5, 15));
                    $expiresAt = $issuedAt->copy()->addMonths(36);
                    $certNum   = strtoupper(sprintf('WFC-%s-%04d', $p['cert_level'][0], rand(1000, 9999)));

                    DB::table('workforce_certifications')->insert([
                        'uuid'                      => Str::uuid(),
                        'organization_id'           => $orgId,
                        'workforce_profile_id'      => $profileId,
                        'cert_definition_id'        => $certDefId,
                        'assessment_score_at_issue' => $p['tdwcf'],
                        'sandbox_score_at_issue'    => $sandboxAvg,
                        'impact_score_at_issue'     => $p['impact'] ? round($p['tdwcf'] * 0.85, 2) : null,
                        'portfolio_score_at_issue'  => null,
                        'composite_score_at_issue'  => round(
                            $p['tdwcf']                      * 0.30 +
                            ($sandboxAvg ?? 0)               * 0.25 +
                            ($p['impact'] ? $p['tdwcf'] * 0.85 : 0) * 0.25 +
                            0                               * 0.20,
                            2
                        ),
                        'status'             => 'active',
                        'issued_at'          => $issuedAt,
                        'expires_at'         => $expiresAt,
                        'revoked_at'         => null,
                        'revoked_reason'     => null,
                        'certificate_number' => $certNum,
                        'qr_code_url'        => null,
                        'digital_badge_url'  => null,
                        'issued_by'          => $adminUserId,
                        'human_reviewer_id'  => $adminUserId,
                        'reviewed_at'        => $issuedAt,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);
                }
            }

            // 9. AiImpactSnapshot for profiles #4-12 (index >= 3)
            if ($p['impact']) {
                $periodStart = $now->copy()->subMonths(3)->startOfMonth();
                $periodEnd   = $now->copy()->subMonths(1)->endOfMonth();
                $baseline    = rand(60, 80);
                $achieved    = round($baseline * (1 + ($p['tdwcf'] / 500)), 2);
                $investCost  = rand(5, 20) * 1000000; // VND
                $benefit     = round($investCost * (1 + $p['tdwcf'] / 200), 2);

                // Calculate improvement_pct and roi_pct manually (mirrors AiImpactSnapshot::saving hook)
                $improvePct = $baseline != 0
                    ? round(($achieved - $baseline) / $baseline * 100, 2)
                    : 0;
                $roiPct = $investCost > 0
                    ? round(($benefit - $investCost) / $investCost * 100, 2)
                    : 0;

                DB::table('ai_impact_snapshots')->insert([
                    'organization_id'  => $orgId,
                    'employee_id'      => $employee->id,
                    'kpi_goal_id'      => null,
                    'impact_category'  => 'productivity',
                    'impact_type'      => 'time_saving',
                    'baseline_value'   => $baseline,
                    'achieved_value'   => $achieved,
                    'improvement_pct'  => $improvePct,
                    'investment_cost'  => $investCost,
                    'benefit_value'    => $benefit,
                    'roi_pct'          => $roiPct,
                    'period_start'     => $periodStart->toDateString(),
                    'period_end'       => $periodEnd->toDateString(),
                    'notes'            => 'Dữ liệu tác động AI — seeded cho demo',
                    'created_by'       => $adminUserId,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);

                // Second snapshot for quality category
                $baseline2   = rand(70, 85);
                $achieved2   = round($baseline2 * (1 + ($p['tdwcf'] / 600)), 2);
                $invest2     = rand(3, 10) * 1000000;
                $benefit2    = round($invest2 * (1 + $p['tdwcf'] / 250), 2);
                $improvePct2 = $baseline2 != 0
                    ? round(($achieved2 - $baseline2) / $baseline2 * 100, 2)
                    : 0;
                $roiPct2 = $invest2 > 0
                    ? round(($benefit2 - $invest2) / $invest2 * 100, 2)
                    : 0;

                DB::table('ai_impact_snapshots')->insert([
                    'organization_id'  => $orgId,
                    'employee_id'      => $employee->id,
                    'kpi_goal_id'      => null,
                    'impact_category'  => 'quality',
                    'impact_type'      => 'error_reduction',
                    'baseline_value'   => $baseline2,
                    'achieved_value'   => $achieved2,
                    'improvement_pct'  => $improvePct2,
                    'investment_cost'  => $invest2,
                    'benefit_value'    => $benefit2,
                    'roi_pct'          => $roiPct2,
                    'period_start'     => $periodStart->toDateString(),
                    'period_end'       => $periodEnd->toDateString(),
                    'notes'            => 'Cải thiện chất lượng công việc nhờ ứng dụng AI',
                    'created_by'       => $adminUserId,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }

            $seeded++;
        }

        $this->command->info("Seeded {$seeded} workforce profiles with full data.");
    }

    private function inferLearningPath(string $maturityLevel): string
    {
        return match ($maturityLevel) {
            'BEGINNER'     => 'AI Foundations & Digital Literacy',
            'AWARE'        => 'AI Tools for Daily Work',
            'PRACTITIONER' => 'AI Workflow Automation',
            'PROFESSIONAL' => 'AI Strategy & Advanced Applications',
            'LEADER'       => 'AI Transformation Leadership',
            default        => 'Digital Upskilling Program',
        };
    }

    private function inferPreviousMaturity(string $current): string
    {
        return match ($current) {
            'AWARE'        => 'BEGINNER',
            'PRACTITIONER' => 'AWARE',
            'PROFESSIONAL' => 'PRACTITIONER',
            'LEADER'       => 'PROFESSIONAL',
            default        => 'BEGINNER',
        };
    }

    private function computeCompleteness(array $p): int
    {
        $score = 40; // base: has D1-D6 scores
        $score += 10; // has career_goal
        $score += 10; // has tdwcf
        if ($p['cert_level']) {
            $score += 15;
        }
        if ($p['sandbox']) {
            $score += 15;
        }
        if ($p['impact']) {
            $score += 10;
        }
        return min(100, $score);
    }
}
