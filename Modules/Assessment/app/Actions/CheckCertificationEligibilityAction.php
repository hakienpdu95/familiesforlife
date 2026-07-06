<?php

namespace Modules\Assessment\Actions;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Events\CertificationIssued;
use Modules\Assessment\Models\CertificationDefinition;
use Modules\Assessment\Models\WorkforceCertification;
use Modules\Assessment\Models\WorkforceProfile;

/**
 * Sau SandboxCompleted (hoặc khi nào gọi), kiểm tra xem workforce_profile
 * đã đủ điều kiện nhận certification nào chưa, và tự động cấp nếu đủ điều kiện.
 *
 * Điều kiện từ spec §4.4:
 *   FOUNDATION    : workforce_score ≥ 40 + sandbox_foundation_complete
 *   PRACTITIONER  : workforce_score ≥ 61 + KPI ≥ 70%  + 1 case study
 *   PROFESSIONAL  : workforce_score ≥ 76 + sandbox ≥ 20h + impact_score > 0
 *   LEADER        : workforce_score ≥ 91 + portfolio approved
 */
class CheckCertificationEligibilityAction
{
    use AsAction;

    public function handle(WorkforceProfile $profile, ?string $certTypeCode = null): array
    {
        $issued = [];

        $definitions = CertificationDefinition::where('is_active', true)
            ->when($certTypeCode, fn($q) => $q->where('cert_type_code', $certTypeCode))
            ->orderBy('level_order')
            ->get();

        foreach ($definitions as $def) {
            // Skip nếu đã có chứng nhận này ở trạng thái active
            $alreadyHas = WorkforceCertification::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->where('cert_definition_id', $def->id)
                ->where('status', 'active')
                ->exists();

            if ($alreadyHas) {
                continue;
            }

            if (! $this->meetsConditions($profile, $def)) {
                continue;
            }

            $cert = $this->issueCertification($profile, $def);
            $issued[] = $cert;
            event(new CertificationIssued($cert, $profile));
        }

        return $issued;
    }

    private function meetsConditions(WorkforceProfile $profile, CertificationDefinition $def): bool
    {
        $score = $profile->tdwcf_score ?? 0;

        if ($def->min_workforce_score && $score < $def->min_workforce_score) {
            return false;
        }

        if ($def->min_kpi_achievement_pct) {
            $kpi = $profile->kpi_achievement_avg ?? 0;
            if ($kpi < $def->min_kpi_achievement_pct) {
                return false;
            }
        }

        if ($def->min_sandbox_hours) {
            if ($profile->sandbox_hours_total < $def->min_sandbox_hours) {
                return false;
            }
        }

        if ($def->min_sandbox_score) {
            $sandboxAvg = $profile->sandbox_score_avg ?? 0;
            if ($sandboxAvg < $def->min_sandbox_score) {
                return false;
            }
        }

        if ($def->requires_impact_score) {
            if (! $profile->impact_score || $profile->impact_score <= 0) {
                return false;
            }
        }

        if ($def->requires_portfolio_approval) {
            $hasApproved = $profile->portfolios()
                ->where('approval_status', 'approved')
                ->exists();
            if (! $hasApproved) {
                return false;
            }
        }

        return true;
    }

    private function issueCertification(WorkforceProfile $profile, CertificationDefinition $def): WorkforceCertification
    {
        $validityMonths = $def->validity_months ?? 24;

        $compositeScore = round(
            ($profile->tdwcf_score        ?? 0) * 0.30 +
            ($profile->sandbox_score_avg  ?? 0) * 0.25 +
            ($profile->impact_score       ?? 0) * 0.25 +
            0 * 0.20, // portfolio_score — calculated separately
        2);

        return WorkforceCertification::create([
            'organization_id'          => $profile->organization_id,
            'workforce_profile_id'     => $profile->id,
            'cert_definition_id'       => $def->id,
            'assessment_score_at_issue'=> $profile->tdwcf_score,
            'sandbox_score_at_issue'   => $profile->sandbox_score_avg,
            'impact_score_at_issue'    => $profile->impact_score,
            'composite_score_at_issue' => $compositeScore,
            'status'                   => 'active',
            'issued_at'                => now(),
            'expires_at'               => now()->addMonths($validityMonths),
            'certificate_number'       => strtoupper('CERT-' . Str::random(10)),
            'uuid'                     => (string) Str::uuid(),
        ]);
    }
}
