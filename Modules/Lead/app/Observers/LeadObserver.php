<?php

namespace Modules\Lead\Observers;

use App\Foundation\BaseModelObserver;
use Illuminate\Database\Eloquent\Model;

class LeadObserver extends BaseModelObserver
{
    protected function module(): string       { return 'Lead'; }
    protected function resourceCode(): string { return 'lead'; }

    protected function updatedContext(Model $m): array
    {
        return [
            'changed_fields'  => implode(',', array_keys($m->getChanges())),
            'stage_before'    => $m->getOriginal('stage_id'),
            'stage_after'     => $m->stage_id,
            'value_before'    => $m->getOriginal('expected_value'),
            'value_after'     => $m->expected_value,
            'organization_id' => $m->organization_id,
        ];
    }

    protected function deletedContext(Model $m): array
    {
        return [
            'organization_id' => $m->organization_id,
            'contact_name'    => $m->contact_name,
        ];
    }
}
