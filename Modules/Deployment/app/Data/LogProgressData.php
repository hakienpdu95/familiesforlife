<?php

namespace Modules\Deployment\Data;

use Spatie\LaravelData\Data;

class LogProgressData extends Data
{
    public function __construct(
        public readonly int     $deployment_target_id,
        public readonly string  $phase,
        public readonly int     $percent,
        public readonly ?string $remark,
    ) {}

    public static function rules(): array
    {
        return [
            'deployment_target_id' => ['required', 'integer', 'exists:deployment_targets,id'],
            'phase'                => ['required', 'string', 'max:50'],
            'percent'              => ['required', 'integer', 'min:0', 'max:100'],
            'remark'               => ['nullable', 'string', 'max:2000'],
        ];
    }

    public static function messages(): array
    {
        return [
            'deployment_target_id.required' => 'Vui lòng chọn đối tượng triển khai.',
            'deployment_target_id.exists'   => 'Đối tượng triển khai không hợp lệ.',
            'phase.required'                => 'Vui lòng nhập phase hiện tại.',
            'phase.max'                     => 'Tên phase không được vượt quá 50 ký tự.',
            'percent.required'              => 'Vui lòng nhập % hoàn thành.',
            'percent.integer'               => '% hoàn thành phải là số nguyên.',
            'percent.min'                   => '% hoàn thành không được nhỏ hơn 0.',
            'percent.max'                   => '% hoàn thành không được lớn hơn 100.',
            'remark.max'                    => 'Ghi chú không được vượt quá 2000 ký tự.',
        ];
    }
}
