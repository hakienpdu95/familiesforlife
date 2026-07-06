<?php

namespace Modules\BusinessSolution\Features\SolutionCatalogManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessSolution\Enums\BusinessSolutionStatus;
use Modules\BusinessSolution\Features\SolutionCatalogManagement\Data\BusinessSolutionData;
use Modules\BusinessSolution\Models\BusinessSolution;

class CreateBusinessSolutionAction
{
    use AsAction;

    public function handle(BusinessSolutionData $data): BusinessSolution
    {
        return BusinessSolution::create([
            'vertical_id'       => $data->vertical_id,
            'code'              => $data->code,
            'name'              => $data->name,
            'short_description' => $data->short_description,
            'description'       => $data->description,
            'target_customers'  => $data->target_customers,
            'visibility'        => $data->visibility,
            'thumbnail_url'     => $data->thumbnail_url,
            'status'            => BusinessSolutionStatus::Draft->value,
        ]);
    }
}
