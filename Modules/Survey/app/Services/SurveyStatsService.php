<?php

namespace Modules\Survey\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\ResponseStatus;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyResponse;

/**
 * Read-only aggregate service (CQRS-lite: tách hoàn toàn khỏi write path).
 *
 * Mọi query ĐỀU index-backed — không có full table scan:
 *   choice   → INDEX (field_id, option_id)
 *   number   → INDEX (field_id, value_number)
 *   boolean  → INDEX (field_id, value_bool)
 *   text     → INDEX (field_id, value_string)
 *   textarea → INDEX (field_id, option_id) via field_id prefix
 *
 * Pattern: batch query per stat type → O(5) queries thay vì O(N fields).
 */
class SurveyStatsService
{
    public const CACHE_TTL = 300; // 5 phút

    public static function cacheKey(int $surveyId): string
    {
        return "survey:stats:{$surveyId}";
    }

    public static function daysCacheKey(int $surveyId, int $days): string
    {
        return "survey:stats:days:{$surveyId}:{$days}";
    }

    public static function purgeCache(int $surveyId): void
    {
        try {
            Cache::store('redis')->forget(static::cacheKey($surveyId));
            Cache::store('redis')->forget(static::daysCacheKey($surveyId, 30));
        } catch (\Throwable) {
            // Redis unavailable; cache will expire naturally.
        }
    }

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Trả toàn bộ thống kê của một survey — có cache Redis 5 phút.
     *
     * @return array{
     *   total_responses: int,
     *   fields: array<int, array{field_key: string, label: string, field_type: string, stats: array}>
     * }
     */
    public function forSurvey(Survey $survey): array
    {
        try {
            return Cache::store('redis')->remember(
                static::cacheKey($survey->id),
                static::CACHE_TTL,
                fn () => $this->computeForSurvey($survey)
            );
        } catch (\Throwable) {
            return $this->computeForSurvey($survey);
        }
    }

