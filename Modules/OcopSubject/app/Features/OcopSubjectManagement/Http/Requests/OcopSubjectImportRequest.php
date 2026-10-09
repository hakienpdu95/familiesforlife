<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OcopSubjectImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'province_code' => ['required', 'string', 'size:2', 'exists:provinces,province_code'],
            'file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'province_code.required' => 'Vui lòng chọn tỉnh/thành.',
            'province_code.exists' => 'Tỉnh/thành được chọn không hợp lệ.',
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'Chỉ chấp nhận file .xlsx hoặc .csv.',
            'file.max' => 'File tối đa 10MB.',
        ];
    }
}
