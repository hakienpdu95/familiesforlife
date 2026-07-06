<?php

namespace Modules\AiCopilot\Data\Requests;

use Spatie\LaravelData\Data;

class StoreAiPromptData extends Data
{
    public function __construct(
        public readonly int     $agent_id,
        public readonly string  $name,
        public readonly string  $system_prompt,
        public readonly string  $user_template,
        public readonly ?int    $organization_id  = null,
        public readonly ?string $description       = null,
        public readonly ?array  $variables_schema  = null,
        public readonly bool    $is_default        = false,
        public readonly bool    $is_active         = true,
    ) {}

    public static function rules(): array
    {
        return [
            'organization_id'   => ['nullable', 'integer', 'exists:organizations,id'],
            'agent_id'          => ['required', 'integer', 'exists:ai_agents,id'],
            'name'              => ['required', 'string', 'max:120'],
            'system_prompt'     => ['required', 'string'],
            'user_template'     => ['required', 'string'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'variables_schema'  => ['nullable', 'array'],
            'variables_schema.*.key'      => ['required', 'string', 'max:60'],
            'variables_schema.*.type'     => ['required', 'string', 'in:string,text,integer,boolean'],
            'variables_schema.*.required' => ['required', 'boolean'],
            'is_default'        => ['sometimes', 'boolean'],
            'is_active'         => ['sometimes', 'boolean'],
        ];
    }
}
