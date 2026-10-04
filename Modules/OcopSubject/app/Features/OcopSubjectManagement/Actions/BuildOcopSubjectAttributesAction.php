<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Actions;

use App\Models\Province;
use App\Models\Ward;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopSubject\Features\OcopSubjectManagement\Data\OcopSubjectData;

class BuildOcopSubjectAttributesAction
{
    use AsAction;

    public function handle(OcopSubjectData $data): array
    {
        return [
            'name' => $data->name,
            'name_en' => $data->name_en,
            'tax_code' => $data->tax_code,
            'organization_type' => $data->organization_type,
            'legal_representative' => $data->legal_representative,
            'position' => $data->position,
            'address' => $data->address,
            'province_code' => $data->province_code,
            'province_name' => Province::where('province_code', $data->province_code)->value('name'),
            'ward_code' => $data->ward_code,
            'ward_name' => Ward::where('ward_code', $data->ward_code)->value('name'),
            'gps_coordinates' => $data->gps_coordinates,
            'factory_code' => $data->factory_code,
            'is_food_business' => $data->is_food_business,
            'ocop_star' => $data->ocop_star,
            'ocop_cert_expiry' => $data->ocop_cert_expiry,
            'hotline' => $data->hotline,
            'email' => $data->email,
            'website' => $data->website,
            'is_active' => $data->is_active,
        ];
    }
}
