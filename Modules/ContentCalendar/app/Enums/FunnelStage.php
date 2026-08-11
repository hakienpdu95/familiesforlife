<?php

namespace Modules\ContentCalendar\Enums;

/**
 * (2026-08-11, đối chiếu 3 nguồn TOFU/MOFU/BOFU — spec/giadinh.md + IdeaDigital/Funnel.io) — giai
 * đoạn hành trình ĐỘC GIẢ (mindset lúc đọc), KHÔNG phải trạng thái sản xuất (`CalendarEntryStatus`)
 * và KHÔNG phải kiến trúc chủ đề pillar/cluster (`Modules\ContentOutlines`'s `content_role`) — 3
 * khái niệm trực giao, có thể kết hợp tự do (1 bài pillar vẫn có thể ở giai đoạn Nóng).
 *
 * Đặt tên theo yêu cầu người dùng "Lạnh → Ấm → Nóng" thay vì dịch thẳng TOFU/MOFU/BOFU — cùng tinh
 * thần CalendarEntryStatus dùng tiếng Việt cho enum hiển thị trực tiếp trên UI biên tập viên.
 * `value` string ngắn (cold/warm/hot) để nhất quán quy ước snake/kebab-free của các enum khác
 * trong module (`CalendarEntryStatus`, `CalendarEntryOrigin`).
 */
enum FunnelStage: string
{
    case Cold = 'cold'; // Lạnh — mới biết vấn đề, CHƯA sẵn sàng nghe về thương hiệu/sản phẩm
    case Warm = 'warm'; // Ấm — đã hiểu vấn đề, đang so sánh giải pháp
    case Hot = 'hot';  // Nóng — đã nghiên cứu xong, cần bằng chứng cụ thể để quyết định

    public function label(): string
    {
        return match ($this) {
            self::Cold => 'Lạnh',
            self::Warm => 'Ấm',
            self::Hot => 'Nóng',
        };
    }

    /**
     * (2026-08-11, đối chiếu thêm aioseo.com/tofu-mofu-bofu) — mô tả hiện NGAY dưới field chọn
     * giai đoạn (không dùng `title` attribute — TomSelect render dropdown riêng, tooltip gắn trên
     * `<option>` gốc không hiện ra được), nêu mindset độc giả + loại nội dung nên viết để biên
     * tập viên chọn xong hiểu ngay "vậy giai đoạn này viết kiểu bài gì".
     */
    public function hint(): string
    {
        return match ($this) {
            self::Cold => 'Mới biết vấn đề, CHƯA sẵn sàng nghe về sản phẩm/dịch vụ. Hợp: bài định nghĩa, giải đáp "là gì/vì sao", hướng dẫn tổng quan — không nhắc thương hiệu.',
            self::Warm => 'Đã hiểu vấn đề, đang so sánh giải pháp. Hợp: bài so sánh, đánh giá, hướng dẫn từng bước có thể nhắc tới sản phẩm/dịch vụ như 1 lựa chọn.',
            self::Hot => 'Đã nghiên cứu xong, cần bằng chứng cụ thể để quyết định. Hợp: case study, so sánh chi tiết, đánh giá thật, FAQ gỡ lăn tăn cuối cùng.',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Cold => 'badge-info',
            self::Warm => 'badge-warning',
            self::Hot => 'badge-error',
        };
    }

    /** @return self[] Thứ tự hành trình — dùng cho UI chọn/hiển thị theo đúng trình tự Lạnh→Ấm→Nóng. */
    public static function ordered(): array
    {
        return [self::Cold, self::Warm, self::Hot];
    }
}
