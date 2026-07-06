<?php

namespace Modules\Branch\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Branch\Data\Requests\StoreBranchData;
use Modules\Branch\Events\BranchCreated;
use Modules\Branch\Models\Branch;
use Illuminate\Support\Str;

class StoreBranchAction
{
    use AsAction;

    public function handle(StoreBranchData $data): Branch
    {
        $branch = Branch::create([
            'uuid'            => Str::uuid(),
            'organization_id' => $data->organization_id,
            'parent_id'       => $data->parent_id,
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
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);

        event(new BranchCreated($branch));

        return $branch;
    }
}
