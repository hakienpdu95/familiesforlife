<?php

namespace Modules\Survey\Data;

use Spatie\LaravelData\Data;

/**
 * Một câu trả lời từ frontend.
 * value là scalar (text, number, date, bool) hoặc int[] (checkbox multi-choice).
 */
class SurveyAnswerData extends Data
{
    public function __construct(
        public readonly string  $field_key,
        public readonly mixed   $value,
        public readonly ?string $other_text = null,
    ) {}
}
