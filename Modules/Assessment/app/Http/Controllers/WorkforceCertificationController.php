<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Assessment\Models\CertificationDefinition;
use Modules\Assessment\Models\WorkforceCertification;
use Modules\Assessment\Models\WorkforceProfile;

class WorkforceCertificationController extends Controller
{
    public function index(Request $request): View
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        $certifications = $profile
            ? WorkforceCertification::withoutTenant()
                ->where('workforce_profile_id', $profile->id)
                ->with('definition')
                ->orderByDesc('issued_at')
                ->get()
            : collect();

        $available = CertificationDefinition::where(function ($q) use ($orgId) {
                $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
            })
            ->where('is_active', true)
            ->orderBy('cert_type_code')
            ->orderBy('level_order')
            ->get();

        // Bug fix: cert_code lives on the definition, not on WorkforceCertification
        $earnedCodes = $certifications
            ->where('status', 'active')
            ->map(fn($c) => $c->definition?->cert_code)
            ->filter()
            ->values()
            ->all();

        // Part 3: readiness — what each definition still needs
        $readiness = $profile
            ? $this->computeReadiness($profile, $available, $earnedCodes)
            : [];

        return view('assessment::certifications.index', compact(
            'profile', 'certifications', 'available', 'earnedCodes', 'readiness'
        ));
    }

    /**
     * Returns per-cert_code readiness: conditions met/unmet.
     * Only computed for definitions the employee has NOT yet earned.
     */
    private function computeReadiness(WorkforceProfile $profile, Collection $available, array $earnedCodes): array
    {
        $readiness = [];

        foreach ($available as $def) {
            if (in_array($def->cert_code, $earnedCodes)) {
                $readiness[$def->cert_code] = ['earned' => true, 'conditions' => []];
                continue;
            }

            $conditions = [];
            $allMet     = true;

            if ($def->min_workforce_score !== null) {
                $current = $profile->tdwcf_score ?? 0;
                $met     = $current >= $def->min_workforce_score;
                if (! $met) $allMet = false;
                $conditions[] = [
                    'label'    => 'TDWCF ≥ '.$def->min_workforce_score,
                    'met'      => $met,
                    'current'  => round($current, 1),
                    'required' => $def->min_workforce_score,
                    'unit'     => 'điểm',
                ];
            }

            if ($def->min_kpi_achievement_pct !== null) {
                $current = $profile->kpi_achievement_avg ?? 0;
                $met     = $current >= $def->min_kpi_achievement_pct;
                if (! $met) $allMet = false;
                $conditions[] = [
                    'label'    => 'KPI ≥ '.$def->min_kpi_achievement_pct.'%',
                    'met'      => $met,
                    'current'  => round($current, 1),
                    'required' => $def->min_kpi_achievement_pct,
                    'unit'     => '%',
                ];
            }

            if ($def->min_sandbox_hours !== null) {
                $current = $profile->sandbox_hours_total ?? 0;
                $met     = $current >= $def->min_sandbox_hours;
                if (! $met) $allMet = false;
                $conditions[] = [
                    'label'    => 'Sandbox ≥ '.$def->min_sandbox_hours.'h',
                    'met'      => $met,
                    'current'  => round($current, 1),
                    'required' => $def->min_sandbox_hours,
                    'unit'     => 'giờ',
                ];
            }

            if ($def->min_sandbox_score !== null) {
                $current = $profile->sandbox_score_avg ?? 0;
                $met     = $current >= $def->min_sandbox_score;
                if (! $met) $allMet = false;
                $conditions[] = [
                    'label'    => 'Điểm sandbox ≥ '.$def->min_sandbox_score,
                    'met'      => $met,
                    'current'  => round($current, 1),
                    'required' => $def->min_sandbox_score,
                    'unit'     => 'điểm',
                ];
            }

            if ($def->requires_impact_score) {
                $current = $profile->impact_score ?? 0;
                $met     = $current > 0;
                if (! $met) $allMet = false;
                $conditions[] = [
                    'label'    => 'Có điểm AI Impact',
                    'met'      => $met,
                    'current'  => round($current, 1),
                    'required' => null,
                    'unit'     => null,
                ];
            }

            if ($def->requires_portfolio_approval) {
                $met = $profile->portfolios()->where('approval_status', 'approved')->exists();
                if (! $met) $allMet = false;
                $conditions[] = [
                    'label'    => 'Portfolio được duyệt',
                    'met'      => $met,
                    'current'  => null,
                    'required' => null,
                    'unit'     => null,
                ];
            }

            $readiness[$def->cert_code] = [
                'earned'     => false,
                'ready'      => $allMet && count($conditions) > 0,
                'conditions' => $conditions,
            ];
        }

        return $readiness;
    }
}
