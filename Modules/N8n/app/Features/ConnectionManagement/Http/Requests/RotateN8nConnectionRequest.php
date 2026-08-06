<?php

namespace Modules\N8n\Features\ConnectionManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * spec/N8n_Integration_Technical_Specification.md §3.2/§7.4 — xoay CHỌN LỌC, ít nhất 1 trong
 * 3 cờ phải = true.
 */
class RotateN8nConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-n8n');
    }

    public function rules(): array
    {
        return [
            'rotate_inbound_token' => ['boolean'],
            'rotate_inbound_secret' => ['boolean'],
            'rotate_outbound_secret' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->boolean('rotate_inbound_token')
                && ! $this->boolean('rotate_inbound_secret')
                && ! $this->boolean('rotate_outbound_secret')) {
                $validator->errors()->add('rotate', 'Phải chọn ít nhất 1 giá trị để xoay.');
            }
        });
    }
}
