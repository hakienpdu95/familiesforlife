<?php

namespace Modules\LeadSource\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LeadSource\Data\Requests\UpdateSourceData;
use Modules\LeadSource\Events\SourceUpdated;
use Modules\LeadSource\Models\LeadSource;

class UpdateSourceAction
{
    use AsAction;

    public function handle(LeadSource $source, UpdateSourceData $data): LeadSource
    {
        $updated = DB::transaction(function () use ($source, $data) {
            $source->update([
                'label'      => $data->label,
                'icon'       => $data->icon,
                'color'      => $data->color ?? $source->color,
                'sort_order' => $data->sort_order,
            ]);

            return $source->fresh();
        });

        event(new SourceUpdated($updated));

        return $updated;
    }
}
