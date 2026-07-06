<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use DomainException;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintIntegrityHandler;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintIntegrityQuery;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintReadinessHandler;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintReadinessQuery;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

class PublishBlueprintVersionAction
{
    use AsAction;

    public function __construct(
        private readonly ValidateBlueprintIntegrityHandler $integrityValidator,
        private readonly ValidateBlueprintReadinessHandler $readinessValidator,
    ) {}

    public function handle(BlueprintVersion $version, int $publishedByUserId): BlueprintVersion
    {
        $integrity = $this->integrityValidator->handle(new ValidateBlueprintIntegrityQuery($version->id));
        if (! $integrity['valid']) {
            throw new DomainException('Không thể publish — cây Blueprint chưa hợp lệ: ' . implode(' | ', $integrity['errors']));
        }

        $readiness = $this->readinessValidator->handle(new ValidateBlueprintReadinessQuery($version->id));
        if (! $readiness['ready']) {
            $failed = collect($readiness['criteria'])->reject(fn ($c) => $c['passed'])->pluck('label');
            throw new DomainException('Không thể publish — chưa đạt Readiness Checklist: ' . $failed->implode(' | '));
        }

        return DB::transaction(function () use ($version, $publishedByUserId) {
            // Nếu version trước đó của CÙNG blueprint đang published → chuyển sang deprecated
            // (không xoá — Runtime cũ vẫn đọc được version cũ, spec §2.6).
            BlueprintVersion::where('blueprint_id', $version->blueprint_id)
                ->where('status', BlueprintVersionStatus::Published->value)
                ->where('id', '!=', $version->id)
                ->update(['status' => BlueprintVersionStatus::Deprecated->value]);

            $version->update([
                'status'       => BlueprintVersionStatus::Published->value,
                'published_at' => now(),
                'published_by' => $publishedByUserId,
            ]);

            $version->blueprint()->update([
                'current_version_id' => $version->id,
                'status'             => BlueprintVersionStatus::Published->value,
            ]);

            return $version->fresh();
        });
    }
}
