<?php

return [
    'name' => 'Menu',

    // Vị trí hiển thị hợp lệ cho MenuItem.location — xem MenuLinkType/MenuItem.
    'locations' => [
        'header' => 'Menu chính (header)',
        'footer' => 'Chân trang (footer)',
    ],
    'default_location' => 'header',

    // Giới hạn số cấp lồng nhau — spec/Menu_Navigation_Technical_Specification.md §0/§5.3.
    // depth 0 = cấp 1 (root), depth 2 = cấp 3 (lá) — vượt quá bị chặn ở CreateMenuItemAction.
    'max_depth' => 2,
];
