<?php

namespace Modules\Ocop\Features\OcopProductManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Models\OcopSubject;

class SyncOcopSubjectSnapshotAction
{
    use AsAction;

    public static function attributesFor(OcopSubject $ocopSubject): array
    {
        return [
            'ocop_subject_id' => $ocopSubject->id,
            'producer_name' => $ocopSubject->name,
            'producer_address' => $ocopSubject->address,
            'province_code' => $ocopSubject->province_code,
            'province_name' => $ocopSubject->province_name,
            'ward_code' => $ocopSubject->ward_code,
            'ward_name' => $ocopSubject->ward_name,
        ];
    }

    public function handle(OcopSubject $ocopSubject): int
    {
        $attributes = self::attributesFor($ocopSubject);

        return $ocopSubject->products()->get()
            ->each(fn (OcopProduct $product) => $product->update($attributes))
            ->count();
    }
}
