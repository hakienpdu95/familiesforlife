<?php

/**
 * Notification type catalog — used by the preferences UI.
 *
 * Structure: [ 'Group Label' => [ 'event_type' => 'Human label' ] ]
 * Defaults when no preference record exists: channel_db=true, channel_mail=false, channel_push=false.
 */
return [

    'Task' => [
        'task_assigned'  => 'Task được giao cho bạn',
        'task_due_soon'  => 'Task sắp đến hạn (D-1)',
        'task_overdue'   => 'Task quá hạn',
        'task_commented' => 'Có comment mới trên task',
    ],

    'Lead' => [
        'lead_assigned'       => 'Lead được giao',
        'lead_status_changed' => 'Lead thay đổi trạng thái',
        'lead_overdue'        => 'Lead quá hạn follow-up',
    ],

    'Nghỉ phép' => [
        'leave_submitted' => 'Đơn xin nghỉ cần duyệt (quản lý)',
        'leave_approved'  => 'Đơn xin nghỉ được duyệt',
        'leave_rejected'  => 'Đơn xin nghỉ bị từ chối',
    ],

    'KPI' => [
        'kpi_target_approaching' => 'KPI đạt 80% mục tiêu',
        'kpi_completed'          => 'KPI hoàn thành 100%',
        'kpi_missed'             => 'KPI không đạt target cuối kỳ',
    ],

    'Knowledge Center (KC)' => [
        'kc_submitted'     => 'Tài liệu KC chờ duyệt (approver)',
        'kc_approved'      => 'Tài liệu KC được duyệt',
        'kc_rejected'      => 'Tài liệu KC bị từ chối',
        'kc_expiring_soon' => 'Tài liệu KC sắp hết hạn',
    ],

    'SOP' => [
        'sop_submitted'      => 'SOP chờ duyệt',
        'sop_approved'       => 'SOP được duyệt',
        'sop_rejected'       => 'SOP bị từ chối',
        'sop_expiry_warning' => 'SOP sắp hết hạn',
        'sop_next_approver'  => 'Bạn là người duyệt tiếp theo',
    ],

    'Performance Review' => [
        'review_period_started' => 'Kỳ đánh giá bắt đầu',
        'review_submitted'      => 'Nhân viên nộp self-assessment',
        'review_completed'      => 'Đánh giá hoàn tất',
    ],

    'Employee' => [
        'employee_onboarded' => 'Nhân viên mới được onboard',
    ],

    'Subscription' => [
        'subscription_expiring_db' => 'Subscription sắp hết hạn',
        'subscription_expired_db'  => 'Subscription đã hết hạn',
    ],

];
