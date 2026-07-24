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

        'organization' => [
            // Class GỐC (App\Shared\Tenancy\Models\Organization), không phải subclass
            // Modules\Organization\Models\Organization — HasApproval đặt trên class gốc vì
            // RegisterOrganizationAction (Modules/Auth) tạo Organization qua chính class này,
            // không qua subclass. getMorphClass() của Organization đã hardcode trả 'organization'
            // sẵn (App\Shared\Tenancy\Models\Organization::getMorphClass()) — khớp key ở đây.
            'model' => \App\Shared\Tenancy\Models\Organization::class,
            'label' => 'Doanh nghiệp / Tổ chức',
            // Tổ chức có sẵn trước khi tích hợp coi là đã Published (tránh khoá tài khoản đang
            // hoạt động bình thường) — xem OrganizationInitialApprovalStatusResolver.
            'initial_status_resolver' => \App\Shared\Tenancy\Support\OrganizationInitialApprovalStatusResolver::class,
        ],
    ],
];
