<?php

namespace Modules\Ocop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcopCategoryTranslation extends Model
{
    protected $fillable = ['ocop_category_id', 'locale', 'name'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OcopCategory::class, 'ocop_category_id');
    }
}
