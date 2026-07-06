<?php

namespace Modules\RoleScope\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

class UpdateRoleScopeData extends Data
{
    public function __construct(
        #[Nullable]
        public readonly ?string $expires_at,

        #[Nullable, Max(500)]
        public readonly ?string $note,
    ) {}

    public static function rules(): array
    {
        return [
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public static function messages(): array
    {
        return [
            'expires_at.after' => 'Ngày hết hạn phải sau thời điểm hiện tại.',
        ];
    }
}
