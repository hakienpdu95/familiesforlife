<?php

namespace Modules\EntityComparison\Features\EntityTypeManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Authorization thật nằm ở EntityTypeAdminController::authorizeResource() (constructor). */
class StoreEntityTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'cover' => ['nullable', 'image', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên loại đối tượng.',
            'name.max' => 'Tên không được vượt quá :max ký tự.',
            'cover.image' => 'File ảnh không hợp lệ.',
            'cover.max' => 'Ảnh không được vượt quá :max KB.',
        ];
    }
}
