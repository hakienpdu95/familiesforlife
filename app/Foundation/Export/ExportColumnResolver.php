<?php

namespace App\Foundation\Export;

use Illuminate\Support\Collection;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Survey\Models\SurveyAnswer;
use Modules\Survey\Models\SurveyResponse;

/**
 * Resolves "source" path strings to actual values for a given DeploymentTarget.
 *
 * Source path syntax:
 *   org.{field}                              → organizations.{field}
 *   settings.{key}                           → organizations.settings[key]
 *   survey.{section_code}.{field_key}        → survey_answers for data_collection_response
 *   media.{collection}.{doc_type}.{prop}     → first media item matching doc_type → custom_properties[prop] or url
 *   media.custom.{prop}                      → used in media_list context (passed via $context['_media'])
 *   media.url                                → used in media_list context (passed via $context['_media'])
 *   parse.{index}                            → used in parse_lines context (passed via $context['_parts'])
 *
 * Results are always cast to string; empty string if not found.
 * Internal caches avoid repeated DB queries across columns of the same target.
 */
class ExportColumnResolver
{
    /** @var array<string, \App\Shared\Tenancy\Models\Organization|null> */
    private array $orgCache = [];

    /** @var array<string, Collection> section_cache_key → keyed answers */
    private array $surveyCache = [];

    /** @var array<string, \Illuminate\Support\Collection> org_id.collection → media */
    private array $mediaCache = [];

    /**
     * Resolve a column source for a target row, with optional row-level context.
     *
     * @param array $context  Optional: ['_media' => $spatieMedia, '_parts' => ['v0','v1',...]]
     */
    public function resolve(DeploymentTarget $target, string $source, array $context = []): string
    {
        // ── Row-level context shortcuts ──────────────────────────────────────
        if ($source === 'media.url' && isset($context['_media'])) {
            return $context['_media']->getUrl();
        }

        if (str_starts_with($source, 'media.custom.') && isset($context['_media'])) {
            $key = substr($source, strlen('media.custom.'));
            return (string) ($context['_media']->custom_properties[$key] ?? '');
        }

        if (str_starts_with($source, 'parse.') && isset($context['_parts'])) {
            $idx = (int) substr($source, strlen('parse.'));
            return trim($context['_parts'][$idx] ?? '');
        }

        // ── Static resolution ────────────────────────────────────────────────
        $parts  = explode('.', $source, 4);
        $prefix = $parts[0] ?? '';

        return match ($prefix) {
            'org'      => $this->resolveOrg($target, $parts[1] ?? ''),
            'settings' => $this->resolveSettings($target, $parts[1] ?? ''),
            'survey'   => $this->resolveSurvey($target, $parts[1] ?? '', $parts[2] ?? ''),
            'media'    => $this->resolveMediaStatic($target, $parts[1] ?? '', $parts[2] ?? '', $parts[3] ?? ''),
            default    => '',
        };
    }

    // ── org.* ────────────────────────────────────────────────────────────────

    private function resolveOrg(DeploymentTarget $target, string $field): string
    {
        $org = $this->getOrg($target);
        if (! $org || ! $field) {
            return '';
        }

        // Support dot-nested like org.full_address → fall through to property
        return (string) ($org->{$field} ?? '');
    }

    // ── settings.* ───────────────────────────────────────────────────────────

    private function resolveSettings(DeploymentTarget $target, string $key): string
    {
        $org = $this->getOrg($target);
        return (string) (($org?->settings ?? [])[$key] ?? '');
    }

    // ── survey.{section}.{field} ─────────────────────────────────────────────

    private function resolveSurvey(DeploymentTarget $target, string $sectionCode, string $fieldKey): string
    {
        $response = $target->dataCollectionResponse;
        if (! $response || ! $sectionCode || ! $fieldKey) {
            return '';
        }

        $cacheKey = "{$response->id}.{$sectionCode}";

        if (! isset($this->surveyCache[$cacheKey])) {
            $this->surveyCache[$cacheKey] = $this->loadSectionAnswers($response, $sectionCode);
        }

        $answer = $this->surveyCache[$cacheKey][$fieldKey] ?? null;

        return (string) ($answer?->value_string ?? $answer?->value_text ?? $answer?->value_number ?? '');
    }

    // ── media.{collection}.{doc_type_filter}.{property} ─────────────────────

    private function resolveMediaStatic(
        DeploymentTarget $target,
        string           $collection,
        string           $docTypeFilter,
        string           $property,
    ): string {
        $org = $this->getOrg($target);
        if (! $org || ! $collection) {
            return '';
        }

        $cacheKey = "{$org->id}.{$collection}";
        if (! isset($this->mediaCache[$cacheKey])) {
            $this->mediaCache[$cacheKey] = $org->getMedia($collection);
        }

        $item = $this->mediaCache[$cacheKey]
            ->filter(fn ($m) => ($m->custom_properties['doc_type'] ?? '') === $docTypeFilter)
            ->first();

        if (! $item) {
            return '';
        }

        return match ($property) {
            'url'   => $item->getUrl(),
            default => (string) ($item->custom_properties[$property] ?? ''),
        };
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getOrg(DeploymentTarget $target)
    {
        $id = $target->target_organization_id;
        if (! $id) {
            return null;
        }

        if (! array_key_exists((string) $id, $this->orgCache)) {
            $this->orgCache[(string) $id] = $target->targetOrganization;
        }

        return $this->orgCache[(string) $id];
    }

    private function loadSectionAnswers(SurveyResponse $response, string $sectionCode): Collection
    {
        return SurveyAnswer::query()
            ->join('survey_fields',   'survey_answers.field_id',    '=', 'survey_fields.id')
            ->join('survey_sections', 'survey_fields.section_id',   '=', 'survey_sections.id')
            ->where('survey_answers.response_id', $response->id)
            ->where('survey_sections.section_code', $sectionCode)
            ->get(['survey_fields.field_key', 'survey_answers.*'])
            ->keyBy('field_key');
    }

    /** Get all media for a collection (used by SurveyExportBuilder for media source resolution) */
    public function getMediaCollection(DeploymentTarget $target, string $collection): \Illuminate\Support\Collection
    {
        $org = $this->getOrg($target);
        if (! $org) {
            return collect();
        }

        $cacheKey = "{$org->id}.{$collection}";
        if (! isset($this->mediaCache[$cacheKey])) {
            $this->mediaCache[$cacheKey] = $org->getMedia($collection);
        }

        return $this->mediaCache[$cacheKey];
    }

    /** Get raw survey answer value for textarea (used for parse_lines) */
    public function getSurveyRaw(DeploymentTarget $target, string $sectionCode, string $fieldKey): string
    {
        return $this->resolveSurvey($target, $sectionCode, $fieldKey);
    }

    /** Get tax_code for a target (used to prepend MA_CHUTHE in media_excel rows) */
    public function getTaxCode(DeploymentTarget $target): string
    {
        return $this->resolveOrg($target, 'tax_code');
    }
}
