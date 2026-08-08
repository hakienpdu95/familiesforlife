<?php

namespace Modules\Post\Support;

use Illuminate\Http\Request;

/**
 * Parse `Accept` header ĐÚNG chuẩn RFC 9110 §12.5.1 (q-value, specificity, loại bỏ q=0) — KHÔNG
 * so khớp chuỗi ngây thơ. Dùng chung được cho bất kỳ Controller nào cần content negotiation
 * (hiện chỉ PublicArticleController dùng, nhưng đặt ở Support cấp module để tái dùng dễ nếu
 * Video/Playlist cần sau này — xem spec/Markdown_Content_Negotiation_Technical_Specification.md §8).
 */
trait NegotiatesMarkdown
{
    protected function prefersMarkdown(Request $request): bool
    {
        return $this->pickType($request, ['text/html', 'text/markdown']) === 'text/markdown';
    }

    /**
     * Trả về type PHÙ HỢP NHẤT trong $produces theo Accept header — ưu tiên q cao hơn, rồi tới
     * độ cụ thể (type/subtype > type/* > * / *), rồi tới thứ tự khai báo trong $produces khi mọi
     * thứ bằng nhau (VD "Accept: * / *" từ curl mặc định → luôn chọn phần tử ĐẦU TIÊN, tức HTML,
     * KHÔNG suy đoán agent muốn Markdown chỉ vì "chấp nhận được").
     *
     * @param  string[]  $produces  Danh sách type hỗ trợ, THỨ TỰ = độ ưu tiên khi tie-break.
     */
    protected function pickType(Request $request, array $produces): ?string
    {
        $accept = $request->header('Accept');

        if (! $accept) {
            return $produces[0] ?? null;
        }

        $ranges = $this->parseAcceptHeader($accept);

        $best = null;
        $bestScore = null;

        foreach ($produces as $index => $type) {
            [$typePart, $subtypePart] = explode('/', $type, 2);

            foreach ($ranges as $range) {
                if ($range['q'] <= 0.0) {
                    continue; // q=0 — client TỪ CHỐI loại này tường minh
                }

                $specificity = match (true) {
                    $range['type'] === $typePart && $range['subtype'] === $subtypePart => 2,
                    $range['type'] === $typePart && $range['subtype'] === '*' => 1,
                    $range['type'] === '*' && $range['subtype'] === '*' => 0,
                    default => null,
                };

                if ($specificity === null) {
                    continue;
                }

                $score = [$range['q'], $specificity, -$index];

                if ($bestScore === null || $score > $bestScore) {
                    $bestScore = $score;
                    $best = $type;
                }
            }
        }

        return $best;
    }

    /** @return array<int, array{type: string, subtype: string, q: float}> */
    private function parseAcceptHeader(string $accept): array
    {
        $ranges = [];

        foreach (explode(',', $accept) as $entry) {
            $parts = explode(';', trim($entry));
            $mediaRange = strtolower(trim(array_shift($parts)));

            if (! str_contains($mediaRange, '/')) {
                continue; // entry hỏng — Accept header do client tự gửi, không throw, bỏ qua
            }

            [$type, $subtype] = explode('/', $mediaRange, 2);

            $q = 1.0;
            foreach ($parts as $param) {
                if (preg_match('/^\s*q\s*=\s*([\d.]+)\s*$/i', $param, $m)) {
                    $q = (float) $m[1];
                }
            }

            $ranges[] = ['type' => trim($type), 'subtype' => trim($subtype), 'q' => $q];
        }

        return $ranges;
    }
}
