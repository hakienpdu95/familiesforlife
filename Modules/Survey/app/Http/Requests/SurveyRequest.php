<?php

namespace Modules\Survey\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('survey.create') || $this->user()->can('survey.update');
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:2000'],
            'version'         => ['nullable', 'integer', 'min:1', 'max:9999'],
            'turnstile_site_id' => ['nullable', 'integer', 'exists:survey_turnstile_sites,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề survey là bắt buộc.',
        ];
    }

    public function toData(): array
    {
        return [
            'organization_id' => (int) $this->input('organization_id'),
            'title'           => $this->input('title'),
            'description'     => $this->input('description') ?: null,
            'version'         => $this->input('version') ? (int) $this->input('version') : null,
            'turnstile_site_id' => $this->input('turnstile_site_id') ? (int) $this->input('turnstile_site_id') : null,
        ];
    }
}
