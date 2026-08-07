<?php

namespace Modules\ContentOutlines\Features\ArticleDrafting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * spec/ContentOutlines_Technical_Specification.md §4.17/§8.1 (v1.14). `max:50000` là ngưỡng AN
 * TOÀN cứng (chặn dán nhầm cả 1 trang web/tài liệu không liên quan) — KHÁC
 * `BuildArticleDraftPromptAction::WORD_COUNT_WARNING_THRESHOLD` (soft warning, không chặn) — 1
 * outline hợp lệ dù ở `outline_depth=detailed` cũng hiếm khi vượt vài nghìn từ (~20-30k ký tự).
 */
class StoreApprovedOutlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('content_outlines.use');
    }

    public function rules(): array
    {
        return [
            'approved_outline' => ['required', 'string', 'max:50000'],
        ];
    }
}
