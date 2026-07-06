<?php

namespace Modules\Employee\Data\Requests;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

class EmployeeSalaryData extends Data
{
    public function __construct(
        #[Nullable]
        public readonly ?float $salary_base,

        #[Nullable]
        public readonly ?string $salary_currency,

        #[Nullable]
        public readonly ?string $bank_account,

        #[Nullable]
        public readonly ?string $bank_name,
    ) {}

    public static function rules(): array
    {
        return [
            'salary_base'     => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'size:3'],
            'bank_account'    => ['nullable', 'string', 'max:30'],
            'bank_name'       => ['nullable', 'string', 'max:100'],
        ];
    }
}
