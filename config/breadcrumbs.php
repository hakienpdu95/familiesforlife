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
        'branches'            => 'Chi nhánh',
        'branch'              => 'Chi nhánh',
        'departments'         => 'Phòng ban',
        'department'          => 'Phòng ban',
        'job-titles'          => 'Chức danh',
        'organizations'       => 'Tổ chức',
        'org-charts'          => 'Sơ đồ tổ chức',
        'role-scopes'         => 'Phạm vi vai trò',
        'users'               => 'Người dùng',
        'roles'               => 'Vai trò',
        'permissions'         => 'Quyền hạn',

        // ── Recruitment ───────────────────────────────────────────────────
        'recruitment'         => 'Tuyển dụng',
        'candidates'          => 'Ứng viên',
        'applications'        => 'Đơn ứng tuyển',
        'interviews'          => 'Phỏng vấn',
        'evaluations'         => 'Đánh giá phỏng vấn',
        'offers'              => 'Đề nghị việc làm',
        'pipeline-stages'     => 'Giai đoạn Pipeline',
        'board'               => 'Kanban Board',
        'job-posts'           => 'Tin tuyển dụng',

        // ── Marketplace ───────────────────────────────────────────────────
        'marketplace'         => 'Marketplace',
        'listings'            => 'Tin đăng',
        'org-approvals'       => 'Duyệt tổ chức',

        // ── Knowledge Center ──────────────────────────────────────────────
        'kc'                  => 'Kho tri thức',
        'kc-items'            => 'Bài viết KC',
        'kc-categories'       => 'Danh mục KC',
        'versions'            => 'Phiên bản',

        // ── KPI ───────────────────────────────────────────────────────────
        'kpi'                 => 'KPI',
        'goals'               => 'Mục tiêu',
        'cycles'              => 'Chu kỳ',
        'leaderboard'         => 'Bảng xếp hạng',

        // ── Leave ─────────────────────────────────────────────────────────
        'leave'               => 'Nghỉ phép',
        'policies'            => 'Chính sách',
        'requests'            => 'Đơn xin nghỉ',
        'balances'            => 'Số dư phép',
        'pending'             => 'Chờ duyệt',

        // ── Performance Review ────────────────────────────────────────────
        'performance-reviews' => 'Đánh giá hiệu suất',

        // ── Project ───────────────────────────────────────────────────────
        'projects'            => 'Dự án',

        // ── SOP ───────────────────────────────────────────────────────────
        'sop'                 => 'Quy trình SOP',

        // ── Survey ────────────────────────────────────────────────────────
        'surveys'             => 'Khảo sát',
        'tokens'              => 'Mã tham gia',
        'stats'               => 'Thống kê',
        'results'             => 'Kết quả',
        'scoring'             => 'Chấm điểm',

        // ── CRM / Lead ────────────────────────────────────────────────────
        'leads'               => 'Danh sách Lead',
        'tags'                => 'Thẻ phân loại',
        'sources'             => 'Nguồn Lead',

        // ── Activity Log ──────────────────────────────────────────────────
        'activity-logs'       => 'Nhật ký hoạt động',
        'alert-rules'         => 'Quy tắc cảnh báo',

        // ── Assessment ────────────────────────────────────────────────────
        'assessments'         => 'Bộ đánh giá',
        'config'              => 'Cấu hình',

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

    ],

];
