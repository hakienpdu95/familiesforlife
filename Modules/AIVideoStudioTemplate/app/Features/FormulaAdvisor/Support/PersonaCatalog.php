<?php

namespace Modules\AIVideoStudioTemplate\Features\FormulaAdvisor\Support;

/**
 * spec/AIVideoStudioTemplate_Technical_Specification.md §13.1 (v1.27) — 13 chân dung KOC (P1-P13),
 * chuyển thể từ spec/chan-dung-nguoi-review.md (Phần 2), do người dùng cung cấp để lấp khoảng trống
 * đã ghi nhận từ v1.23 ("mã tham khảo — cần tài liệu 'Bộ Chân dung Người Review' để diễn giải đầy
 * đủ"). `ProductFormulaCatalog::PRODUCTS[...]['personas']` lưu chuỗi mã thô (VD "P2 → P5 → P6") —
 * class này CHỈ tra cứu/diễn giải mã, KHÔNG đổi cấu trúc dữ liệu catalog sản phẩm.
 */
class PersonaCatalog
{
    /**
     * Mỗi persona: `name` (nhãn ngắn), `trust_driver` (Điểm tin cậy — vì sao khán giả tin người
     * này), `warning` (nullable — Ranh giới bắt buộc nếu nguồn có ghi ⚠️ cho persona đó).
     */
    public const PERSONAS = [
        'P1' => [
            'name' => 'Mẹ bầu cuối thai kỳ (tuần 30–40), con đầu lòng',
            'trust_driver' => 'Sự đồng cảnh — thiếu kinh nghiệm lại là lợi thế, khán giả tin vì "cũng đang lo y như mình".',
            'warning' => null,
        ],
        'P2' => [
            'name' => 'Mẹ bỉm 0–3 tháng, đang ở cữ ⭐',
            'trust_driver' => 'Tính tức thời — mọi sản phẩm đang được dùng NGAY lúc quay, không diễn được.',
            'warning' => null,
        ],
        'P3' => [
            'name' => 'Mẹ bỉm 6–18 tháng (đã qua giai đoạn sơ sinh)',
            'trust_driver' => 'Độ lùi thời gian — chỉ chân dung này nói được "dùng 8 tháng rồi, đây là chỗ nó hỏng".',
            'warning' => null,
        ],
        'P4' => [
            'name' => 'Mẹ từ 2 con trở lên',
            'trust_driver' => 'Thẩm quyền cao nhất ngành — phân biệt được marketing với nhu cầu thật.',
            'warning' => null,
        ],
        'P5' => [
            'name' => 'Mẹ tối ưu chi phí',
            'trust_driver' => 'Tính toán minh bạch — luôn quy ra chi phí/tháng, chi phí/lần dùng.',
            'warning' => null,
        ],
        'P6' => [
            'name' => 'Mẹ kỹ tính về thành phần',
            'trust_driver' => 'Kiến thức kiểm chứng được — nhưng dễ mất uy tín nhất nếu nói sai 1 thông tin thành phần.',
            'warning' => null,
        ],
        'P7' => [
            'name' => 'Mẹ có con da nhạy cảm / viêm da cơ địa',
            'trust_driver' => 'Trải nghiệm thất bại có thật — tỷ lệ chuyển đổi cao nhất trong 13 chân dung.',
            'warning' => 'Không dùng từ "chữa"/"trị"/"khỏi hẳn" cho sản phẩm không phải thuốc — chỉ mô tả trải nghiệm cá nhân, luôn khuyến nghị khám da liễu nhi.',
        ],
        'P8' => [
            'name' => 'Mẹ hút sữa / đi làm lại',
            'trust_driver' => 'Chuyên môn hẹp và sâu — chân dung duy nhất review máy hút sữa đáng tin, AOV cao nhất.',
            'warning' => null,
        ],
        'P9' => [
            'name' => 'Người có chuyên môn y tế (điều dưỡng nhi/hộ sinh/dược sĩ)',
            'trust_driver' => 'Bằng cấp có thể kiểm chứng — nhưng rủi ro pháp lý cao nhất, phát ngôn bị soi kỹ hơn.',
            'warning' => 'Thiết bị y tế chỉ nói công năng đo/hỗ trợ, không nói tác dụng điều trị; KHÔNG gắn tên với sữa công thức/bình sữa/ti giả; thuốc hạ sốt không làm affiliate; TPCN phải đúng nội dung QC + kèm "không phải là thuốc".',
        ],
        'P10' => [
            'name' => 'Bố bỉm sữa',
            'trust_driver' => 'Góc nhìn lạ + tính giải trí — ngách hiếm, cạnh tranh thấp, "cứu" được nhóm đồ gia dụng.',
            'warning' => null,
        ],
        'P11' => [
            'name' => 'Mẹ sinh mổ',
            'trust_driver' => 'Ngách trống — nhu cầu khác biệt hoàn toàn ở 6 tuần đầu, gần như không kênh nào làm riêng.',
            'warning' => 'Nội dung chăm sóc vết mổ phải dẫn hướng về bác sĩ, không tự đưa phác đồ.',
        ],
        'P12' => [
            'name' => 'Mẹ phục hồi sau sinh / chăm sóc bản thân',
            'trust_driver' => 'Tệp riêng, ít cạnh tranh, sẵn sàng chi — 3-12 tháng sau sinh.',
            'warning' => 'Tránh mọi nội dung tạo áp lực giảm cân/so sánh vóc dáng — nhấn vào "phục hồi và thoải mái", không phải "lấy lại dáng".',
        ],
        'P13' => [
            'name' => 'Mẹ ở căn hộ nhỏ / tối ưu không gian',
            'trust_driver' => 'Bài toán là CHỨA đồ, không phải mua đồ — biến nhóm đồ gia dụng lặt vặt thành nội dung có giá trị.',
            'warning' => null,
        ],
    ];

    public static function find(string $code): ?array
    {
        return self::PERSONAS[$code] ?? null;
    }

    /**
     * Tách chuỗi mã thô (VD "P2 → P5 → P6", "P2 + P9", "P5 / P6 / P7") thành mảng mã theo ĐÚNG thứ
     * tự xuất hiện — không giả định 1 ký tự phân cách cố định vì `ProductFormulaCatalog::PRODUCTS`
     * dùng lẫn lộn "→"/"+"/"/" tuỳ entry (nguyên văn theo spec/ma-tran-cong-thuc-kich-ban.md).
     *
     * @return string[]
     */
    public static function parseCodes(string $raw): array
    {
        preg_match_all('/P\d+/', $raw, $matches);

        return $matches[0];
    }
}
