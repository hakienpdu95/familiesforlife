<?php

namespace Modules\Survey\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Data\SurveySchemaData;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Models\Survey;

/**
 * Lấy definition đầy đủ của một khảo sát đang active.
 *
 * Eager load sections → fields (active only) → options trong một query chain
 * để tránh N+1. Kết quả được cache Redis 1 giờ, purge khi admin sửa field/option/section.
 */
class BuildSurveySchemaAction
{
    use AsAction;

    public const CACHE_TTL = 1800; // 30 phút theo spec Task 4.4

    public static function cacheKey(string $slug): string
    {
        // v2: cache plain array, not PHP object — bump version to force invalidation of old corrupt cache
        return "survey:v2:schema:{$slug}";
    }

    public static function purgeCache(string $slug): void
    {
        Cache::store('redis')->forget(static::cacheKey($slug));
    }

    public function handle(string $slug): SurveySchemaData
    {
        // Cache as plain array to avoid __PHP_Incomplete_Class on Redis deserialization.
        // Spatie Data objects are not safely serializable across process boundaries.
        // Wrapped in try/catch so Redis unavailability degrades gracefully to a direct DB query.
        try {
            $cached = Cache::store('redis')->remember(
                static::cacheKey($slug),
                static::CACHE_TTL,
                fn (): array => $this->buildArray($slug)
            );

            return SurveySchemaData::from($cached);
        } catch (\Throwable $e) {
            Log::warning('survey.schema.cache_miss', [
                'slug'  => $slug,
                'error' => $e->getMessage(),
            ]);

            return $this->buildFromDb($slug);
        }
    }

    private function buildArray(string $slug): array
    {
        return $this->buildFromDb($slug)->toArray();
    }

    private function buildFromDb(string $slug): SurveySchemaData
    {
        $survey = Survey::active()
            ->bySlug($slug)
            ->with([
                'sections'                      => fn ($q) => $q->ordered(),
                'sections.fields'               => fn ($q) => $q->active()->ordered(),
                'sections.fields.options'       => fn ($q) => $q->ordered(),
                'sections.fields.conditions'    => fn ($q) => $q->orderBy('sort_order'),
                'sections.fields.rows'          => fn ($q) => $q->orderBy('sort_order'),
                'turnstileSite',
            ])
            ->firstOrFail();

        $this->filterSchema($survey);

        return SurveySchemaData::fromModel($survey);
    }

    /**
     * Task 5.3: lọc choice field không có option.
     * Task 5.2: lọc section không còn field nào sau khi lọc.
     * Thao tác trực tiếp trên relation đã eager-load — không thêm query.
     */
    private function filterSchema(Survey $survey): void
    {
        $choiceTypes = [FieldType::Select, FieldType::Radio, FieldType::Checkbox];

        foreach ($survey->sections as $section) {
            $filtered = $section->fields->filter(function ($field) use ($choiceTypes, $survey) {
                if (in_array($field->field_type, $choiceTypes) && $field->options->isEmpty()) {
                    Log::warning('survey.schema.choice_field_no_options', [
                        'survey_id' => $survey->id,
                        'field_key' => $field->field_key,
                        'label'     => $field->label,
                    ]);
                    return false;
                }

                if ($field->field_type === FieldType::Matrix && $field->rows->isEmpty()) {
                    Log::warning('survey.schema.matrix_field_no_rows', [
                        'survey_id' => $survey->id,
                        'field_key' => $field->field_key,
                        'label'     => $field->label,
                    ]);
                    return false;
                }

                return true;
            })->values();

            $section->setRelation('fields', $filtered);
        }

        // Loại section rỗng sau khi đã lọc field
        $survey->setRelation(
            'sections',
            $survey->sections->filter(fn ($s) => $s->fields->isNotEmpty())->values()
        );
    }
}
