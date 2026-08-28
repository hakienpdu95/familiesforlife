<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/**
 * (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — cảnh báo Keyword Cannibalization:
 * trước khi tạo 1 cụm chủ đề (`topiccluster`) mới, kiểm tra `seed_keyword` đã từng dùng (hoặc gần
 * trùng) ở 1 GeneratedPrompt khác cùng framework chưa, để tránh 2 cụm chủ đề "ăn thịt" từ khóa của
 * nhau trên site.
 *
 * Chỉ áp dụng cho framework `topiccluster` — hard-code framework key thay vì nhận tham số: đây là
 * framework DUY NHẤT mà 1 field mang ý nghĩa "từ khóa hạt giống của 1 cụm nội dung" (các framework
 * khác không có khái niệm cụm/cannibalization, tổng quát hoá thêm lúc này chỉ là suy đoán trước).
 *
 * So khớp bằng chuỗi đã chuẩn hoá (trim + lowercase + gộp khoảng trắng) trong PHP, KHÔNG dùng JSON
 * path SQL trực tiếp lên cột `field_values` — cú pháp JSON path khác nhau giữa MySQL/SQLite (dự án
 * chạy SQLite ở dev, DB khác ở production, xem CLAUDE.md), viết 2 bản cho 2 driver rủi ro trôi lệch
 * hơn nhiều so với quét tối đa 500 bản ghi gần nhất trong PHP (§0 — công cụ nội bộ đội content, quy
 * mô nhỏ, không cần tối ưu cho hàng chục nghìn bản ghi).
 */
class FindSimilarSeedKeywordPromptsAction
{
    use AsAction;

    private const FRAMEWORK_KEY = 'topiccluster';

    private const SCAN_LIMIT = 500;

    /** @return array<int, array{uuid: string, label: string, seed_keyword: string, created_at: ?string}> */
    public function handle(string $seedKeyword, ?string $excludeUuid = null): array
    {
        $needle = $this->normalize($seedKeyword);

        if ($needle === '') {
            return [];
        }

        return GeneratedPrompt::query()
            ->where('framework_key', self::FRAMEWORK_KEY)
            ->when($excludeUuid, fn ($q) => $q->where('uuid', '!=', $excludeUuid))
            ->latest('id')
            ->limit(self::SCAN_LIMIT)
            ->get(['uuid', 'label', 'field_values', 'created_at'])
            ->map(fn (GeneratedPrompt $prompt): array => [
                'uuid' => $prompt->uuid,
                'label' => $prompt->label,
                'seed_keyword' => (string) ($prompt->field_values['seed_keyword'] ?? ''),
                'created_at' => $prompt->created_at?->toIso8601String(),
            ])
            ->filter(fn (array $row): bool => $row['seed_keyword'] !== '' && $this->isSimilar($needle, $this->normalize($row['seed_keyword'])))
            ->values()
            ->all();
    }

    /** Trùng khớp hoàn toàn, hoặc 1 bên là chuỗi con của bên kia (VD "bỉm cho bé" ⊂ "bỉm cho bé sơ sinh"). */
    private function isSimilar(string $needle, string $haystack): bool
    {
        if ($haystack === '') {
            return false;
        }

        return $needle === $haystack || str_contains($needle, $haystack) || str_contains($haystack, $needle);
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($value)) ?? '');
    }
}
