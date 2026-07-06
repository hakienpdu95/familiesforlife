<?php

namespace Modules\Recruitment\Actions\Backend;

use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcApplicationStageLog;
use Modules\Recruitment\Models\RcCandidate;
use Modules\Recruitment\Models\RcPipelineStage;

class ImportFromMarketplaceAction
{
    use AsAction;

    /**
     * Import một ứng viên từ Marketplace vào Recruitment.
     *
     * @param  array{
     *   full_name: string,
     *   email: string,
     *   phone: ?string,
     *   current_title: ?string,
     *   current_company: ?string,
     *   years_experience: ?int,
     *   skills: ?string,
     *   linkedin_url: ?string,
     *   mkt_applicant_id: string,
     *   mkt_application_id: string,
     *   jp_job_post_id: ?string,
     *   cover_letter: ?string,
     *   expected_salary: ?float,
     * } $data
     */
    public function handle(array $data): RcApplication
    {
        $orgId = TenantContext::getOrganizationId();

        // BR-RC-001: dedup candidate theo email + org
        $candidate = RcCandidate::withoutGlobalScope('tenant')
            ->where('org_id', $orgId)
            ->where('email', $data['email'])
            ->first();

        if ($candidate === null) {
            $candidate = RcCandidate::create([
                'org_id'           => $orgId,
                'full_name'        => $data['full_name'],
                'email'            => $data['email'],
                'phone'            => $data['phone'] ?? null,
                'current_title'    => $data['current_title'] ?? null,
                'current_company'  => $data['current_company'] ?? null,
                'years_experience' => $data['years_experience'] ?? null,
                'skills'           => $data['skills'] ?? null,
                'linkedin_url'     => $data['linkedin_url'] ?? null,
                'source'           => 'marketplace',
                'mkt_applicant_id' => $data['mkt_applicant_id'],
                'status'           => 'active',
                'created_by'       => auth()->id(),
            ]);
        } else {
            // Cập nhật mkt_applicant_id nếu chưa có
            if (empty($candidate->mkt_applicant_id)) {
                $candidate->update(['mkt_applicant_id' => $data['mkt_applicant_id']]);
            }
        }

        $firstStage = RcPipelineStage::query()
            ->where('org_id', $orgId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->first();

        $application = RcApplication::create([
            'org_id'             => $orgId,
            'candidate_id'       => $candidate->id,
            'current_stage_id'   => $firstStage?->id,
            'jp_job_post_id'     => $data['jp_job_post_id'] ?? null,
            'mkt_application_id' => $data['mkt_application_id'],
            'apply_source'       => 'marketplace',
            'cover_letter'       => $data['cover_letter'] ?? null,
            'expected_salary'    => $data['expected_salary'] ?? null,
            'status'             => 'active',
        ]);

        if ($firstStage) {
            RcApplicationStageLog::create([
                'application_id' => $application->id,
                'stage_id'       => $firstStage->id,
                'result'         => 'passed',
                'note'           => 'Import từ Marketplace',
                'actioned_by'    => auth()->id(),
            ]);
        }

        return $application;
    }
}
