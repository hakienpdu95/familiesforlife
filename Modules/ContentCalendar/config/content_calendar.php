<?php

// spec/ContentCalendar_Technical_Specification.md §11 — auto-merge vào config('content_calendar.*')
// bởi ContentCalendarServiceProvider (NWIDART: tên file == module nameLower). Không thêm key nào
// chưa có Action nào đọc tới (tránh cấu hình chết) — capacity/SLA (Phase 2/3) sẽ có config riêng
// khi thực sự triển khai, không đoán trước ở đây.
return [

    // Dùng bởi ListCategoryPlannedTitlesAction (§10) — cùng khuôn
    // core_idea_extractor.existing_articles để 2 nguồn dedup có giới hạn nhất quán.
    'dedup' => [
        'db_fetch_limit'  => 100,
        'max_titles'      => 30,
        'active_statuses' => ['idea', 'planned', 'drafting', 'blocked', 'ready'],
    ],

    // Mặc định số ngày nhìn về phía trước khi mở view Calendar lần đầu (không lưu DB).
    'board' => [
        'default_lookahead_days' => 60,
    ],

];
