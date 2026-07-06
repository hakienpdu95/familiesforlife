<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'ward_code', 'place_type', 'province_code', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'province_code');
    }
}
