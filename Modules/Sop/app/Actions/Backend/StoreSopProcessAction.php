<?php

namespace Modules\Sop\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Sop\Data\Requests\StoreSopProcessData;
use Modules\Sop\Events\SopProcessCreated;
use Modules\Sop\Models\SopProcess;

class StoreSopProcessAction
{
    use AsAction;

    public function handle(StoreSopProcessData $data): SopProcess
    {
        $sop = SopProcess::create([
            'organization_id' => $data->organization_id,
            'uuid'            => Str::uuid(),
            'owner_id'        => $data->owner_id,
            'department_id'   => $data->department_id,
            'branch_id'       => $data->branch_id,
            'code'            => strtoupper(trim($data->code)),
            'title'           => $data->title,
            'description'     => $data->description,
            'type'            => $data->type->value,
            'status'          => 'draft',
            'version'         => 0,
            'effective_date'  => $data->effective_date ?: null,
            'expired_date'    => $data->expired_date ?: null,
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
        ]);

        event(new SopProcessCreated($sop));

        return $sop;
    }
}
