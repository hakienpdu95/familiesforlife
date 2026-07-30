<?php

namespace Modules\AccessTrade\Services;

use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AccessTrade\Exceptions\AccessTradeApiException;

/**
 * Client mỏng cho AccessTrade Publisher API (offers_informations, top_products) — xem
 * config('services.accesstrade') cho credential, config('accesstrade.*') cho tham số hành vi
 * (phân trang, merchants, khoảng ngày). Auth theo tài liệu authentication: header
 * "Authorization: Token <access_key>" (chữ "Token" là literal, không phải Bearer).
 */
class AccessTradeClient
{
    public function __construct(private readonly int $timeoutSeconds = 15)
    {
    }

    /**
     * Lặp trang offers_informations tới khi hết dữ liệu hoặc chạm max_pages (an toàn tránh vòng
     * lặp bất tận nếu API trả sai total/luôn còn dữ liệu). Yield từng offer thô (mảng), không
     * transform — nơi gọi (SyncOffersAction) tự map sang cột DB.
     *
     * @param  array<string, mixed>  $filters  vd ['status' => 1, 'merchant' => 'lazada']
     * @return Generator<int, array<string, mixed>>
     */
    public function fetchAllOffers(array $filters = []): Generator
    {
        $perPage  = (int) config('accesstrade.offers.per_page', 50);
        $maxPages = (int) config('accesstrade.offers.max_pages', 50);

        for ($page = 1; $page <= $maxPages; $page++) {
            $items = $this->fetchOffersPage($filters, $page, $perPage);

            if ($items === []) {
                return;
            }

            foreach ($items as $item) {
                yield $item;
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function fetchOffersPage(array $filters, int $page, int $perPage): array
    {
        $response = $this->get('/v1/offers_informations', [...$filters, 'page' => $page, 'limit' => $perPage]);

        return $this->extractList($response);
    }

    /** @return array<int, array<string, mixed>> */
    public function getTopProducts(?string $merchant = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = array_filter([
            'merchant'  => $merchant,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]);

        $response = $this->get('/v1/top_products', $query);

        return $this->extractList($response);
    }

    /** @return array<string, mixed> */
    private function get(string $path, array $query): array
    {
        $accessToken = config('services.accesstrade.access_token');

        if (! $accessToken) {
            throw new AccessTradeApiException('Thiếu ACCESSTRADE_ACCESS_TOKEN — cấu hình trong .env trước khi đồng bộ.');
        }

        $baseUrl = rtrim((string) config('services.accesstrade.base_url', 'https://api.accesstrade.vn'), '/');

        $response = Http::withHeaders(['Authorization' => 'Token '.$accessToken])
            ->timeout($this->timeoutSeconds)
            ->get($baseUrl.$path, $query);

        if ($response->failed()) {
            Log::error('AccessTrade API request failed', [
                'path'   => $path,
                'query'  => $query,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new AccessTradeApiException("AccessTrade API lỗi ({$response->status()}) tại {$path}");
        }

        return $response->json() ?? [];
    }

    /** @return array<int, array<string, mixed>> */
    private function extractList(array $response): array
    {
        $data = $response['data'] ?? [];

        return is_array($data) ? array_values($data) : [];
    }
}
