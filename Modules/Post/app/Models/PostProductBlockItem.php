<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Product\Models\Product;

class PostProductBlockItem extends Model
{
    protected $table = 'post_product_block_items';

    protected $fillable = [
        'block_id',
        'item_key',
        'product_id',
        'title_override',
        'price_label_override',
        'description_override',
        'image_url_override',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(PostProductBlock::class, 'block_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function buttons(): HasMany
    {
        return $this->hasMany(PostProductBlockButton::class, 'block_item_id')->orderBy('sort_order');
    }

    // ── Fallback tới dữ liệu "sống" từ Product (docs/product-catalog-spec.md §9.5) ──

    public function getDisplayTitleAttribute(): ?string
    {
        return $this->title_override ?? $this->product?->name;
    }

    public function getDisplayPriceLabelAttribute(): ?string
    {
        return $this->price_label_override ?? $this->product?->display_price;
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->description_override ?? $this->product?->short_description;
    }

    public function getDisplayImageUrlAttribute(): ?string
    {
        return $this->image_url_override ?? $this->product?->cover_image_url;
    }
}
