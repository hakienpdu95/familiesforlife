<?php

namespace Modules\OcopSubject\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcopSubjectTranslation extends Model
{
    protected $fillable = ['ocop_subject_id', 'locale', 'name', 'story'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OcopSubject::class, 'ocop_subject_id');
    }
}
