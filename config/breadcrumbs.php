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

    ],

];
