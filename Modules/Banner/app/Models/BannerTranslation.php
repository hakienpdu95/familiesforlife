<?php

namespace Modules\Banner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannerTranslation extends Model
{
    protected $fillable = ['banner_id', 'locale', 'alt_text', 'badge_label'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }
}
