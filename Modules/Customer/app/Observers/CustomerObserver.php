<?php
namespace Modules\Customer\Observers;

use App\Foundation\BaseModelObserver;
use Illuminate\Database\Eloquent\Model;

class CustomerObserver extends BaseModelObserver
{
    protected function module(): string       { return 'Customer'; }
    protected function resourceCode(): string { return 'customer'; }

    protected function updatedContext(Model $m): array
    {
        return [
            'changed_fields'  => implode(',', array_keys($m->getChanges())),
            'stage_before'    => $m->getOriginal('lifecycle_stage'),
            'stage_after'     => $m->lifecycle_stage?->value,
            'organization_id' => $m->organization_id,
        ];
    }

    protected function deletedContext(Model $m): array
    {
        return [
            'organization_id' => $m->organization_id,
            'display_name'    => $m->display_name,
        ];
    }
}
