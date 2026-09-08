<?php

return [
    'name' => 'BulkIdeationPromptStudio',

    // spec/BulkIdeationPromptStudio.md §4 — dùng khi biên tập viên bỏ trống "Mục tiêu bài viết".
    'default_business_goal' => 'Phủ sóng từ khóa và cung cấp giá trị hữu ích cho người đọc',

    // Số Ý tưởng Bài viết (Blog Ideas) yêu cầu AI sinh ra ở Phần 2 (spec §4).
    'idea_count' => 5,

    'limits' => [
        'label_max' => 150,
        'raw_inputs_max' => 8000,
        'business_goal_max' => 500,
    ],
];
