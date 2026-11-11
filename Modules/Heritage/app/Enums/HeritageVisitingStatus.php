<?php

namespace Modules\Heritage\Enums;

/**
 * spec/Heritage_Technical_Specification.md §3.3 — field biên tập nhẹ, không phải hệ thống theo
 * dõi thời gian thực; biên tập viên tự cập nhật khi có thông tin, không có tích hợp API ngoài.
 */
enum HeritageVisitingStatus: string
{
    case Open = 'open';       // Đang mở cửa đón khách
    case Restoring = 'restoring';  // Đang trùng tu/hạn chế tham quan
    case Closed = 'closed';     // Tạm đóng cửa
    case Unknown = 'unknown';    // Chưa xác nhận — mặc định khi tạo mới

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Đang mở cửa'),
            self::Restoring => __('Đang trùng tu'),
            self::Closed => __('Tạm đóng cửa'),
            self::Unknown => __('Chưa xác nhận'),
        };
    }
}
