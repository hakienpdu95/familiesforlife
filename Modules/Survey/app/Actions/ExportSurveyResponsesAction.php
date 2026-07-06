<?php

namespace Modules\Survey\Actions;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Jobs\ExportSurveyResponsesJob;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyAnswer;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyResponse;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Xuất danh sách responses thành file Excel.
 *
 * ≤ 10.000 rows: streaming download đồng bộ, dùng cursor() thay get().
 * > 10.000 rows: dispatch Queue job (ExportSurveyResponsesJob) + trả về null
 *   để controller redirect với flash message.
 *
 * Bulk load answers (1 query per chunk) → không N+1.
 */
class ExportSurveyResponsesAction
{
    use AsAction;

    public const SYNC_LIMIT = 10_000;

    public function handle(
        Survey  $survey,
        ?string $respondentRef = null,
        ?string $from          = null,
        ?string $to            = null,
    ): StreamedResponse|array {
        $fields    = $this->loadFields($survey);
        $optionMap = $this->buildOptionMap($fields);

        $total = $this->countResponses($survey, $respondentRef, $from, $to);

        if ($total === 0) {
            return $this->downloadEmpty($survey, $fields);
        }

        // > 10.000 rows: phải dùng Queue job, không export đồng bộ
        if ($total > static::SYNC_LIMIT) {
            $outputKey = \Illuminate\Support\Str::uuid()->toString();

            ExportSurveyResponsesJob::dispatch($survey->id, $outputKey, $respondentRef, $from, $to);

            return ['queued_key' => $outputKey, 'slug' => $survey->slug];
        }

        // ≤ 10.000 rows: đồng bộ, dùng cursor() để tiết kiệm RAM
        $responses   = $this->loadResponses($survey, $respondentRef, $from, $to);
        $answerIndex = $this->buildAnswerIndex($responses->pluck('id')->all());

        $rows = $this->rowGenerator($responses, $fields, $answerIndex, $optionMap);

        $safeSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower($survey->slug));
        $filename = sprintf('survey-%s-responses-%s.xlsx', $safeSlug, now()->format('Ymd_His'));

        return (new FastExcel($rows))->download($filename);
    }

    // ── Data loading ──────────────────────────────────────────────────────

    private function countResponses(Survey $survey, ?string $respondentRef, ?string $from, ?string $to): int
    {
        return SurveyResponse::forSurvey($survey->id)
            ->complete()
            ->when($respondentRef, fn ($q) => $q->where('respondent_ref', $respondentRef))
            ->when($from, fn ($q) => $q->where('submitted_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('submitted_at', '<=', $to . ' 23:59:59'))
            ->count();
    }

    /** @return Collection<int, SurveyField> ordered, with options */
    private function loadFields(Survey $survey): Collection
    {
        return SurveyField::forSurvey($survey->id)
            ->active()
            ->with(['options' => fn ($q) => $q->ordered()])
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, SurveyResponse>
     * Dùng INDEX (survey_id, status, submitted_at) cho filter submitted_at range.
     */
    private function loadResponses(
        Survey  $survey,
        ?string $respondentRef,
        ?string $from,
        ?string $to,
    ): Collection {
        return SurveyResponse::forSurvey($survey->id)
            ->complete()
            ->when($respondentRef, fn ($q) => $q->where('respondent_ref', $respondentRef))
            ->when($from,          fn ($q) => $q->where('submitted_at', '>=', $from))
            ->when($to,            fn ($q) => $q->where('submitted_at', '<=', $to . ' 23:59:59'))
            ->orderBy('submitted_at')
            ->get();
    }

    /**
     * Bulk load tất cả answers, trả về index 2 cấp:
     *   [response_id => [field_id => Collection<SurveyAnswer>]]
     *
     * Dùng INDEX (response_id, field_id) — covering index cho pivot.
     *
     * @param  int[]  $responseIds
     * @return array<int, array<int, Collection>>
     */
    private function buildAnswerIndex(array $responseIds): array
    {
        $answers = SurveyAnswer::query()
            ->select(['response_id', 'field_id', 'option_id', 'value_string', 'value_text', 'value_number', 'value_date', 'value_bool'])
            ->whereIn('response_id', $responseIds)
            ->orderBy('response_id')
            ->orderBy('field_id')
            ->get();

        $index = [];
        foreach ($answers as $answer) {
            $index[$answer->response_id][$answer->field_id][] = $answer;
        }

        return $index;
    }

    /**
     * Map option_id → label cho tất cả fields.
     * @return array<int, string>  option_id => label
     */
    private function buildOptionMap(Collection $fields): array
    {
        $map = [];
        foreach ($fields as $field) {
            foreach ($field->options as $option) {
                $map[$option->id] = $option->label;
            }
        }

        return $map;
    }

    // ── Row generator ─────────────────────────────────────────────────────

    /**
     * Yield từng row cho FastExcel — không giữ toàn bộ dataset trong RAM.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    private function rowGenerator(
        Collection $responses,
        Collection $fields,
        array      $answerIndex,
        array      $optionMap,
    ): \Generator {
        foreach ($responses as $response) {
            $fieldAnswers = $answerIndex[$response->id] ?? [];

            $row = [
                'ID'           => $response->id,
                'Respondent'   => $response->respondent_ref ?? '',
                'Submitted At' => $response->submitted_at?->format('Y-m-d H:i:s') ?? '',
            ];

            foreach ($fields as $field) {
                $answers = $fieldAnswers[$field->id] ?? [];
                $row[$field->label] = $this->formatCell($field, $answers, $optionMap);
            }

            yield $row;
        }
    }

    // ── Cell formatter ────────────────────────────────────────────────────

    /**
     * @param  array<int, SurveyAnswer>  $answers  — có thể nhiều row (checkbox)
     */
    private function formatCell(SurveyField $field, array $answers, array $optionMap): string
    {
        if (empty($answers)) {
            return '';
        }

        return match ($field->field_type) {
            FieldType::Text     => (string) ($answers[0]->value_string ?? ''),
            FieldType::Textarea => (string) ($answers[0]->value_text   ?? ''),
            FieldType::Number,
            FieldType::Rating   => $answers[0]->value_number !== null
                                    ? rtrim(rtrim(number_format((float) $answers[0]->value_number, 2), '0'), '.')
                                    : '',
            FieldType::Date     => $answers[0]->value_date
                                    ? \Carbon\Carbon::parse($answers[0]->value_date)->format('Y-m-d')
                                    : '',
            FieldType::Boolean  => $this->formatBool($answers[0]->value_bool),

            FieldType::Select,
            FieldType::Radio    => $optionMap[$answers[0]->option_id] ?? '',

            // Checkbox: nhiều rows — nối label bằng ", "
            FieldType::Checkbox => implode(', ', array_filter(
                array_map(fn ($a) => $optionMap[$a->option_id] ?? null, $answers)
            )),
        };
    }

    private function formatBool(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return ((int) $value === 1) ? 'Có' : 'Không';
    }

    // ── Empty export fallback ─────────────────────────────────────────────

    private function downloadEmpty(Survey $survey, Collection $fields): StreamedResponse
    {
        $header = array_merge(
            ['ID' => '', 'Respondent' => '', 'Submitted At' => ''],
            $fields->mapWithKeys(fn ($f) => [$f->label => ''])->all()
        );

        $safeSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower($survey->slug));
        $filename = sprintf('survey-%s-responses-%s.xlsx', $safeSlug, now()->format('Ymd_His'));

        return (new FastExcel(collect([$header])))->download($filename);
    }
}
