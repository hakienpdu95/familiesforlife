<?php

namespace Modules\Employee\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class OffboardEmployeeData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $separation_type,

        #[Required]
        public readonly string $effective_date,

        #[Nullable, StringType]
        public readonly ?string $reason,

        #[Nullable]
        public readonly ?int $reassign_manager_id,
    ) {}

    public static function rules(): array
    {
        return [
            'separation_type'    => ['required', 'string', 'in:resigned,terminated'],
            'effective_date'     => ['required', 'date'],
            'reason'             => ['nullable', 'string', 'max:2000'],
            'reassign_manager_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
