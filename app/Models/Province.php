<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Province extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'short_name', 'logo',
        'province_code', 'place_type',
        'region_id', 'country', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class, 'province_code', 'province_code');
    }
}
