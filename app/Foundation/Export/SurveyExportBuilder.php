<?php

namespace App\Foundation\Export;

use Illuminate\Support\Collection;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Survey\Models\SurveySection;

/**
 * Builds an Excel-ready row collection from deployment targets + user-selected fields.
 *
 * Field sources (passed as "source" strings):
 *   org.{field}               — organizations table column
 *   settings.{key}            — organizations.settings JSON key
 *   survey.{section}.{field}  — survey_answers for data_collection_response
 *
 * No hardcoded column names — columns come from the survey's own field labels.
 */
class SurveyExportBuilder
{
    /**
     * Return available field groups for the field-picker UI.
     * Groups: "Thông tin tổ chức" (org fields) + each survey section.
     *
     * @param  DeploymentTarget  $target  used to load the survey template structure
     * @return array<int, array{group: string, fields: array}>
     */
    public function fieldCatalog(DeploymentTarget $target): array
    {
        $catalog = [];

        // ── Org fields ───────────────────────────────────────────────────────
        $catalog[] = [
            'group'  => 'Thông tin tổ chức',
            'icon'   => '🏢',
            'fields' => [
                ['source' => 'org.name',                          'label' => 'Tên tổ chức'],
                ['source' => 'org.tax_code',                      'label' => 'Mã số thuế'],
                ['source' => 'org.phone',                         'label' => 'Điện thoại'],
                ['source' => 'org.email',                         'label' => 'Email'],
                ['source' => 'org.city',                          'label' => 'Tỉnh / Thành phố'],
                ['source' => 'org.full_address',                  'label' => 'Địa chỉ đầy đủ'],
                ['source' => 'settings.representative',           'label' => 'Người đại diện'],
                ['source' => 'settings.representative_phone',     'label' => 'SĐT người đại diện'],
                ['source' => 'settings.main_product_type',        'label' => 'Loại sản xuất chính'],
            ],
        ];

        // ── Survey sections + fields ─────────────────────────────────────────
        $response = $target->dataCollectionResponse;

        if ($response) {
            $sections = SurveySection::where('survey_id', $response->survey_id)
                ->with(['fields' => fn ($q) => $q->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get();

            foreach ($sections as $section) {
                $fields = $section->fields->map(fn ($f) => [
                    'source' => "survey.{$section->section_code}.{$f->field_key}",
                    'label'  => $f->label,
                ])->values()->all();

                if (! empty($fields)) {
                    $catalog[] = [
                        'group'  => ($section->icon ? $section->icon . ' ' : '') . $section->title,
                        'icon'   => $section->icon ?? '',
                        'fields' => $fields,
                    ];
                }
            }
        }

        return $catalog;
    }

    /**
     * Build rows for FastExcel from the given targets and selected field sources.
     *
     * @param  Collection<int, DeploymentTarget>  $targets
     * @param  array<int, string>                 $selectedSources  e.g. ['org.name', 'survey.org_profile.tax_code']
     * @param  array<string, string>              $labelMap         source → human label (from fieldCatalog)
     * @return Collection<int, array<string, string>>
     */
    public function buildRows(Collection $targets, array $selectedSources, array $labelMap): Collection
    {
        $resolver = new ExportColumnResolver;

        return $targets->map(function ($target) use ($selectedSources, $labelMap, $resolver) {
            $row = [];
            foreach ($selectedSources as $source) {
                $header       = $labelMap[$source] ?? $source;
                $row[$header] = $resolver->resolve($target, $source);
            }
            return $row;
        })->values();
    }

    /**
     * Flatten fieldCatalog into a source → label map (used when reconstructing labels from POST data).
     *
     * @return array<string, string>  source => label
     */
    public function flatLabelMap(array $catalog): array
    {
        $map = [];
        foreach ($catalog as $group) {
            foreach ($group['fields'] as $field) {
                $map[$field['source']] = $field['label'];
            }
        }
        return $map;
    }
}
