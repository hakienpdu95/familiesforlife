<?php

namespace Modules\OcopRubric\Features\ProductGroupCatalog\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Features\ProductGroupCatalog\Data\ProductGroupData;
use Modules\OcopRubric\Models\OcopProductGroup;

class CreateProductGroupAction
{
    use AsAction;

    public function handle(ProductGroupData $data): OcopProductGroup
    {
        return OcopProductGroup::create([
            'code'                     => $data->code,
            'name'                     => $data->name,
            'industry_code'            => $data->industry_code,
            'industry_name'            => $data->industry_name,
            'group_label'              => $data->group_label,
            'managing_agency'          => $data->managing_agency,
            'requires_sample_product'  => $data->requires_sample_product,
            'is_active'                => $data->is_active,
            'sort_order'               => $data->sort_order,
        ]);
    }
}
