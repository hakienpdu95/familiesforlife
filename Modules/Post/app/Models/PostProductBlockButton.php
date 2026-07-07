<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Post\Enums\ButtonStyle;
use Modules\Post\Enums\ButtonTarget;
use Modules\Post\Enums\ButtonUrlType;
use Modules\Product\Enums\ProductLinkType;

class PostProductBlockButton extends Model
{
    protected $table = 'post_product_block_buttons';

    protected $fillable = [
        'block_id',
        'block_item_id',
        'button_key',
        'label',
        'url_type',
        'url',
        'product_link_type',
        'target',
        'style',
        'sort_order',
        'click_count',
    ];

    protected $casts = [
        'url_type'    => ButtonUrlType::class,
        'target'      => ButtonTarget::class,
        'style'       => ButtonStyle::class,
        'sort_order'  => 'integer',
        'click_count' => 'integer',
    ];

    public function block(): BelongsTo
    {
        return $this->belongsTo(PostProductBlock::class, 'block_id');
    }

    public function blockItem(): BelongsTo
    {
        return $this->belongsTo(PostProductBlockItem::class, 'block_item_id');
    }

    /**
     * Nhãn hiển thị — fallback ProductLinkType::label() khi dùng use_product_link
     * (docs/post-module-spec.md §9.5).
     */
    public function getDisplayLabelAttribute(): ?string
    {
        if ($this->label) {
            return $this->label;
        }

        if ($this->url_type === ButtonUrlType::UseProductLink && $this->product_link_type) {
            return ProductLinkType::tryFrom($this->product_link_type)?->label();
        }

        return null;
    }

    /**
     * URL đích thật — resolve động từ `Product` khi use_product_link, không bao giờ đọc
     * cột `url` (luôn null cho case này — docs/post-module-spec.md §9.8.1).
     */
    public function resolveTargetUrl(): ?string
    {
        if ($this->url_type === ButtonUrlType::UseProductLink) {
            $linkType = ProductLinkType::tryFrom((string) $this->product_link_type);
            $product  = $this->blockItem?->product;

            if (! $linkType || ! $product) {
                return null;
            }

            return $product->{$linkType->urlColumn()};
        }

        return match ($this->url_type) {
            ButtonUrlType::Phone => $this->url ? ('tel:' . $this->url) : null,
            ButtonUrlType::Zalo  => $this->url && ! str_starts_with($this->url, 'http') ? ('https://zalo.me/' . $this->url) : $this->url,
            ButtonUrlType::Email => $this->url ? ('mailto:' . $this->url) : null,
            default              => $this->url,
        };
    }
}
