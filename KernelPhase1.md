app/
├── Shared/
│   ├── Tenancy/
│   │   ├── TenantContext.php          — set/get/resolve/flush/runForOrganization
│   │   ├── OrganizationScope.php      — global Eloquent scope lọc theo org_id
│   │   ├── Enums/
│   │   │   └── OrganizationStatus.php — active | suspended | inactive
│   │   ├── Models/
│   │   │   └── Organization.php       — model đầy đủ: slug, status, settings, owner
│   │   └── Traits/
│   │       └── BelongsToOrganization.php — auto-scope + auto-assign org_id khi create
│   ├── Contracts/
│   │   ├── QueryInterface.php         — marker interface (CQRS read)
│   │   ├── CommandInterface.php       — marker interface (CQRS write)
│   │   ├── QueryHandlerInterface.php  — handle(Query): mixed
│   │   └── CommandHandlerInterface.php— handle(Command): mixed
│   ├── Actions/
│   │   └── TenantAwareAction.php      — base action + AsAction + organization() helper
│   ├── Data/
│   │   └── BaseData.php               — base Spatie Data DTO
│   └── Support/
│       └── ActivityLogger.php         — wrapper activitylog, auto-inject org_id
├── Foundation/
│   ├── Models/
│   │   └── TenantAwareModel.php       — SoftDeletes + BelongsToOrganization + LogsActivity
│   ├── Jobs/
│   │   └── TenantAwareJob.php         — capture org_id khi dispatch, restore khi run
│   └── Exceptions/
│       ├── TenantNotSetException.php
│       └── UnauthorizedException.php
├── Http/Middleware/
│   └── IdentifyOrganization.php       — 4-layer: subdomain→header→auth user→session
├── Models/
│   └── User.php                       — HasRoles + organization() + current_organization_id accessor
└── Providers/
    ├── AppServiceProvider.php         — Model::shouldBeStrict
    └── TenantServiceProvider.php      — flush context sau queue job

bootstrap/
├── app.php                            — wire IdentifyOrganization vào web group
└── providers.php                      — đăng ký TenantServiceProvider

database/migrations/extensions/
├── 100001_add_fields_to_organizations_table.php  — slug, status, owner_id, settings
└── 100002_add_organization_id_to_users_table.php — organization_id FK