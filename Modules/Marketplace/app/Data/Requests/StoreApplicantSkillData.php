<?php
namespace Modules\Marketplace\Data\Requests;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Modules\Marketplace\Enums\ProficiencyLevel;

class StoreApplicantSkillData extends Data
{
    public function __construct(
        #[Required, StringType, Max(100)]
        public readonly string $skill_name,

        public readonly ProficiencyLevel $proficiency_level = ProficiencyLevel::Intermediate,

        #[Nullable]
        public readonly ?int $years_used = null,

        public readonly int $sort_order = 0,
    ) {}
}
