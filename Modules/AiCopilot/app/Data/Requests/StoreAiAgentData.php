<?php

namespace Modules\AiCopilot\Data\Requests;

use Spatie\LaravelData\Data;

class StoreAiAgentData extends Data
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $slug,
        public readonly string  $task_type,
        public readonly string  $provider,
        public readonly string  $model,
        public readonly ?int    $organization_id = null,
        public readonly float   $temperature    = 0.7,
        public readonly int     $max_tokens     = 1024,
        public readonly int     $timeout_seconds = 30,
        public readonly bool    $sync_mode      = false,
        public readonly bool    $is_active      = true,
        public readonly ?string $description    = null,
    ) {}

    public static function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'name'            => ['required', 'string', 'max:120'],
            'slug'            => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_.]+$/'],
            'task_type'       => ['required', 'string', 'in:sop,kpi,hr,lead,email,general,custom'],
            'provider'        => ['required', 'string', 'in:claude,openai,mock'],
            'model'           => ['required', 'string', 'max:80'],
            'temperature'     => ['sometimes', 'numeric', 'min:0', 'max:2'],
            'max_tokens'      => ['sometimes', 'integer', 'min:64', 'max:16000'],
            'timeout_seconds' => ['sometimes', 'integer', 'min:5', 'max:120'],
            'sync_mode'       => ['sometimes', 'boolean'],
            'is_active'       => ['sometimes', 'boolean'],
            'description'     => ['nullable', 'string', 'max:1000'],
        ];
    }
}
