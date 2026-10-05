<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Province extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'short_name', 'logo',
        'province_code', 'place_type',
        'region_id', 'country', 'is_active',
        'slogan', 'description', 'cover_image', 'tvc_video_url', 'vr360_map_url', 'highlight_tags',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_featured' => 'boolean', 'highlight_tags' => 'array'];
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image ? Storage::url($this->cover_image) : null;
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null;
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
