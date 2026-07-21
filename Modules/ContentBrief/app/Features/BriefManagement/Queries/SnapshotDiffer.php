<?php

namespace Modules\ContentBrief\Features\BriefManagement\Queries;

/**
 * spec/ContentBrief_Technical_Specification.md §0/§4.2/§9 — diff scalar-compare + positional
 * thuần PHP, KHÔNG dùng thư viện diff chuyên dụng (cùng tiền lệ Post_VersionHistory). Giới hạn
 * đã biết: chèn/xoá 1 phần tử giữa mảng list (outline/key_facts) làm lệch vị trí các phần tử
 * phía sau, khiến diff báo "đổi" dù nội dung logic chỉ dịch chuyển — chấp nhận được ở v1.
 */
class SnapshotDiffer
{
    /** @return array<int, array{field: string, old: mixed, new: mixed}> */
    public static function diff(array $old, array $new): array
    {
        $changes = [];
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        foreach ($keys as $key) {
            if ($key === 'schema_version') {
                continue; // field hệ thống, không phải nội dung do người soạn chỉnh sửa
            }

            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            if (is_array($oldValue) && is_array($newValue)) {
                if (self::normalize($oldValue) !== self::normalize($newValue)) {
                    $changes[] = ['field' => $key, 'old' => $oldValue, 'new' => $newValue];
                }

                continue;
            }

            if ($oldValue !== $newValue) {
                $changes[] = ['field' => $key, 'old' => $oldValue, 'new' => $newValue];
            }
        }

        return $changes;
    }

    private static function normalize(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
