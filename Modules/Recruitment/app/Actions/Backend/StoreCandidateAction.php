<?php

namespace Modules\Recruitment\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Data\Requests\StoreCandidateData;
use Modules\Recruitment\Models\RcCandidate;

class StoreCandidateAction
{
    use AsAction;

    public function handle(StoreCandidateData $data): RcCandidate
    {
        return RcCandidate::create([
            'org_id'           => $data->organization_id,
            'full_name'        => $data->full_name,
            'email'            => $data->email,
            'phone'            => $data->phone,
            'date_of_birth'    => $data->date_of_birth,
            'gender'           => $data->gender,
            'current_title'    => $data->current_title,
            'current_company'  => $data->current_company,
            'years_experience' => $data->years_experience,
            'skills'           => $data->skills,
            'source'           => $data->source,
            'referred_by'      => $data->referred_by,
            'linkedin_url'     => $data->linkedin_url,
            'portfolio_url'    => $data->portfolio_url,
            'status'           => 'active',
            'created_by'       => auth()->id(),
        ]);
    }
}
