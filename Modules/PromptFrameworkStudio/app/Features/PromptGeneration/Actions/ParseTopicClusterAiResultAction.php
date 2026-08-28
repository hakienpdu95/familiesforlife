<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

/**
 * (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — đọc lại kết quả AI (Markdown, người
 * dùng tự dán vào sau khi chạy prompt framework `topiccluster` ở AI ngoài) để tách ra cấu trúc
 * Pillar/Cluster, phục vụ màn hình duyệt theo từng mục (checkbox) trước khi đẩy sang
 * `Modules\ContentOutlines`.
 *
 * CHỦ Ý dùng regex trên 1 khối "máy đọc được" mà chính prompt đã ép AI phải sinh kèm (xem
 * `task_instructions` cuối cùng của framework `topiccluster` trong config) — dạng dòng
 * "PILLAR: <tiêu đề> | <từ khóa>" / "CLUSTER: <tiêu đề> | <từ khóa>" — thay vì cố suy đoán cấu trúc
 * từ heading Markdown tự do (H1/H2 lồng nhau khó đoán chắc chắn, và câu chữ AI trả về ngoài tầm
 * kiểm soát của hệ thống). KHÔNG gọi AI Provider để trích xuất — giữ đúng nguyên tắc "không gọi AI
 * trong app" của cả module `PromptFrameworkStudio` (xem module.json).
 *
 * Giới hạn đã biết: nếu tiêu đề/từ khóa AI sinh ra chứa ký tự "|", dòng đó sẽ bị cắt sai vị trí —
 * chấp nhận được vì đây chỉ là gợi ý để biên tập viên REVIEW lại trước khi đẩy (không tự động tạo
 * bài viết), sai thì sửa tay ở bước duyệt.
 */
class ParseTopicClusterAiResultAction
{
    use AsAction;

    private const PILLAR_PATTERN = '/^[ \t]*PILLAR:\s*(.+?)\s*\|\s*(.+?)\s*$/mu';

    private const CLUSTER_PATTERN = '/^[ \t]*CLUSTER:\s*(.+?)\s*\|\s*(.+?)\s*$/mu';

    /**
     * @return array{
     *     parsed: bool,
     *     pillar: ?array{title: string, target_keyword: string, content_outline_uuid: null},
     *     clusters: array<int, array{title: string, target_keyword: string, content_outline_uuid: null}>,
     * }
     */
    public function handle(string $rawResult): array
    {
        $pillar = $this->firstMatch(self::PILLAR_PATTERN, $rawResult);
        $clusters = $this->allMatches(self::CLUSTER_PATTERN, $rawResult);

        return [
            'parsed' => $pillar !== null && $clusters !== [],
            'pillar' => $pillar,
            'clusters' => $clusters,
        ];
    }

    /** @return ?array{title: string, target_keyword: string, content_outline_uuid: null} */
    private function firstMatch(string $pattern, string $text): ?array
    {
        if (preg_match($pattern, $text, $m) !== 1) {
            return null;
        }

        return $this->toItem($m[1], $m[2]);
    }

    /** @return array<int, array{title: string, target_keyword: string, content_outline_uuid: null}> */
    private function allMatches(string $pattern, string $text): array
    {
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER) === false || $matches === []) {
            return [];
        }

        return array_map(fn (array $m) => $this->toItem($m[1], $m[2]), $matches);
    }

    /** @return array{title: string, target_keyword: string, content_outline_uuid: null} */
    private function toItem(string $title, string $targetKeyword): array
    {
        return [
            'title' => trim($title, " \t\"'*"),
            'target_keyword' => trim($targetKeyword, " \t\"'*"),
            'content_outline_uuid' => null,
        ];
    }
}
