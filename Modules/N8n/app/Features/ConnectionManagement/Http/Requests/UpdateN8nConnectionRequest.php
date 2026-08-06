<?php

namespace Modules\N8n\Features\ConnectionManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\N8n\Rules\ValidCidr;

/**
 * spec/N8n_Integration_Technical_Specification.md §7.1 — dùng Rule::unique(...)->ignore($id)
 * như thường lệ; vẫn tính CẢ trashed vì ignore() chỉ loại trừ đúng bản ghi đang sửa, không
 * loại trừ toàn bộ trashed khác (tên vẫn không bao giờ được giải phóng).
 */
class UpdateN8nConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-n8n');
    }

    /** Cùng lý do StoreN8nConnectionRequest::prepareForValidation(). */
    protected function prepareForValidation(): void
    {
        if ($this->has('allowed_ip_cidrs_text') && ! $this->has('allowed_ip_cidrs')) {
            $lines = array_filter(array_map('trim', explode("\n", (string) $this->input('allowed_ip_cidrs_text'))));
            $this->merge(['allowed_ip_cidrs' => array_values($lines) ?: null]);
        }
    }

    public function rules(): array
    {
        $connection = $this->route('connection');

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('n8n_connections', 'name')->ignore($connection?->id)],
            'purpose_note' => ['nullable', 'string', 'max:500'],
            'inbound_enabled' => ['boolean'],
            'outbound_enabled' => ['boolean'],
            'outbound_webhook_url' => ['required_if:outbound_enabled,true', 'nullable', 'url', 'max:2000'],
            'allowed_ip_cidrs' => ['nullable', 'array'],
            'allowed_ip_cidrs.*' => ['string', new ValidCidr],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:6000'],
        ];
    }

    public function toData(): array
    {
        return [
            'name' => $this->input('name'),
            'purpose_note' => $this->input('purpose_note') ?: null,
            'inbound_enabled' => $this->boolean('inbound_enabled'),
            'outbound_enabled' => $this->boolean('outbound_enabled'),
            'outbound_webhook_url' => $this->input('outbound_webhook_url') ?: null,
            'allowed_ip_cidrs' => $this->input('allowed_ip_cidrs') ?: null,
            'rate_limit_per_minute' => $this->input('rate_limit_per_minute') ?: null,
        ];
    }
}
