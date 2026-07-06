<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Survey\Enums\SurveyStatus;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Survey extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $table = 'surveys';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'slug',
        'assessment_code',
        'specialized_set_code',
        'target_role_code',
        'target_role_level',
        'status',
        'version',
        'allow_multiple_responses',
        'turnstile_site_id',
    ];

    protected function casts(): array
    {
        return [
            'status'                   => SurveyStatus::class,
            'version'                  => 'integer',
            'allow_multiple_responses' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function sections(): HasMany
    {
        return $this->hasMany(SurveySection::class)->orderBy('sort_order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(SurveyField::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(SurveyToken::class);
    }

    public function turnstileSite(): BelongsTo
    {
        return $this->belongsTo(SurveyTurnstileSite::class, 'turnstile_site_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SurveyStatus::Active);
    }

    public function scopeBySlug(Builder $query, string $slug): Builder
    {
        return $query->where('slug', $slug);
    }

    public function scopeByAssessmentCode(Builder $query, string $code): Builder
    {
        return $query->where('assessment_code', $code);
    }

    public function hasScoring(): bool
    {
        return $this->assessment_code !== null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'status', 'assessment_code', 'version'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => "Tạo khảo sát: {$this->title}",
                'updated' => "Cập nhật khảo sát: {$this->title}",
                'deleted' => "Xóa khảo sát: {$this->title}",
                default   => $event,
            })
            ->useLogName('Survey');
    }
}
