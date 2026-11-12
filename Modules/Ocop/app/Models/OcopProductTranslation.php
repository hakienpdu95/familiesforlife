<?php

namespace Modules\Ocop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcopProductTranslation extends Model
{
    protected $fillable = ['ocop_product_id', 'locale', 'name', 'slug', 'description', 'story', 'origin', 'ingredients', 'usage_instructions', 'storage_instructions'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OcopProduct::class, 'ocop_product_id');
    }
}
