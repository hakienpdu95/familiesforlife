<?php

namespace Modules\ContentOutlines\Features\ArticleReview\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * spec/ContentOutlines_Technical_Specification.md §4.20/§8.1 (v1.16). `max:100000` — ngưỡng AN
 * TOÀN cứng, cao hơn `StoreApprovedOutlineRequest::max` (50.000) vì đây là 1 BÀI VIẾT HOÀN CHỈNH
 * (thường dài hơn 1 outline) — KHÁC `BuildArticleReviewPromptAction::WORD_COUNT_WARNING_THRESHOLD`
 * (soft warning, không chặn).
 */
class StoreDraftedArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('content_outlines.use');
    }

    public function rules(): array
    {
        return [
            'drafted_article' => ['required', 'string', 'max:100000'],
        ];
    }
}
