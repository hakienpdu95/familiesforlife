<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class ArchiveBlueprintVersionAction
{
    use AsAction;

    public function handle(BlueprintVersion $version): BlueprintVersion
    {
        $version->update(['status' => BlueprintVersionStatus::Archived->value]);

        return $version->fresh();
    }
}
