<?php

namespace Modules\Survey\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyAnswer;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyResponse;
use Rap2hpoutre\FastExcel\FastExcel;

/**
 * Xuất danh sách responses ra file Excel dưới dạng Queue job.
 *
 * Dùng DB::cursor() (LazyCollection) + chunk 2000 để không load hết vào RAM.
 * File được lưu tại storage/app/exports/{outputKey}.xlsx.
 * Sau khi hoàn thành, đường dẫn được lưu vào Redis với TTL 1 giờ.
 */
class ExportSurveyResponsesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 phút cho dataset lớn
    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        private readonly int     $surveyId,
        private readonly string  $outputKey,
        private readonly ?string $respondentRef = null,
        private readonly ?string $from          = null,
        private readonly ?string $to            = null,
    ) {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $survey    = Survey::findOrFail($this->surveyId);
        $fields    = $this->loadFields($survey);
        $optionMap = $this->buildOptionMap($fields);

        $exportDir = storage_path('app/exports');
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $safeSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower($survey->slug));
        $filename = "survey-{$safeSlug}-export-{$this->outputKey}.xlsx";
        $path     = "{$exportDir}/{$filename}";

        (new FastExcel($this->generateRows($survey, $fields, $optionMap)))->export($path);

        Cache::store('redis')->put("survey:export:{$this->outputKey}", [
            'path'     => $path,
            'filename' => $filename,
        ], 3600);
    }

    // ── Generator: LazyCollection cursor + chunk 2000 ────────────────────

    private function generateRows(Survey $survey, Collection $fields, array $optionMap): \Generator
    {
        $query = SurveyResponse::forSurvey($survey->id)
            ->complete()
            ->select(['id', 'respondent_ref', 'submitted_at'])
            ->when($this->respondentRef, fn ($q) => $q->where('respondent_ref', $this->respondentRef))
            ->when($this->from, fn ($q) => $q->where('submitted_at', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->where('submitted_at', '<=', $this->to . ' 23:59:59'))
            ->orderBy('submitted_at');

        // LazyCollection::chunk(2000) → yield từng chunk thành Collection
        foreach ($query->cursor()->chunk(2000) as $chunk) {
            $responseIds = $chunk->pluck('id')->all();
            $answerIndex = $this->buildAnswerIndex($responseIds);

            foreach ($chunk as $response) {
                $fieldAnswers = $answerIndex[$response->id] ?? [];

                $row = [
                    'ID'           => $response->id,
                    'Respondent'   => $response->respondent_ref ?? '',
                    'Submitted At' => $response->submitted_at?->format('Y-m-d H:i:s') ?? '',
                ];

                foreach ($fields as $field) {
                    $answers        = $fieldAnswers[$field->id] ?? [];
                    $row[$field->label] = $this->formatCell($field, $answers, $optionMap);
                }

                yield $row;
            }
        }
    }

    // ── Data loading ──────────────────────────────────────────────────────

    private function loadFields(Survey $survey): Collection
    {
        return SurveyField::forSurvey($survey->id)
            ->active()
            ->with(['options' => fn ($q) => $q->ordered()])
            ->ordered()
            ->get();
    }

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

    /**
     * Bulk load answers cho một chunk responses.
     * @param  int[]  $responseIds
     * @return array<int, array<int, array>>  [response_id => [field_id => [SurveyAnswer, ...]]]
     */
    private function buildAnswerIndex(array $responseIds): array
    {
        $answers = SurveyAnswer::query()
            ->select(['response_id', 'field_id', 'row_key', 'option_id', 'value_string', 'value_text', 'value_number', 'value_date', 'value_bool'])
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

    // ── Cell formatter ────────────────────────────────────────────────────

    private function formatCell(SurveyField $field, array $answers, array $optionMap): string
    {
        if (empty($answers)) {
            return '';
        }

        return match ($field->field_type) {
            FieldType::Text     => (string) ($answers[0]->value_string ?? ''),
            FieldType::Textarea => (string) ($answers[0]->value_text   ?? ''),
            FieldType::Number,
            FieldType::Rating,
            FieldType::Nps      => $answers[0]->value_number !== null
                                    ? rtrim(rtrim(number_format((float) $answers[0]->value_number, 2), '0'), '.')
                                    : '',
            FieldType::Date     => $answers[0]->value_date
                                    ? \Carbon\Carbon::parse($answers[0]->value_date)->format('Y-m-d')
                                    : '',
            FieldType::Boolean  => $answers[0]->value_bool !== null
                                    ? ((int) $answers[0]->value_bool === 1 ? 'Có' : 'Không')
                                    : '',
            FieldType::Select,
            FieldType::Radio    => $optionMap[$answers[0]->option_id] ?? '',
            FieldType::Checkbox => implode(', ', array_filter(
                array_map(fn ($a) => $optionMap[$a->option_id] ?? null, $answers)
            )),
            FieldType::Ranking  => implode(' > ', array_map(
                fn ($a) => $optionMap[$a->option_id] ?? '',
                collect($answers)->sortBy(fn ($a) => (float) ($a->value_number ?? 0))->all()
            )),
            FieldType::Matrix   => implode('; ', array_filter(array_map(
                fn ($a) => $a->row_key !== null
                    ? $a->row_key . ': ' . ($optionMap[$a->option_id] ?? '')
                    : null,
                $answers
            ))),
        };
    }
}
