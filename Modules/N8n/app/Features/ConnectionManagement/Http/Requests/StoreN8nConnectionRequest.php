<?php

namespace Modules\N8n\Features\ConnectionManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\N8n\Rules\ValidCidr;

/**
 * spec/N8n_Integration_Technical_Specification.md §7.1 — người dùng KHÔNG có field nào để tự
 * nhập inbound_token/inbound_secret/outbound_secret (§3.2) — chỉ name/purpose_note/enabled
 * flags/outbound_webhook_url/allowed_ip_cidrs/rate_limit_per_minute.
 */
class StoreN8nConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-n8n');
    }

    /**
     * Form nhập CIDR dạng textarea (1 dòng/CIDR, UX đơn giản hơn input[] động) — chuyển thành
     * mảng `allowed_ip_cidrs` TRƯỚC khi validate, khớp đúng shape rules() kỳ vọng.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('allowed_ip_cidrs_text') && ! $this->has('allowed_ip_cidrs')) {
            $lines = array_filter(array_map('trim', explode("\n", (string) $this->input('allowed_ip_cidrs_text'))));
            $this->merge(['allowed_ip_cidrs' => array_values($lines) ?: null]);
        }
    }

    public function rules(): array
    {
        return [
            // Rule::unique() chạy qua query builder thô, KHÔNG qua Eloquent global scope nên
            // KHÔNG tự loại trừ soft-deleted — khớp ĐÚNG với unique index DB: name không bao
            // giờ được tái sử dụng, kể cả sau xoá mềm (§2.5/§7.1).
            'name' => ['required', 'string', 'max:150', Rule::unique('n8n_connections', 'name')],
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
