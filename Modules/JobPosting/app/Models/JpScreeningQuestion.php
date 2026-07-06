<?php

namespace Modules\JobPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\JobPosting\Enums\ScreeningQuestionType;

class JpScreeningQuestion extends Model
{
    public $timestamps = false;

    protected $table = 'jp_screening_questions';

    protected $fillable = [
        'uuid',
        'job_post_id',
        'question_text',
        'question_type',
        'is_required',
        'is_disqualifying',
        'disqualify_if_answer',
        'placeholder',
        'max_length',
        'sort_order',
    ];

    protected $casts = [
        'question_type'    => ScreeningQuestionType::class,
        'is_required'      => 'boolean',
        'is_disqualifying' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JpJobPost::class, 'job_post_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(JpScreeningChoice::class, 'question_id')->orderBy('sort_order');
    }
}
