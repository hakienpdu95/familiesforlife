<?php

namespace Modules\Marketplace\Models;

use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Modules\Marketplace\Enums\ApplicantAccountType;
use Modules\Marketplace\Enums\ApplicantAvailability;
use Modules\Marketplace\Enums\ApplicantStatus;
use Spatie\MediaLibrary\HasMedia;

class MktApplicant extends Authenticatable implements HasMedia
{
    use HasTenantMedia;
    use Notifiable;

    protected $table = 'mkt_applicants';

    protected $fillable = [
        'uuid', 'email', 'password_hash', 'email_verified_at',
        'account_type', 'display_name', 'slug', 'headline', 'bio',
        'phone', 'location', 'avatar_url', 'website_url', 'linkedin_url',
        'years_experience', 'expected_salary_min', 'expected_salary_max',
        'salary_currency', 'status', 'availability',
        'is_profile_public', 'is_email_public',
        'profile_complete_pct', 'total_applications', 'hired_count', 'avg_rating',
        'workforce_profile_id',
        'ai_readiness_score',
        'highest_cert_level',
        'career_goal',
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    protected $casts = [
        'account_type'      => ApplicantAccountType::class,
        'status'            => ApplicantStatus::class,
        'availability'      => ApplicantAvailability::class,
        'email_verified_at' => 'datetime',
        'is_profile_public' => 'boolean',
        'is_email_public'   => 'boolean',
    ];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->display_name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name ?: 'applicant');
        $slug = $base;
        $i    = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    // ── Relationships ────────────────────────────────────────────────

    public function skills(): HasMany
    {
        return $this->hasMany(MktApplicantSkill::class, 'applicant_id')->orderBy('sort_order');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(MktApplicantExperience::class, 'applicant_id')->orderBy('sort_order');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(MktApplicantPortfolio::class, 'applicant_id')->orderBy('sort_order');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(MktApplication::class, 'applicant_id');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(MktListingBookmark::class, 'applicant_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function hasApplied(int $listingId): bool
    {
        return $this->applications()->where('listing_id', $listingId)->exists();
    }

    public function hasBookmarked(int $listingId): bool
    {
        return $this->bookmarks()->where('listing_id', $listingId)->exists();
    }
}
