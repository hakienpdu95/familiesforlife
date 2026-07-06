<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MktListingBookmark extends Model
{
    protected $table = 'mkt_listing_bookmarks';
    public $timestamps = false;

    protected $fillable = [
        'uuid', 'listing_id', 'applicant_id', 'note', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MktListing::class, 'listing_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MktApplicant::class, 'applicant_id');
    }
}