    private function computeForSurvey(Survey $survey): array
    {
        $totalResponses = $this->countCompleteResponses($survey->id);

        // Load tất cả active fields + options + rows (choice/matrix cần options, matrix cần rows)
        $fields = SurveyField::forSurvey($survey->id)
            ->active()
            ->with(['options' => fn ($q) => $q->ordered(), 'rows' => fn ($q) => $q->orderBy('sort_order'), 'section'])
            ->ordered()
            ->get();

        // Nhóm field_id theo stat type để batch query
        $choiceIds   = $fields->filter(fn ($f) => $f->field_type->isChoice())->pluck('id')->all();
        $ratingIds   = $fields->filter(fn ($f) => $f->field_type === FieldType::Rating)->pluck('id')->all();
        $numberIds   = $fields->filter(fn ($f) => $f->field_type === FieldType::Number)->pluck('id')->all();
        $booleanIds  = $fields->filter(fn ($f) => $f->field_type === FieldType::Boolean)->pluck('id')->all();
        $textIds     = $fields->filter(fn ($f) => $f->field_type === FieldType::Text)->pluck('id')->all();
        $textareaIds = $fields->filter(fn ($f) => $f->field_type === FieldType::Textarea)->pluck('id')->all();
        $npsIds      = $fields->filter(fn ($f) => $f->field_type === FieldType::Nps)->pluck('id')->all();
        $matrixIds   = $fields->filter(fn ($f) => $f->field_type === FieldType::Matrix)->pluck('id')->all();
        $rankingIds  = $fields->filter(fn ($f) => $f->field_type === FieldType::Ranking)->pluck('id')->all();

        // Checkbox fields need per-field respondent count as denominator (not total_responses),
        // because one respondent can tick multiple options, making percentages > 100% otherwise.
        $checkboxIds = $fields->filter(fn ($f) => $f->field_type === FieldType::Checkbox)->pluck('id')->all();
        $checkboxRespondentCounts = $this->batchCheckboxRespondentCounts($checkboxIds);

        // Batch queries — một lần mỗi loại, tất cả index-backed
        $choiceStats   = $this->batchChoiceStats($choiceIds, $fields, $totalResponses, $checkboxRespondentCounts);
        $ratingStats   = $this->batchRatingStats($ratingIds);
        $numberStats   = $this->batchNumberStats($numberIds);
        $booleanStats  = $this->batchBooleanStats($booleanIds);
        $textStats     = $this->batchTextStats($textIds);
        $textareaStats = $this->batchTextareaStats($textareaIds);

        // NPS: individual queries (one per field) — typically few NPS fields per survey
        $npsStats    = [];
        foreach ($npsIds as $id) {
            $npsStats[$id] = $this->npsScore($survey->id, $id);
        }

        // Matrix/Ranking: individual queries — typically few per survey
        $matrixStats  = [];
        foreach ($matrixIds as $id) {
            $f = $fields->firstWhere('id', $id);
            $matrixStats[$id] = $this->computeMatrixBreakdown($f, $totalResponses);
        }
        $rankingStats = [];
        foreach ($rankingIds as $id) {
            $f = $fields->firstWhere('id', $id);
            $rankingStats[$id] = $this->computeRankingBreakdown($f);
        }

        $allStats = $choiceStats + $ratingStats + $numberStats + $booleanStats
            + $textStats + $textareaStats + $npsStats + $matrixStats + $rankingStats;

        return [
            'total_responses' => $totalResponses,
            'fields'          => $fields
                ->map(fn ($f) => [
                    'field_key'     => $f->field_key,
                    'label'         => $f->label,
                    'field_type'    => $f->field_type->name,
                    'section_id'    => $f->section_id,
                    'section_title' => $f->section?->title ?? 'Chung',
                    'section_order' => $f->section?->sort_order ?? 0,
                    'stats'         => $allStats[$f->id] ?? null,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Chỉ đếm responses hoàn chỉnh (status = complete).
     * Dùng INDEX (survey_id, status, submitted_at).
     */
    public function countCompleteResponses(int $surveyId): int
    {
        return SurveyResponse::forSurvey($surveyId)
            ->complete()
            ->count();
    }

    // ── Batch: choice (select / radio / checkbox) ─────────────────────────
    //   INDEX: (field_id, option_id)

    /**
     * @param  int[]  $fieldIds  checkbox field IDs
     * @return array<int, int>  field_id => distinct respondent count
     */
    private function batchCheckboxRespondentCounts(array $fieldIds): array
    {
        if (empty($fieldIds)) {
            return [];
        }

        return DB::table('survey_answers')
            ->selectRaw('field_id, COUNT(DISTINCT response_id) as respondent_count')
            ->whereIn('field_id', $fieldIds)
            ->groupBy('field_id')
            ->get()
            ->keyBy('field_id')
            ->map(fn ($r) => (int) $r->respondent_count)
            ->all();
    }

    /**
     * @param  int[]     $fieldIds
     * @param  Collection<int, SurveyField> $allFields  — options đã eager load
     * @param  array<int, int>  $checkboxRespondentCounts  field_id => distinct respondent count
     * @return array<int, array>  keyed by field_id
     */
    private function batchChoiceStats(array $fieldIds, Collection $allFields, int $totalResponses, array $checkboxRespondentCounts = []): array
    {
        if (empty($fieldIds)) {
            return [];
        }

        // GROUP BY (field_id, option_id) — hits (field_id, option_id) covering index
        $counts = DB::table('survey_answers')
            ->selectRaw('field_id, option_id, COUNT(*) as cnt')
            ->whereIn('field_id', $fieldIds)
            ->whereNotNull('option_id')
            ->groupBy('field_id', 'option_id')
            ->get()
            ->groupBy('field_id');             // Collection<field_id, Collection<{option_id, cnt}>>

        $choiceFields = $allFields->whereIn('id', $fieldIds)->keyBy('id');

        $result = [];

        foreach ($fieldIds as $fieldId) {
            $field       = $choiceFields[$fieldId];
            $fieldCounts = $counts->get($fieldId, collect())->keyBy('option_id');

            // Checkbox: use distinct respondents who answered THIS field (sum of percents can exceed 100%).
            // Select/Radio: use total survey responses (each respondent picks exactly one option).
            $denominator = $field->field_type === FieldType::Checkbox
                ? ($checkboxRespondentCounts[$fieldId] ?? $totalResponses)
                : $totalResponses;

            $distribution = $field->options->map(function ($option) use ($fieldCounts, $denominator) {
                $count = (int) ($fieldCounts->get($option->id)?->cnt ?? 0);

                return [
                    'option_value' => $option->option_value,
                    'label'        => $option->label,
                    'count'        => $count,
                    'percent'      => $denominator > 0
                        ? round($count / $denominator * 100, 1)
                        : 0.0,
                ];
            })->values()->all();

            $result[$fieldId] = [
                'type'         => 'choice',
                'distribution' => $distribution,
            ];
        }

        return $result;
    }

    // ── Batch: number / rating ────────────────────────────────────────────
    //   INDEX: (field_id, value_number)

    /**
     * @param  int[]  $fieldIds
     * @return array<int, array>  keyed by field_id
     */
    private function batchNumberStats(array $fieldIds): array
    {
        if (empty($fieldIds)) {
            return [];
        }

        $rows = DB::table('survey_answers')
            ->selectRaw('field_id,
                COUNT(*)            AS cnt,
                AVG(value_number)   AS avg_val,
                MIN(value_number)   AS min_val,
                MAX(value_number)   AS max_val')
            ->whereIn('field_id', $fieldIds)
            ->whereNotNull('value_number')   // WHERE field_id IN (...) → uses index prefix
            ->groupBy('field_id')
            ->get()
            ->keyBy('field_id');

        $result = [];

        foreach ($fieldIds as $fieldId) {
            $row = $rows->get($fieldId);

            $result[$fieldId] = [
                'type'  => 'number',
                'count' => (int)   ($row?->cnt     ?? 0),
                'avg'   => $row?->avg_val !== null ? round((float) $row->avg_val, 2) : null,
                'min'   => $row?->min_val !== null ? (float) $row->min_val          : null,
                'max'   => $row?->max_val !== null ? (float) $row->max_val          : null,
            ];
        }

        return $result;
    }

    // ── Batch: rating ─────────────────────────────────────────────────────
    //   INDEX: (field_id, value_number)

    /**
     * @param  int[]  $fieldIds
     * @return array<int, array>  keyed by field_id
     */
    private function batchRatingStats(array $fieldIds): array
    {
        if (empty($fieldIds)) {
            return [];
        }

        $aggregates = DB::table('survey_answers')
            ->selectRaw('field_id, COUNT(*) AS cnt, AVG(value_number) AS avg_val, MIN(value_number) AS min_val, MAX(value_number) AS max_val')
            ->whereIn('field_id', $fieldIds)
            ->whereNotNull('value_number')
            ->groupBy('field_id')
            ->get()
            ->keyBy('field_id');

        // ROUND(value_number, 0) works in both SQLite and MySQL
        $distributionRows = DB::table('survey_answers')
            ->selectRaw('field_id, ROUND(value_number, 0) AS score, COUNT(*) AS cnt')
            ->whereIn('field_id', $fieldIds)
            ->whereNotNull('value_number')
            ->groupBy('field_id', DB::raw('ROUND(value_number, 0)'))
            ->orderByDesc('score')
            ->get()
            ->groupBy('field_id');

        $result = [];
        foreach ($fieldIds as $fieldId) {
            $agg   = $aggregates->get($fieldId);
            $total = (int) ($agg?->cnt ?? 0);
            $dist  = $distributionRows->get($fieldId, collect());

            $scoreList = $dist->map(fn ($r) => [
                'score'   => (int) round((float) $r->score),
                'count'   => (int) $r->cnt,
                'percent' => $total > 0 ? round((int) $r->cnt / $total * 100, 1) : 0.0,
            ])->values()->all();

            $result[$fieldId] = [
                'type'         => 'rating',
                'count'        => $total,
                'avg'          => $agg?->avg_val !== null ? round((float) $agg->avg_val, 2) : null,
                'min'          => $agg?->min_val !== null ? (float) $agg->min_val : null,
                'max'          => $agg?->max_val !== null ? (float) $agg->max_val : null,
                'distribution' => $scoreList,
            ];
        }

        return $result;
    }

    // ── Batch: boolean ────────────────────────────────────────────────────
    //   INDEX: (field_id, value_bool)

    /**
     * @param  int[]  $fieldIds
     * @return array<int, array>  keyed by field_id
     */
    private function batchBooleanStats(array $fieldIds): array
    {
        if (empty($fieldIds)) {
            return [];
        }

        // GROUP BY (field_id, value_bool) — 2 rows per field (true / false)
        $rows = DB::table('survey_answers')
            ->selectRaw('field_id, value_bool, COUNT(*) AS cnt')
            ->whereIn('field_id', $fieldIds)
            ->whereNotNull('value_bool')
            ->groupBy('field_id', 'value_bool')
            ->get()
            ->groupBy('field_id');

        $result = [];

        foreach ($fieldIds as $fieldId) {
            $fieldRows = $rows->get($fieldId, collect());

            // MySQL lưu TINYINT 1/0 — cast sang int khi so sánh
            $yesCount = (int) ($fieldRows->first(fn ($r) => (int) $r->value_bool === 1)?->cnt ?? 0);
            $noCount  = (int) ($fieldRows->first(fn ($r) => (int) $r->value_bool === 0)?->cnt ?? 0);

            $result[$fieldId] = [
                'type'      => 'boolean',
                'yes_count' => $yesCount,
                'no_count'  => $noCount,
                'total'     => $yesCount + $noCount,
            ];
        }

        return $result;
    }

    // ── Batch: text (value_string) ────────────────────────────────────────
    //   INDEX: (field_id, value_string)  — covering index cho COUNT

    /**
     * @param  int[]  $fieldIds
     * @return array<int, array>  keyed by field_id
     */
    private function batchTextStats(array $fieldIds): array
    {
        if (empty($fieldIds)) {
            return [];
        }

        $rows = DB::table('survey_answers')
            ->selectRaw('field_id, COUNT(*) AS cnt')
            ->whereIn('field_id', $fieldIds)
            ->whereNotNull('value_string')   // (field_id, value_string) index
            ->groupBy('field_id')
            ->get()
            ->keyBy('field_id');

        return $this->buildTextResult($fieldIds, $rows);
    }

    // ── Batch: textarea (value_text) ──────────────────────────────────────
    //   value_text không có index riêng — dùng field_id prefix của index
    //   (field_id, option_id) để scan theo field, filter value_text IS NOT NULL

    /**
     * @param  int[]  $fieldIds
     * @return array<int, array>  keyed by field_id
     */
    private function batchTextareaStats(array $fieldIds): array
    {
        if (empty($fieldIds)) {
            return [];
        }

        // Tất cả answer cho textarea field đều có value_text (không NULL).
        // COUNT(*) WHERE field_id IN (...) dùng prefix của (field_id, option_id) index.
        $rows = DB::table('survey_answers')
            ->selectRaw('field_id, COUNT(*) AS cnt')
            ->whereIn('field_id', $fieldIds)
            ->groupBy('field_id')
            ->get()
            ->keyBy('field_id');

        return $this->buildTextResult($fieldIds, $rows);
    }

    // ── Task 14: Per-field breakdown ──────────────────────────────────────

    /**
     * Return detailed stats for a single field.
     * For NPS fields, delegates to npsScore(). For Matrix/Ranking, uses dedicated queries.
     *
     * @return array{type: string, ...}
     */
    public function fieldBreakdown(int $surveyId, int $fieldId): array
    {
        $field = SurveyField::where('survey_id', $surveyId)
            ->where('id', $fieldId)
            ->with(['options' => fn ($q) => $q->ordered(), 'rows' => fn ($q) => $q->orderBy('sort_order')])
            ->firstOrFail();

        $totalResponses = $this->countCompleteResponses($surveyId);

        return match ($field->field_type) {
            FieldType::Nps => $this->npsScore($surveyId, $fieldId),

            FieldType::Matrix => $this->computeMatrixBreakdown($field, $totalResponses),

            FieldType::Ranking => $this->computeRankingBreakdown($field),

            FieldType::Select,
            FieldType::Radio,
            FieldType::Checkbox => ($this->batchChoiceStats(
                [$fieldId],
                collect([$field->id => $field])->keyBy('id'),
                $totalResponses,
                $field->field_type === FieldType::Checkbox
                    ? $this->batchCheckboxRespondentCounts([$fieldId])
                    : []
            ))[$fieldId] ?? ['type' => 'choice', 'distribution' => []],

            FieldType::Rating => ($this->batchRatingStats([$fieldId]))[$fieldId]
                ?? ['type' => 'rating', 'count' => 0, 'avg' => null, 'min' => null, 'max' => null, 'distribution' => []],

            FieldType::Number => ($this->batchNumberStats([$fieldId]))[$fieldId]
                ?? ['type' => 'number', 'count' => 0, 'avg' => null, 'min' => null, 'max' => null],

            FieldType::Boolean => ($this->batchBooleanStats([$fieldId]))[$fieldId]
                ?? ['type' => 'boolean', 'yes_count' => 0, 'no_count' => 0, 'total' => 0],

            FieldType::Text => ($this->batchTextStats([$fieldId]))[$fieldId]
                ?? ['type' => 'text', 'count' => 0],

            FieldType::Textarea => ($this->batchTextareaStats([$fieldId]))[$fieldId]
                ?? ['type' => 'text', 'count' => 0],

            FieldType::Date => ['type' => 'date', 'count' => DB::table('survey_answers')
                ->where('field_id', $fieldId)->whereNotNull('value_date')->count()],
        };
    }

    /**
     * NPS score for a single NPS field.
     *
     * @return array{type: string, total: int, promoters: int, passives: int, detractors: int, nps_score: float, distribution: array}
     */
    public function npsScore(int $surveyId, int $fieldId): array
    {
        $rows = DB::table('survey_answers')
            ->selectRaw('CAST(value_number AS UNSIGNED) AS score, COUNT(*) AS cnt')
            ->where('field_id', $fieldId)
            ->whereNotNull('value_number')
            ->groupByRaw('CAST(value_number AS UNSIGNED)')
            ->orderByRaw('CAST(value_number AS UNSIGNED)')
            ->get()
            ->keyBy('score');

        $distribution = [];
        $promoters = 0;
        $passives  = 0;
        $detractors = 0;
        $total = 0;

        for ($s = 0; $s <= 10; $s++) {
            $count = (int) ($rows->get($s)?->cnt ?? 0);
            $distribution[] = ['score' => $s, 'count' => $count];
            $total += $count;

            if ($s >= 9) {
                $promoters += $count;
            } elseif ($s >= 7) {
                $passives += $count;
            } else {
                $detractors += $count;
            }
        }

        $npsScore = $total > 0
            ? round(($promoters - $detractors) / $total * 100, 1)
            : 0.0;

        return [
            'type'         => 'nps',
            'total'        => $total,
            'promoters'    => $promoters,
            'passives'     => $passives,
            'detractors'   => $detractors,
            'nps_score'    => $npsScore,
            'distribution' => $distribution,
        ];
    }

    private function computeMatrixBreakdown(SurveyField $field, int $totalResponses): array
    {
        $rows = $field->rows;
        $cols = $field->options;

        if ($rows->isEmpty() || $cols->isEmpty()) {
            return ['type' => 'matrix', 'rows' => [], 'columns' => []];
        }

        // One query: GROUP BY (row_key, option_id) for all rows of this field
        $counts = DB::table('survey_answers')
            ->selectRaw('row_key, option_id, COUNT(*) AS cnt')
            ->where('field_id', $field->id)
            ->whereNotNull('row_key')
            ->whereNotNull('option_id')
            ->groupBy('row_key', 'option_id')
            ->get()
            ->groupBy('row_key')
            ->map(fn ($g) => $g->keyBy('option_id'));

        $colDefs = $cols->map(fn ($c) => ['option_id' => $c->id, 'label' => $c->label, 'option_value' => $c->option_value])->values()->all();

        $rowData = $rows->map(function ($row) use ($counts, $cols, $totalResponses) {
            $rowCounts = $counts->get($row->row_key, collect());
            $colCounts = $cols->map(function ($col) use ($rowCounts, $totalResponses) {
                $count = (int) ($rowCounts->get($col->id)?->cnt ?? 0);
                return [
                    'option_id'    => $col->id,
                    'option_value' => $col->option_value,
                    'count'        => $count,
                    'percent'      => $totalResponses > 0 ? round($count / $totalResponses * 100, 1) : 0.0,
                ];
            })->values()->all();

            return [
                'row_key'  => $row->row_key,
                'label'    => $row->label,
                'col_counts' => $colCounts,
            ];
        })->values()->all();

        return [
            'type'    => 'matrix',
            'columns' => $colDefs,
            'rows'    => $rowData,
        ];
    }

    private function computeRankingBreakdown(SurveyField $field): array
    {
        $options = $field->options;

        if ($options->isEmpty()) {
            return ['type' => 'ranking', 'options' => []];
        }

        // Average rank per option (lower = ranked higher/earlier)
        $rows = DB::table('survey_answers')
            ->selectRaw('option_id, COUNT(*) AS cnt, AVG(value_number) AS avg_rank')
            ->where('field_id', $field->id)
            ->whereNotNull('option_id')
            ->whereNotNull('value_number')
            ->groupBy('option_id')
            ->get()
            ->keyBy('option_id');

        $optionData = $options->map(function ($opt) use ($rows) {
            $row = $rows->get($opt->id);
            return [
                'option_id'    => $opt->id,
                'option_value' => $opt->option_value,
                'label'        => $opt->label,
                'count'        => (int) ($row?->cnt ?? 0),
                'avg_rank'     => $row?->avg_rank !== null ? round((float) $row->avg_rank, 2) : null,
            ];
        })->sortBy('avg_rank')->values()->all();

        return [
            'type'    => 'ranking',
            'options' => $optionData,
        ];
    }

    // ── Task 4.4: Total responses per day ─────────────────────────────────
    //   INDEX: (survey_id, status, submitted_at) — prefix scan → DATE() → GROUP BY

    /**
     * Trả mảng {day, count} liên tục $days ngày gần nhất (điền 0 cho ngày trống).
     *
     * @return array<int, array{day: string, count: int}>
     */
    public function totalByDay(Survey $survey, int $days = 30): array
    {
        try {
            return Cache::store('redis')->remember(
                static::daysCacheKey($survey->id, $days),
                static::CACHE_TTL,
                fn () => $this->computeTotalByDay($survey, $days)
            );
        } catch (\Throwable) {
            return $this->computeTotalByDay($survey, $days);
        }
    }

    private function computeTotalByDay(Survey $survey, int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        // Dùng Eloquent để SoftDeletes global scope tự động loại trừ deleted_at IS NOT NULL
        $rows = SurveyResponse::forSurvey($survey->id)
            ->complete()
            ->where('submitted_at', '>=', $from)
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as cnt')
            ->groupByRaw('DATE(submitted_at)')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        // Điền ngày trống với count = 0 để biểu đồ liên tục
        $result = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day      = now()->subDays($i)->format('Y-m-d');
            $result[] = ['day' => $day, 'count' => (int) ($rows->get($day)?->cnt ?? 0)];
        }

        return $result;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function buildTextResult(array $fieldIds, Collection $rows): array
    {
        $result = [];

        foreach ($fieldIds as $fieldId) {
            $result[$fieldId] = [
                'type'  => 'text',
                'count' => (int) ($rows->get($fieldId)?->cnt ?? 0),
            ];
        }

        return $result;
    }

}
