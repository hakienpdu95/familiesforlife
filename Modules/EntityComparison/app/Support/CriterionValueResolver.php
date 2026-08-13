<?php

namespace Modules\EntityComparison\Support;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Modules\EntityComparison\Enums\CriterionType;
use Modules\EntityComparison\Models\Criterion;
use Modules\EntityComparison\Models\CriterionValue;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §3.5.1/§3.5.2 mục 3 — nguồn sự thật duy nhất
 * cho việc đọc/ghi/format mọi CriterionType. Mirror Modules\Survey\Support\AnswerValueResolver,
 * rút gọn vì mỗi CriterionType đã map 1-1 với 1 cách lưu (không cần enum ValueKind tách riêng).
 *
 * Đọc kỹ §3.5.1 trước khi sửa — multi_select và range không map 1-1 vào 1 cột đơn như các type
 * còn lại.
 */
final class CriterionValueResolver
{
    /**
     * write() LUÔN persist ($value->save()/sync()) — không để caller phải nhớ type nào cần
     * save() thủ công. $value phải đã có entity_id/criterion_id gán sẵn (firstOrNew ở
     * SetCriterionValuesAction) trước khi gọi.
     */
    public function write(CriterionValue $value, Criterion $criterion, mixed $input): void
    {
        match ($criterion->type) {
            CriterionType::Text => $this->writeText($value, $input),
            CriterionType::Number => $this->writeNumber($value, $input),
            CriterionType::Boolean => $this->writeBoolean($value, $input),
            CriterionType::Date => $this->writeDate($value, $input),
            CriterionType::Select => $this->writeSelect($value, $input),
            CriterionType::Range => $this->writeRange($value, $input),
            CriterionType::MultiSelect => $this->writeMultiSelect($value, $input),
        };
    }

    public function read(CriterionValue $value, Criterion $criterion): CriterionValueResult
    {
        return match ($criterion->type) {
            CriterionType::Text => CriterionValueResult::scalar($value->value_string),
            CriterionType::Number => CriterionValueResult::scalar(
                $value->value_number !== null ? (float) $value->value_number : null
            ),
            CriterionType::Boolean => CriterionValueResult::scalar($value->value_bool),
            CriterionType::Date => CriterionValueResult::scalar($value->value_date),
            CriterionType::Select => CriterionValueResult::scalar($value->option),
            CriterionType::Range => CriterionValueResult::scalar(
                $value->value_number !== null && $value->value_number_max !== null
                    ? ['min' => (float) $value->value_number, 'max' => (float) $value->value_number_max]
                    : null
            ),
            CriterionType::MultiSelect => CriterionValueResult::multiple($value->options),
        };
    }

    /** $result->isMultiple() chọn cách format (badge list vs text đơn) — kèm $criterion->unit. */
    public function format(CriterionValueResult $result, Criterion $criterion): string
    {
        if ($result->isEmpty()) {
            return '—';
        }

        if ($result->isMultiple()) {
            return $result->asOptions()->pluck('label')->implode(', ');
        }

        $unit = $criterion->unit ? " {$criterion->unit}" : '';

        return match ($criterion->type) {
            CriterionType::Text => (string) $result->asScalar(),
            CriterionType::Number => number_format((float) $result->asScalar(), 0, ',', '.').$unit,
            CriterionType::Boolean => $result->asScalar() ? 'Có' : 'Không',
            CriterionType::Date => $result->asScalar() instanceof Carbon ? $result->asScalar()->format('d/m/Y') : (string) $result->asScalar(),
            CriterionType::Select => $result->asScalar()?->label ?? '—',
            CriterionType::Range => $this->formatRange($result->asScalar(), $unit),
            CriterionType::MultiSelect => throw new InvalidArgumentException('multi_select luôn isMultiple() === true — không tới nhánh này.'),
        };
    }

    // ── write() theo type ────────────────────────────────────────────

    private function writeText(CriterionValue $value, mixed $input): void
    {
        $value->value_string = $input !== null ? (string) $input : null;
        $value->save();
    }

    private function writeNumber(CriterionValue $value, mixed $input): void
    {
        $value->value_number = $input !== null ? (float) $input : null;
        $value->save();
    }

    private function writeBoolean(CriterionValue $value, mixed $input): void
    {
        $value->value_bool = $input !== null ? (bool) $input : null;
        $value->save();
    }

    private function writeDate(CriterionValue $value, mixed $input): void
    {
        $value->value_date = $input !== null ? Carbon::parse($input) : null;
        $value->save();
    }

    /** $input là option_id (int) đã chọn — validate option thuộc đúng criterion là việc của Action. */
    private function writeSelect(CriterionValue $value, mixed $input): void
    {
        $value->option_id = $input !== null ? (int) $input : null;
        $value->save();
    }

    /**
     * $input dạng ['min' => x, 'max' => y] — §3.5.1. min<=max validate ở Action, không ở đây.
     * `isset($input['min'])` KHÔNG đủ — true kể cả khi 'min' là chuỗi rỗng (bỏ trống "Từ" trên
     * form nhưng vẫn điền "Đến"), khi đó `(float) ''` = 0.0 sẽ bị ghi nhầm thành 0 thay vì NULL.
     * Phải loại trừ '' tường minh.
     */
    private function writeRange(CriterionValue $value, mixed $input): void
    {
        $value->value_number = isset($input['min']) && $input['min'] !== '' ? (float) $input['min'] : null;
        $value->value_number_max = isset($input['max']) && $input['max'] !== '' ? (float) $input['max'] : null;
        $value->save();
    }

    /**
     * $input là mảng option_id (int[]) đã chọn. Header row phải save() trước để có id làm điểm
     * neo FK cho criterion_value_options — sync() không tự save() model cha.
     */
    private function writeMultiSelect(CriterionValue $value, mixed $input): void
    {
        if (! $value->exists) {
            $value->save();
        }

        $value->options()->sync(array_map('intval', $input ?? []));
    }

    private function formatRange(?array $range, string $unit): string
    {
        if ($range === null) {
            return '—';
        }

        $min = number_format($range['min'], 0, ',', '.');
        $max = number_format($range['max'], 0, ',', '.');

        return "{$min}–{$max}{$unit}";
    }
}
