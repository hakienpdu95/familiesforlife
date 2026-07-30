<?php

namespace Modules\AccessTrade\Features\Sync\Actions;

use Illuminate\Support\Facades\Log;
use Modules\AccessTrade\Exceptions\AccessTradeApiException;
use Modules\AccessTrade\Models\AccessTradeTopProduct;
use Modules\AccessTrade\Services\AccessTradeClient;

/** Đồng bộ top_products vào accesstrade_top_products, mỗi lần chạy là 1 snapshot theo merchant. */
class SyncTopProductsAction
{
    public function __construct(private readonly AccessTradeClient $client)
    {
    }

    /** @return array{synced: int, failed: int} */
    public function handle(): array
    {
        $merchants = config('accesstrade.top_products.merchants', []);
        $merchants = $merchants === [] ? [null] : $merchants;

        $daysBack = (int) config('accesstrade.top_products.days_back', 30);
        $dateTo   = now();
        $dateFrom = now()->subDays($daysBack);

        $synced = 0;
        $failed = 0;

        foreach ($merchants as $merchant) {
            try {
                $items = $this->client->getTopProducts($merchant, $dateFrom->format('d-m-Y'), $dateTo->format('d-m-Y'));

                foreach ($items as $item) {
                    if (empty($item['product_id'])) {
                        continue;
                    }

                    $this->upsert($item, $merchant ?? '', $dateFrom->toDateString(), $dateTo->toDateString());
                    $synced++;
                }
            } catch (AccessTradeApiException $e) {
                $failed++;
                Log::error('AccessTrade top products sync failed for merchant', [
                    'merchant' => $merchant,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return ['synced' => $synced, 'failed' => $failed];
    }

    /** @param array<string, mixed> $item */
    private function upsert(array $item, string $merchant, string $dateFrom, string $dateTo): void
    {
        AccessTradeTopProduct::updateOrCreate(
            [
                'merchant'             => $merchant,
                'external_product_id'  => (string) $item['product_id'],
            ],
            [
                'name'             => (string) ($item['name'] ?? ''),
                'category_id'      => $item['category_id'] ?? null,
                'category_name'    => $item['category_name'] ?? null,
                'price'            => $item['price'] ?? null,
                'discount'         => $item['discount'] ?? null,
                'image'            => $item['image'] ?? null,
                'link'             => $item['link'] ?? null,
                'aff_link'         => $item['aff_link'] ?? null,
                'desc'             => $item['desc'] ?? null,
                'total'            => $item['total'] ?? null,
                'brand'            => $item['brand'] ?? null,
                'product_category' => $item['product_category'] ?? null,
                'synced_date_from' => $dateFrom,
                'synced_date_to'   => $dateTo,
                'last_synced_at'   => now(),
            ]
        );
    }
}
