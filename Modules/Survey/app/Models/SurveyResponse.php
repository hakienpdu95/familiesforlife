<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Assessment\Contracts\ScoringSubjectInterface;
use Modules\Assessment\Engine\AnswerReader;
use Modules\Survey\Enums\ResponseStatus;

class SurveyResponse extends Model implements ScoringSubjectInterface
{
    use HasFactory, SoftDeletes;

    protected $table = 'survey_responses';

    protected $fillable = [
        'survey_id',
        'respondent_ref',
        'respondent_ip',
        'source_type',
        'subject_user_id',
        'evaluator_user_id',
        'source_weight',
        'requires_human_review',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status'                => ResponseStatus::class,
            'submitted_at'          => 'datetime',
            'deleted_at'            => 'datetime',
            'source_weight'         => 'float',
            'requires_human_review' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class, 'response_id');
    }

    public function result(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SurveyResult::class, 'response_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────

    /**
     * Decode BINARY(16) → human-readable IP string (strips ::ffff: IPv4-mapped prefix).
     * Storage uses inet_pton(); this accessor reverses it for display without a DB function call.
     */
    protected function respondentIp(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null) {
                    return null;
                }
                $ip = inet_ntop($value);
                if ($ip === false) {
                    return null;
                }
                return str_starts_with($ip, '::ffff:') ? substr($ip, 7) : $ip;
            },
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeComplete(Builder $query): Builder
    {
        return $query->where('status', ResponseStatus::Complete);
    }

    public function scopeForSurvey(Builder $query, int $surveyId): Builder
    {
        return $query->where('survey_id', $surveyId);
    }

    // ── ScoringSubjectInterface ───────────────────────────────────────

    public function getScoringSubjectId(): int
    {
        return $this->id;
    }

    public function getScoringSubjectType(): string
    {
        return static::class;
    }

    public function getAssessmentCode(): string
    {
        return $this->survey?->assessment_code ?? '';
    }

    public function getScoringAnswers(): array
    {
        return app(AnswerReader::class)->read($this->id, $this->survey_id);
    }
}
