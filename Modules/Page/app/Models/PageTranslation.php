<?php

namespace Modules\Page\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    protected $fillable = ['page_id', 'locale', 'title', 'slug', 'excerpt', 'content', 'seo_title', 'seo_description'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
