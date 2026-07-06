<?php

namespace Modules\JobPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class JpScreeningChoice extends Model
{
    public $timestamps = false;

    protected $table = 'jp_screening_choices';

    protected $fillable = [
        'uuid',
        'question_id',
        'choice_text',
        'is_disqualifying',
        'sort_order',
    ];

    protected $casts = [
        'is_disqualifying' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(JpScreeningQuestion::class, 'question_id');
    }
}
