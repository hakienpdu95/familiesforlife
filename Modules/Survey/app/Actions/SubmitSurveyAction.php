<?php

namespace Modules\Survey\Actions;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Data\SurveyAnswerData;
use Modules\Survey\Data\SurveyResponseData;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\ResponseStatus;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Exceptions\SurveyNotActiveException;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyAnswer;
use Modules\Survey\Models\SurveyDraft;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyFieldCondition;
use Modules\Survey\Models\SurveyResponse;
use Modules\Survey\Services\WebhookDispatcher;
use Modules\Survey\Jobs\CalculateSurveyScoreJob;
use Modules\WorkflowAutomation\Support\Workflows;
use Modules\Survey\Services\SurveyStatsService;
use Modules\Survey\Support\AnswerValueResolver;
use Spatie\LaravelData\DataCollection;

/**
 * Core submit flow:
 *   validate 5 lớp (tất cả errors trước) → transaction → insert answers.
 *
 * Không tự load survey — caller truyền model đã fetch (tránh duplicate query).
 * Fields + options được load một lần trong loadFieldMap(), không có N+1.
 */
class SubmitSurveyAction
{
    use AsAction;

    public function __construct(
        private readonly AnswerValueResolver $resolver,
        private readonly WebhookDispatcher  $webhooks,
    ) {}

