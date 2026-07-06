<aside class="sidebar" id="sidebar">

    <div class="brand">
        <div class="brand-logo">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" class="brand-logo-img">
        </div>
        {{-- brand-name ẩn: tên đã có trong logo image --}}
    </div>

    <nav class="nav-wrap">
        <p class="section-title">Chính</p>
        <div class="nav-group">
            <a href="{{ route('backend.dashboard') }}"
               class="nav-link {{ request()->routeIs('backend.dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="nav-label">Dashboard</span>
            </a>
        </div>

        @can(\App\Enums\PermissionEnum::SOLUTION_CATALOG_VIEW->value)
        <a href="{{ route('solution_catalog.index') }}"
           class="nav-link {{ request()->routeIs('solution_catalog.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <span class="nav-label">Danh mục Solution</span>
        </a>
        @endcan

        @auth
        <a href="{{ route('backend.surveys.my') }}"
           class="nav-link {{ request()->routeIs('backend.surveys.my') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="nav-label">Khảo sát</span>
        </a>
        @endauth

        @can('survey.view')
        <details {{ request()->routeIs('backend.surveys.*') && !request()->routeIs('backend.surveys.my') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.surveys.*') && !request()->routeIs('backend.surveys.my') && !request()->routeIs('backend.surveys.take*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span class="nav-label">Quản lý khảo sát</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.surveys.index') }}" class="sub-link {{ request()->routeIs('backend.surveys.index') ? 'active' : '' }}">Danh sách khảo sát</a>
                @can('survey.create')
                <a href="{{ route('backend.surveys.create') }}" class="sub-link {{ request()->routeIs('backend.surveys.create') ? 'active' : '' }}">Tạo khảo sát</a>
                @endcan
            </div>
        </details>
        @endcan

        @if(auth()->user()?->hasAnyPermission(['assessment.view','assessment.config','assessment.results']))
        <details {{ request()->routeIs('assessments.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('assessments.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="nav-label">Chấm điểm</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                @can('assessment.view')
                <a href="{{ route('assessments.index') }}"
                   class="sub-link {{ request()->routeIs('assessments.index') ? 'active' : '' }}">
                    Danh sách Assessment
                </a>
                @endcan
                @can('assessment.config')
                <a href="{{ route('assessments.create') }}"
                   class="sub-link {{ request()->routeIs('assessments.create') ? 'active' : '' }}">
                    Tạo Assessment mới
                </a>
                @endcan
            </div>
        </details>
        @endif

        @can(\App\Enums\PermissionEnum::OCOP_PRODUCT_VIEW->value)
        <details {{ request()->routeIs('ocop.products.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('ocop.products.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9a12.02 12.02 0 00-.382-3.016z"/></svg>
                <span class="nav-label">Sản phẩm OCOP</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('ocop.products.index') }}" class="sub-link {{ request()->routeIs('ocop.products.index') ? 'active' : '' }}">Danh sách sản phẩm</a>
                @can(\App\Enums\PermissionEnum::OCOP_PRODUCT_MANAGE->value)
                <a href="{{ route('ocop.products.create') }}" class="sub-link {{ request()->routeIs('ocop.products.create') ? 'active' : '' }}">Đăng ký sản phẩm</a>
                @endcan
                @can(\App\Enums\PermissionEnum::OCOP_PRACTICE_USE->value)
                <a href="{{ route('ocop.practice.start') }}" class="sub-link {{ request()->routeIs('ocop.practice.start') ? 'active' : '' }}">Luyện tập chấm điểm</a>
                <a href="{{ route('ocop.practice.history') }}" class="sub-link {{ request()->routeIs('ocop.practice.history') ? 'active' : '' }}">Lịch sử luyện tập</a>
                @endcan
            </div>
        </details>
        @endcan

        @if(auth()->user()?->hasAnyPermission(['leads.view_all','leads.view_assigned','leads.view_source']))
        <p class="section-title" style="margin-top:16px;">CRM</p>
        <div class="nav-group">

            <details {{ request()->routeIs('lead.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('lead.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    <span class="nav-label">Cơ hội (Lead)</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('lead.index') }}" class="sub-link {{ request()->routeIs('lead.index') ? 'active' : '' }}">Danh sách cơ hội</a>
                    @can('leads.create')
                    <a href="{{ route('lead.create') }}" class="sub-link {{ request()->routeIs('lead.create') ? 'active' : '' }}">Thêm cơ hội</a>
                    @endcan
                    @can('leads.manage_pipeline')
                    <a href="{{ route('lead-pipeline-stage.index') }}" class="sub-link {{ request()->routeIs('lead-pipeline-stage.*') ? 'active' : '' }}">Pipeline stages</a>
                    @endcan
                    @can('leads.manage_sources')
                    <a href="{{ route('lead-source.index') }}" class="sub-link {{ request()->routeIs('lead-source.*') ? 'active' : '' }}">Nguồn cơ hội</a>
                    @endcan
                    @can('leads.manage_tags')
                    <a href="{{ route('lead.tags.index') }}" class="sub-link {{ request()->routeIs('lead.tags.*') ? 'active' : '' }}">Tags</a>
                    @endcan
                </div>
            </details>

            @if(auth()->user()?->hasAnyPermission(['customers.view_all','customers.view_assigned']))
            <details {{ request()->routeIs('customer.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('customer.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="nav-label">Khách hàng</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('customer.index') }}" class="sub-link {{ request()->routeIs('customer.index') ? 'active' : '' }}">Danh sách khách hàng</a>
                    @can('customers.create')
                    <a href="{{ route('customer.create') }}" class="sub-link {{ request()->routeIs('customer.create') ? 'active' : '' }}">Thêm khách hàng</a>
                    @endcan
                </div>
            </details>
            @endif

        </div>
        @endif

        <p class="section-title" style="margin-top:16px;">Tổ chức</p>
        <div class="nav-group">

            <details {{ request()->routeIs('backend.organizations.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.organizations.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="nav-label">Tổ chức</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.organizations.index') }}" class="sub-link {{ request()->routeIs('backend.organizations.index') ? 'active' : '' }}">Danh sách tổ chức</a>
                    <a href="{{ route('backend.organizations.create') }}" class="sub-link {{ request()->routeIs('backend.organizations.create') ? 'active' : '' }}">Thêm tổ chức</a>
                </div>
            </details>

            @can(\App\Enums\PermissionEnum::VERTICAL_TEMPLATES_MANAGE->value)
            <details {{ request()->routeIs('backend.vertical-templates.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.vertical-templates.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="nav-label">Thư viện mẫu Vertical</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.vertical-templates.index') }}" class="sub-link {{ request()->routeIs('backend.vertical-templates.index') ? 'active' : '' }}">Danh sách bản mẫu</a>
                    <a href="{{ route('backend.vertical-templates.create') }}" class="sub-link {{ request()->routeIs('backend.vertical-templates.create') ? 'active' : '' }}">Thêm bản mẫu</a>
                </div>
            </details>
            @endcan

            @can(\App\Enums\PermissionEnum::SOLUTION_CATALOG_MANAGE->value)
            <details {{ request()->routeIs('business_solutions.admin.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('business_solutions.admin.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="nav-label">Danh mục Business Solution</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('business_solutions.admin.index') }}" class="sub-link {{ request()->routeIs('business_solutions.admin.index') ? 'active' : '' }}">Danh sách Solution</a>
                    <a href="{{ route('business_solutions.admin.create') }}" class="sub-link {{ request()->routeIs('business_solutions.admin.create') ? 'active' : '' }}">Thêm Solution</a>
                </div>
            </details>
            @endcan

            @can(\App\Enums\PermissionEnum::BLUEPRINT_VIEW->value)
            <details {{ request()->routeIs('business_blueprint.admin.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('business_blueprint.admin.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 4.5v15m6-15v15M4.5 9h15m-15 6h15M5 4.5h14A1.5 1.5 0 0120.5 6v12a1.5 1.5 0 01-1.5 1.5H5A1.5 1.5 0 013.5 18V6A1.5 1.5 0 015 4.5z"/></svg>
                    <span class="nav-label">Business Blueprint</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('business_blueprint.admin.index') }}" class="sub-link {{ request()->routeIs('business_blueprint.admin.index') ? 'active' : '' }}">Danh sách Blueprint</a>
                    @can(\App\Enums\PermissionEnum::BLUEPRINT_CREATE->value)
                    <a href="{{ route('business_blueprint.admin.create') }}" class="sub-link {{ request()->routeIs('business_blueprint.admin.create') ? 'active' : '' }}">Thêm Blueprint</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can(\App\Enums\PermissionEnum::SOLUTION_ACTIVATE->value)
            <a href="{{ route('organization_solutions.index') }}"
               class="nav-link {{ request()->routeIs('organization_solutions.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="nav-label">Solution của tổ chức</span>
            </a>
            @endcan

            @can('viewAny', \Modules\Branch\Models\Branch::class)
            <details {{ request()->routeIs('backend.branches.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.branches.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="nav-label">Chi nhánh</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.branches.index') }}" class="sub-link {{ request()->routeIs('backend.branches.index') ? 'active' : '' }}">Danh sách chi nhánh</a>
                    @can('create', \Modules\Branch\Models\Branch::class)
                    <a href="{{ route('backend.branches.create') }}" class="sub-link {{ request()->routeIs('backend.branches.create') ? 'active' : '' }}">Thêm chi nhánh</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\Department\Models\Department::class)
            <details {{ request()->routeIs('backend.departments.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.departments.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span class="nav-label">Phòng ban</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.departments.index') }}" class="sub-link {{ request()->routeIs('backend.departments.index') ? 'active' : '' }}">Danh sách phòng ban</a>
                    @can('create', \Modules\Department\Models\Department::class)
                    <a href="{{ route('backend.departments.create') }}" class="sub-link {{ request()->routeIs('backend.departments.create') ? 'active' : '' }}">Thêm phòng ban</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\JobTitle\Models\JobTitle::class)
            <details {{ request()->routeIs('backend.job-titles.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.job-titles.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    <span class="nav-label">Chức danh</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.job-titles.index') }}" class="sub-link {{ request()->routeIs('backend.job-titles.index') ? 'active' : '' }}">Danh sách chức danh</a>
                    @can('create', \Modules\JobTitle\Models\JobTitle::class)
                    <a href="{{ route('backend.job-titles.create') }}" class="sub-link {{ request()->routeIs('backend.job-titles.create') ? 'active' : '' }}">Thêm chức danh</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\Employee\Models\Employee::class)
            <details {{ request()->routeIs('backend.employees.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.employees.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="nav-label">Nhân viên</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.employees.index') }}" class="sub-link {{ request()->routeIs('backend.employees.index') ? 'active' : '' }}">Danh sách nhân viên</a>
                    @can('create', \Modules\Employee\Models\Employee::class)
                    <a href="{{ route('backend.employees.create') }}" class="sub-link {{ request()->routeIs('backend.employees.create') ? 'active' : '' }}">Thêm nhân viên</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\Leave\Models\LeavePolicy::class)
            <details {{ request()->routeIs('backend.leave.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.leave.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="nav-label">Nghỉ phép</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.leave.requests.index') }}" class="sub-link {{ request()->routeIs('backend.leave.requests.*') ? 'active' : '' }}">Đơn nghỉ phép</a>
                    <a href="{{ route('backend.leave.balances.me') }}" class="sub-link {{ request()->routeIs('backend.leave.balances.me') ? 'active' : '' }}">Số dư của tôi</a>
                    <a href="{{ route('backend.leave.policies.index') }}" class="sub-link {{ request()->routeIs('backend.leave.policies.*') ? 'active' : '' }}">Chính sách nghỉ phép</a>
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\KpiGoal\Models\KpiGoal::class)
            <details {{ request()->routeIs('backend.kpi.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.kpi.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    <span class="nav-label">KPI Goals</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.kpi.goals.index') }}" class="sub-link {{ request()->routeIs('backend.kpi.goals.*') ? 'active' : '' }}">Mục tiêu KPI</a>
                    @can('viewLeaderboard', \Modules\KpiGoal\Models\KpiGoal::class)
                    <a href="{{ route('backend.kpi.leaderboard') }}" class="sub-link {{ request()->routeIs('backend.kpi.leaderboard') ? 'active' : '' }}">Bảng xếp hạng</a>
                    @endcan
                    @can('create', \Modules\KpiGoal\Models\KpiGoal::class)
                    <a href="{{ route('backend.kpi.goals.create') }}" class="sub-link {{ request()->routeIs('backend.kpi.goals.create') ? 'active' : '' }}">Thêm mục tiêu</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\PerformanceReview\Models\PerformanceReview::class)
            <details {{ request()->routeIs('backend.performance-reviews.*') || request()->routeIs('backend.review-templates.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.performance-reviews.*') || request()->routeIs('backend.review-templates.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span class="nav-label">Đánh giá hiệu suất</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.performance-reviews.index') }}" class="sub-link {{ request()->routeIs('backend.performance-reviews.index') ? 'active' : '' }}">Danh sách đánh giá</a>
                    @can('create', \Modules\PerformanceReview\Models\PerformanceReview::class)
                    <a href="{{ route('backend.performance-reviews.create') }}" class="sub-link {{ request()->routeIs('backend.performance-reviews.create') ? 'active' : '' }}">Tạo đánh giá</a>
                    <a href="{{ route('backend.review-templates.index') }}" class="sub-link {{ request()->routeIs('backend.review-templates.*') ? 'active' : '' }}">Mẫu đánh giá</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\Project\Models\Project::class)
            <details {{ request()->routeIs('backend.projects.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.projects.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span class="nav-label">Dự án</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.projects.index') }}" class="sub-link {{ request()->routeIs('backend.projects.index') ? 'active' : '' }}">Danh sách dự án</a>
                    @can('create', \Modules\Project\Models\Project::class)
                    <a href="{{ route('backend.projects.create') }}" class="sub-link {{ request()->routeIs('backend.projects.create') ? 'active' : '' }}">Tạo dự án mới</a>
                    @endcan
                </div>
            </details>
            @endcan

            @if(auth()->user()?->hasAnyPermission(['ai_copilot.use','ai_copilot.config','prompt.full']))
            <details {{ request()->routeIs('ai.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="nav-label">AI Copilot</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    @can('ai_copilot.view_usage')
                    <a href="{{ route('ai.usage.index') }}" class="sub-link {{ request()->routeIs('ai.usage.*') ? 'active' : '' }}">Usage Dashboard</a>
                    @endcan
                    @can('ai_logs.full')
                    <a href="{{ route('ai.logs.index') }}" class="sub-link {{ request()->routeIs('ai.logs.*') ? 'active' : '' }}">Request Logs</a>
                    @endcan
                    @can('ai_copilot.config')
                    <a href="{{ route('ai.agents.index') }}" class="sub-link {{ request()->routeIs('ai.agents.*') ? 'active' : '' }}">AI Agents</a>
                    @endcan
                    @can('prompt.full')
                    <a href="{{ route('ai.prompts.index') }}" class="sub-link {{ request()->routeIs('ai.prompts.*') ? 'active' : '' }}">Prompt Library</a>
                    @endcan
                </div>
            </details>
            @endif

            @can('viewAny', \Modules\Task\Models\Task::class)
            <details {{ request()->routeIs('backend.tasks.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.tasks.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span class="nav-label">Công việc</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.tasks.index') }}" class="sub-link {{ request()->routeIs('backend.tasks.index') ? 'active' : '' }}">Danh sách công việc</a>
                    @can('create', \Modules\Task\Models\Task::class)
                    <a href="{{ route('backend.tasks.create') }}" class="sub-link {{ request()->routeIs('backend.tasks.create') ? 'active' : '' }}">Thêm công việc</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\OrgChart\Models\OrgChartConfig::class)
            <details {{ request()->routeIs('backend.org-charts.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.org-charts.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 9l3-3 3 3M8 15l3 3 3-3M12 3v18M3 9h4M3 15h4M17 9h4M17 15h4"/></svg>
                    <span class="nav-label">Sơ đồ tổ chức</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.org-charts.index') }}" class="sub-link {{ request()->routeIs('backend.org-charts.index') ? 'active' : '' }}">Danh sách cấu hình</a>
                    @can('create', \Modules\OrgChart\Models\OrgChartConfig::class)
                    <a href="{{ route('backend.org-charts.create') }}" class="sub-link {{ request()->routeIs('backend.org-charts.create') ? 'active' : '' }}">Thêm cấu hình</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\JobPosting\Models\JpJobPost::class)
            <details {{ request()->routeIs('backend.job-posts.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.job-posts.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="nav-label">Tin tuyển dụng</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.job-posts.index') }}" class="sub-link {{ request()->routeIs('backend.job-posts.index') ? 'active' : '' }}">Danh sách tin</a>
                    @can('create', \Modules\JobPosting\Models\JpJobPost::class)
                    <a href="{{ route('backend.job-posts.create') }}" class="sub-link {{ request()->routeIs('backend.job-posts.create') ? 'active' : '' }}">Tạo tin mới</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('marketplace.view')
            @php
                $mktSyncCount = 0;
                try {
                    if (\App\Shared\Tenancy\TenantContext::isSet()) {
                        $mktSyncCount = \Illuminate\Support\Facades\Cache::remember(
                            'mkt:org:' . \App\Shared\Tenancy\TenantContext::getOrganizationId() . ':sync-count',
                            60,
                            fn() => \Modules\Marketplace\Models\MktListing::withoutGlobalScope('tenant')
                                ->where('org_id', \App\Shared\Tenancy\TenantContext::getOrganizationId())
                                ->where('jp_sync_status', 'out_of_sync')
                                ->count()
                        );
                    }
                } catch (\Throwable $e) { $mktSyncCount = 0; }
            @endphp
            <details {{ request()->routeIs('backend.marketplace.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.marketplace.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="nav-label">Marketplace Center</span>
                    @if($mktSyncCount > 0)
                    <span class="badge badge-warning badge-xs ml-auto">{{ $mktSyncCount }}</span>
                    @endif
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.marketplace.listings.index') }}"
                       class="sub-link {{ request()->routeIs('backend.marketplace.listings.index') || (request()->routeIs('backend.marketplace.listings.*') && !request()->routeIs('backend.marketplace.listings.create')) ? 'active' : '' }}">Tin đăng</a>
                    @can('marketplace.create')
                    <a href="{{ route('backend.marketplace.listings.create') }}"
                       class="sub-link {{ request()->routeIs('backend.marketplace.listings.create') ? 'active' : '' }}">Đăng tin mới</a>
                    @endcan
                    <a href="{{ route('backend.marketplace.analytics.index') }}"
                       class="sub-link {{ request()->routeIs('backend.marketplace.analytics.*') ? 'active' : '' }}">Analytics</a>
                    @can('marketplace.manage')
                    <a href="{{ route('backend.marketplace.org-approvals.index') }}"
                       class="sub-link {{ request()->routeIs('backend.marketplace.org-approvals.*') ? 'active' : '' }}">Duyệt tổ chức</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('recruitment.view')
            <details {{ request()->routeIs('backend.recruitment.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.recruitment.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="nav-label">Tuyển dụng (ATS)</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.recruitment.candidates.index') }}" class="sub-link {{ request()->routeIs('backend.recruitment.candidates.*') ? 'active' : '' }}">Danh sách ứng viên</a>
                    @can('recruitment.create')
                    <a href="{{ route('backend.recruitment.candidates.create') }}" class="sub-link {{ request()->routeIs('backend.recruitment.candidates.create') ? 'active' : '' }}">Thêm ứng viên</a>
                    @endcan
                    <a href="{{ route('backend.recruitment.interviews.my-schedule') }}" class="sub-link {{ request()->routeIs('backend.recruitment.interviews.my-schedule') ? 'active' : '' }}">Lịch phỏng vấn của tôi</a>
                    @can('recruitment.manage')
                    <a href="{{ route('backend.recruitment.analytics.index') }}" class="sub-link {{ request()->routeIs('backend.recruitment.analytics.*') ? 'active' : '' }}">Analytics</a>
                    <a href="{{ route('backend.recruitment.pipeline-stages.index') }}" class="sub-link {{ request()->routeIs('backend.recruitment.pipeline-stages.*') ? 'active' : '' }}">Pipeline Stages</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\RoleScope\Models\UserRoleScope::class)
            <details {{ request()->routeIs('backend.role-scopes.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.role-scopes.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span class="nav-label">Phân quyền phạm vi</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.role-scopes.index') }}" class="sub-link {{ request()->routeIs('backend.role-scopes.index') ? 'active' : '' }}">Danh sách phân quyền</a>
                    @can('create', \Modules\RoleScope\Models\UserRoleScope::class)
                    <a href="{{ route('backend.role-scopes.create') }}" class="sub-link {{ request()->routeIs('backend.role-scopes.create') ? 'active' : '' }}">Cấp quyền mới</a>
                    @endcan
                </div>
            </details>
            @endcan

        </div>

        @can('viewAny', \Modules\KcCategory\Models\KcCategory::class)
        <p class="section-title" style="margin-top:16px;">Kho tri thức</p>
        <div class="nav-group">

            @can('viewAny', \Modules\KcCategory\Models\KcCategory::class)
            <details {{ request()->routeIs('backend.kc-categories.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.kc-categories.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    <span class="nav-label">Danh mục KC</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.kc-categories.index') }}" class="sub-link {{ request()->routeIs('backend.kc-categories.index') ? 'active' : '' }}">Danh sách danh mục</a>
                    @can('create', \Modules\KcCategory\Models\KcCategory::class)
                    <a href="{{ route('backend.kc-categories.create') }}" class="sub-link {{ request()->routeIs('backend.kc-categories.create') ? 'active' : '' }}">Thêm danh mục</a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\KcItem\Models\KcItem::class)
            @php $kcItemActive = request()->routeIs('backend.kc-items.*'); @endphp
            <details {{ $kcItemActive ? 'open' : '' }}>
                <summary class="nav-summary {{ $kcItemActive ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="nav-label">Tài liệu KC</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.kc-items.index') }}"
                       class="sub-link {{ request()->routeIs('backend.kc-items.index') && !request()->hasAny(['status']) ? 'active' : '' }}">
                        Tất cả tài liệu
                    </a>
                    <a href="{{ route('backend.kc-items.index', ['status' => 'pending_review']) }}"
                       class="sub-link {{ request()->routeIs('backend.kc-items.index') && request('status') === 'pending_review' ? 'active' : '' }}">
                        Chờ duyệt
                    </a>
                    @can('create', \Modules\KcItem\Models\KcItem::class)
                    <a href="{{ route('backend.kc-items.create') }}"
                       class="sub-link {{ request()->routeIs('backend.kc-items.create') ? 'active' : '' }}">
                        Tạo tài liệu
                    </a>
                    @endcan
                </div>
            </details>
            @endcan

            @can('viewAny', \Modules\KcItem\Models\KcTag::class)
            <a href="{{ route('backend.kc-tags.index') }}"
               class="nav-link {{ request()->routeIs('backend.kc-tags.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                <span class="nav-label">Tags KC</span>
            </a>
            @endcan

            @can('viewAny', \Modules\KcItem\Models\KcItem::class)
            <a href="{{ route('backend.kc.analytics') }}"
               class="nav-link {{ request()->routeIs('backend.kc.analytics') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="nav-label">Analytics KC</span>
            </a>
            @endcan

        </div>
        @endcan

        {{-- ── Năng lực số — hiển thị cho mọi user đã login ───────────────────── --}}
        @auth
        <p class="section-title" style="margin-top:16px;">Năng lực số</p>
        <div class="nav-group">

            {{-- Personal items — không cần permission, chỉ cần auth + feature gate --}}
            <a href="{{ route('backend.workforce.me') }}"
               class="nav-link {{ request()->routeIs('backend.workforce.me') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="nav-label">Hồ sơ Digital Twin</span>
            </a>

            <a href="{{ route('backend.sandbox.index') }}"
               class="nav-link {{ request()->routeIs('backend.sandbox.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span class="nav-label">AI Sandbox</span>
            </a>

            <a href="{{ route('backend.certifications.index') }}"
               class="nav-link {{ request()->routeIs('backend.certifications.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span class="nav-label">Chứng nhận AI</span>
            </a>

            <a href="{{ route('backend.career-pathway.index') }}"
               class="nav-link {{ request()->routeIs('backend.career-pathway.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="nav-label">Lộ trình nghề nghiệp</span>
            </a>

            <a href="{{ route('backend.ai-impact.index') }}"
               class="nav-link {{ request()->routeIs('backend.ai-impact.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <span class="nav-label">AI Impact Tracker</span>
            </a>

            <a href="{{ route('passport.index') }}"
               class="nav-link {{ request()->routeIs('passport.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <span class="nav-label">Career Journal</span>
            </a>

            <a href="{{ route('campaigns.index') }}"
               class="nav-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="nav-label">Assessment Marketplace</span>
            </a>

            {{-- Admin items — chỉ hiển thị khi có assessment.results hoặc assessment.config --}}
            @if(auth()->user()?->hasAnyPermission(['assessment.results','assessment.config']))
            <a href="{{ route('backend.workforce.index') }}"
               class="nav-link {{ request()->routeIs('backend.workforce.index') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="nav-label">Workforce Admin</span>
            </a>
            <a href="{{ route('backend.workforce.unit-dashboard') }}"
               class="nav-link {{ request()->routeIs('backend.workforce.unit-dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span class="nav-label">Dashboard đơn vị</span>
            </a>
            <a href="{{ route('campaigns.admin.index') }}"
               class="nav-link {{ request()->routeIs('campaigns.admin.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span class="nav-label">Campaign Admin</span>
            </a>
            @endif

            @can('assessment.config')
            <a href="{{ route('backend.sandbox-admin.index') }}"
               class="nav-link {{ request()->routeIs('backend.sandbox-admin.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="nav-label">Sandbox Admin</span>
            </a>
            <a href="{{ route('backend.career-pathway-admin.index') }}"
               class="nav-link {{ request()->routeIs('backend.career-pathway-admin.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <span class="nav-label">Pathway Admin</span>
            </a>
            <a href="{{ route('backend.roadmap-admin.index') }}"
               class="nav-link {{ request()->routeIs('backend.roadmap-admin.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                <span class="nav-label">Roadmap KC Admin</span>
            </a>
            <a href="{{ route('backend.certs-admin.index') }}"
               class="nav-link {{ request()->routeIs('backend.certs-admin.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span class="nav-label">Certs Admin</span>
            </a>
            @endcan

        </div>
        @endauth

        @if(auth()->user()?->hasAnyPermission(['sop.view','sop.view_related','sop.create','sop.create_hr','sop.edit','sop.approve','sop.config']))
        <p class="section-title" style="margin-top:16px;">Vận hành</p>
        <div class="nav-group">

            <details {{ request()->routeIs('backend.sop.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.sop.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    <span class="nav-label">Quy trình SOP</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.sop.index') }}" class="sub-link {{ request()->routeIs('backend.sop.index') ? 'active' : '' }}">Danh sách SOP</a>
                    @if(auth()->user()?->hasAnyPermission(['sop.create','sop.create_hr','sop.config']))
                    <a href="{{ route('backend.sop.create') }}" class="sub-link {{ request()->routeIs('backend.sop.create') ? 'active' : '' }}">Tạo SOP mới</a>
                    @endif
                </div>
            </details>

        </div>
        @endif

        <p class="section-title" style="margin-top:16px;">Tài khoản</p>
        <div class="nav-group">

            <details {{ request()->routeIs('backend.users.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.users.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="nav-label">Tài khoản</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('backend.users.index') }}" class="sub-link {{ request()->routeIs('backend.users.index') ? 'active' : '' }}">Danh sách tài khoản</a>
                    <a href="{{ route('backend.users.create') }}" class="sub-link {{ request()->routeIs('backend.users.create') ? 'active' : '' }}">Thêm tài khoản</a>
                </div>
            </details>

            <a href="{{ route('backend.notifications.index') }}"
               class="nav-link {{ request()->routeIs('backend.notifications.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span class="nav-label">Thông báo</span>
            </a>

        </div>

        <p class="section-title" style="margin-top:16px;">Hệ thống</p>
        <div class="nav-group">

            @can('activitylog.view')
            <details {{ request()->routeIs('activitylog.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('activitylog.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="nav-label">Activity Log</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('activitylog.index') }}"
                       class="sub-link {{ request()->routeIs('activitylog.index') ? 'active' : '' }}">
                       Danh sách log
                    </a>
                </div>
            </details>
            @endcan

            @can(\App\Enums\PermissionEnum::SUBSCRIPTION_VIEW->value)
            <details {{ request()->routeIs('subscription.portal.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('subscription.portal.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    <span class="nav-label">Billing</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('subscription.portal.billing') }}"
                       class="sub-link {{ request()->routeIs('subscription.portal.billing') ? 'active' : '' }}">
                        Subscription
                    </a>
                    <a href="{{ route('subscription.portal.plans') }}"
                       class="sub-link {{ request()->routeIs('subscription.portal.plans') ? 'active' : '' }}">
                        Xem các gói
                    </a>
                    @can(\App\Enums\PermissionEnum::SUBSCRIPTION_BILLING->value)
                    <a href="{{ route('subscription.portal.invoices') }}"
                       class="sub-link {{ request()->routeIs('subscription.portal.invoices*') ? 'active' : '' }}">
                        Hóa đơn
                    </a>
                    @endcan
                </div>
            </details>
            @endcan

            @can(\App\Enums\PermissionEnum::SUBSCRIPTION_ADMIN->value)
            <details {{ request()->routeIs('subscription.admin.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('subscription.admin.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    <span class="nav-label">Subscription</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('subscription.admin.plans.index') }}"
                       class="sub-link {{ request()->routeIs('subscription.admin.plans.*') ? 'active' : '' }}">
                        Quản lý Plans
                    </a>
                    <a href="{{ route('subscription.admin.subscriptions.index') }}"
                       class="sub-link {{ request()->routeIs('subscription.admin.subscriptions.*') ? 'active' : '' }}">
                        Subscriptions
                    </a>
                    <a href="{{ route('subscription.admin.invoices.index') }}"
                       class="sub-link {{ request()->routeIs('subscription.admin.invoices.*') ? 'active' : '' }}">
                        Invoices
                    </a>
                </div>
            </details>
            @endcan

            @can(\App\Enums\PermissionEnum::OCOP_RUBRIC_MANAGE->value)
            <details {{ request()->routeIs('ocop_rubric.admin.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('ocop_rubric.admin.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9a12.02 12.02 0 00-.382-3.016z"/></svg>
                    <span class="nav-label">Bộ tiêu chí OCOP</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('ocop_rubric.admin.product-groups.index') }}"
                       class="sub-link {{ request()->routeIs('ocop_rubric.admin.product-groups.*') ? 'active' : '' }}">
                        Danh mục Bộ sản phẩm
                    </a>
                </div>
            </details>
            @endcan

            @can(\App\Enums\PermissionEnum::WORKFLOW_MONITOR->value)
            <details {{ request()->routeIs('workflows.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('workflows.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span class="nav-label">Workflow</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('workflows.index') }}"
                       class="sub-link {{ request()->routeIs('workflows.index') ? 'active' : '' }}">
                        Danh sách workflow
                    </a>
                    @can(\App\Enums\PermissionEnum::WORKFLOW_EDIT->value)
                    <a href="{{ route('workflows.create') }}"
                       class="sub-link {{ request()->routeIs('workflows.create') ? 'active' : '' }}">
                        Tạo workflow mới
                    </a>
                    @endcan
                </div>
            </details>
            @endcan

            <details {{ request()->routeIs('backend.settings.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('backend.settings.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="nav-label">Cài đặt</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="#" class="sub-link">Chung</a>
                    <a href="#" class="sub-link">Thanh toán</a>
                    <a href="#" class="sub-link">Vận chuyển</a>
                    <a href="#" class="sub-link">Email</a>
                </div>
            </details>

        @if(auth()->user()?->hasAnyPermission(['reports.full','reports.hr','reports.team','reports.personal','reports.ops','reports.shared']))
        <p class="section-title" style="margin-top:16px;">Phân tích</p>
        <div class="nav-group">
            <details {{ request()->routeIs('report.*') ? 'open' : '' }}>
                <summary class="nav-summary {{ request()->routeIs('report.*') ? 'active' : '' }}">
                    <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span class="nav-label">Báo cáo</span>
                    <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
                </summary>
                <div class="sub-menu">
                    <a href="{{ route('report.index') }}" class="sub-link {{ request()->routeIs('report.index') ? 'active' : '' }}">Tổng quan</a>
                    @if(auth()->user()?->hasAnyPermission(['reports.hr','reports.full']))
                    <a href="{{ route('report.hr.headcount') }}" class="sub-link {{ request()->routeIs('report.hr.*') ? 'active' : '' }}">Nhân sự (HR)</a>
                    <a href="{{ route('report.competency.index') }}" class="sub-link {{ request()->routeIs('report.competency.*') ? 'active' : '' }}">Năng lực số</a>
                    @endif
                    @if(auth()->user()?->hasAnyPermission(['reports.team','reports.personal','reports.full']))
                    <a href="{{ route('report.sales.pipeline') }}" class="sub-link {{ request()->routeIs('report.sales.*') ? 'active' : '' }}">Sales & CRM</a>
                    @endif
                    @if(auth()->user()?->hasAnyPermission(['reports.ops','reports.full']))
                    <a href="{{ route('report.project.index') }}" class="sub-link {{ request()->routeIs('report.project.*') ? 'active' : '' }}">Dự án</a>
                    <a href="{{ route('report.kpi.cycle') }}" class="sub-link {{ request()->routeIs('report.kpi.*') ? 'active' : '' }}">KPI</a>
                    @endif
                </div>
            </details>
        </div>
        @endif

        </div>

        @auth
        <p class="section-title" style="margin-top:16px;">Triển khai</p>
        <div class="nav-group">
            <a href="{{ route('deployment.landing') }}"
               class="nav-link {{ request()->routeIs('deployment.landing') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="nav-label">Hub triển khai</span>
            </a>

            @if(isset($activeVerticals) && $activeVerticals->isNotEmpty())
            @foreach($activeVerticals as $av)
            <a href="{{ route('deployment.dashboard', ['vertical' => $av->code()]) }}"
               class="nav-link nav-link-sub {{ request()->routeIs('deployment.*') && request()->route('vertical') === $av->code() ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="nav-label">{{ $av->label() }}</span>
            </a>
            @endforeach
            @endif
        </div>
        @endauth

        </div>
    </nav>
</aside>
