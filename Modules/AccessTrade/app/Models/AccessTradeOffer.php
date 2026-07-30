<?php

namespace Modules\AccessTrade\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Dữ liệu chỉ đọc, nạp bởi Modules\AccessTrade\Features\Sync\Actions\SyncOffersAction — không
 * có form tạo/sửa tay (nguồn sự thật là AccessTrade), xem module.json.
 */
class AccessTradeOffer extends Model
{
    protected $table = 'accesstrade_offers';

    protected $fillable = [
        'external_id', 'name', 'content', 'merchant', 'domain', 'link', 'aff_link', 'image',
        'categories', 'coupons', 'banners', 'has_coupon', 'status',
        'start_time', 'end_time', 'last_synced_at',
    ];

    protected $casts = [
        'categories'     => 'array',
        'coupons'        => 'array',
        'banners'        => 'array',
        'has_coupon'     => 'boolean',
        'status'         => 'boolean',
        'start_time'     => 'datetime',
        'end_time'       => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('status', true)
            ->where(fn ($q) => $q->whereNull('end_time')->orWhere('end_time', '>=', now()));
    }

    public function scopeWithCoupon(Builder $query): void
    {
        $query->where('has_coupon', true);
    }
}
