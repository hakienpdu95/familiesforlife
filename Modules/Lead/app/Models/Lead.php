<?php

namespace Modules\Lead\Models;

use App\Models\User;
use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Lead\Enums\LeadStatus;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;
use Modules\LeadSource\Models\LeadSource;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Lead extends TenantAwareModel
{
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('lead')
            ->logOnly([
                'stage_id', 'status', 'assigned_to',
                'expected_value', 'expected_close_date',
                'title', 'description',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $table = 'leads';

    protected $fillable = [
        'organization_id', 'contact_id', 'customer_id',
        'contact_name', 'contact_phone', 'contact_company',
        'stage_id', 'stage_changed_at',
        'source_id', 'source_detail',
        'assigned_to', 'assigned_at',
        'expected_value', 'currency',
        'expected_close_date', 'actual_close_date', 'actual_value',
        'title', 'description',
        'survey_response_id', 'survey_band_code', 'survey_score',
        'lead_score', 'score_updated_at',
        'status', 'last_activity_at', 'activity_count',
        'idempotent_key', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'stage_changed_at'    => 'datetime',
        'assigned_at'         => 'datetime',
        'expected_close_date' => 'date',
        'actual_close_date'   => 'date',
        'expected_value'      => 'decimal:2',
        'actual_value'        => 'decimal:2',
        'survey_score'        => 'decimal:2',
        'score_updated_at'    => 'datetime',
        'last_activity_at'    => 'datetime',
        'status'              => LeadStatus::class,
    ];

    // ── Relationships ───────────────────────────────────────────────

    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LeadContact::class, 'contact_id');
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\Modules\Customer\Models\Customer::class, 'customer_id');
    }

    public function stage(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LeadPipelineStage::class, 'stage_id');
    }

    public function source(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function assignee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('created_at');
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeadNote::class)->orderByDesc('is_pinned')->orderByDesc('created_at');
    }

    public function meta(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeadMeta::class);
    }

    public function stageHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeadStageHistory::class)->orderByDesc('changed_at');
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            LeadTagDefinition::class,
            'lead_tag_map',
            'lead_id',
            'tag_id'
        );
    }

    // ── Helpers ─────────────────────────────────────────────────────

    public function displayTitle(): string
    {
        if ($this->title) return $this->title;

        $parts = array_filter([$this->contact_name, $this->contact_company]);

        return implode(' — ', $parts) ?: "Lead #{$this->id}";
    }

    public function isActive(): bool
    {
        return $this->status === LeadStatus::Active;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [LeadStatus::Converted, LeadStatus::Archived]);
    }

    // ── Query Scopes ────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', LeadStatus::Active->value);
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeClosingSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereBetween('expected_close_date', [
            now()->toDateString(),
            now()->addDays($days)->toDateString(),
        ]);
    }

    public function scopeStale(Builder $query, int $days = 14): Builder
    {
        return $query->where('last_activity_at', '<', now()->subDays($days))
                     ->orWhereNull('last_activity_at');
    }

    public function scopeHot(Builder $query, int $threshold = 70): Builder
    {
        return $query->where('lead_score', '>=', $threshold);
    }
}
