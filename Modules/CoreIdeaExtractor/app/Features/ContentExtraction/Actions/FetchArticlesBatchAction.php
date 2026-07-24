<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Actions\Concerns\GuardsUrlSafety;
use Modules\CoreIdeaExtractor\Features\ContentExtraction\Exceptions\UrlFetchException;

/**
 * Bản pooled (Http::pool) của FetchArticleHtmlAction cho nhiều URL cùng lúc (batch, tối đa
 * config('core_idea_extractor.batch.max_urls')) — mỗi "round" bắn song song 1 GET cho từng URL
 * còn "chưa xong" (chưa thành công/thất bại hẳn), URL bị redirect thì cập nhật con trỏ và tiếp
 * tục ở round sau — GIỐNG HỆT vòng lặp hop-by-hop của FetchArticleHtmlAction, chỉ khác là chạy
 * cho N url đồng thời thay vì 1. Dùng chung GuardsUrlSafety để mỗi hop (kể cả sau redirect) đều
 * được assertSafeUrl() y hệt bản single — không có url nào né được kiểm tra SSRF chỉ vì chạy
 * trong batch.
 *
 * 403/429/503 KHÔNG coi là exception ở đây — trả về $failure có status 'blocked'/'error' để
 * controller build kết quả từng nguồn riêng, 1 url lỗi không làm hỏng cả batch.
 */
class FetchArticlesBatchAction
{
    use AsAction, GuardsUrlSafety;

    private const REDIRECT_STATUSES = [301, 302, 303, 307, 308];

    /**
     * @param  array<int|string, string>  $urls
     * @return array<int|string, array{html: ?string, resolved_url: ?string, http_status: ?int, fetched_at: string, failure: ?array{status: string, failure_type: string, http_status: ?int, error_message: string}}>
     */
    public function handle(array $urls): array
    {
        $maxRedirects = (int) config('core_idea_extractor.fetch.max_redirects', 3);
        $userAgent    = config('core_idea_extractor.fetch.user_agent');
        $timeout      = (int) config('core_idea_extractor.fetch.timeout_seconds', 15);
        $maxBytes     = (int) config('core_idea_extractor.fetch.max_content_bytes', 5 * 1024 * 1024);

        $items = [];

        foreach ($urls as $key => $url) {
            $items[$key] = [
                'current'      => $url,
                'resolved_url' => null,
                'html'         => null,
                'http_status'  => null,
                'fetched_at'   => null,
                'failure'      => null,
                'done'         => false,
            ];

            try {
                $this->assertSafeUrl($url);
            } catch (UrlFetchException $e) {
                $this->markFailed($items, $key, 'error', 'invalid_url', null, $e->getMessage());
            }
        }

        for ($hop = 0; $hop <= $maxRedirects; $hop++) {
            $pending = array_filter($items, static fn (array $item) => ! $item['done']);

            if (empty($pending)) {
                break;
            }

            $responses = Http::pool(function (Pool $pool) use ($pending, $userAgent, $timeout) {
                $requests = [];

                foreach ($pending as $key => $item) {
                    $requests[] = $pool->as((string) $key)
                        ->withHeaders(['User-Agent' => $userAgent])
                        ->timeout($timeout)
                        ->withOptions(['allow_redirects' => false])
                        ->get($item['current']);
                }

                return $requests;
            });

            foreach ($pending as $key => $item) {
                $response = $responses[(string) $key] ?? null;

                if ($response instanceof \Throwable) {
                    $this->markFailed($items, $key, 'error', 'network_error', null, 'Lỗi kết nối khi tải trang: '.$response->getMessage());

                    continue;
                }

                if (in_array($response->status(), self::REDIRECT_STATUSES, true)) {
                    $this->advanceRedirect($items, $key, $response);

                    continue;
                }

                if (! $response->successful()) {
                    $classification = ClassifyFetchFailureAction::run($response->status(), $response->body(), $response->headers());

                    $this->markFailed(
                        $items,
                        $key,
                        $classification['status'],
                        $classification['failure_type'],
                        $classification['http_status'],
                        $classification['error_message'],
                    );

                    continue;
                }

                $this->finishSuccess($items, $key, $response, $maxBytes);
            }
        }

        foreach ($items as $key => $item) {
            if (! $item['done']) {
                $this->markFailed($items, $key, 'error', 'too_many_redirects', null, "Quá nhiều lượt redirect (>{$maxRedirects}).");
            }
        }

        return array_map(static fn (array $item) => [
            'html'         => $item['html'],
            'resolved_url' => $item['resolved_url'],
            'http_status'  => $item['http_status'],
            'fetched_at'   => $item['fetched_at'],
            'failure'      => $item['failure'],
        ], $items);
    }

    private function advanceRedirect(array &$items, int|string $key, $response): void
    {
        $location = $response->header('Location');

        if (! $location) {
            $this->markFailed($items, $key, 'error', 'redirect_error', $response->status(), 'Redirect không có header Location.');

            return;
        }

        $next = $this->resolveRedirectUrl($items[$key]['current'], $location);

        try {
            $this->assertSafeUrl($next);
        } catch (UrlFetchException $e) {
            $this->markFailed($items, $key, 'error', 'invalid_url', $response->status(), $e->getMessage());

            return;
        }

        $items[$key]['current']      = $next;
        $items[$key]['resolved_url'] = $next;
    }

    private function finishSuccess(array &$items, int|string $key, $response, int $maxBytes): void
    {
        $contentType = (string) $response->header('Content-Type');

        if ($contentType !== '' && ! str_contains($contentType, 'html')) {
            $this->markFailed($items, $key, 'error', 'invalid_content_type', $response->status(), "Nội dung không phải HTML (Content-Type: {$contentType}).");

            return;
        }

        $body = $this->normalizeToUtf8($response->body(), $contentType);

        $items[$key]['done']        = true;
        $items[$key]['html']        = strlen($body) > $maxBytes ? substr($body, 0, $maxBytes) : $body;
        $items[$key]['http_status'] = $response->status();
        $items[$key]['fetched_at']  = now()->toIso8601String();
    }

    private function markFailed(array &$items, int|string $key, string $status, string $failureType, ?int $httpStatus, string $errorMessage): void
    {
        $items[$key]['done']        = true;
        $items[$key]['http_status'] = $httpStatus;
        $items[$key]['fetched_at']  = now()->toIso8601String();
        $items[$key]['failure']     = [
            'status'        => $status,
            'failure_type'  => $failureType,
            'http_status'   => $httpStatus,
            'error_message' => $errorMessage,
        ];
    }
}
