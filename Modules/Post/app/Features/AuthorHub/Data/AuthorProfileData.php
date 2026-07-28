<?php

namespace Modules\Post\Features\AuthorHub\Data;

use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở AuthorProfileSelfController::validated() (cùng pattern BreakingNewsData)
 * — DTO này chỉ hydrate dữ liệu đã qua validate.
 */
class AuthorProfileData extends Data
{
    public function __construct(
        public readonly ?string $pen_name = null,
        public readonly ?string $bio = null,
        public readonly ?string $job_title = null,
        public readonly ?string $credentials = null,
        /** @var array{facebook?: ?string, x?: ?string, linkedin?: ?string, website?: ?string}|null */
        public readonly ?array $social_links = null,
        public readonly bool $is_public = true,
        public readonly ?string $avatar_media_uuid = null,
    ) {}
}
