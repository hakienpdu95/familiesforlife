<?php

namespace Modules\Survey\Support;

use Modules\Survey\Enums\ValueKind;

/**
 * Nơi DUY NHẤT quyết định value_kind → cột vật lý trong survey_answers.
 *
 * resolve() trả về một hoặc nhiều row-arrays sẵn sàng để insert.
 * Caller (SubmitSurveyAction) chỉ cần merge thêm response_id + field_id rồi insert.
 *
 * Multi-choice (checkbox): mỗi option được chọn = một phần tử trong mảng kết quả.
 * "Other" option: row đó có thêm value_string chứa nội dung người dùng nhập.
 */
final class AnswerValueResolver
{
    /**
     * @param ValueKind   $kind          Loại giá trị (từ SurveyField::value_kind)
     * @param mixed       $value         Giá trị thô từ submit — scalar hoặc int[] (checkbox)
     * @param string|null $otherText     Text nhập khi chọn option is_other = 1
     * @param int|null    $otherOptionId ID của option is_other (để biết gắn text vào row nào)
     *
     * @return array<int, array<string, mixed>>  Mảng row-arrays để insert vào survey_answers
     */
    public function resolve(
        ValueKind $kind,
        mixed $value,
        ?string $otherText = null,
        ?int $otherOptionId = null,
    ): array {
        return match ($kind) {
            ValueKind::String => [['value_string' => (string) $value]],
            ValueKind::Text   => [['value_text'   => (string) $value]],
            ValueKind::Number => [['value_number' => (float)  $value]],
            ValueKind::Date   => [['value_date'   => (string) $value]],
            ValueKind::Bool   => [['value_bool'   => (bool)   $value]],
            ValueKind::Option => $this->resolveOption($value, $otherText, $otherOptionId),
        };
    }

    /**
     * @param int|int[]   $value
     * @return array<int, array<string, mixed>>
     */
    private function resolveOption(
        mixed $value,
        ?string $otherText,
        ?int $otherOptionId,
    ): array {
        $optionIds = is_array($value)
            ? array_map('intval', $value)
            : [(int) $value];

        $hasOtherText = $otherText !== null && $otherText !== '';

        return array_map(function (int $optionId) use ($hasOtherText, $otherText, $otherOptionId, $optionIds): array {
            $row = ['option_id' => $optionId];

            // Gắn value_string vào row của option "Khác"
            if ($hasOtherText && $this->isOtherRow($optionId, $optionIds, $otherOptionId)) {
                $row['value_string'] = $otherText;
            }

            return $row;
        }, $optionIds);
    }

    /**
     * Xác định row nào là "other row" để gắn value_string.
     *
     * - Nếu biết otherOptionId: chỉ đúng option đó.
     * - Nếu không biết (single option + có text): mặc định gắn vào option duy nhất đó.
     */
    private function isOtherRow(int $optionId, array $allIds, ?int $otherOptionId): bool
    {
        if ($otherOptionId !== null) {
            return $optionId === $otherOptionId;
        }

        // Single-option submit không truyền otherOptionId → gắn vào option duy nhất
        return count($allIds) === 1;
    }
}
