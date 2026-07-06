<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\Concerns;

use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Exceptions\BlueprintVersionLockedException;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * Mọi Upsert*Action trên node con của Blueprint (Outcome/Capability/Workflow/...)
 * bắt buộc kiểm tra version chưa isImmutable() trước khi cho sửa (BR-004 A04.1).
 */
trait GuardsImmutableVersion
{
    private function guardMutable(BlueprintVersion $version): void
    {
        if ($version->isImmutable()) {
            throw new BlueprintVersionLockedException($version->status);
        }
    }
}
