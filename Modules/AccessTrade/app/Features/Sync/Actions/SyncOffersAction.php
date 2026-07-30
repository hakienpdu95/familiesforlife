<?php

namespace Modules\AccessTrade\Features\Sync\Actions;

use Illuminate\Support\Facades\Log;
use Modules\AccessTrade\Exceptions\AccessTradeApiException;
use Modules\AccessTrade\Models\AccessTradeOffer;
use Modules\AccessTrade\Services\AccessTradeClient;

/**
 * Đồng bộ offers_informations (status=1 — active) vào accesstrade_offers. Gộp chung 2 tài liệu
 * "vouchers/coupons/deals" + "khuyến mãi đang hoạt động" vì cùng 1 endpoint — has_coupon phân
 * biệt sau khi đã lưu (xem migration accesstrade_offers).
 */
class SyncOffersAction
{
    public function __construct(private readonly AccessTradeClient $client)
    {
    }

    /** @return array{synced: int, failed: int} */
    public function handle(): array
    {
        $merchants = config('accesstrade.offers.merchants', []);
        $merchants = $merchants === [] ? [null] : $merchants;

        $synced = 0;
        $failed = 0;

        foreach ($merchants as $merchant) {
            $filters = array_filter(['status' => 1, 'merchant' => $merchant]);

            try {
                foreach ($this->client->fetchAllOffers($filters) as $item) {
                    if (empty($item['id'])) {
                        continue;
                    }

                    $this->upsert($item);
                    $synced++;
                }
            } catch (AccessTradeApiException $e) {
                $failed++;
                Log::error('AccessTrade offers sync failed for merchant', [
                    'merchant' => $merchant,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    /** @param array<string, mixed> $item */
    private function upsert(array $item): void
    {
        $coupons = is_array($item['coupons'] ?? null) ? $item['coupons'] : [];

        AccessTradeOffer::updateOrCreate(
            ['external_id' => (string) $item['id']],
            [
                'name'           => (string) ($item['name'] ?? ''),
                'content'        => $item['content'] ?? null,
                'merchant'       => $item['merchant'] ?? null,
                'domain'         => $item['domain'] ?? null,
                'link'           => $item['link'] ?? null,
                'aff_link'       => $item['aff_link'] ?? null,
                'image'          => $item['image'] ?? null,
                'categories'     => is_array($item['categories'] ?? null) ? $item['categories'] : [],
                'coupons'        => $coupons,
                'banners'        => is_array($item['banners'] ?? null) ? $item['banners'] : [],
                'has_coupon'     => $coupons !== [],
                'status'         => true,
                'start_time'     => $item['start_time'] ?? null,
                'end_time'       => $item['end_time'] ?? null,
                'last_synced_at' => now(),
            ]
        );
    }
}
