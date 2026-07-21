<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions\Concerns;

use Modules\ContentBrief\Models\ContentBrief;

/**
 * spec/ContentBrief_Technical_Specification.md §3.5 — dùng chung giữa mọi Action tạo version
 * mới (Create/Update/Reject/Restore) để không lặp lại logic canonical hash + version_number.
 */
trait GeneratesBriefVersions
{
    /**
     * Đệ quy ksort mọi mảng con — đảm bảo json_encode ra cùng 1 chuỗi byte cho cùng 1 nội dung
     * logic, bất kể thứ tự key khi build mảng PHP. An toàn với mảng dạng list (outline,
     * key_facts...): ksort trên key số nguyên tuần tự 0,1,2... không đổi thứ tự phần tử.
     */
    private static function canonicalize(array $data): array
    {
        ksort($data);

        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = self::canonicalize($value);
            }
        }

        return $data;
    }

    private function hashSnapshot(array $snapshot): string
    {
        return hash('sha256', json_encode(self::canonicalize($snapshot), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function nextVersionNumber(ContentBrief $brief): int
    {
        return ($brief->versions()->max('version_number') ?? 0) + 1;
    }
}
