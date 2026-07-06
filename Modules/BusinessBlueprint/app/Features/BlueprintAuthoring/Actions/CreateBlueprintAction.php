<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\BlueprintData;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * Tạo blueprints + version đầu tiên "1.0.0" status=draft (spec §2.6).
 */
class CreateBlueprintAction
{
    use AsAction;

    public function handle(BlueprintData $data, ?int $createdBy = null): Blueprint
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $blueprint = Blueprint::create([
                'business_solution_id' => $data->business_solution_id,
                'code'                  => $data->code,
                'name'                  => $data->name,
                'description'           => $data->description,
                'status'                => BlueprintVersionStatus::Draft->value,
                'created_by'            => $createdBy ?? auth()->id(),
            ]);

            $version = BlueprintVersion::create([
                'blueprint_id' => $blueprint->id,
                'version'      => '1.0.0',
                'status'       => BlueprintVersionStatus::Draft->value,
            ]);

            $blueprint->update(['current_version_id' => $version->id]);

            return $blueprint->fresh('currentVersion');
        });
    }
}
