<?php

namespace Modules\Deployment\Services;

class GapAnalysisService
{
    private const RECOMMENDATIONS = [
        // readiness_v1 domains
        'legal' => [
            'title'   => 'Hoàn thiện hồ sơ pháp lý',
            'actions' => [
                'Thu thập đầy đủ giấy phép kinh doanh và giấy tờ pháp lý còn thiếu.',
                'Lập danh sách kiểm tra (checklist) các loại giấy tờ bắt buộc cho từng xã viên.',
                'Sắp xếp hệ thống lưu trữ tài liệu pháp lý theo thứ tự và dễ tra cứu.',
            ],
        ],
        'hr' => [
            'title'   => 'Nâng cao năng lực nhân sự',
            'actions' => [
                'Xác định và bổ nhiệm người phụ trách dữ liệu (data focal point) chính thức.',
                'Tổ chức buổi giới thiệu hệ thống cho ban lãnh đạo trước khi triển khai.',
                'Đảm bảo ít nhất 2 nhân sự có thể vận hành hệ thống trên thiết bị di động.',
            ],
        ],
        'infra' => [
            'title'   => 'Cải thiện hạ tầng kỹ thuật',
            'actions' => [
                'Kiểm tra và nâng cấp kết nối Internet tại địa điểm sản xuất chính.',
                'Chuẩn bị ít nhất 1 smartphone (Android 9+ hoặc iOS 13+) được dành riêng cho hệ thống.',
                'Khảo sát và đánh dấu các khu vực có tín hiệu GPS yếu để có phương án xử lý.',
            ],
        ],
        'process' => [
            'title'   => 'Chuẩn hóa quy trình vận hành',
            'actions' => [
                'Ghi chép và tài liệu hóa quy trình sản xuất/vận hành hiện tại.',
                'Chuẩn hóa biểu mẫu thu thập dữ liệu sản xuất trước khi chuyển sang hệ thống số.',
                'Thiết lập lịch cập nhật dữ liệu định kỳ (tuần/tháng) và phân công người thực hiện.',
            ],
        ],

        // TXNG readiness domains
        'infrastructure' => [
            'title'   => 'Nâng cấp hạ tầng kỹ thuật',
            'actions' => [
                'Trang bị smartphone (Android 9+) hoặc laptop để nhập liệu và chụp ảnh thực địa.',
                'Kiểm tra và cải thiện kết nối Internet tại văn phòng HTX và vùng sản xuất chính.',
                'Cài đặt ứng dụng CheckVN và thử nghiệm quét mã QR tại thực địa.',
            ],
        ],
        'personnel' => [
            'title'   => 'Phát triển năng lực nhân sự',
            'actions' => [
                'Bổ nhiệm 1–2 nhân sự chuyên trách TXNG và đào tạo sử dụng hệ thống.',
                'Tổ chức buổi đào tạo Excel cơ bản và chụp ảnh thực địa đạt chuẩn.',
                'Lập kế hoạch phân công nhập liệu theo ca/tuần để dữ liệu được cập nhật đều đặn.',
            ],
        ],
        'data_readiness' => [
            'title'   => 'Chuẩn bị và số hóa dữ liệu',
            'actions' => [
                'Lập nhật ký canh tác/sản xuất cho vụ hiện tại trước khi triển khai hệ thống.',
                'Chụp ảnh thực địa khu vực trồng: lô, cây, vùng thu hoạch với đủ ánh sáng và góc chụp.',
                'Đo GPS toàn bộ vùng trồng và lưu lại tọa độ ranh giới lô thửa.',
                'Hoàn thiện hồ sơ pháp lý: ĐKKD, MST, và các giấy chứng nhận OCOP/ATTP nếu có.',
            ],
        ],
    ];

    private const DOMAIN_LABELS = [
        // readiness_v1
        'legal'   => 'Pháp lý & Giấy tờ',
        'hr'      => 'Nhân sự & Năng lực',
        'infra'   => 'Hạ tầng & Công nghệ',
        'process' => 'Quy trình & Dữ liệu',
        // TXNG readiness
        'infrastructure'  => 'Hạ tầng kỹ thuật',
        'personnel'       => 'Nhân sự & năng lực',
        'data_readiness'  => 'Dữ liệu hiện có',
    ];

    /**
     * @param  array<string, array{score: int, ...}> $domainScores
     * @return array<array{domain: string, label: string, score: int, priority: string, title: string, actions: string[]}>
     */
    public function analyze(array $domainScores): array
    {
        if (empty($domainScores)) {
            return [];
        }

        asort($domainScores);

        $gaps = [];
        foreach ($domainScores as $code => $data) {
            $score    = $data['score'];
            $priority = $this->priority($score);

            if ($priority === null) {
                continue;
            }

            $rec = self::RECOMMENDATIONS[$code] ?? [
                'title'   => "Cải thiện lĩnh vực {$code}",
                'actions' => ['Xem xét và cải thiện các chỉ tiêu trong lĩnh vực này.'],
            ];

            $gaps[] = [
                'domain'   => $code,
                'label'    => self::DOMAIN_LABELS[$code] ?? $code,
                'score'    => $score,
                'priority' => $priority,
                'title'    => $rec['title'],
                'actions'  => $rec['actions'],
            ];
        }

        return array_slice($gaps, 0, 3);
    }

    private function priority(int $score): ?string
    {
        return match (true) {
            $score < 40  => 'high',
            $score < 60  => 'medium',
            $score < 80  => 'low',
            default      => null,
        };
    }
}
