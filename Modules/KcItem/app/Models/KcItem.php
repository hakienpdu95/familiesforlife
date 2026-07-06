<?php

namespace Modules\KcItem\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Assessment\Models\RoadmapMilestone;
use Modules\KcCategory\Models\KcCategory;
use Modules\KcItem\Enums\KcItemStatus;
use Modules\KcItem\Enums\KcItemType;
use Modules\KcItem\Enums\KcItemVisibility;
use Spatie\MediaLibrary\HasMedia;

class KcItem extends TenantAwareModel implements HasMedia
{
    use HasTenantMedia;

    protected $table = 'kc_items';

    protected $fillable = [
        'uuid',
        'category_id',
        'domain_code',
        'difficulty',
        'organization_id',
        'title',
        'slug',
        'summary',
        'content',
        'type',
        'status',
        'visibility',
        'language',
        'view_count',
        'download_count',
        'is_featured',
        'is_pinned',
        'owner_id',
        'approved_by',
        'approved_at',
        'version',
        'effective_date',
        'expired_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type'           => KcItemType::class,
        'status'         => KcItemStatus::class,
        'visibility'     => KcItemVisibility::class,
        'is_featured'    => 'boolean',
        'is_pinned'      => 'boolean',
        'view_count'     => 'integer',
        'download_count' => 'integer',
        'difficulty'     => 'integer',
        'version'        => 'integer',
        'approved_at'    => 'datetime',
        'effective_date' => 'datetime',
        'expired_date'   => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(KcCategory::class, 'category_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KcItemAttachment::class, 'item_id')->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KcTag::class, 'kc_item_tags', 'item_id', 'tag_id');
    }

    public function versionHistories(): HasMany
    {
        return $this->hasMany(KcVersionHistory::class, 'item_id')->orderByDesc('version_number');
    }

    public function accessControls(): HasMany
    {
        return $this->hasMany(KcAccessControl::class, 'item_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(KcFeedback::class, 'item_id');
    }

    public function viewLogs(): HasMany
    {
        return $this->hasMany(KcViewLog::class, 'item_id');
    }

    public function roadmapMilestones(): BelongsToMany
    {
        return $this->belongsToMany(RoadmapMilestone::class, 'roadmap_milestone_kc_items', 'kc_item_id', 'roadmap_milestone_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeApproved($query)
    {
        return $query->where('status', KcItemStatus::Approved->value);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', KcItemStatus::Draft->value);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForDomain($query, string $domainCode)
    {
        return $query->where('domain_code', $domainCode);
    }

    public function scopeForDifficulty($query, int $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isEditable(): bool
    {
        return in_array($this->status, [KcItemStatus::Draft, KcItemStatus::Rejected]);
    }

    public function canSubmit(): bool
    {
        return $this->status === KcItemStatus::Draft || $this->status === KcItemStatus::Rejected;
    }

    public function canApprove(): bool
    {
        return $this->status === KcItemStatus::PendingReview;
    }
}
