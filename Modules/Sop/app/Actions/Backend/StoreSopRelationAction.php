<?php

namespace Modules\Sop\Actions\Backend;

use Illuminate\Support\Str;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopRelation;

class StoreSopRelationAction
{
    public function handle(SopProcess $sop, array $data): SopRelation
    {
        return SopRelation::create([
            'uuid'           => Str::uuid(),
            'sop_id'         => $sop->id,
            'related_sop_id' => $data['related_sop_id'],
            'relation_type'  => $data['relation_type'],
            'note'           => $data['note'] ?? null,
            'created_by'     => auth()->id(),
        ]);
    }
}
