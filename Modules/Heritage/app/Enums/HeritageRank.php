<?php

namespace Modules\Heritage\Enums;

/**
 * spec/Heritage_Technical_Specification.md §3.2 — Unranked tồn tại có chủ đích: biên tập viên
 * có thể viết về 1 địa điểm có giá trị văn hóa/lịch sử nhưng chưa (hoặc không) được nhà nước
 * xếp hạng chính thức — không ép mọi bản ghi phải có hồ sơ xếp hạng mới tạo được.
 */
enum HeritageRank: string
{
    case SpecialNational = 'special_national'; // Di tích quốc gia đặc biệt
    case National = 'national';         // Di tích cấp quốc gia
    case Provincial = 'provincial';       // Di tích cấp tỉnh/thành phố
    case Unranked = 'unranked';         // Chưa xếp hạng

    public function label(): string
    {
        return match ($this) {
            self::SpecialNational => 'Di tích quốc gia đặc biệt',
            self::National => 'Di tích cấp quốc gia',
            self::Provincial => 'Di tích cấp tỉnh/thành phố',
            self::Unranked => 'Chưa xếp hạng',
        };
    }
}
