<?php

namespace Modules\Assessment\Services;

use Illuminate\Support\Str;
use Modules\Assessment\Models\CareerPathwayStep;
use Modules\Assessment\Models\SandboxSession;
use Modules\Assessment\Models\WorkforceCertification;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Models\WorkforceProfileHistory;

/**
 * Evaluates whether an employee has met the requirements for their current
 * career pathway step and advances their maturity level when all conditions pass.
 *
 * Called after: sandbox session scored, certification issued, or manual trigger.
 *
 * Advancement conditions (per CareerPathwayStep):
 *   required_cert_code           → active WorkforceCertification with that cert code
 *   recommended_sandbox_env_code → at least one passed SandboxSession in that env
 *   (null fields = condition not required)
 */
class CareerLevelService
{
    public const LEVEL_ORDER = [
        'DIGITAL_BEGINNER'     => 0,
        'DIGITAL_AWARE'        => 1,
        'DIGITAL_PRACTITIONER' => 2,
        'DIGITAL_PROFESSIONAL' => 3,
        'DIGITAL_LEADER'       => 4,
    ];

    /**
     * Check if the profile has met requirements for the current step.
     * Returns true and advances level if conditions are satisfied.
     */
    public function checkAndAdvance(WorkforceProfile $profile): bool
    {
        if (! $profile->tdwcf_maturity_level) {
            return false;
        }

        // Already at the top — no step to advance through
        if ($profile->tdwcf_maturity_level === 'DIGITAL_LEADER') {
            return false;
        }

        $step = CareerPathwayStep::where(function ($q) use ($profile) {
                $q->whereNull('organization_id')
                  ->orWhere('organization_id', $profile->organization_id);
            })
            ->where('from_level', $profile->tdwcf_maturity_level)
            ->where('is_active', true)
            ->orderBy('step_order')
            ->first();

        if (! $step || $step->from_level === $step->to_level) {
            return false;
        }

        // ── Cert check ────────────────────────────────────────────────────────
        if ($step->required_cert_code) {
            $hasCert = WorkforceCertification::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->where('status', 'active')
                ->whereHas('definition', fn($q) => $q->where('cert_code', $step->required_cert_code))
                ->exists();

            if (! $hasCert) {
                return false;
            }
        }

        // ── Sandbox check ─────────────────────────────────────────────────────
        if ($step->recommended_sandbox_env_code) {
            $hasPass = SandboxSession::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->where('passed', true)
                ->whereHas('task.environment', fn($q) => $q->where('env_code', $step->recommended_sandbox_env_code))
                ->exists();

            if (! $hasPass) {
                return false;
            }
        }

        // ── Advance ───────────────────────────────────────────────────────────
        $levelBefore = $profile->tdwcf_maturity_level;
        $levelAfter  = $step->to_level;

        $profile->update(['tdwcf_maturity_level' => $levelAfter]);

        WorkforceProfileHistory::create([
            'workforce_profile_id'  => $profile->id,
            'event_type'            => 'career_advancement',
            'source_id'             => $step->id,
            'source_type'           => CareerPathwayStep::class,
            'tdwcf_score_before'    => $profile->tdwcf_score,
            'tdwcf_score_after'     => $profile->tdwcf_score,
            'maturity_level_before' => $levelBefore,
            'maturity_level_after'  => $levelAfter,
            'change_delta'          => 0,
            'recorded_at'           => now(),
        ]);

        return true;
    }

    /**
     * Returns the detailed readiness status for the current step:
     * which conditions are met and which are still pending.
     */
    public function readiness(WorkforceProfile $profile): array
    {
        if (! $profile->tdwcf_maturity_level) {
            return ['step' => null, 'ready' => false, 'conditions' => []];
        }

        $step = CareerPathwayStep::where(function ($q) use ($profile) {
                $q->whereNull('organization_id')
                  ->orWhere('organization_id', $profile->organization_id);
            })
            ->where('from_level', $profile->tdwcf_maturity_level)
            ->where('is_active', true)
            ->orderBy('step_order')
            ->first();

        if (! $step) {
            return ['step' => null, 'ready' => false, 'conditions' => []];
        }

        $conditions = [];
        $allMet     = true;

        if ($step->required_cert_code) {
            $met = WorkforceCertification::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->where('status', 'active')
                ->whereHas('definition', fn($q) => $q->where('cert_code', $step->required_cert_code))
                ->exists();

            $conditions[] = ['type' => 'cert', 'code' => $step->required_cert_code, 'met' => $met];
            if (! $met) $allMet = false;
        }

        if ($step->recommended_sandbox_env_code) {
            $met = SandboxSession::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->where('passed', true)
                ->whereHas('task.environment', fn($q) => $q->where('env_code', $step->recommended_sandbox_env_code))
                ->exists();

            $conditions[] = ['type' => 'sandbox', 'code' => $step->recommended_sandbox_env_code, 'met' => $met];
            if (! $met) $allMet = false;
        }

        return [
            'step'       => $step,
            'ready'      => $allMet && count($conditions) > 0,
            'conditions' => $conditions,
        ];
    }
}
