<?php

namespace Modules\Assessment\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Assessment\Models\PassportCertification;
use Modules\Assessment\Models\PassportDomainScore;
use Modules\Assessment\Models\PassportEntry;
use Modules\Assessment\Models\PassportImpactHighlight;
use Modules\Assessment\Models\PassportSandboxSummary;
use Modules\Assessment\Models\SandboxSession;
use Modules\Assessment\Models\WorkforceCertification;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\KpiGoal\Models\AiImpactSnapshot;
use Modules\Organization\Models\OrganizationMember;
use App\Shared\Tenancy\Models\Organization;

class SnapshotPassportEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly int     $userId,
        private readonly int     $orgId,
        private readonly ?Carbon $snapshotCutoff = null,
    ) {}

    public function handle(): void
    {
        $cutoff = $this->snapshotCutoff ?? now();

        // Idempotent: không tạo 2 snapshot cùng ngày cho cùng user+org
        $existing = PassportEntry::where('user_id', $this->userId)
            ->where('source_org_id', $this->orgId)
            ->where('entry_type', 'org_tenure')
            ->whereDate('snapshot_at', $cutoff->toDateString())
            ->first();

        if ($existing) {
            Log::info("SnapshotPassportEntryJob: skip — already exists for user={$this->userId} org={$this->orgId} date={$cutoff->toDateString()}");
            return;
        }

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $this->orgId)
            ->where('user_id', $this->userId)
            ->first();

        if (!$profile) {
            Log::info("SnapshotPassportEntryJob: no workforce_profile for user={$this->userId} org={$this->orgId} — skip");
            return;
        }

        $membership = OrganizationMember::where('user_id', $this->userId)
            ->where('organization_id', $this->orgId)
            ->whereIn('status', ['inactive', 'suspended'])
            ->latest('left_at')
            ->first();

        $org = Organization::find($this->orgId);
        if (!$org) {
            Log::warning("SnapshotPassportEntryJob: org={$this->orgId} not found — skip");
            return;
        }

        $hasLateGap = $this->snapshotCutoff !== null && $this->snapshotCutoff->lt(now()->subHours(1));
        $offboardedAt = $hasLateGap ? now() : null;

        DB::transaction(function () use ($profile, $membership, $org, $cutoff, $hasLateGap, $offboardedAt) {

            // ── 1. Header entry ──────────────────────────────────────────
            $entry = PassportEntry::create([
                'uuid'                   => (string) Str::uuid(),
                'user_id'                => $this->userId,
                'entry_type'             => 'org_tenure',
                'source_org_id'          => $this->orgId,
                'source_org_name'        => $org->name,
                'source_org_logo_path'   => $org->logo_path ?? null,
                'snapshot_at'            => $cutoff,
                'offboarded_at'          => $offboardedAt,
                'has_late_offboard_gap'  => $hasLateGap ? 1 : 0,
                'tenure_start'           => $membership?->joined_at?->toDateString(),
                'tenure_end'             => $membership?->left_at?->toDateString()
                                            ?? $cutoff->toDateString(),
                'tenure_months'          => $membership?->joined_at
                    ? (int) $membership->joined_at->diffInMonths($cutoff)
                    : null,
                'job_title_at_exit'      => $membership?->job_title_at_exit,
                'department_at_exit'     => $membership?->department_at_exit,
                'role_at_exit'           => $membership?->role_at_exit,
                'tdwcf_score'            => $profile->tdwcf_score,
                'tdwcf_maturity_level'   => $profile->tdwcf_maturity_level,
                'workforce_trust_score'  => $profile->workforce_trust_score,
                'ai_readiness_score'     => $profile->ai_readiness_score,
                'sandbox_hours_total'    => (int) ($profile->sandbox_hours_total ?? 0),
                'sandbox_score_avg'      => $profile->sandbox_score_avg,
                'certifications_count'   => (int) ($profile->certifications_count ?? 0),
                'highest_cert_level'     => $profile->highest_cert_level,
                'impact_entries_count'   => 0, // updated after highlights inserted
                'visibility'             => 'private',
            ]);

            // ── 2. Domain scores (6 rows) ────────────────────────────────
            $domainMap = [
                'D1' => ['name' => 'Năng lực số cơ bản',     'score' => $profile->score_d1_digital_literacy],
                'D2' => ['name' => 'Năng lực dữ liệu',        'score' => $profile->score_d2_data_literacy],
                'D3' => ['name' => 'Năng lực AI',             'score' => $profile->score_d3_ai_literacy],
                'D4' => ['name' => 'Quy trình & Tự động hoá', 'score' => $profile->score_d4_workflow],
                'D5' => ['name' => 'Đổi mới & Sáng kiến',    'score' => $profile->score_d5_innovation],
                'D6' => ['name' => 'Hiệu suất & KPI',         'score' => $profile->score_d6_performance],
            ];

            $requirements = DB::table('job_title_domain_requirements')
                ->where('job_title_id', $profile->employee?->job_title_id)
                ->get()
                ->keyBy('domain_code');

            foreach ($domainMap as $code => $data) {
                $req = $requirements->get($code);
                PassportDomainScore::create([
                    'passport_entry_id' => $entry->id,
                    'domain_code'       => $code,
                    'domain_name'       => $data['name'],
                    'score'             => $data['score'],
                    'required_score'    => $req?->required_score,
                    'gap'               => ($data['score'] !== null && $req)
                                            ? round($data['score'] - $req->required_score, 2)
                                            : null,
                    'is_critical'       => $req?->is_critical ?? 0,
                ]);
            }

            // ── 3. Certifications active tại cutoff ──────────────────────
            WorkforceCertification::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->where('status', 'active')
                ->where('issued_at', '<=', $cutoff)
                ->where(function ($q) use ($cutoff) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', $cutoff);
                })
                ->with('definition')
                ->get()
                ->each(function ($cert) use ($entry) {
                    if (!$cert->definition) return;
                    PassportCertification::create([
                        'passport_entry_id'       => $entry->id,
                        'cert_definition_id'      => $cert->cert_definition_id,
                        'cert_code'               => $cert->definition->cert_code,
                        'cert_name'               => $cert->definition->name,
                        'cert_type_code'          => $cert->definition->cert_type_code ?? '',
                        'level_code'              => $cert->definition->level_code ?? 'FOUNDATION',
                        'level_order'             => $cert->definition->level_order ?? 1,
                        'issued_at'               => $cert->issued_at->toDateString(),
                        'expires_at'              => $cert->expires_at?->toDateString(),
                        'certificate_number'      => $cert->certificate_number,
                        'composite_score_at_issue' => $cert->composite_score,
                    ]);
                });

            // ── 4. Impact highlights (top 5 theo improvement_pct, cutoff) ─
            $impacts = AiImpactSnapshot::withoutTenant()
                ->where('organization_id', $this->orgId)
                ->where('employee_id', $profile->employee_id)
                ->where('created_at', '<=', $cutoff)
                ->orderByDesc('improvement_pct')
                ->limit(5)
                ->get();

            $impacts->each(function ($impact, $idx) use ($entry) {
                PassportImpactHighlight::create([
                    'passport_entry_id' => $entry->id,
                    'source_impact_id'  => $impact->id,
                    'title'             => $impact->notes ?? $impact->impact_type,
                    'impact_category'   => $impact->impact_category,
                    'impact_type'       => $impact->impact_type,
                    'baseline_value'    => $impact->baseline_value,
                    'achieved_value'    => $impact->achieved_value,
                    'improvement_pct'   => $impact->improvement_pct,
                    'roi_pct'           => $impact->roi_pct,
                    'period_label'      => $impact->period_start?->format('m/Y'),
                    'sort_order'        => $idx,
                ]);
            });

            // Update denormalized count
            $entry->withoutEvents(fn () => $entry->update(['impact_entries_count' => $impacts->count()]));

            // ── 5. Sandbox summaries per environment ─────────────────────
            SandboxSession::withoutTenant()
                ->where('organization_id', $this->orgId)
                ->where('workforce_profile_id', $profile->id)
                ->where('status', 'completed')
                ->where('created_at', '<=', $cutoff)
                ->with('task.environment')
                ->get()
                ->filter(fn ($s) => $s->task?->environment !== null)
                ->groupBy(fn ($s) => $s->task->environment->id)
                ->each(function ($sessions, $envId) use ($entry) {
                    $env = $sessions->first()->task->environment;
                    $totalMinutes = $sessions->sum(function ($s) {
                        return $s->completed_at && $s->started_at
                            ? $s->completed_at->diffInMinutes($s->started_at)
                            : 0;
                    });
                    PassportSandboxSummary::create([
                        'passport_entry_id'  => $entry->id,
                        'sandbox_env_id'     => $envId,
                        'env_code'           => $env->env_code ?? (string) $envId,
                        'env_name'           => $env->name,
                        'sessions_completed' => $sessions->count(),
                        'hours_spent'        => round($totalMinutes / 60, 1),
                        'avg_score'          => round($sessions->avg('final_score') ?? 0, 2),
                    ]);
                });
        });

        Log::info("SnapshotPassportEntryJob: created passport entry for user={$this->userId} org={$this->orgId}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("SnapshotPassportEntryJob FAILED: user={$this->userId} org={$this->orgId}", [
            'error' => $e->getMessage(),
        ]);
    }
}
