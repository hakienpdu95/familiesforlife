<?php

namespace Modules\Organization\Models;

use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use App\Shared\Tenancy\Models\Organization as BaseOrganization;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;

/**
 * Extended Organization model for the Organization module.
 *
 * Adds: members, settings table relations.
 * Overrides getSetting()/setSetting() to use organization_settings table.
 */
class Organization extends BaseOrganization implements HasMedia
{
    use HasTenantMedia;
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'status',
        'owner_id',
        'settings',
        // Business fields
        'tax_code',
        'phone',
        'email',
        'website',
        'industry',
        'description',
        'logo_path',
        // Address fields
        'address',
        'city',
        'country',
        'postal_code',
        'province_code',
        'ward_code',
        'full_address',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function orgSettings(): HasMany
    {
        return $this->hasMany(OrganizationSetting::class);
    }

    public function memberUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_members', 'organization_id', 'user_id')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class, 'ward_code', 'ward_code');
    }

    // ── Settings (table-backed) ──────────────────────────────────────

    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->orgSettings()->where('key', $key)->first();

        if ($setting === null) {
            return data_get($this->settings, $key, $default);
        }

        return $setting->getCastedValue();
    }

    public function setSetting(string $key, mixed $value, string $type = 'string'): void
    {
        $serialized = match ($type) {
            'json'    => json_encode($value),
            'boolean' => $value ? '1' : '0',
            default   => (string) $value,
        };

        $this->orgSettings()->updateOrCreate(
            ['key' => $key],
            ['value' => $serialized, 'type' => $type]
        );
    }
}
