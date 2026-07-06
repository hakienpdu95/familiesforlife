<?php

namespace Modules\Recruitment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RcApplicationAnswer extends Model
{
    protected $table = 'rc_application_answers';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'application_id',
        'jp_question_id',
        'question_text',
        'question_type',
        'answer_text',
        'answer_bool',
        'answer_choices',
        'is_disqualifying',
    ];

    protected $casts = [
        'answer_bool'      => 'boolean',
        'is_disqualifying' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(RcApplication::class, 'application_id');
    }
}
