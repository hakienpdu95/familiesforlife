<?php

namespace Modules\ContentOutlines\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostCategory;

/**
 * spec/ContentOutlines_Technical_Specification.md §2.1 — công cụ nghiên cứu nội dung nền tảng,
 * KHÔNG extends TenantAwareModel/organization_id — cùng nhóm content_foundations. KHÔNG soft
 * delete, KHÔNG LogsActivity (§2.1 — không phải credential/tài sản nghiệp vụ cần audit).
 */
class ContentOutline extends Model
{
    protected $table = 'content_outlines';

    protected $fillable = [
        'uuid',
        'label',
        'topic',
        'target_keyword',
        'secondary_keywords',
        'search_intent',
        'post_category_id',
        'target_audience',
        'content_goal',
        'cta_url',
        'tone_style',
        'competitor_urls',
        'desired_word_count',
        'language',
        'outline_depth',
        'content_role',
        'additional_notes',
        'generated_prompt',
        'approved_outline',
        'article_draft_prompt',
        'drafted_article',
        'review_prompt',
        'linked_post_article_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'desired_word_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function linkedArticle(): BelongsTo
    {
        return $this->belongsTo(PostArticle::class, 'linked_post_article_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
