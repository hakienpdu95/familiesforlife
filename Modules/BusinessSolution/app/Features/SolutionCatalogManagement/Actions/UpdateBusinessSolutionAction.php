<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Data\BusinessSolutionData;
use Modules\BusinessSolution\Models\BusinessSolution;

class UpdateBusinessSolutionAction
{
    use AsAction;

    public function handle(BusinessSolution $businessSolution, BusinessSolutionData $data): BusinessSolution
    {
        $businessSolution->update([
            'vertical_id'        => $data->vertical_id,
            'code'               => $data->code,
            'name'               => $data->name,
            'short_description'  => $data->short_description,
            'description'        => $data->description,
            'target_customers'   => $data->target_customers,
            'visibility'         => $data->visibility,
            'thumbnail_url'      => $data->thumbnail_url,
        ]);

        return $businessSolution->fresh();
    }
}
