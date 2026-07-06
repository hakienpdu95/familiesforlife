<?php

namespace Modules\Survey\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Models\SurveyFieldOption;
use Modules\Survey\Models\SurveySection;

class AiReadinessSurveySeeder extends Seeder
{
    private Survey $survey;

    public function run(): void
    {
        if (Survey::where('slug', 'ai-readiness-workflow')->exists()) {
            $this->command->info('Survey "AI Readiness & Workflow" đã tồn tại, bỏ qua.');
            return;
        }

        $this->survey = Survey::create([
            'title'           => 'Bộ Khảo Sát AI Readiness & Workflow',
            'slug'            => 'ai-readiness-workflow',
            'assessment_code' => 'ai_workflow_v1',
            'status'          => SurveyStatus::Active,
            'version'         => 1,
        ]);

        $this->seedSection1ThongTinDoanhNghiep();
        $this->seedSection2WorkflowVanHanh();
        $this->seedSection3SalesKhachHang();
        $this->seedSection4NhanSuDaoTao();
        $this->seedSection5DuLieuHeThong();
        $this->seedSection6AiReadiness();
        $this->seedSection7DanhGiaTongQuan();
        $this->seedSection8ThongTinBoSung();

        $this->command->info('Seeded survey "Bộ Khảo Sát AI Readiness & Workflow" thành công.');
    }

    // ── Section 1: Thông tin doanh nghiệp ─────────────────────────────────────

    private function seedSection1ThongTinDoanhNghiep(): void
    {
        $section = $this->createSection('Thông tin doanh nghiệp', '🏢', 1);

        // 1.1 Tên doanh nghiệp — Text, required
        $this->addField($section, 1, 'company_name', '1.1 Tên doanh nghiệp', FieldType::Text, true, 'Nhập tên doanh nghiệp');

        // 1.2 Ngành nghề — Select, required
        $field = $this->addField($section, 2, 'industry', '1.2 Ngành nghề', FieldType::Select, true);
        $this->addOptions($field, [
            ['agriculture_food',   'Nông nghiệp / Thực phẩm'],
            ['retail_ecommerce',   'Bán lẻ / TMĐT'],
            ['service',            'Dịch vụ'],
            ['manufacturing',      'Sản xuất'],
            ['education_training', 'Giáo dục / Đào tạo'],
            ['insurance_finance',  'Bảo hiểm / Tài chính'],
            ['other',              'Khác', true],
        ]);

        // 1.3 Số năm hoạt động — Select
        $field = $this->addField($section, 3, 'years_operating', '1.3 Số năm hoạt động', FieldType::Select);
        $this->addOptions($field, [
            ['under_1y', 'Dưới 1 năm'],
            ['1_3y',     '1 - 3 năm'],
            ['3_5y',     '3 - 5 năm'],
            ['over_5y',  'Trên 5 năm'],
        ]);

        // 1.4 Số lượng nhân sự — Select, required
        $field = $this->addField($section, 4, 'employee_count', '1.4 Số lượng nhân sự hiện tại', FieldType::Select, true);
        $this->addOptions($field, [
            ['under_10', 'Dưới 10'],
            ['10_50',    '10 - 50'],
            ['51_200',   '51 - 200'],
            ['over_200', 'Trên 200'],
        ]);

        // 1.5 Quy mô doanh thu — Select
        $field = $this->addField($section, 5, 'revenue_scale', '1.5 Quy mô doanh thu', FieldType::Select);
        $this->addOptions($field, [
            ['under_1b', 'Dưới 1 tỷ/năm'],
            ['1_5b',     '1 - 5 tỷ/năm'],
            ['5_20b',    '5 - 20 tỷ/năm'],
            ['over_20b', 'Trên 20 tỷ/năm'],
        ]);

        // 1.6 Mô hình vận hành — Radio
        $field = $this->addField($section, 6, 'business_model', '1.6 Mô hình vận hành', FieldType::Radio);
        $this->addOptions($field, [
            ['offline', 'Offline'],
            ['online',  'Online'],
            ['hybrid',  'Kết hợp'],
        ]);

        // 1.7 Chi nhánh / Phòng ban — Text
        $this->addField($section, 7, 'branch_department', '1.7 Chi nhánh / Phòng ban', FieldType::Text, false, 'Nhập chi nhánh hoặc phòng ban');

        // 1.8 Người phụ trách khảo sát — Text, required
        $this->addField($section, 8, 'contact_person', '1.8 Người phụ trách khảo sát', FieldType::Text, true, 'Họ tên người phụ trách');

        // 1.9 Chức vụ — Text
        $this->addField($section, 9, 'contact_position', '1.9 Chức vụ', FieldType::Text, false, 'Nhập chức vụ');

        // 1.10 Số điện thoại — Text, required
        $this->addField($section, 10, 'contact_phone', '1.10 Số điện thoại', FieldType::Text, true, 'Nhập số điện thoại');

        // 1.11 Email — Text, required
        $this->addField($section, 11, 'contact_email', '1.11 Email', FieldType::Text, true, 'Nhập email');
    }

