<?php

namespace Modules\AiCopilot\Data\Requests;

use Spatie\LaravelData\Data;

class AiTaskData extends Data
{
    public function __construct(
        public readonly string  $agent_slug,
        public readonly array   $variables   = [],
        public readonly ?string $subject_type = null,
        public readonly ?int    $subject_id   = null,
    ) {}

    public static function rules(): array
    {
        return [
            'agent_slug'   => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_.]+$/'],
            'variables'    => ['sometimes', 'array', 'max:20'],
            'variables.*'  => ['nullable', 'string', 'max:5000'],
            'subject_type' => ['nullable', 'string', 'max:150'],
            'subject_id'   => ['nullable', 'integer', 'min:1'],
        ];
    }
}
