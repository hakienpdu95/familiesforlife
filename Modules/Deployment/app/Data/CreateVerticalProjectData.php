<?php

namespace Modules\Deployment\Data;

use Illuminate\Validation\Rule;
use Modules\Project\Enums\ProjectStatus;
use Spatie\LaravelData\Data;

class CreateVerticalProjectData extends Data
{
    public function __construct(
        public readonly string        $name,
        public readonly string        $code,
        public readonly ProjectStatus $status,
        public readonly ?string       $description,
        public readonly ?string       $start_date,
        public readonly ?string       $end_date,
    ) {}

    public static function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:50'],
            'status'      => ['required', Rule::enum(ProjectStatus::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date'  => ['nullable', 'date_format:Y-m-d'],
            'end_date'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public static function messages(): array
    {
        return [
            'name.required'             => 'Vui lòng nhập tên dự án.',
            'name.max'                  => 'Tên dự án không được vượt quá 255 ký tự.',
            'code.required'             => 'Vui lòng nhập mã dự án.',
            'code.max'                  => 'Mã dự án không được vượt quá 50 ký tự.',
            'status.required'           => 'Vui lòng chọn trạng thái dự án.',
            'status.enum'               => 'Trạng thái dự án không hợp lệ.',
            'description.max'           => 'Mô tả không được vượt quá 2000 ký tự.',
            'start_date.date_format'    => 'Ngày bắt đầu phải có định dạng YYYY-MM-DD.',
            'end_date.date_format'      => 'Ngày kết thúc phải có định dạng YYYY-MM-DD.',
            'end_date.after_or_equal'   => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
        ];
    }
}
