<?php

/**
 * Khai báo entity nào dùng trait HasApproval — dùng để build morph map
 * (ApprovalServiceProvider::boot(), tránh lưu FQCN thô trong cột subject_type) và để
 * ApprovalDashboardService (Phase 5) biết cần gom pending item từ những model nào.
 *
 * Xem spec/Workflow_Approval_Technical_Specification.md §5.
 */
return [
    'subjects' => [
        'product' => [
            'model' => \Modules\Product\Models\Product::class,
            'label' => 'Sản phẩm',
            // Product đang bán bình thường (Active/OutOfStock/Discontinued) được coi là đã
            // Published ngay khi backfill — tránh gãy catalog hiện có (§9.6).
            'initial_status_resolver' => \Modules\Product\Support\ProductInitialApprovalStatusResolver::class,
        ],
        // spec/RealEstateForSale_Technical_Specification.md §6 — tin BĐS bán/thuê của
        // Organization, KHÔNG cần initial_status_resolver (bảng mới, không backfill).
        'real_estate_listing' => [
            'model' => \Modules\RealEstate\Models\RealEstateListing::class,
            'label' => 'Tin bất động sản',
        ],
    ],

    'platform_users' => [
        'default_password' => env('PLATFORM_USER_DEFAULT_PASSWORD', 'vgd@123!'),
    ],
];
