<?php

namespace Modules\KcItem\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\KcItem\Models\KcAccessControl;
use Modules\KcItem\Models\KcItem;

class StoreKcAccessControlAction
{
    use AsAction;

    public function handle(KcItem $kcItem, array $data): KcAccessControl
    {
        return KcAccessControl::create([
            'uuid'        => Str::uuid(),
            'item_id'     => $kcItem->id,
            'target_type' => $data['target_type'],
            'target_id'   => (int) $data['target_id'],
            'permission'  => $data['permission'] ?? 'view',
            'granted_by'  => auth()->id(),
            'expired_at'  => $data['expired_at'] ?? null,
        ]);
    }
}
