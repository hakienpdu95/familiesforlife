<?php

namespace Modules\AIVideoStudioTemplate\Features\ShotManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioShot;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §7 — cùng rules với StoreShotRequest.
 *
 * **Khác StoreShotRequest ở `toData()` (v1.7):** field KHÔNG có trong request thì giữ nguyên giá trị
 * đang lưu, thay vì thành `null`. Trước v1.7 mọi field vắng mặt đều bị ghi `null`, nên 1 request
 * `PUT {"subject": "x"}` sẽ xoá sạch 14 field còn lại. UI hiện gửi đủ field mỗi lần debounce nên
 * chưa lộ, nhưng đây là bẫy chờ sẵn: chỉ cần thêm 1 field vào Model/Request mà quên gắn class
 * `.aivs-field` trong JS là field đó bị xoá trắng ở MỌI shot sau mỗi lần gõ.
 *
 * Gửi chuỗi rỗng vẫn xoá field như cũ (`"" ?: null`) — chỉ field VẮNG MẶT mới được giữ nguyên.
 */
class UpdateShotRequest extends FormRequest
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
        /** @var AiVideoStudioShot|null $shot */
        $shot = $this->route('shot');

        return [
            'label' => $this->valueOrExisting('label', $shot),
            'subject' => $this->valueOrExisting('subject', $shot),
            'action' => $this->valueOrExisting('action', $shot),
            'environment' => $this->valueOrExisting('environment', $shot),
            'camera' => $this->valueOrExisting('camera', $shot),
            'style' => $this->valueOrExisting('style', $shot),
            'mood' => $this->valueOrExisting('mood', $shot),
            'durationSeconds' => $this->valueOrExisting('duration_seconds', $shot),
            'audioDirection' => $this->valueOrExisting('audio_direction', $shot),
            'constraints' => $this->valueOrExisting('constraints', $shot),
            'scriptLine' => $this->valueOrExisting('script_line', $shot),
            'ctaText' => $this->valueOrExisting('cta_text', $shot),
            'modelTool' => $this->valueOrExisting('model_tool', $shot),
            'referenceAssets' => $this->valueOrExisting('reference_assets', $shot),
            'qcNotes' => $this->valueOrExisting('qc_notes', $shot),
        ];
    }

    /** Có trong request → dùng giá trị gửi lên (rỗng = xoá); vắng mặt → giữ nguyên giá trị đang lưu. */
    private function valueOrExisting(string $key, ?AiVideoStudioShot $shot): mixed
    {
        if ($this->has($key)) {
            return $this->input($key) ?: null;
        }

        return $shot?->{$key};
    }
}
