<?php

namespace Modules\Branch\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Branch\Data\Requests\UpdateBranchData;
use Modules\Branch\Events\BranchUpdated;
use Modules\Branch\Models\Branch;

class UpdateBranchAction
{
    use AsAction;

    public function handle(Branch $branch, UpdateBranchData $data): Branch
    {
        $branch->update([
            'parent_id'     => $data->parent_id,
            'name'          => $data->name,
            'code'          => strtoupper(trim($data->code)),
            'type'          => $data->type->value,
            'status'        => $data->status->value,
            'tax_code'      => $data->tax_code,
            'phone'         => $data->phone,
            'email'         => $data->email,
            'fax'           => $data->fax,
            'province_code' => $data->province_code,
            'ward_code'     => $data->ward_code,
            'address'       => $data->address,
            'lat'           => $data->lat,
            'lng'           => $data->lng,
            'timezone'      => $data->timezone,
            'currency'      => $data->currency ? strtoupper($data->currency) : null,
            'opened_at'     => $data->opened_at,
            'closed_at'     => $data->closed_at,
            'updated_by'    => auth()->id(),
        ]);

        event(new BranchUpdated($branch));

        return $branch;
    }
}