    /**
     * @throws SurveyNotActiveException  403 nếu survey không ở trạng thái active
     * @throws ValidationException       422 với errors keyed by field_key
     * @return int                       response_id của bản ghi vừa tạo
     */
    public function handle(Survey $survey, SurveyResponseData $data): int
    {
        // Fast-fail before loading fields (non-binding; re-checked with lock inside transaction).
        if ($survey->status !== SurveyStatus::Active) {
            throw new SurveyNotActiveException($survey->status);
        }

        // Dedup: nếu survey không cho phép nhiều lần submit
        if (! $survey->allow_multiple_responses && $data->respondent_ref !== null) {
            $exists = SurveyResponse::forSurvey($survey->id)
                ->where('respondent_ref', $data->respondent_ref)
                ->where('status', ResponseStatus::Complete->value)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'respondent_ref' => 'Bạn đã hoàn thành khảo sát này rồi.',
                ]);
            }
        }

        // Log warning nếu cùng ref submit trong vòng 5 phút (phát hiện bot/spam).
        if ($data->respondent_ref !== null) {
            $recentExists = SurveyResponse::forSurvey($survey->id)
                ->where('respondent_ref', $data->respondent_ref)
                ->where('submitted_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($recentExists) {
                Log::warning('survey.duplicate_submit', [
                    'survey_id'      => $survey->id,
                    'respondent_ref' => $data->respondent_ref,
                ]);
            }
        }

        $fieldMap = $this->loadFieldMap($survey);

        $errors = $this->runValidation($data->answers, $fieldMap);
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $surveyId   = $survey->id;
        $responseId = DB::transaction(function () use ($survey, $data, $fieldMap): int {
            // Authoritative status check with row lock — prevents TOCTOU race.
            $locked = Survey::lockForUpdate()->find($survey->id);
            if ($locked === null || $locked->status !== SurveyStatus::Active) {
                throw new SurveyNotActiveException($locked?->status ?? $survey->status);
            }

            $response = new SurveyResponse([
                'survey_id'      => $survey->id,
                'respondent_ref' => $data->respondent_ref,
                'respondent_ip'  => $data->respondent_ip,
                'submitted_at'   => now(),
            ]);
            $response->status = ResponseStatus::Complete;
            $response->save();

            $rows = [];
            foreach ($data->answers as $answer) {
                /** @var SurveyAnswerData $answer */
                array_push(
                    $rows,
                    ...$this->buildAnswerRows($answer, $fieldMap->get($answer->field_key), $response->id)
                );
            }

            if (!empty($rows)) {
                SurveyAnswer::insert($rows);
            }

            return $response->id;
        });

        // Purge cache after a committed transaction — never inside the transaction boundary.
        SurveyStatsService::purgeCache($surveyId);

        // Dispatch scoring job nếu survey có assessment_code
        if ($survey->assessment_code !== null) {
            CalculateSurveyScoreJob::dispatch($responseId);
        }

        // Fire workflow trigger (this code already runs after the DB transaction commits)
        $response = SurveyResponse::with('survey')->find($responseId);
        if ($response !== null) {
            Workflows::fireNow('survey.submitted', $response, [
                'survey_id'      => $response->survey_id,
                'survey_slug'    => $response->survey?->slug,
                'respondent_ref' => $response->respondent_ref,
            ]);
        }

        // Dispatch webhook for response.created event
        $this->webhooks->dispatch($survey->id, 'response.created', [
            'survey_id'      => $survey->id,
            'survey_slug'    => $survey->slug,
            'response_id'    => $responseId,
            'respondent_ref' => $data->respondent_ref,
            'submitted_at'   => now()->toISOString(),
        ]);

        // Clear server-side draft if one exists for this respondent
        if ($data->respondent_ref !== null) {
            SurveyDraft::where('survey_id', $survey->id)
                ->where('respondent_ref', $data->respondent_ref)
                ->delete();
        }

        return $responseId;
    }

    // ── Field map ─────────────────────────────────────────────────────────

    /** @return Collection<string, SurveyField>  keyed by field_key */
    private function loadFieldMap(Survey $survey): Collection
    {
        return SurveyField::forSurvey($survey->id)
            ->active()
            ->with([
                'options'    => fn ($q) => $q->ordered(),
                'conditions' => fn ($q) => $q->orderBy('sort_order'),
                'rows'       => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->get()
            ->keyBy('field_key');
    }

    // ── Validation orchestrator ───────────────────────────────────────────

    /**
     * Chạy cả 5 lớp, thu thập TẤT CẢ lỗi trước khi trả về.
     * Không throw ngay khi gặp lỗi đầu tiên — để user biết tất cả field cần sửa.
     *
     * @return array<string, string[]>  lỗi keyed by field_key
     */
    private function runValidation(DataCollection $answers, Collection $fieldMap): array
    {
        $errors          = [];
        $submittedFields = []; // field_key => SurveyAnswerData (để layer 4 dùng)

        // Build answer value map for conditional visibility evaluation
        $answerValues = [];
        foreach ($answers as $answer) {
            $answerValues[$answer->field_key] = $answer->value;
        }

        // Compute which fields are hidden by conditions
        $hiddenFields = $this->computeHiddenFields($fieldMap, $answerValues);

        foreach ($answers as $answer) {
            /** @var SurveyAnswerData $answer */
            $key = $answer->field_key;

            // Duplicate field_key trong cùng một submit
            if (isset($submittedFields[$key])) {
                $errors[$key][] = "field_key '$key' bị trùng trong cùng một lần submit.";
                continue;
            }
            $submittedFields[$key] = $answer;

            // Layer 1 — field tồn tại + is_active
            $field = $this->layer1($answer, $fieldMap, $errors);
            if ($field === null) {
                continue; // Không thể tiếp tục validate field không tồn tại
            }

            // Layer 2 — kiểu dữ liệu khớp field_type
            $this->layer2($answer, $field, $errors);

            // Layer 3 — option_value hợp lệ (chỉ khi layer 2 không lỗi)
            if (!isset($errors[$key]) && $field->field_type->isChoice()) {
                $this->layer3($answer, $field, $errors);
            }

            // Layer 5 — rule_min / rule_max / rule_max_select (chỉ khi chưa có lỗi)
            if (!isset($errors[$key])) {
                $this->layer5($answer, $field, $errors);
            }
        }

        // Layer 4 — required fields (chạy sau khi có đủ danh sách đã submit)
        $this->layer4($submittedFields, $fieldMap, $errors, $hiddenFields);

        return $errors;
    }

    // ── Conditional visibility ────────────────────────────────────────────

    /**
     * Compute which field_keys are hidden based on conditions.
     * Mirrors the client-side evalCondition logic for consistency.
     *
     * @param  Collection<string, SurveyField>  $fieldMap
     * @param  array<string, mixed>             $answerValues  [field_key => raw value from submission]
     * @return array<string, bool>              [field_key => true if hidden]
     */
    private function computeHiddenFields(Collection $fieldMap, array $answerValues): array
    {
        $hidden = [];

        foreach ($fieldMap as $fieldKey => $field) {
            if (! $field->relationLoaded('conditions') || $field->conditions->isEmpty()) {
                continue;
            }

            foreach ($field->conditions as $condition) {
                // Resolve the depends_on field_key
                $depField = $fieldMap->first(fn ($f) => $f->id === $condition->depends_on_field_id);
                if (! $depField) {
                    continue;
                }

                $depVal    = $answerValues[$depField->field_key] ?? null;
                $condMet   = $this->evalCondition($depVal, $condition->operator, $condition->trigger_value);
                $isVisible = $condition->action === 'show' ? $condMet : !$condMet;

                if (! $isVisible) {
                    $hidden[$fieldKey] = true;
                    break; // any single condition making it hidden is enough
                }
            }
        }

        return $hidden;
    }

    private function evalCondition(mixed $val, string $op, mixed $target): bool
    {
        return match ($op) {
            '='        => $val == $target,
            '!='       => $val != $target,
            'in'       => is_array($target) && in_array($val, $target, false),
            'not_in'   => is_array($target) && ! in_array($val, $target, false),
            'answered' => $val !== null && $val !== '' && $val !== [],
            '>'        => is_numeric($val) && is_numeric($target) && (float) $val > (float) $target,
            '<'        => is_numeric($val) && is_numeric($target) && (float) $val < (float) $target,
            'contains' => is_array($val) && in_array($target, $val, false),
            default    => true,
        };
    }

    // ── Layer 1 — field_key tồn tại + is_active ──────────────────────────

    private function layer1(SurveyAnswerData $answer, Collection $fieldMap, array &$errors): ?SurveyField
    {
        $field = $fieldMap->get($answer->field_key);

        if ($field === null) {
            $errors[$answer->field_key][] =
                "field_key '{$answer->field_key}' không tồn tại hoặc đã bị tắt trong khảo sát này.";
        }

        return $field;
    }

    // ── Layer 2 — type compatibility ──────────────────────────────────────

    private function layer2(SurveyAnswerData $answer, SurveyField $field, array &$errors): void
    {
        $key   = $answer->field_key;
        $value = $answer->value;

        match ($field->field_type) {
            FieldType::Checkbox => $this->assertNonEmptyArray($key, $value, $errors),

            FieldType::Select,
            FieldType::Radio    => $this->assertScalar($key, $value, $errors),

            FieldType::Number,
            FieldType::Rating   => $this->assertNumeric($key, $value, $errors),

            FieldType::Nps      => $this->assertNps($key, $value, $errors),

            FieldType::Date     => $this->assertDate($key, $value, $errors),

            FieldType::Boolean  => $this->assertBoolean($key, $value, $errors),

            FieldType::Text,
            FieldType::Textarea => $this->assertString($key, $value, $errors),

            FieldType::Matrix   => $this->assertMatrix($key, $value, $field, $errors),
            FieldType::Ranking  => $this->assertRanking($key, $value, $field, $errors),
        };
    }

    private function assertNonEmptyArray(string $key, mixed $value, array &$errors): void
    {
        if (!is_array($value) || empty($value)) {
            $errors[$key][] = 'Trường này yêu cầu ít nhất một lựa chọn (phải là mảng).';
        }
    }

    private function assertScalar(string $key, mixed $value, array &$errors): void
    {
        if (is_array($value)) {
            $errors[$key][] = 'Trường này chỉ nhận một giá trị, không phải mảng.';
        }
    }

    private function assertNumeric(string $key, mixed $value, array &$errors): void
    {
        if (!is_numeric($value)) {
            $errors[$key][] = 'Trường này phải là số.';
        }
    }

    private function assertDate(string $key, mixed $value, array &$errors): void
    {
        // checkdate() rejects overflow dates (e.g. 2024-02-30) that Carbon 3.x silently wraps.
        if (!is_string($value)
            || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)
            || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])
        ) {
            $errors[$key][] = 'Trường này phải là ngày hợp lệ theo định dạng YYYY-MM-DD.';
        }
    }

    private function assertBoolean(string $key, mixed $value, array &$errors): void
    {
        $valid = is_bool($value) || in_array($value, [0, 1, '0', '1'], true);

        if (!$valid) {
            $errors[$key][] = 'Trường này phải là giá trị đúng/sai (true hoặc false).';
        }
    }

    private function assertString(string $key, mixed $value, array &$errors): void
    {
        if (!is_string($value) && !is_numeric($value)) {
            $errors[$key][] = 'Trường này phải là chuỗi văn bản.';
        }
    }

    private function assertNps(string $key, mixed $value, array &$errors): void
    {
        if (!is_numeric($value) || (int) $value < 0 || (int) $value > 10) {
            $errors[$key][] = 'NPS phải là số nguyên từ 0 đến 10.';
        }
    }

    private function assertMatrix(string $key, mixed $value, SurveyField $field, array &$errors): void
    {
        if (!is_array($value) || empty($value)) {
            $errors[$key][] = 'Trường ma trận phải là một object có dạng {row_key: option_value}.';
            return;
        }

        $validRowKeys    = $field->rows->pluck('row_key')->all();
        $validOptionVals = $field->options->pluck('option_value')->all();

        foreach ($value as $rowKey => $optionValue) {
            if (!in_array($rowKey, $validRowKeys, true)) {
                $errors[$key][] = "Row key '$rowKey' không hợp lệ cho trường '{$key}'.";
            }
            if (!in_array((string) $optionValue, $validOptionVals, true)) {
                $errors[$key][] = "Lựa chọn '$optionValue' không hợp lệ cho trường '{$key}' row '$rowKey'.";
            }
        }
    }

    private function assertRanking(string $key, mixed $value, SurveyField $field, array &$errors): void
    {
        if (!is_array($value) || empty($value)) {
            $errors[$key][] = 'Trường xếp hạng phải là một mảng các giá trị lựa chọn.';
            return;
        }

        $validOptionVals = $field->options->pluck('option_value')->all();

        foreach ($value as $optionValue) {
            if (!in_array((string) $optionValue, $validOptionVals, true)) {
                $errors[$key][] = "Lựa chọn '$optionValue' không hợp lệ cho trường '{$key}'.";
            }
        }

        if (count(array_unique(array_map('strval', $value))) !== count($value)) {
            $errors[$key][] = 'Xếp hạng không được có giá trị trùng lặp.';
        }
    }

    // ── Layer 3 — option_value hợp lệ + is_other text ────────────────────

    private function layer3(SurveyAnswerData $answer, SurveyField $field, array &$errors): void
    {
        $optionsByValue = $field->options->keyBy('option_value');
        $submitted      = is_array($answer->value)
            ? $answer->value
            : [$answer->value];

        foreach ($submitted as $optionValue) {
            if (!$optionsByValue->has((string) $optionValue)) {
                $errors[$answer->field_key][] =
                    "Lựa chọn '$optionValue' không hợp lệ cho trường '{$answer->field_key}'.";
                continue;
            }

            // is_other: nếu field required, other_text không được rỗng
            $opt = $optionsByValue->get((string) $optionValue);
            if ($opt->is_other && $field->is_required) {
                $otherText = $answer->other_text ?? '';
                if (trim($otherText) === '') {
                    $errors[$answer->field_key][] =
                        "Trường '{$field->label}' yêu cầu nhập nội dung khi chọn lựa chọn khác.";
                }
            }
        }
    }

    // ── Layer 4 — required fields ─────────────────────────────────────────

    /**
     * @param array<string, SurveyAnswerData>  $submittedFields
     * @param array<string, bool>              $hiddenFields  [field_key => true] for hidden fields
     */
    private function layer4(array $submittedFields, Collection $fieldMap, array &$errors, array $hiddenFields = []): void
    {
        $fieldMap
            ->filter(fn (SurveyField $f) => $f->is_required && ! isset($hiddenFields[$f->field_key]))
            ->each(function (SurveyField $field) use ($submittedFields, &$errors): void {
                $key = $field->field_key;

                // Field bắt buộc nhưng không có trong submit
                if (!array_key_exists($key, $submittedFields)) {
                    $errors[$key][] = "Trường '{$field->label}' là bắt buộc.";
                    return;
                }

                // Field có mặt nhưng value rỗng
                $value   = $submittedFields[$key]->value;
                $isEmpty = $value === null
                    || $value === ''
                    || (is_array($value) && empty($value));

                if ($isEmpty) {
                    $errors[$key][] = "Trường '{$field->label}' là bắt buộc và không được để trống.";
                }
            });
    }

    // ── Layer 5 — rule constraints ────────────────────────────────────────

    private function layer5(SurveyAnswerData $answer, SurveyField $field, array &$errors): void
    {
        $key   = $answer->field_key;
        $value = $answer->value;

        // Number / Rating: giá trị nằm trong khoảng [rule_min, rule_max]
        if ($field->field_type === FieldType::Number || $field->field_type === FieldType::Rating) {
            $num = (float) $value;

            if ($field->rule_min !== null && $num < $field->rule_min) {
                $errors[$key][] = "Giá trị tối thiểu cho trường này là {$field->rule_min}.";
            }
            if ($field->rule_max !== null && $num > $field->rule_max) {
                $errors[$key][] = "Giá trị tối đa cho trường này là {$field->rule_max}.";
            }
        }

        // Text / Textarea: độ dài chuỗi trong khoảng [rule_min, rule_max]
        if ($field->field_type === FieldType::Text || $field->field_type === FieldType::Textarea) {
            $len = mb_strlen((string) $value);

            if ($field->rule_min !== null && $len < $field->rule_min) {
                $errors[$key][] = "Cần nhập tối thiểu {$field->rule_min} ký tự (hiện tại: $len).";
            }
            if ($field->rule_max !== null && $len > $field->rule_max) {
                $errors[$key][] = "Vượt quá giới hạn {$field->rule_max} ký tự (hiện tại: $len).";
            }
        }

        // Checkbox: số lựa chọn không vượt quá rule_max_select
        if ($field->field_type === FieldType::Checkbox
            && is_array($value)
            && $field->rule_max_select !== null
            && count($value) > $field->rule_max_select
        ) {
            $errors[$key][] = "Chỉ được chọn tối đa {$field->rule_max_select} lựa chọn.";
        }
    }

    // ── Answer row builder ────────────────────────────────────────────────

    /**
     * Chuyển một SurveyAnswerData thành một hoặc nhiều row để bulk-insert.
     * Choice fields: mỗi option_value được chọn = một row riêng.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildAnswerRows(SurveyAnswerData $answer, SurveyField $field, int $responseId): array
    {
        // All nullable value columns must be present in every row so that
        // Laravel's insert() doesn't misalign columns when ksort() normalises
        // rows that have different key sets (e.g. option_id vs value_string).
        $baseRow = [
            'response_id'  => $responseId,
            'field_id'     => $field->id,
            'row_key'      => null,
            'option_id'    => null,
            'value_string' => null,
            'value_text'   => null,
            'value_number' => null,
            'value_date'   => null,
            'value_bool'   => null,
            'created_at'   => now(),
        ];

        if ($field->field_type->isMatrixLike()) {
            // value = {row_key: option_value, ...}
            $optionsByValue = $field->options->keyBy('option_value');
            $rows = [];
            foreach ((array) $answer->value as $rowKey => $optionValue) {
                $option = $optionsByValue[(string) $optionValue] ?? null;
                if ($option) {
                    $rows[] = array_merge($baseRow, [
                        'row_key'   => (string) $rowKey,
                        'option_id' => $option->id,
                    ]);
                }
            }
            return $rows;
        }

        if ($field->field_type->isRanking()) {
            // value = ['opt_val1', 'opt_val2', ...] — first element = rank 1
            $optionsByValue = $field->options->keyBy('option_value');
            $rows = [];
            foreach (array_values((array) $answer->value) as $rank => $optionValue) {
                $option = $optionsByValue[(string) $optionValue] ?? null;
                if ($option) {
                    $rows[] = array_merge($baseRow, [
                        'option_id'    => $option->id,
                        'value_number' => $rank + 1,
                    ]);
                }
            }
            return $rows;
        }

        if ($field->field_type->isChoice()) {
            $optionsByValue = $field->options->keyBy('option_value');
            $submitted      = is_array($answer->value) ? $answer->value : [$answer->value];

            // option_value → option_id
            $optionIds = array_map(
                fn ($v) => $optionsByValue[(string) $v]->id,
                $submitted
            );

            // Tìm is_other option_id trong các lựa chọn đã submit
            $otherOptionId = null;
            foreach ($submitted as $v) {
                $opt = $optionsByValue[(string) $v];
                if ($opt->is_other) {
                    $otherOptionId = $opt->id;
                    break;
                }
            }

            $resolvedRows = $this->resolver->resolve(
                $field->value_kind,
                $optionIds,
                $answer->other_text,
                $otherOptionId,
            );
        } else {
            $resolvedRows = $this->resolver->resolve($field->value_kind, $answer->value);
        }

        return array_map(fn ($row) => array_merge($baseRow, $row), $resolvedRows);
    }
}
