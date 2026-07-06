<?php

namespace Modules\Task\Data\Requests;

use Spatie\LaravelData\Data;

class UpdateTimeLogData extends Data
{
    public function __construct(
        public readonly ?float $hours,
        public readonly ?string $log_date,
        public readonly ?string $description,
        public readonly ?bool $is_billable,
    ) {}

    public static function rules(): array
    {
        return [
            'hours'       => ['sometimes', 'numeric', 'gt:0', 'max:24'],
            'log_date'    => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_billable' => ['sometimes', 'boolean'],
        ];
    }
}
