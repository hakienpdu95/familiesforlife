<?php

namespace Modules\Marketplace\Models;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Marketplace\Enums\ExperienceLevel;
use Modules\Marketplace\Enums\EmploymentType;
use Modules\Marketplace\Enums\JpSyncStatus;
use Modules\Marketplace\Enums\ListingStatus;
use Modules\Marketplace\Enums\ListingType;
use Modules\Marketplace\Enums\ListingVisibility;
use Modules\Marketplace\Enums\PosterType;
use Modules\Marketplace\Enums\WorkType;

class MktListing extends Model
{
    protected $table = 'mkt_listings';

    protected $fillable = [
        'uuid', 'org_id', 'posted_by', 'poster_type', 'listing_type',
        'title', 'slug', 'description', 'requirements', 'benefits',
        'status', 'visibility', 'work_type', 'employment_type', 'experience_level',
        'salary_min', 'salary_max', 'salary_currency', 'salary_is_negotiable', 'salary_is_visible',
        'budget_min', 'budget_max', 'duration_days',
        'location', 'department_id', 'position_id', 'headcount',
        'application_count', 'view_count', 'bookmark_count',
        'jp_job_post_id', 'jp_sync_status', 'auto_close_on_jp',
        'expire_at', 'closed_at',
        'required_workforce_score',
        'required_cert_level',
        'required_ai_readiness_score',
        'required_cert_type_code',
    ];

    protected $casts = [
        'listing_type'        => ListingType::class,
        'listing_status'      => ListingStatus::class,
        'poster_type'         => PosterType::class,
        'status'              => ListingStatus::class,
        'visibility'          => ListingVisibility::class,
        'work_type'           => WorkType::class,
        'employment_type'     => EmploymentType::class,
        'experience_level'    => ExperienceLevel::class,
        'jp_sync_status'      => JpSyncStatus::class,
        'salary_is_negotiable' => 'boolean',
        'salary_is_visible'   => 'boolean',
        'auto_close_on_jp'    => 'boolean',
        'expire_at'           => 'datetime',
        'closed_at'           => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (TenantContext::isSet()) {
                $builder->where('mkt_listings.org_id', TenantContext::getOrganizationId());
            }
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->org_id) && TenantContext::isSet() && $model->poster_type !== PosterType::INDIVIDUAL) {
                $model->org_id = TenantContext::getOrganizationId();
            }
            if (empty($model->slug)) {
                $model->slug = $model->generateSlug();
            }
        });
    }

    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('mkt_listings.status', ListingStatus::ACTIVE->value);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('mkt_listings.visibility', ListingVisibility::PUBLIC->value);
    }

    // ── Relationships ────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Shared\Tenancy\Models\Organization::class, 'org_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MktTag::class, 'mkt_listing_tags', 'listing_id', 'tag_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(MktApplication::class, 'listing_id');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(MktListingBookmark::class, 'listing_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MktReview::class, 'listing_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function generateSlug(): string
    {
        $base = Str::slug($this->title ?? 'listing');
        $slug = $base;
        $i    = 1;
        while (static::withoutTenant()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function isActive(): bool
    {
        return $this->status === ListingStatus::ACTIVE;
    }

    public function isFromJp(): bool
    {
        return $this->jp_job_post_id !== null;
    }
}
