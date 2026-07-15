<?php

return [
    'name' => 'Banner',

    // Danh sách placement hợp lệ — THÊM 1 ENTRY MỚI Ở ĐÂY khi cần 1 vị trí mới, không sửa gì
    // khác (không migration, không Enum). Key = giá trị lưu trong banners.placement.
    // Đọc qua Banner::getPlacementLabel()/getPlacementRecommendedSize()/validPlacementKeys()
    // thay vì config('banner.placements.xxx') rải rác nhiều nơi.
    // spec/Banner_Management_Technical_Specification.md §2/§4.2.
    'placements' => [
        'header_ad' => [
            'label'            => 'Banner cạnh logo (mọi trang)',
            'recommended_size' => '970×90',
        ],
        'home_top' => [
            'label'            => 'Trang chủ — dưới khối tin nổi bật',
            'recommended_size' => '1200×150',
        ],
        'home_between_features' => [
            'label'            => 'Trang chủ — xen giữa các khối tin (bố cục tạp chí)',
            'recommended_size' => '1200×150',
        ],
        'category_top' => [
            'label'            => 'Danh mục bài viết — đầu trang',
            'recommended_size' => '1200×150',
        ],
        'article_inline' => [
            'label'            => 'Chi tiết bài viết — giữa nội dung',
            'recommended_size' => '728×90',
        ],
        'event_list_top' => [
            'label'            => 'Danh sách sự kiện — đầu trang',
            'recommended_size' => '1200×150',
        ],
        'event_show_top' => [
            'label'            => 'Chi tiết sự kiện — đầu trang',
            'recommended_size' => '728×90',
        ],
    ],

    // Loại targeting hợp lệ (v1.1) — dùng cho dropdown target_type ở form admin và validate.
    // Key 'global' chỉ tồn tại ở TẦNG FORM/UI — khi lưu, BannerData chuyển 'global' →
    // target_type=null trong DB (không lưu chuỗi 'global'). THÊM 1 DÒNG MỚI Ở ĐÂY khi có
    // target_type mới (vd 'page', 'event_category'), không cần sửa migration/Enum.
    'target_types' => [
        'global'   => 'Toàn site (Global)',
        'category' => 'Theo danh mục bài viết',
    ],

    // Resize ảnh gốc xuống tối đa chiều rộng này nếu lớn hơn (giảm dung lượng file — banner
    // nặng làm chậm mọi trang có banner đó, kể cả header_ad xuất hiện trên toàn site) —
    // spec §5.1.
    'max_image_width' => 1200,
];
