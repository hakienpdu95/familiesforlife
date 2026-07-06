<?php

namespace Modules\OcopRubric\Features\RubricAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\OcopRubric\Enums\RubricVersionStatus;
use Modules\OcopRubric\Features\RubricAuthoring\Events\RubricVersionPublished;
use Modules\OcopRubric\Features\RubricAuthoring\Queries\ValidateRubricIntegrityHandler;
use Modules\OcopRubric\Features\RubricAuthoring\Queries\ValidateRubricIntegrityQuery;
use Modules\OcopRubric\Models\OcopRubricVersion;

class PublishRubricVersionAction
{
    use AsAction;

    public function __construct(private readonly ValidateRubricIntegrityHandler $validator) {}

    public function handle(OcopRubricVersion $version, int $publishedByUserId): OcopRubricVersion
    {
        $result = $this->validator->handle(new ValidateRubricIntegrityQuery($version->id));

        if (!$result['valid']) {
            throw new \DomainException(
                'Không thể publish — bộ tiêu chí chưa hợp lệ: ' . implode(' | ', $result['errors'])
            );
        }

        return DB::transaction(function () use ($version, $publishedByUserId) {
            // Retire bản active cũ của CÙNG bộ sản phẩm (chỉ 1 version active tại 1 thời điểm)
            OcopRubricVersion::where('product_group_id', $version->product_group_id)
                ->where('status', RubricVersionStatus::Active->value)
                ->update(['status' => RubricVersionStatus::Retired->value, 'effective_to' => now()]);

            $version->update([
                'status'         => RubricVersionStatus::Active->value,
                'published_by'   => $publishedByUserId,
                'published_at'   => now(),
                'effective_from' => now(),
            ]);

            RubricVersionPublished::dispatch($version);

            return $version->fresh();
        });
    }
}
