<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Ocop\Models\OcopProduct;
use Modules\Product\Models\Product;

class PostProductBlockItem extends Model
{
    protected $table = 'post_product_block_items';

    protected $fillable = [
        'block_id',
        'item_key',
        'product_id',
        'ocop_product_id',
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

    public function ocopProduct(): BelongsTo
    {
        return $this->belongsTo(OcopProduct::class, 'ocop_product_id')->withTrashed();
    }

    /** Nguồn dữ liệu "sống" của item — Product hoặc OcopProduct tuỳ source của khối. */
    public function source(): Product|OcopProduct|null
    {
        return $this->ocop_product_id ? $this->ocopProduct : $this->product;
    }

    public function referenceId(): ?int
    {
        return $this->ocop_product_id ?? $this->product_id;
    }

    public function buttons(): HasMany
    {
        return $this->hasMany(PostProductBlockButton::class, 'block_item_id')->orderBy('sort_order');
    }

    // ── Fallback tới dữ liệu "sống" từ Product (docs/product-catalog-spec.md §9.5) ──

    public function getDisplayTitleAttribute(): ?string
    {
        return $this->title_override ?? $this->source()?->name;
    }

    public function getDisplayPriceLabelAttribute(): ?string
    {
        if ($this->price_label_override !== null) {
            return $this->price_label_override;
        }

        if ($this->ocop_product_id) {
            $stars = $this->ocopProduct?->star_rating;

            return $stars ? 'OCOP '.$stars.' sao' : null;
        }

        return $this->product?->display_price;
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->description_override
            ?? ($this->ocop_product_id ? Str::limit(trim(strip_tags((string) $this->ocopProduct?->description)), 200) ?: null : $this->product?->short_description);
    }

    public function getDisplayImageUrlAttribute(): ?string
    {
        return $this->image_url_override
            ?? ($this->ocop_product_id ? ($this->ocopProduct?->imageUrl('medium') ?: null) : $this->product?->cover_image_url);
    }
}
