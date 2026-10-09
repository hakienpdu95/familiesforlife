<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Root segments to strip before parsing
    |--------------------------------------------------------------------------
    | Segments at the start of a route name that represent "admin area"
    | and should not appear as breadcrumb items.
    */
    'skip_root_segments' => ['backend'],

    /*
    |--------------------------------------------------------------------------
    | Model display attributes
    |--------------------------------------------------------------------------
    | When the current page shows a specific model (show/edit), these
    | attributes are tried in order to get the display name.
    */
    'model_name_attributes' => ['full_name', 'name', 'title', 'subject', 'label', 'code'],

    /*
    |--------------------------------------------------------------------------
    | Segment → Vietnamese label map
    |--------------------------------------------------------------------------
    | Maps each route name segment to its display label.
    | null  = skip (segment is hidden from breadcrumbs, e.g. 'index')
    */
    'segments' => [

        // ── CRUD actions ──────────────────────────────────────────────────
        'index'   => null,       // hidden: the resource itself is the crumb
        'show'    => null,       // hidden: model name is used instead
        'create'  => 'Tạo mới',
        'edit'    => 'Chỉnh sửa',
        'store'   => null,
        'update'  => null,
        'destroy' => null,

        // ── HR / Org ──────────────────────────────────────────────────────
        'employees'           => 'Nhân viên',
        'organizations'       => 'Tổ chức',
        'users'               => 'Người dùng',
        'roles'               => 'Vai trò',
        'permissions'         => 'Quyền hạn',

        'pipeline-stages'     => 'Giai đoạn Pipeline',

        'versions'            => 'Phiên bản',

        // ── SOP ───────────────────────────────────────────────────────────
        'sop'                 => 'Quy trình SOP',

        // ── Survey ────────────────────────────────────────────────────────
        'surveys'             => 'Khảo sát',
        'tokens'              => 'Mã tham gia',
        'stats'               => 'Thống kê',
        'results'             => 'Kết quả',

        // ── CRM / Lead ────────────────────────────────────────────────────
        'leads'               => 'Danh sách Lead',
        'tags'                => 'Thẻ phân loại',
        'sources'             => 'Nguồn Lead',

        // ── Activity Log ──────────────────────────────────────────────────
        'activity-logs'       => 'Nhật ký hoạt động',
        'alert-rules'         => 'Quy tắc cảnh báo',

        // ── Workflow ──────────────────────────────────────────────────────
        'workflows'           => 'Luồng tự động',
        'executions'          => 'Lịch sử chạy',

        // ── Auth / Profile ────────────────────────────────────────────────
        'profile'             => 'Hồ sơ cá nhân',
        'me'                  => 'Thông tin cá nhân',

        // ── Common shared pages ───────────────────────────────────────────
        'analytics'           => 'Phân tích',
        'overview'            => 'Tổng quan',
        'attachments'         => 'Tài liệu đính kèm',
        'notes'               => 'Ghi chú',
        'export'              => 'Xuất dữ liệu',
        'import'              => 'Nhập dữ liệu',
        'my-schedule'         => 'Lịch của tôi',
        'summary'             => 'Tóm tắt',
        'items'               => 'Danh sách',
        'categories'          => 'Danh mục',
        'settings'            => 'Cài đặt',
        'logs'                => 'Nhật ký',
        'history'             => 'Lịch sử',
        'status'              => 'Trạng thái',
        'reports'             => 'Báo cáo',
        'dashboard'           => 'Bảng điều khiển',
        'charts'              => 'Biểu đồ',
        'lead-funnel'         => 'Phễu Lead',
        'workflow-health'     => 'Tình trạng luồng tự động',
        'notifications'       => 'Thông báo',
        'preferences'         => 'Tuỳ chọn thông báo',
        'platform-users'      => 'Người dùng Platform',
        'customers'           => 'Khách hàng',
        'orders'              => 'Đơn hàng',
        'products'            => 'Sản phẩm',

        // ── Approval ──────────────────────────────────────────────────────
        'approval'            => 'Phê duyệt',

        // ── Nội dung (CMS) ────────────────────────────────────────────────
        'post'                => 'Bài viết',
        'articles'            => 'Danh sách bài viết',
        'pending-review'      => 'Chờ duyệt',
        'needs-freshness-review' => 'Cần cập nhật nội dung',
        'clicks'              => 'Lượt nhấp',
        'translations'        => 'Bản dịch',
        'compare'             => 'So sánh',
        'markdown-preview'    => 'Xem trước Markdown',
        'breaking-news'       => 'Tin nóng',
        'event'               => 'Sự kiện',
        'video'               => 'Video',
        'playlist'            => 'Playlist',
        'page'                => 'Trang tĩnh',
        'banner'              => 'Banner',
        'menu'                => 'Điều hướng menu',
        'newsletter'          => 'Bản tin',
        'subscribers'         => 'Người đăng ký',
        'broadcast'           => 'Gửi bản tin',
        'contentcalendar'     => 'Lịch nội dung',
        'board'               => 'Board',
        'calendar'            => 'Lịch tháng',

        // ── Dữ liệu chuyên ngành ──────────────────────────────────────────
        'ocop'                => 'OCOP',
        'ocop-subjects'       => 'Chủ thể OCOP',
        'provinces'           => 'Tỉnh thành',
        'real-estate'         => 'Bất động sản',
        'heritage'            => 'Di tích/Di sản',
        'sites'               => 'Danh sách',
        'pension-calculator'  => 'Tính lương hưu BHXH',
        'periods'             => 'Giai đoạn',
        'price-index'         => 'Chỉ số giá',
        'rate-brackets'       => 'Bậc tỷ lệ',
        'entity_comparison'   => 'So sánh thực thể',
        'entity_types'        => 'Loại thực thể',
        'entities'            => 'Thực thể',
        'criteria'            => 'Tiêu chí',

        // ── AI Studio ─────────────────────────────────────────────────────
        'aicem'               => 'AICEM',
        'knowledge-documents' => 'Tài liệu tri thức',
        'example-candidates'  => 'Ví dụ đề xuất',
        'generation'          => 'Sinh nội dung',
        'contentfoundation'   => 'Content Foundation',
        'contentoutlines'     => 'Dàn ý nội dung',
        'coreideaextractor'   => 'Trích ý bài viết',
        'videoideaextractor'  => 'Trích ý video',
        'promptstudio'        => 'Prompt Framework Studio',
        'library'             => 'Thư viện',
        'prompts'             => 'Prompt',
        'topic-cluster-result' => 'Kết quả Topic Cluster',
        'bulkideationpromptstudio' => 'Bulk Ideation Prompt Studio',
        'videoseriespromptstudio'  => 'Video Series Prompt Studio',
        'aivideostudiotemplate'    => 'AI Video Studio Template',
        'formula-advisor'     => 'Tư vấn công thức',

        // ── Tích hợp ──────────────────────────────────────────────────────
        'n8n'                 => 'n8n',
        'connections'         => 'Kết nối',

    ],

];
