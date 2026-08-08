<?php

use Modules\Post\Models\PostArticle;
use Modules\Video\Models\Video;

/**
 * spec/Playlist_Technical_Specification.md §4.6/§0 — mỗi entry mang theo cả FQCN (nguồn duy
 * nhất cho Relation::morphMap(), §4.7) LẪN quan hệ cần eager-load khi hiển thị công khai
 * ('with', dùng cho MorphTo::morphWith(), §7.4). Thêm loại nội dung mới chỉ cần thêm 1 entry +
 * 1 class implement Modules\Playlist\Contracts\PlaylistableContract, KHÔNG migrate lại
 * playlist_items.
 */
return [
    'name' => 'Playlist',

    'itemables' => [
        'video' => [
            'model' => Video::class,
            'with' => [],
        ],
        'post_article' => [
            'model' => PostArticle::class,
            'with' => ['translations'],
        ],
    ],

    // Số kết quả tối đa MỖI nguồn khi tìm kiếm hợp nhất ở modal "Thêm item" (§6.4) — tránh 1
    // nguồn có nhiều bản ghi khớp từ khoá lấn át hoàn toàn nguồn còn lại.
    'search_limit_per_type' => 10,

    'per_page' => 12,
];
