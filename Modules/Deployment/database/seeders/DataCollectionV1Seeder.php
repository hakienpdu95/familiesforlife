<?php

namespace Modules\Deployment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveySection;

/**
 * Seeds the `data_collection_v1` survey template.
 *
 * Surveyor dùng form này để thu thập dữ liệu thực địa từ HTX:
 *   Section A (org_profile)     — xác nhận thông tin tổ chức → sync vào organizations
 *   Section B (production_info) — vùng sản xuất, GPS trung tâm
 *   Section C (products)        — danh mục sản phẩm
 *   Section D (history)         — có lịch sử hoạt động cần nhập không
 *
 * File upload (ĐKKD, ảnh, Excel mẫu) đi qua Organization MediaLibrary — không qua survey field
 * vì FieldType không hỗ trợ file.
 */
class DataCollectionV1Seeder extends Seeder
{
    private Survey $survey;
    private int    $sort = 0;

    public function run(): void
    {
        if (Survey::where('slug', 'data_collection_v1')->exists()) {
            $this->command?->info('[DataCollectionV1] Đã tồn tại, bỏ qua.');
            return;
        }

        $this->survey = Survey::create([
            'organization_id'          => null,
            'title'                    => 'Thu thập dữ liệu triển khai TXNG v1',
            'slug'                     => 'data_collection_v1',
            'assessment_code'          => null,
            'status'                   => SurveyStatus::Active,
            'allow_multiple_responses' => false,
            'version'                  => 1,
        ]);

        $this->seedSectionA();
        $this->seedSectionB();
        $this->seedSectionC();
        $this->seedSectionD();

        $this->command?->info('[DataCollectionV1] Seeded survey "data_collection_v1" với 4 sections, ' . $this->sort . ' fields.');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Section A — Thông tin tổ chức
    // ─────────────────────────────────────────────────────────────────────
    private function seedSectionA(): void
    {
        $section = $this->makeSection('org_profile', 'Thông tin tổ chức', '🏢', 1);

        $this->field($section, 'org_name',          'Tên tổ chức / HTX',                      FieldType::Text,     true,  'Ví dụ: Hợp tác xã Hoa Sơn');
        $this->field($section, 'tax_code',           'Mã số thuế / Mã HTX',                    FieldType::Text,     true,  'Ví dụ: 5700123456');
        $this->field($section, 'province',           'Tỉnh / Thành phố',                        FieldType::Text,     true,  'Ví dụ: Quảng Ninh');
        $this->field($section, 'full_address',       'Địa chỉ đầy đủ',                          FieldType::Textarea, false, 'Số nhà, thôn, xã, huyện, tỉnh');
        $this->field($section, 'representative',     'Họ tên người đại diện',                   FieldType::Text,     true,  'Ví dụ: Nguyễn Văn A');
        $this->field($section, 'rep_phone',          'Số điện thoại người đại diện',            FieldType::Text,     true,  'Ví dụ: 0912 345 678');
        $this->field($section, 'main_product_type',  'Loại sản xuất chính',                     FieldType::Text,     false, 'Ví dụ: chè, cà phê, gà, cá, lúa...');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Section B — Thông tin vùng sản xuất
    // ─────────────────────────────────────────────────────────────────────
    private function seedSectionB(): void
    {
        $section = $this->makeSection('production_info', 'Thông tin vùng sản xuất', '🌿', 2);

        $this->field($section, 'site_name',      'Tên vùng sản xuất',                     FieldType::Text,     true,  'Ví dụ: Vùng chè Hoa Sơn');
        $this->field($section, 'total_area_sqm', 'Tổng diện tích (m²)',                   FieldType::Number,   false, 'Diện tích toàn bộ vùng sản xuất');
        $this->field($section, 'area_count',     'Số khu / chuồng / ao',                  FieldType::Number,   true,  'Số đơn vị cấp 1 trong vùng');
        $this->field($section, 'lot_count',      'Tổng số lô / ô / thửa',                FieldType::Number,   true,  'Tổng số đơn vị cấp 2');
        $this->field($section, 'item_count',     'Tổng số cây / con / đơn vị',           FieldType::Number,   false, 'Tổng số đơn vị nhỏ nhất');
        $this->field($section, 'gps_lat',        'GPS trung tâm — Vĩ độ (Latitude)',     FieldType::Text,     true,  'Nhấn nút bên cạnh để lấy vị trí tự động');
        $this->field($section, 'gps_lng',        'GPS trung tâm — Kinh độ (Longitude)',  FieldType::Text,     true,  'Nhấn nút bên cạnh để lấy vị trí tự động');
        $this->field($section, 'area_notes',     'Mô tả sơ đồ vùng sản xuất',           FieldType::Textarea, false, 'Mô tả bố cục, ranh giới, đặc điểm vùng');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Section C — Danh mục sản phẩm
    // ─────────────────────────────────────────────────────────────────────
    private function seedSectionC(): void
    {
        $section = $this->makeSection('products', 'Danh mục sản phẩm', '📦', 3);

        $this->field($section, 'product_count', 'Số loại sản phẩm',                      FieldType::Number,   false, 'Tổng số loại sản phẩm HTX đang sản xuất');
        $this->field($section, 'product_list',  'Danh sách sản phẩm',                    FieldType::Textarea, true,
            "Mỗi dòng 1 sản phẩm theo định dạng: Tên sản phẩm | Mã SP | Đơn vị\nVí dụ:\nTrà hoa vàng | TH001 | Hộp 100g\nMật ong rừng | MO001 | Lọ 500ml"
        );
        $this->field($section, 'main_product',  'Sản phẩm chủ lực',                      FieldType::Text,     true,  'Sản phẩm mang lại doanh thu chính');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Section D — Lịch sử hoạt động
    // ─────────────────────────────────────────────────────────────────────
    private function seedSectionD(): void
    {
        $section = $this->makeSection('history', 'Lịch sử hoạt động', '📅', 4);

        $this->field($section, 'has_history',    'Có lịch sử canh tác cần nhập không?',  FieldType::Boolean,  true,  'Nếu có, Surveyor sẽ upload file Excel mẫu');
        $this->field($section, 'history_years',  'Số năm có lịch sử (nếu có)',           FieldType::Number,   false, 'Số năm hoạt động có ghi chép nhật ký');
        $this->field($section, 'history_notes',  'Ghi chú về lịch sử hoạt động',         FieldType::Textarea, false, 'Loại hình ghi chép, ai lưu trữ, định dạng gì...');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────
    private function makeSection(string $code, string $title, string $icon, int $sectionSort): SurveySection
    {
        return SurveySection::create([
            'survey_id'    => $this->survey->id,
            'title'        => $title,
            'icon'         => $icon,
            'sort_order'   => $sectionSort,
            'section_code' => $code,
        ]);
    }

    private function field(
        SurveySection $section,
        string        $key,
        string        $label,
        FieldType     $type,
        bool          $required,
        string        $placeholder = '',
    ): void {
        $this->sort++;
        SurveyField::create([
            'survey_id'   => $this->survey->id,
            'section_id'  => $section->id,
            'field_key'   => $key,
            'label'       => $label,
            'field_type'  => $type->value,
            'value_kind'  => $type->valueKind()->value,
            'is_required' => $required,
            'sort_order'  => $this->sort,
            'placeholder' => $placeholder,
        ]);
    }
}
