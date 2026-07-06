<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns\GuardsImmutableVersion;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\AnalyticData;
use Modules\BusinessBlueprint\Models\BlueprintAnalytic;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class UpsertAnalyticAction
{
    use AsAction;
    use GuardsImmutableVersion;

    public function handle(AnalyticData $data, ?BlueprintAnalytic $analytic = null): BlueprintAnalytic
    {
        $version = BlueprintVersion::findOrFail($data->blueprint_version_id);
        $this->guardMutable($version);

        $attributes = [
            'blueprint_version_id' => $data->blueprint_version_id,
            'metric_code'           => $data->metric_code,
            'name'                  => $data->name,
            'description'           => $data->description,
            'metric_type'           => $data->metric_type,
            'formula'               => $data->formula,
            'source_type'           => $data->source_type,
            'status'                => $data->status,
        ];

        if (! $analytic) {
            return BlueprintAnalytic::create($attributes);
        }

        $analytic->update($attributes);

        return $analytic->fresh();
    }
}
