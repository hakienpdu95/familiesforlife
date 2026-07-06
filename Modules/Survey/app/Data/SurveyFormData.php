<?php

namespace Modules\Survey\Data;

use Spatie\LaravelData\Data;

class SurveyFormData extends Data
{
    public function __construct(
        public readonly int     $organization_id,
        public readonly string  $title,
        public readonly ?string $description = null,
        public readonly ?int    $version = null,
        public readonly ?int    $turnstile_site_id = null,
    ) {}

    public static function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ];
    }
}
