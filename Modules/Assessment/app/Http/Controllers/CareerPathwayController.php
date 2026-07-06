<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assessment\Models\CareerPathwayStep;
use Modules\Assessment\Models\CertificationDefinition;
use Modules\Assessment\Models\SandboxEnvironment;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Services\CareerLevelService;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Models\KcLearningProgress;

class CareerPathwayController extends Controller
{
    public function index(Request $request): View
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        $steps = CareerPathwayStep::where(function ($q) use ($orgId) {
                $q->whereNull('organization_id')->orWhere('organization_id', $orgId);
            })
            ->where('is_active', true)
            ->orderBy('step_order')
            ->get();

        $currentLevel = $profile?->tdwcf_maturity_level ?? 'DIGITAL_BEGINNER';

        $levelOrder = [
            'DIGITAL_BEGINNER'     => 0,
            'DIGITAL_AWARE'        => 1,
            'DIGITAL_PRACTITIONER' => 2,
            'DIGITAL_PROFESSIONAL' => 3,
            'DIGITAL_LEADER'       => 4,
        ];
        $currentOrder = $levelOrder[$currentLevel] ?? 0;

        // Pre-load sandbox env names and cert names for richer display
        $envCodes  = $steps->pluck('recommended_sandbox_env_code')->filter()->unique();
        $certCodes = $steps->pluck('required_cert_code')->filter()->unique();

        $sandboxEnvs = SandboxEnvironment::whereIn('env_code', $envCodes)
            ->get(['id', 'env_code', 'name'])
            ->keyBy('env_code');

        $certDefs = CertificationDefinition::whereIn('cert_code', $certCodes)
            ->get(['cert_code', 'name'])
            ->keyBy('cert_code');

        // Readiness check for the current step (for CTA and progress badge)
        $readiness = null;
        if ($profile) {
            $readiness = app(CareerLevelService::class)->readiness($profile);
        }

        // KC items for the current step (loaded by tag slug/name)
        $currentStepKcItems = collect();
        $kcProgress         = collect();

        $currentStep = $steps->firstWhere('from_level', $currentLevel);
        if ($currentStep?->recommended_kc_tag) {
            $tag = $currentStep->recommended_kc_tag;
            $currentStepKcItems = KcItem::approved()
                ->whereHas('tags', fn($q) => $q->where('slug', $tag)->orWhere('name', $tag))
                ->orderBy('title')
                ->get(['id', 'title', 'type', 'domain_code', 'difficulty']);

            if ($currentStepKcItems->isNotEmpty()) {
                $kcProgress = KcLearningProgress::withoutTenant()
                    ->where('user_id', $user->id)
                    ->whereIn('kc_item_id', $currentStepKcItems->pluck('id'))
                    ->get()
                    ->keyBy('kc_item_id');
            }
        }

        return view('assessment::career-pathway.index', compact(
            'profile', 'steps', 'currentLevel', 'currentOrder', 'levelOrder',
            'sandboxEnvs', 'certDefs', 'readiness', 'currentStepKcItems', 'kcProgress'
        ));
    }

    /**
     * Manual trigger: re-evaluate and advance level if conditions are met.
     */
    public function checkLevel(Request $request): RedirectResponse
    {
        $user  = $request->user();
        $orgId = TenantContext::getOrganizationId();

        $profile = WorkforceProfile::withoutTenant()
            ->where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->first();

        if (! $profile) {
            return back()->with('error', 'Bạn chưa có hồ sơ Digital Twin.');
        }

        $advanced = app(CareerLevelService::class)->checkAndAdvance($profile);

        return back()->with(
            $advanced ? 'success' : 'info',
            $advanced
                ? 'Chúc mừng! Bạn đã thăng lên cấp độ mới: '.$profile->fresh()->tdwcf_maturity_level
                : 'Bạn chưa đủ điều kiện thăng cấp. Hãy hoàn thành các yêu cầu còn lại.'
        );
    }
}
