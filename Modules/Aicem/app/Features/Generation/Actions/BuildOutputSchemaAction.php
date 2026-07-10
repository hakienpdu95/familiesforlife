<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Chuyển output_contract.item_shape (hình dạng rút gọn trong aicem_context_templates.schema)
 * thành JSON Schema chuẩn dùng chung cho cả 2 provider — spec/AICEM_Technical_Specification.md
 * mục 8.3. OpenAI strict mode bắt buộc MỌI property có mặt trong "required" (kể cả field
 * nullable) và field nullable phải khai kiểu dạng ["string","null"] — 2 quy tắc này bắt buộc,
 * không được lược bỏ.
 */
class BuildOutputSchemaAction
{
    use AsAction;

    /** @param array<string, string> $itemShape VD ['field' => 'string|null', 'suggested_text' => 'string'] */
    public function handle(array $itemShape): array
    {
        $properties = [];
        $required   = [];

        foreach ($itemShape as $key => $typeDecl) {
            $properties[$key] = $this->mapType($typeDecl);
            $required[]        = $key;
        }

        return [
            'type'       => 'object',
            'properties' => [
                'suggestions' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'properties'           => $properties,
                        'required'             => $required,
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required'             => ['suggestions'],
            'additionalProperties' => false,
        ];
    }

    private function mapType(string $decl): array
    {
        $types = array_map(fn (string $p) => match ($p) {
            'string' => 'string',
            'int'    => 'integer',
            'null'   => 'null',
            default  => 'string',
        }, explode('|', $decl));

        return count($types) === 1 ? ['type' => $types[0]] : ['type' => array_values($types)];
    }
}
