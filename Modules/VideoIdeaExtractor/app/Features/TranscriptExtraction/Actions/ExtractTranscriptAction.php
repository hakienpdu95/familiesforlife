<?php

namespace Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\VideoIdeaExtractor\Features\TranscriptExtraction\Data\ChapterData;

/**
 * Layer 1 cho transcript dán tay — tương đương ExtractRawContentAction bên CoreIdeaExtractor
 * nhưng KHÔNG có bước fetch/parse HTML nào (transcript là input dạng text thuần người dùng tự dán,
 * không có khái niệm "bị chặn"/"lỗi mạng"/selector CSS).
 */
class ExtractTranscriptAction
{
    use AsAction;

    /**
     * Dòng CHỈ chứa timestamp (không có gì khác) — format phổ biến khi copy từ panel "Show
     * transcript" của YouTube: mỗi đoạn phụ đề là 2 dòng riêng biệt (dòng timestamp, rồi dòng chữ
     * ngay sau) — timestamp đứng 1 mình không mang thông tin chương/mục, cần loại khỏi
     * word_count/transcript hiển thị để không đếm nhầm thành "từ".
     */
    private const PURE_TIMESTAMP_LINE = '/^\s*(?:\d{1,2}:)?\d{1,2}:\d{2}\s*$/u';

    /**
     * Dòng có timestamp KÈM chữ ngay trên CÙNG 1 dòng — format thường gặp khi người dùng dán tay
     * danh sách chương từ mô tả video (VD "12:34 Giới thiệu sản phẩm"), khác hẳn dòng timestamp
     * đơn lẻ ở trên (PURE_TIMESTAMP_LINE không khớp vì có chữ theo sau) — dùng để nhận diện
     * `chapters[]`, tương đương `headings` bên CoreIdeaExtractor.
     */
    private const CHAPTER_LINE = '/^\s*((?:\d{1,2}:)?\d{1,2}:\d{2})\s+(\S.*)$/u';

    /**
     * @return array{
     *     chapters: ChapterData[],
     *     transcript: string,
     *     word_count: int,
     * }
     */
    public function handle(string $rawTranscript): array
    {
        $normalized = $this->normalizeLineEndings($rawTranscript);
        $lines      = explode("\n", $normalized);

        $chapters       = [];
        $contentLines   = [];

        foreach ($lines as $line) {
            if (preg_match(self::CHAPTER_LINE, $line, $matches)) {
                $chapters[]     = new ChapterData(time: $matches[1], text: trim($matches[2]));
                $contentLines[] = trim($matches[2]);

                continue;
            }

            if (preg_match(self::PURE_TIMESTAMP_LINE, $line)) {
                // Dòng timestamp đơn lẻ (định dạng caption dày đặc) — bỏ khỏi transcript hiển thị,
                // không mang thông tin ngoài mốc thời gian.
                continue;
            }

            $contentLines[] = $line;
        }

        $transcript = $this->collapseBlankLines(implode("\n", $contentLines));

        return [
            'chapters'   => $chapters,
            'transcript' => $transcript,
            'word_count' => $this->countWords($transcript),
        ];
    }

    private function normalizeLineEndings(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }

    /** Gộp từ 3 dòng trống liên tiếp trở lên về đúng 2 (giữ ranh giới đoạn văn nhưng không để trang trống dài do các dòng timestamp đã bị loại). */
    private function collapseBlankLines(string $text): string
    {
        return trim(preg_replace('/\n{3,}/', "\n\n", $text) ?? $text);
    }

    /** Đếm từ trên transcript đã làm sạch — ngôn ngữ không phân từ bằng khoảng trắng thì đây là ước lượng thô, cùng quy ước với CoreIdeaExtractor (§6.1.4). */
    private function countWords(string $text): int
    {
        if (trim($text) === '') {
            return 0;
        }

        return count(array_filter(preg_split('/\s+/u', trim($text)) ?: []));
    }
}
