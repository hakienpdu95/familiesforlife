<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Features\RubricAuthoring\Data\OptionData;
use Modules\OcopRubric\Models\OcopRubricOption;

class UpsertOptionAction
{
    use AsAction;

    public function handle(OptionData $data, ?OcopRubricOption $option = null): OcopRubricOption
    {
        $attributes = [
            'criterion_id' => $data->criterion_id,
            'label'        => $data->label,
            'points'       => $data->points,
            'sort_order'   => $data->sort_order,
        ];

        if (!$option) {
            return OcopRubricOption::create($attributes);
        }

        $option->update($attributes);

        return $option->fresh();
    }
}
