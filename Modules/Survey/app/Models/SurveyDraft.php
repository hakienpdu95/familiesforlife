<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyDraft extends Model
{
    protected $fillable = [
        'survey_id',
        'respondent_ref',
        'answers',
        'current_section',
        'expires_at',
    ];

    protected $casts = [
        'answers'     => 'array',
        'expires_at'  => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
