<?php

namespace Modules\Survey\Data;

use Modules\Survey\Models\Survey;
use Spatie\LaravelData\Data;

class SurveySchemaData extends Data
{
    public function __construct(
        public readonly int    $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly int    $version,
        /** @var SurveySectionData[] */
        public readonly array  $sections,
        public readonly ?string $turnstile_site_key = null,
    ) {}

    public static function fromModel(Survey $survey): self
    {
        // Build id→key map for all fields across all sections (needed to resolve condition references)
        $fieldKeyMap = [];
        foreach ($survey->sections as $section) {
            foreach ($section->fields as $field) {
                $fieldKeyMap[$field->id] = $field->field_key;
            }
        }

        return new self(
            id:       $survey->id,
            title:    $survey->title,
            slug:     $survey->slug,
            version:  $survey->version,
            sections: $survey->sections
                ->map(fn($s) => SurveySectionData::fromModel($s, $fieldKeyMap))
                ->all(),
            turnstile_site_key: $survey->turnstileSite?->site_key,
        );
    }
}
