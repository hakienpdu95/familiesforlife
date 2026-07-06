<?php

namespace Modules\Deployment\Database\Seeders;

use App\Foundation\Vertical\VerticalChecklistItem;
use App\Foundation\Vertical\VerticalConfigItem;
use App\Foundation\Vertical\VerticalPhase;
use App\Foundation\Vertical\VerticalTemplate;
use Illuminate\Database\Seeder;

/**
 * Bản mẫu thư viện mặc định — "Truy xuất nguồn gốc Nông sản" (organization_id = null).
 * Tối giản, không có hạng mục đặc thù (vd. biển đầu bờ/biển khu vực) — tổ chức nào cần
 * tự thêm qua UI builder (Phase 7-8, chưa làm). Dùng đúng slug data_collection_v1/readiness_v1
 * — 2 survey seed cùng lượt trong SystemDataSeeder (DataCollectionV1Seeder/ReadinessV1SurveySeeder).
 */
class DefaultVerticalTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $template = VerticalTemplate::updateOrCreate(
            ['organization_id' => null, 'code' => 'truy-xuat-nguon-goc'],
            [
                'label'                         => 'Truy xuất nguồn gốc Nông sản',
                'target_label'                  => 'Tổ chức',
                'target_org_category'           => 'cooperative',
                'has_physical_assets'           => false,
                'readiness_template_slug'       => 'readiness_v1',
                'data_collection_template_slug' => 'data_collection_v1',
                'default_roles'                 => ['pm', 'surveyor', 'data_ops', 'data_entry', 'trainer'],
                'sidebar_config'                => $this->sidebarConfig(),
                'is_active'                     => true,
                'status'                        => 'active',
            ]
        );

        foreach ($this->phases() as $sortOrder => $phaseData) {
            $phase = VerticalPhase::updateOrCreate(
                ['vertical_template_id' => $template->id, 'key' => $phaseData['key']],
                [
                    'label'                       => $phaseData['label'],
                    'sort_order'                  => $sortOrder,
                    'is_initial'                  => $phaseData['is_initial'] ?? false,
                    'auto_assign_data_collection' => $phaseData['auto_assign_data_collection'] ?? false,
                ]
            );

            foreach ($phaseData['checklist'] ?? [] as $itemSort => $item) {
                VerticalChecklistItem::updateOrCreate(
                    ['vertical_phase_id' => $phase->id, 'key' => $item['key']],
                    [
                        'label'       => $item['label'],
                        'is_required' => $item['required'],
                        'sort_order'  => $itemSort,
                    ]
                );
            }
        }

        foreach ($this->issueTypes() as $sortOrder => $issueType) {
            VerticalConfigItem::updateOrCreate(
                ['vertical_template_id' => $template->id, 'config_group' => 'issue_type', 'code' => $issueType['code']],
                [
                    'label'       => $issueType['label'],
                    'is_required' => false,
                    'is_active'   => true,
                    'sort_order'  => $sortOrder,
                ]
            );
        }

        $this->command?->info('[DefaultVerticalTemplate] Seeded "Truy xuất nguồn gốc Nông sản" (' . count($this->phases()) . ' phase).');
    }

    /**
     * Gợi ý khởi điểm, không bắt buộc — mỗi tổ chức tự sửa/xoá/thêm loại issue riêng
     * qua trang Cấu hình vertical (không phải danh mục cố định của hệ thống).
     */
    private function issueTypes(): array
    {
        return [
            ['code' => 'pest_disease', 'label' => 'Sâu bệnh'],
            ['code' => 'equipment',    'label' => 'Thiết bị'],
            ['code' => 'other',        'label' => 'Khác'],
        ];
    }

    private function phases(): array
    {
        return [
            ['key' => 'draft', 'label' => 'Khởi tạo', 'is_initial' => true],
            [
                'key'                          => 'surveying',
                'label'                        => 'Khảo sát thực địa',
                'auto_assign_data_collection'  => true,
                'checklist' => [
                    ['key' => 'entity_profile_verified',  'label' => 'Xác nhận thông tin tổ chức (Section A)', 'required' => true],
                    ['key' => 'data_collection_assigned', 'label' => 'Đã giao form thu thập dữ liệu',          'required' => true],
                    ['key' => 'field_survey_done',        'label' => 'Hoàn thành khảo sát thực địa',            'required' => true],
                    ['key' => 'gps_captured',              'label' => 'Thu thập GPS trung tâm vùng sản xuất',   'required' => true],
                    ['key' => 'photos_uploaded',           'label' => 'Upload ảnh thực địa',                    'required' => false],
                ],
            ],
            [
                'key' => 'collecting', 'label' => 'Thu thập dữ liệu',
                'checklist' => [
                    ['key' => 'legal_docs_uploaded',      'label' => 'Upload hồ sơ pháp lý (ĐKKD, CCCD...)', 'required' => true],
                    ['key' => 'product_list_confirmed',   'label' => 'Danh mục sản phẩm đầy đủ (Section C)', 'required' => true],
                    ['key' => 'history_files_uploaded',   'label' => 'Upload file lịch sử hoạt động (nếu có)', 'required' => false],
                    ['key' => 'donvi_files_uploaded',     'label' => 'Upload file đơn vị chi tiết (nếu có)', 'required' => false],
                ],
            ],
            [
                'key' => 'standardizing', 'label' => 'Chuẩn hóa dữ liệu',
                'checklist' => [
                    ['key' => 'org_data_standardized',     'label' => 'Chuẩn hóa dữ liệu tổ chức vào org profile', 'required' => true],
                    ['key' => 'product_data_standardized', 'label' => 'Chuẩn hóa danh mục sản phẩm',               'required' => true],
                    ['key' => 'ai_validator_passed',       'label' => 'AI Validator đạt ≥ 90%',                     'required' => true],
                ],
            ],
            [
                'key' => 'exporting', 'label' => 'Xuất dữ liệu',
                'checklist' => [
                    ['key' => 'export_package_generated',  'label' => 'Tạo gói export dữ liệu',                  'required' => true],
                    ['key' => 'export_reviewed',           'label' => 'Đã review file export',                   'required' => true],
                    ['key' => 'partner_import_confirmed',  'label' => 'Xác nhận đã nhập vào hệ thống đối tác',   'required' => true],
                ],
            ],
            [
                'key' => 'training', 'label' => 'Đào tạo',
                'checklist' => [
                    ['key' => 'login_trained',        'label' => 'Đào tạo đăng nhập hệ thống',       'required' => true],
                    ['key' => 'data_view_trained',    'label' => 'Đào tạo xem và kiểm tra dữ liệu',  'required' => true],
                    ['key' => 'activity_log_trained', 'label' => 'Đào tạo nhập nhật ký',              'required' => true],
                    ['key' => 'photo_upload_trained', 'label' => 'Đào tạo upload ảnh',                'required' => true],
                ],
            ],
            [
                'key' => 'handover', 'label' => 'Bàn giao',
                'checklist' => [
                    ['key' => 'documents_handedover',    'label' => 'Bàn giao hồ sơ pháp lý',        'required' => true],
                    ['key' => 'data_handover_confirmed', 'label' => 'Xác nhận dữ liệu đã bàn giao',   'required' => true],
                    ['key' => 'user_guide_provided',     'label' => 'Cung cấp tài liệu hướng dẫn',    'required' => true],
                    ['key' => 'handover_minutes_signed', 'label' => 'Ký biên bản bàn giao',           'required' => true],
                    ['key' => 'account_transfer_done',   'label' => 'Chuyển giao tài khoản hệ thống', 'required' => true],
                ],
            ],
            ['key' => 'completed', 'label' => 'Hoàn thành'],
        ];
    }

    private function sidebarConfig(): array
    {
        return [
            'TRIỂN KHAI' => [
                ['label' => 'Dashboard',         'route' => '{vertical}.dashboard'],
                ['label' => 'Dự án',             'route' => '{vertical}.projects.index'],
                ['label' => '{target}',          'route' => '{vertical}.targets.index'],
                ['label' => 'Khảo sát năng lực', 'route' => '{vertical}.readiness.index'],
            ],
            'THU THẬP DỮ LIỆU' => [
                ['label' => 'Thu thập dữ liệu', 'route' => '{vertical}.data-collection.index'],
                ['label' => 'Hồ sơ pháp lý',    'route' => '{vertical}.org-docs.index'],
                ['label' => 'Export',           'route' => '{vertical}.export.index'],
            ],
            'CÔNG VIỆC' => [
                ['label' => 'Công việc', 'route' => '{vertical}.tasks.index'],
                ['label' => 'Tiến độ',   'route' => '{vertical}.progress.index'],
                ['label' => 'Issues',    'route' => '{vertical}.issues.index'],
                ['label' => 'Bàn giao',  'route' => '{vertical}.handover.index'],
            ],
            'BÁO CÁO' => [
                ['label' => 'Báo cáo', 'route' => '{vertical}.reports.index'],
            ],
            'ĐÀO TẠO' => [
                ['label' => 'Academy',    'route' => '{vertical}.academy.index'],
                ['label' => 'Chứng nhận', 'route' => '{vertical}.certifications.index'],
            ],
            'CẤU HÌNH' => [
                ['label' => 'Cấu hình', 'route' => '{vertical}.settings.index'],
            ],
        ];
    }
}
