<?php
namespace Modules\Marketplace\Actions\Backend;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Enums\ApplicationImportStatus;
use Modules\Marketplace\Models\MktApplication;

class ImportApplicationToRcAction
{
    use AsAction;

    public function handle(MktApplication $application, User $importedBy): array
    {
        if ($application->import_status->value === ApplicationImportStatus::Imported->value) {
            return ['status' => 'already_imported'];
        }

        $listing   = $application->listing()->withoutGlobalScope('tenant')->first();
        $applicant = $application->applicant;

        if (! $listing || ! $listing->jp_job_post_id) {
            return ['status' => 'no_jp_post'];
        }

        // Use RcCandidate and RcApplication if Recruitment module exists
        if (! class_exists(\Modules\Recruitment\Models\RcCandidate::class)) {
            return ['status' => 'rc_unavailable'];
        }

        // Find or create RC candidate by email within org
        $rcCandidate = \Modules\Recruitment\Models\RcCandidate::withoutGlobalScope('tenant')
            ->where('email', $applicant->email)
            ->where('org_id', TenantContext::getOrganizationId())
            ->first();

        if (! $rcCandidate) {
            $rcCandidate = \Modules\Recruitment\Models\RcCandidate::create([
                'org_id'           => TenantContext::getOrganizationId(),
                'full_name'        => $applicant->display_name,
                'email'            => $applicant->email,
                'phone'            => $applicant->phone,
                'current_title'    => $applicant->headline,
                'years_experience' => $applicant->years_experience,
                'source'           => \Modules\Recruitment\Enums\CandidateSource::Marketplace->value,
                'mkt_applicant_id' => $applicant->uuid,
                'created_by'       => $importedBy->id,
                'updated_by'       => $importedBy->id,
            ]);
        }

        // Create RC application
        $rcApplication = \Modules\Recruitment\Models\RcApplication::create([
            'org_id'               => TenantContext::getOrganizationId(),
            'candidate_id'         => $rcCandidate->id,
            'jp_job_post_id'       => $listing->jp_job_post_id,
            'mkt_application_id'   => $application->uuid,
            'status'               => 'new',
            'apply_source'         => \Modules\Recruitment\Enums\CandidateSource::Marketplace->value,
            'cover_letter'         => $application->cover_letter,
            'expected_salary'      => $application->expected_salary,
            'assigned_to'          => $importedBy->id,
        ]);

        $application->update([
            'import_status'                => ApplicationImportStatus::Imported->value,
            'imported_rc_candidate_id'     => $rcCandidate->uuid,
            'imported_rc_application_id'   => $rcApplication->uuid,
            'imported_at'                  => now(),
            'imported_by'                  => $importedBy->id,
        ]);

        return ['status' => 'imported', 'rc_candidate_id' => $rcCandidate->uuid];
    }
}
