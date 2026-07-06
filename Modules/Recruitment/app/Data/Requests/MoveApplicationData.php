<?php

namespace Modules\Recruitment\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class MoveApplicationData extends Data
{
    public function __construct(
        #[Required]
        public readonly int $stage_id,

        #[Required]
        public readonly string $result,

        #[Nullable]
        public readonly ?string $note,
    ) {}
}
