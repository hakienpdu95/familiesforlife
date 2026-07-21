<?php

namespace Modules\ContentBrief\Features\BriefManagement\Data;

use Modules\ContentBrief\Enums\SearchIntent;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

/**
 * spec/ContentBrief_Technical_Specification.md §2.3/§3.6 — validate thật nằm ở
 * BriefAdminController::validated() (cùng pattern MenuItemData/PageData), DTO này chỉ hydrate
 * dữ liệu đã qua validate. `schema_version` KHÔNG nằm trong DTO này — client không gửi field
 * này, được Action tự stamp SAU khi validate (§3.5), trước khi canonical hoá + hash.
 */
class BriefSnapshotData extends Data
{
    public function __construct(
        #[Required, Max(150)]
        public readonly string $target_keyword,

        /** @var string[] */
        public readonly array $secondary_keywords = [],
        public readonly ?string $suggested_category = null,
        public readonly SearchIntent $search_intent = SearchIntent::Informational,
        public readonly ?string $audience_persona = null,
        public readonly ?string $tone_of_voice = null,
        public readonly ?int $word_count_min = null,
        public readonly ?int $word_count_max = null,

        /** @var BriefOutlineItemData[] */
        #[DataCollectionOf(BriefOutlineItemData::class)]
        public readonly array $outline = [],

        /** @var BriefKeyFactData[] */
        #[DataCollectionOf(BriefKeyFactData::class)]
        public readonly array $key_facts = [],

        /** @var BriefCompetitorReferenceData[] */
        #[DataCollectionOf(BriefCompetitorReferenceData::class)]
        public readonly array $competitor_references = [],

        /** @var BriefRelatedReferenceData[] */
        #[DataCollectionOf(BriefRelatedReferenceData::class)]
        public readonly array $related_references = [],
        public readonly ?string $internal_linking_notes = null,
        public readonly ?string $seo_title_suggestion = null,
        public readonly ?string $seo_description_suggestion = null,
        public readonly ?string $additional_instructions = null,
    ) {}
}
