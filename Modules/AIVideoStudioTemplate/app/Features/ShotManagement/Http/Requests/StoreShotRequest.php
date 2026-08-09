<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** spec/AIVideoStudioTemplate_Technical_Specification.md §7 — không field nào bắt buộc (cho phép điền dần). */
class StoreShotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ai_video_studio_template.use');
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:200'],
            'subject' => ['nullable', 'string'],
            'action' => ['nullable', 'string'],
            'environment' => ['nullable', 'string'],
            'camera' => ['nullable', 'string'],
            'style' => ['nullable', 'string'],
            'mood' => ['nullable', 'string'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:36000'],
            'audio_direction' => ['nullable', 'string'],
            'constraints' => ['nullable', 'string'],
            'script_line' => ['nullable', 'string'],
            'cta_text' => ['nullable', 'string', 'max:200'],
            'model_tool' => ['nullable', 'string', 'max:150'],
            'reference_assets' => ['nullable', 'string'],
            'qc_notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): array
    {
        return [
            'label' => $this->input('label') ?: null,
            'subject' => $this->input('subject') ?: null,
            'action' => $this->input('action') ?: null,
            'environment' => $this->input('environment') ?: null,
            'camera' => $this->input('camera') ?: null,
            'style' => $this->input('style') ?: null,
            'mood' => $this->input('mood') ?: null,
            'durationSeconds' => $this->input('duration_seconds') ?: null,
            'audioDirection' => $this->input('audio_direction') ?: null,
            'constraints' => $this->input('constraints') ?: null,
            'scriptLine' => $this->input('script_line') ?: null,
            'ctaText' => $this->input('cta_text') ?: null,
            'modelTool' => $this->input('model_tool') ?: null,
            'referenceAssets' => $this->input('reference_assets') ?: null,
            'qcNotes' => $this->input('qc_notes') ?: null,
        ];
    }
}
