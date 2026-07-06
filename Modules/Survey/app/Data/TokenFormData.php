<?php

namespace Modules\Survey\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class TokenFormData extends Data
{
    public function __construct(
        public readonly string  $name,
        public readonly ?Carbon $expires_at = null,
    ) {}

    public static function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:150'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
