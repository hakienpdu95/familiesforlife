<?php

namespace Modules\Sop\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Sop\Data\Requests\UpdateSopProcessData;
use Modules\Sop\Events\SopProcessUpdated;
use Modules\Sop\Models\SopProcess;

class UpdateSopProcessAction
{
    use AsAction;

    public function handle(SopProcess $sop, UpdateSopProcessData $data): SopProcess
    {
        $sop->update([
            'owner_id'      => $data->owner_id,
            'department_id' => $data->department_id,
            'branch_id'     => $data->branch_id,
            'title'         => $data->title,
            'description'   => $data->description,
            'type'          => $data->type->value,
            'effective_date' => $data->effective_date ?: null,
            'expired_date'  => $data->expired_date ?: null,
            'updated_by'    => auth()->id(),
        ]);

        event(new SopProcessUpdated($sop));

        return $sop;
    }
}