    // ── Section 2: Workflow & Vận hành ────────────────────────────────────────

    private function seedSection2WorkflowVanHanh(): void
    {
        $section = $this->createSection('Workflow & Vận hành', '⚙️', 2);

        // 2.1 Doanh nghiệp đang vận hành theo? — Radio
        $field = $this->addField($section, 1, 'workflow_mode', '2.1 Doanh nghiệp đang vận hành theo?', FieldType::Radio);
        $this->addOptions($field, [
            ['standard_process', 'Quy trình chuẩn'],
            ['by_experience',    'Theo kinh nghiệm'],
            ['by_direction',     'Theo chỉ đạo trực tiếp'],
            ['unclear',          'Chưa rõ quy trình'],
        ]);

        // 2.2 Phòng ban hiện có — Checkbox
        $field = $this->addField($section, 2, 'departments', '2.2 Phòng ban hiện có', FieldType::Checkbox);
        $this->addOptions($field, [
            ['sales',       'Sales'],
            ['marketing',   'Marketing'],
            ['cskh',        'CSKH'],
            ['accounting',  'Kế toán'],
            ['hr',          'Nhân sự'],
            ['warehouse',   'Kho vận'],
            ['production',  'Sản xuất'],
            ['it',          'IT'],
            ['other',       'Khác', true],
        ]);

        // 2.3 Công cụ quản lý hiện tại — Checkbox
        $field = $this->addField($section, 3, 'management_tools', '2.3 Công cụ quản lý hiện tại', FieldType::Checkbox);
        $this->addOptions($field, [
            ['excel',             'Excel'],
            ['google_sheet',      'Google Sheet'],
            ['zalo',              'Zalo'],
            ['paper',             'Sổ sách giấy'],
            ['crm',               'CRM'],
            ['erp',               'ERP'],
            ['internal_software', 'Phần mềm nội bộ'],
        ]);

        // 2.4 Các vấn đề lớn nhất hiện tại — Checkbox
        $field = $this->addField($section, 4, 'current_problems', '2.4 Các vấn đề lớn nhất hiện tại', FieldType::Checkbox);
        $this->addOptions($field, [
            ['staff_forget',    'Nhân sự quên việc'],
            ['hard_to_control', 'Khó kiểm soát tiến độ'],
            ['data_scattered',  'Dữ liệu phân tán'],
            ['hard_to_train',   'Khó đào tạo nhân sự'],
            ['repeated_errors', 'Sai sót lặp lại'],
            ['sales_no_follow', 'Sales không follow'],
            ['ceo_no_control',  'CEO khó kiểm soát'],
            ['no_sop',          'Không có SOP'],
        ]);

        // 2.5 Công đoạn mất nhiều thời gian nhất — Textarea
        $this->addField($section, 5, 'time_consuming_steps', '2.5 Công đoạn mất nhiều thời gian nhất', FieldType::Textarea, false, 'Mô tả chi tiết...');

        // 2.6 Bộ phận thường xuyên xảy ra lỗi nhất — Textarea
        $this->addField($section, 6, 'error_prone_dept', '2.6 Bộ phận thường xuyên xảy ra lỗi nhất?', FieldType::Textarea, false, 'Mô tả chi tiết...');

        // 2.7 Ảnh hưởng khi nhân sự nghỉ việc — Radio
        $field = $this->addField($section, 7, 'staff_leave_impact', '2.7 Ảnh hưởng khi nhân sự nghỉ việc', FieldType::Radio);
        $this->addOptions($field, [
            ['very_high',  'Rất lớn'],
            ['medium',     'Trung bình'],
            ['low',        'Ít'],
            ['negligible', 'Không đáng kể'],
        ]);

        // 2.8 Hiện có hệ thống/tài liệu nào? — Checkbox
        $field = $this->addField($section, 8, 'existing_systems', '2.8 Hiện có hệ thống/tài liệu nào?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['sop',            'SOP'],
            ['kpi',            'KPI'],
            ['workflow',       'Workflow'],
            ['dashboard',      'Dashboard'],
            ['crm',            'CRM'],
            ['erp',            'ERP'],
            ['access_control', 'Phân quyền'],
            ['approval',       'Phê duyệt'],
        ]);
    }

    // ── Section 3: Sales & Khách hàng ─────────────────────────────────────────

    private function seedSection3SalesKhachHang(): void
    {
        $section = $this->createSection('Sales & Khách hàng', '📈', 3);

        // 3.1 Nguồn khách hàng hiện tại — Checkbox
        $field = $this->addField($section, 1, 'customer_sources', '3.1 Nguồn khách hàng hiện tại', FieldType::Checkbox);
        $this->addOptions($field, [
            ['facebook',    'Facebook'],
            ['tiktok',      'TikTok'],
            ['website',     'Website'],
            ['field_sales', 'Sale thị trường'],
            ['referral',    'Giới thiệu'],
            ['agent',       'Đại lý'],
            ['ecommerce',   'Sàn TMĐT'],
            ['other',       'Khác', true],
        ]);

        // 3.2 Lead hiện quản lý bằng gì? — Checkbox
        $field = $this->addField($section, 2, 'lead_management_tool', '3.2 Lead hiện quản lý bằng gì?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['excel',        'Excel'],
            ['google_sheet', 'Google Sheet'],
            ['crm',          'CRM'],
            ['zalo',         'Zalo'],
            ['none',         'Không quản lý'],
        ]);

        // 3.3 Tình trạng gặp phải — Checkbox
        $field = $this->addField($section, 3, 'sales_problems', '3.3 Tình trạng gặp phải', FieldType::Checkbox);
        $this->addOptions($field, [
            ['lost_customers',       'Mất khách hàng'],
            ['no_follow_reminder',   'Không nhớ follow'],
            ['no_assignment_track',  'Không biết sale nào chăm khách'],
            ['no_sale_metrics',      'Không đo hiệu quả sale'],
            ['data_loss_on_resign',  'Sale nghỉ mất data'],
            ['no_customer_history',  'Không có lịch sử khách hàng'],
        ]);

        // 3.4 Quy trình sale hiện tại — Textarea
        $this->addField($section, 4, 'sales_process', '3.4 Quy trình sale hiện tại', FieldType::Textarea, false, 'Mô tả các bước trong quy trình sale...');

        // 3.5 Bao nhiêu % lead bị bỏ quên? — Select
        $field = $this->addField($section, 5, 'lead_forget_rate', '3.5 Bao nhiêu % lead bị bỏ quên?', FieldType::Select);
        $this->addOptions($field, [
            ['under_10pct', 'Dưới 10%'],
            ['10_30pct',    '10 - 30%'],
            ['30_50pct',    '30 - 50%'],
            ['over_50pct',  'Trên 50%'],
        ]);

        // 3.6 CEO có xem realtime các thông tin này không? — Checkbox
        $field = $this->addField($section, 6, 'ceo_realtime_access', '3.6 CEO có xem realtime các thông tin này không?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['lead',            'Lead'],
            ['revenue',         'Doanh thu'],
            ['conversion_rate', 'Tỷ lệ chốt'],
            ['sale_kpi',        'KPI sale'],
            ['performance',     'Hiệu suất'],
        ]);

        // 3.7 Đang dùng CRM chưa? — Radio
        $field = $this->addField($section, 7, 'using_crm', '3.7 Đang dùng CRM chưa?', FieldType::Radio);
        $this->addOptions($field, [
            ['yes', 'Có'],
            ['no',  'Không'],
        ]);

        // 3.7b Nếu có, đang dùng CRM nào? — Text (sub-question)
        $this->addField($section, 8, 'crm_name', '3.7b Nếu có, đang dùng CRM nào?', FieldType::Text, false, 'Nhập tên CRM đang sử dụng...');

        // 3.7c Vì sao không hiệu quả hoặc chưa dùng? — Textarea (sub-question)
        $this->addField($section, 9, 'crm_issues', '3.7c Vì sao không hiệu quả hoặc chưa dùng?', FieldType::Textarea, false, 'Vì sao không hiệu quả hoặc chưa dùng?');
    }

    // ── Section 4: Nhân sự & Đào tạo ─────────────────────────────────────────

    private function seedSection4NhanSuDaoTao(): void
    {
        $section = $this->createSection('Nhân sự & Đào tạo', '👥', 4);

        // 4.1 Checklist onboarding — Radio
        $field = $this->addField($section, 1, 'onboarding_checklist', '4.1 Doanh nghiệp có checklist onboarding không?', FieldType::Radio);
        $this->addOptions($field, [
            ['full',    'Có đầy đủ'],
            ['partial', 'Có nhưng chưa chuẩn'],
            ['none',    'Chưa có'],
        ]);

        // 4.2 Hệ thống KPI / phân công công việc — Checkbox
        $field = $this->addField($section, 2, 'kpi_system', '4.2 Hệ thống KPI / phân công công việc?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['has_kpi',             'Có KPI'],
            ['has_task_mgmt',       'Có task management'],
            ['has_periodic_eval',   'Có đánh giá định kỳ'],
            ['unclear_resp',        'Chưa rõ trách nhiệm'],
        ]);

        // 4.3 Vấn đề nhân sự đang gặp — Checkbox
        $field = $this->addField($section, 3, 'hr_problems', '4.3 Vấn đề nhân sự đang gặp', FieldType::Checkbox);
        $this->addOptions($field, [
            ['new_staff_struggle',      'Nhân sự mới khó bắt việc'],
            ['depend_on_old_staff',     'Phụ thuộc người cũ'],
            ['no_training_docs',        'Không có tài liệu đào tạo'],
            ['manual_training',         'Đào tạo thủ công'],
            ['no_productivity_measure', 'Không đo năng suất'],
            ['overlapping_tasks',       'Việc chồng chéo'],
        ]);

        // 4.4 Mô tả thêm vấn đề nhân sự / đào tạo — Textarea
        $this->addField($section, 4, 'hr_description', '4.4 Mô tả thêm vấn đề nhân sự / đào tạo', FieldType::Textarea, false, 'Mô tả thêm vấn đề nhân sự / đào tạo...');
    }

    // ── Section 5: Dữ liệu & Hệ thống ────────────────────────────────────────

    private function seedSection5DuLieuHeThong(): void
    {
        $section = $this->createSection('Dữ liệu & Hệ thống', '🗄️', 5);

        // 5.1 Dữ liệu hiện đang lưu ở đâu? — Checkbox
        $field = $this->addField($section, 1, 'data_storage', '5.1 Dữ liệu hiện đang lưu ở đâu?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['excel_gsheet',     'Excel / Google Sheet'],
            ['google_drive',     'Google Drive'],
            ['personal_computer','Máy tính cá nhân'],
            ['internal_server',  'Server nội bộ'],
            ['cloud',            'Cloud'],
            ['software',         'Phần mềm quản lý'],
            ['zalo_chat',        'Zalo / Chat'],
            ['paper',            'Giấy tờ'],
        ]);

        // 5.2 Các vấn đề dữ liệu — Checkbox
        $field = $this->addField($section, 2, 'data_problems', '5.2 Các vấn đề dữ liệu', FieldType::Checkbox);
        $this->addOptions($field, [
            ['duplicate',     'Dữ liệu trùng lặp'],
            ['data_loss',     'Mất dữ liệu'],
            ['not_synced',    'Không đồng bộ'],
            ['wrong_data',    'Sai dữ liệu'],
            ['hard_to_search','Khó tìm kiếm'],
            ['no_backup',     'Không backup'],
        ]);

        // 5.3 Dữ liệu khách hàng có tập trung không? — Radio
        $field = $this->addField($section, 3, 'data_centralized', '5.3 Dữ liệu khách hàng có tập trung không?', FieldType::Radio);
        $this->addOptions($field, [
            ['fully_centralized',   'Có, tập trung hoàn toàn'],
            ['partially_centralized','Có, nhưng chưa hoàn toàn'],
            ['not_centralized',     'Không, dữ liệu phân tán'],
            ['very_scattered',      'Rất phân tán, khó quản lý'],
        ]);

        // 5.4 Có phân quyền truy cập dữ liệu không? — Radio
        $field = $this->addField($section, 4, 'data_access_control', '5.4 Có phân quyền truy cập dữ liệu không?', FieldType::Radio);
        $this->addOptions($field, [
            ['full',    'Có, đầy đủ'],
            ['partial', 'Có, nhưng chưa rõ ràng'],
            ['none',    'Không'],
        ]);

        // 5.5 Có báo cáo realtime không? — Radio
        $field = $this->addField($section, 5, 'realtime_reports', '5.5 Có báo cáo realtime không?', FieldType::Radio);
        $this->addOptions($field, [
            ['full',    'Có, đầy đủ'],
            ['partial', 'Có, nhưng chưa realtime'],
            ['none',    'Không'],
        ]);

        // 5.6 Công nghệ/dịch vụ đang dùng — Checkbox
        $field = $this->addField($section, 6, 'tech_services', '5.6 Công nghệ/dịch vụ đang dùng', FieldType::Checkbox);
        $this->addOptions($field, [
            ['api',        'API'],
            ['automation', 'Automation'],
            ['ai',         'AI'],
            ['ocr',        'OCR'],
            ['chatbot',    'Chatbot'],
            ['workflow',   'Workflow'],
        ]);

        // 5.7 Hệ thống hiện đáp ứng nhu cầu ở mức độ nào? — Rating 1-5
        $this->addField($section, 7, 'system_satisfaction', '5.7 Hệ thống hiện đáp ứng nhu cầu ở mức độ nào?', FieldType::Rating, false, null, 1, 5);

        // 5.8 Liệt kê các phần mềm/hệ thống đang sử dụng — Textarea
        $this->addField($section, 8, 'software_list', '5.8 Liệt kê các phần mềm/hệ thống đang sử dụng', FieldType::Textarea, false, 'Liệt kê các phần mềm/hệ thống đang sử dụng...');
    }

    // ── Section 6: AI Readiness ────────────────────────────────────────────────

    private function seedSection6AiReadiness(): void
    {
        $section = $this->createSection('AI Readiness', '🤖', 6);

        // 6.1 Đã từng sử dụng AI ở công cụ nào? — Checkbox
        $field = $this->addField($section, 1, 'ai_tools_used', '6.1 Đã từng sử dụng AI ở công cụ nào?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['chatgpt',        'ChatGPT'],
            ['gemini',         'Gemini'],
            ['ms_copilot',     'Microsoft Copilot'],
            ['ai_chatbot',     'AI Chatbot'],
            ['ai_content',     'AI viết nội dung'],
            ['ai_data_analysis','AI phân tích dữ liệu'],
            ['ai_image',       'AI tạo hình ảnh'],
            ['never_used',     'Chưa từng dùng AI'],
        ]);

        // 6.2 Mức độ hiểu biết AI của đội ngũ — Radio
        $field = $this->addField($section, 2, 'ai_knowledge_level', '6.2 Mức độ hiểu biết AI của đội ngũ', FieldType::Radio);
        $this->addOptions($field, [
            ['no_knowledge', 'Chưa biết gì về AI'],
            ['basic',        'Biết cơ bản'],
            ['tried',        'Đã thử sử dụng'],
            ['used_at_work', 'Đã dùng vào công việc'],
            ['proficient',   'Sử dụng thành thạo'],
        ]);

        // 6.3 Muốn AI hỗ trợ ở đâu nhất? — Checkbox
        $field = $this->addField($section, 3, 'ai_support_areas', '6.3 Muốn AI hỗ trợ ở đâu nhất?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['sales_marketing',   'Sales & Marketing'],
            ['internal_training', 'Đào tạo nội bộ'],
            ['customer_service',  'CSKH'],
            ['process_automation','Tự động hóa quy trình'],
            ['data_entry',        'Nhập liệu & xử lý dữ liệu'],
            ['ceo_dashboard',     'CEO Dashboard'],
            ['reporting_analysis','Báo cáo & phân tích'],
            ['production_ops',    'Sản xuất / Vận hành'],
        ]);

        // 6.4 Điều lo ngại nhất khi ứng dụng AI? — Checkbox
        $field = $this->addField($section, 4, 'ai_concerns', '6.4 Điều lo ngại nhất khi ứng dụng AI?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['high_cost',               'Chi phí cao'],
            ['dont_know_where_to_start','Không biết bắt đầu từ đâu'],
            ['staff_cant_use',          'Nhân sự không biết dùng'],
            ['data_loss',               'Mất dữ liệu'],
            ['not_effective',           'Không hiệu quả'],
            ['not_practical',           'Không phù hợp thực tế'],
        ]);

        // 6.5 Sẵn sàng chuyển đổi ở mức nào? — Radio
        $field = $this->addField($section, 5, 'ai_readiness_level', '6.5 Sẵn sàng chuyển đổi ở mức nào?', FieldType::Radio);
        $this->addOptions($field, [
            ['explore_only',   'Chỉ muốn tìm hiểu'],
            ['small_pilot',    'Muốn thử nghiệm nhỏ'],
            ['partial_deploy', 'Muốn triển khai từng phần'],
            ['full_deploy',    'Muốn triển khai toàn diện'],
        ]);

        // 6.6 Mục tiêu kỳ vọng khi ứng dụng AI — Textarea
        $this->addField($section, 6, 'ai_expectations', '6.6 Mục tiêu kỳ vọng khi ứng dụng AI', FieldType::Textarea, false, 'Mục tiêu kỳ vọng khi ứng dụng AI...');
    }

    // ── Section 7: Đánh giá tổng quan ────────────────────────────────────────

    private function seedSection7DanhGiaTongQuan(): void
    {
        $section = $this->createSection('Đánh giá tổng quan', '📋', 7);

        // 7.1 Mức độ số hóa hiện tại — Rating 1-5 (1=Rất thấp, 5=Rất cao)
        $this->addField($section, 1, 'digitalization_level', '7.1 Mức độ số hóa hiện tại', FieldType::Rating, false, null, 1, 5);

        // 7.2 Sẵn sàng chuyển đổi số mức nào? — Radio
        $field = $this->addField($section, 2, 'digital_ready', '7.2 Sẵn sàng chuyển đổi số mức nào?', FieldType::Radio);
        $this->addOptions($field, [
            ['explore_only',   'Chỉ muốn tìm hiểu'],
            ['small_pilot',    'Muốn thử nghiệm nhỏ'],
            ['partial_deploy', 'Triển khai từng phần'],
            ['full_deploy',    'Triển khai toàn diện'],
        ]);

        // 7.3 Ngân sách chuyển đổi số / AI? — Radio
        $field = $this->addField($section, 3, 'digital_budget', '7.3 Ngân sách chuyển đổi số / AI?', FieldType::Radio);
        $this->addOptions($field, [
            ['no_budget',  'Chưa có ngân sách'],
            ['under_50m',  'Dưới 50 triệu/năm'],
            ['50_200m',    '50 - 200 triệu/năm'],
            ['200_500m',   '200 - 500 triệu/năm'],
            ['over_500m',  'Trên 500 triệu/năm'],
        ]);

        // 7.4 Kỳ vọng khi triển khai AI & tự động hóa — Checkbox
        $field = $this->addField($section, 4, 'transformation_goals', '7.4 Kỳ vọng khi triển khai AI & tự động hóa', FieldType::Checkbox);
        $this->addOptions($field, [
            ['improve_efficiency',   'Tăng hiệu suất'],
            ['reduce_cost',          'Giảm chi phí'],
            ['reduce_errors',        'Giảm sai sót'],
            ['increase_revenue',     'Tăng doanh thu'],
            ['improve_cx',           'Tăng trải nghiệm KH'],
            ['realtime_data',        'Dữ liệu realtime'],
            ['standardize_process',  'Chuẩn hóa quy trình'],
        ]);

        // 7.6 Muốn THUCHOCVN hỗ trợ điều gì nhất? — Checkbox
        $field = $this->addField($section, 5, 'support_needs', '7.6 Muốn THUCHOCVN hỗ trợ điều gì nhất?', FieldType::Checkbox);
        $this->addOptions($field, [
            ['survey_assessment', 'Khảo sát & đánh giá tổng thể'],
            ['build_sop_workflow', 'Xây dựng SOP/Workflow'],
            ['ai_consulting',     'Tư vấn & triển khai AI'],
            ['ai_training',       'Đào tạo nhân sự dùng AI'],
            ['data_system',       'Xây dựng hệ thống dữ liệu'],
            ['dashboard_report',  'Dashboard báo cáo'],
        ]);
    }

    // ── Section 8: Thông tin bổ sung ─────────────────────────────────────────

    private function seedSection8ThongTinBoSung(): void
    {
        $section = $this->createSection('Thông tin bổ sung', 'ℹ️', 8);

        // 8.1 Đã từng làm việc với đơn vị tư vấn/chuyển đổi số? — Radio
        $field = $this->addField($section, 1, 'past_consultant', '8.1 Đã từng làm việc với đơn vị tư vấn/chuyển đổi số?', FieldType::Radio);
        $this->addOptions($field, [
            ['never',     'Chưa từng'],
            ['before',    'Đã từng'],
            ['currently', 'Đang làm'],
        ]);

        // 8.2 Mức độ hài lòng với kết quả không? — Radio (emoji scale)
        $field = $this->addField($section, 2, 'consultant_satisfaction', '8.2 Mức độ hài lòng với kết quả không?', FieldType::Radio);
        $this->addOptions($field, [
            ['very_dissatisfied', 'Rất không hài lòng'],
            ['dissatisfied',      'Không hài lòng'],
            ['neutral',           'Bình thường'],
            ['satisfied',         'Hài lòng'],
            ['very_satisfied',    'Rất hài lòng'],
        ]);

        // 8.3 Yêu cầu hoặc mong muốn đặc biệt — Textarea
        $this->addField($section, 3, 'special_requests', '8.3 Yêu cầu hoặc mong muốn đặc biệt', FieldType::Textarea, false, 'Nhập nội dung...');

        // 8.4 Kênh biết đến THUCHOCVN? — Select
        $field = $this->addField($section, 4, 'referral_channel', '8.4 Kênh biết đến THUCHOCVN?', FieldType::Select);
        $this->addOptions($field, [
            ['facebook', 'Facebook'],
            ['website',  'Website'],
            ['referral', 'Giới thiệu'],
            ['event',    'Sự kiện'],
            ['partner',  'Đối tác'],
        ]);

        // 8.5 Đồng ý để THUCHOCVN liên hệ tư vấn? — Radio
        $field = $this->addField($section, 5, 'contact_consent', '8.5 Đồng ý để THUCHOCVN liên hệ tư vấn?', FieldType::Radio);
        $this->addOptions($field, [
            ['yes', 'Có'],
            ['no',  'Không'],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function createSection(string $title, string $icon, int $sortOrder): SurveySection
    {
        return SurveySection::create([
            'survey_id'  => $this->survey->id,
            'title'      => $title,
            'icon'       => $icon,
            'sort_order' => $sortOrder,
        ]);
    }

    private function addField(
        SurveySection $section,
        int           $sortOrder,
        string        $key,
        string        $label,
        FieldType     $type,
        bool          $required = false,
        ?string       $placeholder = null,
        ?int          $ruleMin = null,
        ?int          $ruleMax = null,
        ?int          $ruleMaxSelect = null,
    ): SurveyField {
        return SurveyField::create([
            'survey_id'      => $this->survey->id,
            'section_id'     => $section->id,
            'field_key'      => $key,
            'label'          => $label,
            'field_type'     => $type->value,
            'value_kind'     => $type->valueKind()->value,
            'is_required'    => $required,
            'sort_order'     => $sortOrder,
            'rule_min'       => $ruleMin,
            'rule_max'       => $ruleMax,
            'rule_max_select' => $ruleMaxSelect,
            'placeholder'    => $placeholder,
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2?: bool}>  $options
     *         Each entry: [option_value, label, is_other=false]
     */
    private function addOptions(SurveyField $field, array $options): void
    {
        foreach ($options as $index => $opt) {
            SurveyFieldOption::create([
                'field_id'     => $field->id,
                'option_value' => $opt[0],
                'label'        => $opt[1],
                'sort_order'   => $index + 1,
                'is_other'     => $opt[2] ?? false,
            ]);
        }
    }
}
