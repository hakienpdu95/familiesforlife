<?php

namespace Modules\Deployment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveySection;

/**
 * Seeds the `readiness_v1` assessment template.
 * Slug matches TraceabilityTemplateSeeder.readiness_template_slug = 'readiness_v1'.
 *
 * 4 domains × 5 Rating(1–5) questions = 20 total.
 * Section.section_code = domain code used by ReadinessScoreService.
 */
class ReadinessV1SurveySeeder extends Seeder
{
    private Survey $survey;
    private int    $fieldSort = 0;

    public function run(): void
    {
        if (Survey::where('slug', 'readiness_v1')->exists()) {
            $this->command?->info('[ReadinessV1] Đã tồn tại, bỏ qua.');
            return;
        }

        $this->survey = Survey::create([
            'organization_id'          => null,
            'title'                    => 'Đánh giá sẵn sàng triển khai (Readiness v1)',
            'slug'                     => 'readiness_v1',
            'assessment_code'          => 'readiness_v1',
            'status'                   => SurveyStatus::Active,
            'allow_multiple_responses' => false,
            'version'                  => 1,
        ]);

        $this->seedDomain('legal',   'Pháp lý & Giấy tờ',    '📋', 1, $this->legalQuestions());
        $this->seedDomain('hr',      'Nhân sự & Năng lực',   '👥', 2, $this->hrQuestions());
        $this->seedDomain('infra',   'Hạ tầng & Công nghệ',  '🖥️', 3, $this->infraQuestions());
        $this->seedDomain('process', 'Quy trình & Dữ liệu',  '⚙️', 4, $this->processQuestions());

        $this->command?->info('[ReadinessV1] Seeded survey "readiness_v1" với 20 câu hỏi.');
    }

    private function seedDomain(string $code, string $title, string $icon, int $sectionSort, array $questions): void
    {
        $section = SurveySection::create([
            'survey_id'       => $this->survey->id,
            'title'           => $title,
            'icon'            => $icon,
            'sort_order'      => $sectionSort,
            'section_code'    => $code,
            'assessment_code' => 'readiness_v1',
            'min_score'       => 5,
            'max_score'       => 25,
        ]);

        foreach ($questions as $i => $label) {
            $this->fieldSort++;
            SurveyField::create([
                'survey_id'  => $this->survey->id,
                'section_id' => $section->id,
                'field_key'  => "{$code}_q" . ($i + 1),
                'label'      => $label,
                'field_type' => FieldType::Rating->value,
                'value_kind' => FieldType::Rating->valueKind()->value,
                'is_required'=> true,
                'sort_order' => $this->fieldSort,
                'rule_min'   => 1,
                'rule_max'   => 5,
            ]);
        }
    }

    private function legalQuestions(): array
    {
        return [
            'Tổ chức có đầy đủ giấy phép đăng ký kinh doanh và các chứng chỉ pháp lý liên quan?',
            'Hồ sơ pháp lý của các thành viên/xã viên đã được thu thập và lưu trữ đầy đủ?',
            'Tổ chức đã nắm rõ các quy định pháp luật liên quan đến hoạt động sản xuất/kinh doanh?',
            'Tổ chức có hệ thống lưu trữ và quản lý tài liệu pháp lý an toàn?',
            'Các hợp đồng và thỏa thuận với đối tác, nhà phân phối đã được chuẩn hóa?',
        ];
    }

    private function hrQuestions(): array
    {
        return [
            'Tổ chức có đủ nhân sự chủ chốt được phân công rõ ràng cho dự án triển khai?',
            'Nhân viên vận hành có khả năng sử dụng thiết bị công nghệ (điện thoại/máy tính)?',
            'Ban lãnh đạo và nhân sự có cam kết tham gia tích cực vào quá trình triển khai?',
            'Tổ chức đã xác định được người phụ trách dữ liệu (data focal point) cho hệ thống?',
            'Nhân sự có thể tham gia các buổi đào tạo mà không ảnh hưởng đến hoạt động thường ngày?',
        ];
    }

    private function infraQuestions(): array
    {
        return [
            'Địa điểm sản xuất/làm việc có kết nối Internet ổn định (3G/4G/WiFi)?',
            'Tổ chức có ít nhất 1 thiết bị di động (smartphone ≥ Android 9 hoặc iOS 13)?',
            'Khu vực sản xuất/nông trại có tín hiệu GPS đủ mạnh để định vị chính xác?',
            'Cơ sở hạ tầng vật chất (kho, khu sản xuất) đã được tổ chức, phân khu rõ ràng?',
            'Tổ chức có nguồn điện ổn định tại các khu vực sản xuất chính?',
        ];
    }

    private function processQuestions(): array
    {
        return [
            'Tổ chức có quy trình sản xuất/vận hành được ghi chép thành tài liệu?',
            'Tổ chức đang theo dõi/ghi chép nhật ký sản xuất theo hình thức nào đó (sổ sách/bảng tính)?',
            'Dữ liệu về thành viên/xã viên và diện tích canh tác/sản lượng đã được thu thập?',
            'Tổ chức có quy trình kiểm soát chất lượng sản phẩm hiện tại?',
            'Lãnh đạo tổ chức sẵn sàng cam kết duy trì cập nhật dữ liệu sau khi hệ thống được triển khai?',
        ];
    }
}
