<?php

namespace Modules\AccessTrade\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Snapshot top sản phẩm bán chạy — nạp bởi
 * Modules\AccessTrade\Features\Sync\Actions\SyncTopProductsAction, chỉ đọc.
 */
class AccessTradeTopProduct extends Model
{
    protected $table = 'accesstrade_top_products';

    protected $fillable = [
        'external_product_id', 'merchant', 'name', 'category_id', 'category_name',
        'price', 'discount', 'image', 'link', 'aff_link', 'desc', 'total', 'brand',
        'product_category', 'synced_date_from', 'synced_date_to', 'last_synced_at',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'discount'         => 'decimal:2',
        'total'            => 'integer',
        'synced_date_from' => 'date',
        'synced_date_to'   => 'date',
        'last_synced_at'   => 'datetime',
    ];

    public function scopeForMerchant(Builder $query, ?string $merchant): void
    {
        if ($merchant) {
            $query->where('merchant', $merchant);
        }
    }
}
