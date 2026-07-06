<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class MktTag extends Model
{
    protected $table = 'mkt_tags';

    protected $fillable = ['uuid', 'name', 'slug', 'listing_type', 'use_count'];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(MktListing::class, 'mkt_listing_tags', 'tag_id', 'listing_id');
    }
}
