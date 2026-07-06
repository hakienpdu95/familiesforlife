# User Module — Architecture & Developer Reference

> Module path: `Modules/User/`  
> Last updated: 2026-05-21  
> Stack: Laravel 13 · PHP 8.4 · Spatie Permission v7 (teams) · NWIDART Modules · Alpine.js 3 · DaisyUI 5

---

## Table of Contents

1. [Overview](#1-overview)
2. [Directory Structure](#2-directory-structure)
3. [Routes](#3-routes)
4. [Controllers](#4-controllers)
5. [Actions (Write Path)](#5-actions-write-path)
6. [DTOs — Data Objects](#6-dtos--data-objects)
7. [CQRS Query Layer (Read Path)](#7-cqrs-query-layer-read-path)
8. [API Resource](#8-api-resource)
9. [Policy & Authorization](#9-policy--authorization)
10. [Events & Listeners](#10-events--listeners)
11. [Notifications](#11-notifications)
12. [RoleEnum — System Roles](#12-roleenum--system-roles)
13. [Permission Matrix](#13-permission-matrix)
14. [Spatie Teams — Critical Pattern](#14-spatie-teams--critical-pattern)
15. [Frontend Architecture](#15-frontend-architecture)
16. [Database Relationships](#16-database-relationships)
17. [Known Constraints & Invariants](#17-known-constraints--invariants)
18. [Development Guidelines](#18-development-guidelines)

---

## 1. Overview

The User module manages all lifecycle operations for user accounts within the multi-tenant SaaS platform. Each user belongs to exactly one `Organization`, and their system role (CEO, HR, Sales, etc.) is scoped to that organization via Spatie's Teams feature.

**Responsibilities:**
- CRUD for users (create, list, edit, delete)
- System role assignment and enforcement (Spatie Permission, team-scoped)
- Organization membership record management
- Welcome email with credentials
- Activity logging for audit trail
- Index page with server-side filtering, pagination, and sort via Tabulator

**Out of scope:** Authentication, password reset, profile editing by the user themselves — handled by the `Auth` module and profile routes.

---

## 2. Directory Structure

```
Modules/User/
├── app/
│   ├── Actions/
│   │   ├── StoreUserAction.php         # Creates user + role + org membership
│   │   ├── UpdateUserAction.php        # Updates user + role + org membership
│   │   └── DestroyUserAction.php       # Deletes org membership then user
│   ├── Data/
│   │   ├── StoreUserData.php           # DTO + server-side validation (create)
│   │   └── UpdateUserData.php          # DTO + server-side validation (edit)
│   ├── Events/
│   │   ├── UserCreated.php
│   │   └── UserRoleAssigned.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── UserController.php      # Web CRUD controller
│   │   │   └── Api/
│   │   │       └── UserApiController.php  # JSON endpoint for Tabulator
│   │   └── Resources/
│   │       └── UserListResource.php    # Transforms User → JSON row
│   ├── Listeners/
│   │   ├── LogUserCreated.php
│   │   └── LogUserRoleAssigned.php
│   ├── Notifications/
│   │   └── WelcomeUserNotification.php
│   ├── Policies/
│   │   └── UserPolicy.php
│   ├── Providers/
│   │   ├── UserServiceProvider.php
│   │   ├── EventServiceProvider.php
│   │   └── RouteServiceProvider.php
│   └── Queries/
│       ├── ListUsersQuery.php          # Value object (query parameters)
│       └── ListUsersHandler.php        # Executes the DB query
├── resources/views/
│   ├── index.blade.php                 # Tabulator-powered list page
│   ├── create.blade.php                # Flat form with Alpine.js validation
│   └── edit.blade.php                  # Flat edit form with role presets
├── routes/
│   └── web.php
└── database/seeders/
    └── UserDatabaseSeeder.php
```

---

## 3. Routes

**File:** `Modules/User/routes/web.php`

| Method | URI | Name | Controller | Notes |
|--------|-----|------|------------|-------|
| GET | `/dashboard/users` | `backend.users.index` | `UserController@index` | Renders Tabulator shell |
| GET | `/dashboard/users/create` | `backend.users.create` | `UserController@create` | Flat create form |
| POST | `/dashboard/users` | `backend.users.store` | `UserController@store` | Submits create form |
| GET | `/dashboard/users/{user}/edit` | `backend.users.edit` | `UserController@edit` | Flat edit form |
| PUT/PATCH | `/dashboard/users/{user}` | `backend.users.update` | `UserController@update` | Submits edit form |
| DELETE | `/dashboard/users/{user}` | `backend.users.destroy` | `UserController@destroy` | Delete action |
| GET | `/backend/api/users` | `backend.api.users` | `UserApiController@index` | JSON for Tabulator |

All routes require `auth` middleware. No `show` route exists (detail is inline in edit).

---

## 4. Controllers

### `UserController`

Standard resource controller. Thin by design — business logic lives in Actions.

**Key private helpers:**

#### `getOrganizationsFor(User $actor)`
Returns organizations available for selection on create/edit forms.
- Admin/super-admin → all active organizations, alphabetical
- HR → only their own organization

#### `buildRolesFor(User $actor): array`
Returns role options for the role-selector UI.
- Admin/super-admin → all 8 roles
- HR → all roles **except** `ceo` and `system_admin`

#### `guardRoleEscalation(User $actor, ?string $requestedRole): void`
Called in `store()` and `update()`. Aborts with 403 if a non-admin tries to assign `ceo` or `system_admin`.

#### `resolveUserRole(User $user): string`
Gets the user's current system role using the correct team context.  
**Critical:** Must temporarily set `setPermissionsTeamId($user->organization_id)` and `unsetRelation('roles')` before calling `getRoleNames()`. See [Section 14](#14-spatie-teams--critical-pattern).

#### `permissionMatrix(): array`
Returns the static permission matrix array (11 modules × 8 roles). Used to populate the permission preview table on create/edit forms.

### `UserApiController`

JSON-only controller serving Tabulator on the index page.

- Resolves tenant scope: admin can pass `organization_id` filter; non-admin is locked to their own org via `TenantContext`.
- Builds `ListUsersQuery` DTO and delegates to `ListUsersHandler`.
- Returns `{ data: [...], last_page: N, total: N }`.

---

## 5. Actions (Write Path)

All actions use `Lorisleiva\Actions\Concerns\AsAction` and run inside `DB::transaction()`.

### `StoreUserAction`

**`handle(StoreUserData $data): User`**

1. Calls `guardAgainstConflict()` — throws `DomainException` if email belongs to a different org (race-condition guard; normal duplicate is blocked by DTO validation).
2. Creates `User` record.
3. Creates `OrganizationMember` record (`owner` if CEO/system_admin, otherwise `member`).
4. Assigns Spatie role inside correct team context (save/restore pattern).
5. Fires `UserCreated` and `UserRoleAssigned` events.
6. Optionally dispatches `WelcomeUserNotification` (queued).

### `UpdateUserAction`

**`handle(User $user, UpdateUserData $data): User`**

1. Updates user fields. Password only updated if non-empty.
2. Updates or creates `OrganizationMember`. Never downgrades an `owner`.
3. Syncs Spatie role inside correct team context (save/restore pattern).
4. Fires `UserRoleAssigned` only if role actually changed.

### `DestroyUserAction`

**`handle(User $user): string`**

Deletes org membership records, deletes user, returns user name for the flash message.

---

## 6. DTOs — Data Objects

Both use `Spatie\LaravelData\Data` with `validateAndCreate($request->all())`.

### `StoreUserData`

| Field | Type | Validation |
|-------|------|-----------|
| `name` | `string` | required, max:255 |
| `email` | `string` | required, email:rfc,dns, max:255, unique:users |
| `password` | `string` | required, confirmed, min:8, letters, mixedCase, numbers |
| `organization_id` | `int` | required, exists:organizations,id |
| `department` | `?string` | nullable, max:50 |
| `system_role` | `string` | required, in:[all RoleEnum values] |
| `is_active` | `bool` | default: true |
| `send_welcome_email` | `bool` | default: false |

### `UpdateUserData`

| Field | Type | Validation |
|-------|------|-----------|
| `name` | `string` | required, max:255 |
| `email` | `string` | required, email:rfc, max:255, unique:users (ignore current) |
| `password` | `?string` | nullable, confirmed, min:8, letters, mixedCase, numbers |
| `organization_id` | `int` | required, exists:organizations,id |
| `department` | `?string` | nullable, max:50 |
| `system_role` | `string` | required, in:[all RoleEnum values] |
| `is_active` | `bool` | default: false |

`UpdateUserData` uses `Rule::unique('users', 'email')->ignore($currentId)` where `$currentId` is resolved from `request()->route('user')?->id`.

---

## 7. CQRS Query Layer (Read Path)

### `ListUsersQuery` (Value Object)

Immutable query parameters. Constructed in `UserApiController`.

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `page` | `int` | 1 | Current page |
| `perPage` | `int` | 25 | Rows per page (5–100) |
| `sortField` | `string` | `created_at` | Must be in SORTABLE whitelist |
| `sortDir` | `string` | `desc` | `asc` or `desc` |
| `search` | `?string` | null | OR across name, email |
| `organizationId` | `?int` | null | null = all (admin only) |
| `role` | `?string` | null | RoleEnum value |
| `status` | `?string` | null | `'1'` = active, `'0'` = inactive |
| `dateFrom` | `?string` | null | ISO date, inclusive |
| `dateTo` | `?string` | null | ISO date, inclusive |

### `ListUsersHandler`

Builds a `User::query()` with chained constraints:

- Base: `whereNotNull('users.organization_id')` (excludes super-admin)
- `with(['organization:id,name', 'organizationMembership'])` — eager load
- Tenant scope → `where('users.organization_id', $orgId)`
- Search → `where(name LIKE) OR where(email LIKE)`
- Role filter → raw `EXISTS` against `model_has_roles + roles` tables (team-context-independent)
- Status filter → `where('users.is_active', bool)`
- Date range → `whereDate` on `created_at`
- Sort: `organization_name` requires a `leftJoin`; all others use `orderBy('users.*')`

**SORTABLE whitelist:** `name`, `email`, `department`, `is_active`, `created_at`, `organization_name`

**Why raw EXISTS for role filter:**  
Spatie's `whereHasRole()` respects the current team context, which would miss users from other orgs when called from a super-admin context. The raw EXISTS query directly joins `model_has_roles` and `roles` tables, bypassing team context entirely.

---

## 8. API Resource

### `UserListResource`

Transforms a `User` model into a JSON object for the Tabulator row.

| Key | Source | Notes |
|-----|--------|-------|
| `id` | `$this->id` | |
| `name` | `$this->name` | |
| `email` | `$this->email` | |
| `department` | `$this->department` | |
| `avatar_url` | dicebear initials API | Seed = user name, indigo background |
| `organization_id` | `$this->organization_id` | |
| `organization_name` | `$this->organization->name` | Eager loaded |
| `role` | `resolveSystemRole()` | RoleEnum value string |
| `role_label` | `RoleEnum::tryFrom($role)->label()` | Human-readable label |
| `is_active` | `$this->is_active` | bool |
| `status_label` | `'Hoạt động' / 'Vô hiệu'` | |
| `created_at` | `d/m/Y` format | |
| `edit_url` | `route('backend.users.edit', ...)` | |
| `delete_url` | `route('backend.users.destroy', ...)` | |

`resolveSystemRole()` uses the canonical save/restore pattern (see Section 14) to get the role under the target user's org context, not the viewer's.

---

## 9. Policy & Authorization

**File:** `Modules/User/app/Policies/UserPolicy.php`

Gate bypass: `super-admin` role is granted all permissions via `Gate::before()` in `AppServiceProvider`.

| Method | Who can | Notes |
|--------|---------|-------|
| `viewAny` | super-admin, system_admin, ceo, hr | Index page access |
| `view` | super-admin, system_admin (all orgs); ceo, hr (same org only) | |
| `create` | super-admin, system_admin, hr | |
| `update` | super-admin, system_admin (all); hr (same org) | **Cannot edit yourself** |
| `delete` | super-admin, system_admin only | **Cannot delete yourself** |

**HR role restriction (controller-level, not policy):**  
Even if policy grants `create`/`update`, `guardRoleEscalation()` in `UserController` aborts(403) if an HR user tries to assign `ceo` or `system_admin` roles.

---

## 10. Events & Listeners

**File:** `Modules/User/app/Providers/EventServiceProvider.php`

| Event | Payload | Listener | Effect |
|-------|---------|----------|--------|
| `UserCreated` | `User $user` | `LogUserCreated` | `activity()->on($user)->log('user.created')` |
| `UserRoleAssigned` | `User $user, string $role` | `LogUserRoleAssigned` | Activity log entry for role change |

`UserRoleAssigned` is fired on creation (always) and on update (only when role actually changed).

Activity log entries are searchable via Spatie Activity Log — accessible in any future audit trail UI.

---

## 11. Notifications

### `WelcomeUserNotification`

**File:** `Modules/User/app/Notifications/WelcomeUserNotification.php`

- Implements `ShouldQueue` — delivered asynchronously via the queue worker.
- Constructor: `__construct(string $password, string $role)` — receives the plain-text password (only available at creation time) and role value.
- Sends a mail notification with: login URL, email, temporary password, and role label.
- Only sent when `StoreUserData::send_welcome_email === true`.

To process: `php artisan queue:listen`

---

## 12. RoleEnum — System Roles

**File:** `app/Enums/RoleEnum.php`

| Case | Value | Label |
|------|-------|-------|
| `CEO` | `ceo` | CEO / Founder |
| `SALES` | `sales` | Sales Team |
| `OPS` | `ops` | Operations |
| `MARKETING` | `marketing` | Marketing |
| `HR` | `hr` | HR / Admin Staff |
| `AI_OP` | `ai_operator` | AI Operator |
| `ADMIN` | `system_admin` | System Admin |
| `VIEWER` | `viewer` | Viewer / Partner |

**Methods:**
- `label(): string` — human-readable label (Vietnamese-friendly)
- `visibleModules(): array` — module slugs this role can access (controls sidebar rendering)

**Privileged roles** (cannot be assigned by HR): `ceo`, `system_admin`

---

## 13. Permission Matrix

Defined in `UserController::permissionMatrix()`. This is a static reference for the UI — it does not enforce permissions at the gate level (that is handled by `config/permissions.php` and Spatie policies).

| Module | CEO | Sales | Ops | Marketing | HR | AI Op | System Admin | Viewer |
|--------|-----|-------|-----|-----------|-----|-------|-------------|--------|
| CEO Dashboard | Full | — | Limited | — | — | Limited | Config | View ltd |
| CRM Leads | Full | Assigned | Limited | Source view | — | Limited | Config | — |
| Sales AI | Full | Use | — | Limited | — | Config prompt | Config | — |
| Tasks | Full | Assigned | Full team | Limited | HR tasks | Limited | Config | View ltd |
| SOP | Approve/View | View related | Create/Edit | View related | Create HR SOP | AI config | Config | View ltd |
| Workflow | Monitor | Limited | Monitor/Edit | Limited | Limited | AI config | Full config | — |
| Prompt Mgmt | View | — | — | — | — | Full | Admin config | — |
| AI Logs | View summary | — | Limited | — | — | Full | Full | — |
| Users | View | — | — | — | Limited | — | Full | — |
| Roles/Perms | — | — | — | — | — | — | Full | — |
| Reports | Full | Personal/team | Operations | Marketing | HR | AI usage | Full | Shared only |

---

## 14. Spatie Teams — Critical Pattern

**Configuration:** `config/permission.php` → `'teams' => true`, `'team_foreign_key' => 'organization_id'`

When teams mode is enabled, all Spatie role queries automatically add `WHERE model_has_roles.organization_id = <current_team_id>`. The current team ID is set globally via `setPermissionsTeamId()`.

### The Problem

A super-admin's request has `team_id = null`. When they view users from org #1, calling `$user->getRoleNames()` without changing the team context queries:
```sql
WHERE model_has_roles.organization_id IS NULL
```
But the user's role was stored with `organization_id = 1` → **empty result**.

### The Pattern (Required for ALL role reads/writes on behalf of another user)

```php
// READ — used in UserListResource, UserController::resolveUserRole()
$savedTeamId = getPermissionsTeamId();
try {
    $user->unsetRelation('roles');         // clear cached roles from wrong context
    setPermissionsTeamId($user->organization_id);
    $role = $user->getRoleNames()->first();
} finally {
    setPermissionsTeamId($savedTeamId);    // always restore
    $user->unsetRelation('roles');
}

// WRITE — used in StoreUserAction, UpdateUserAction
$prevTeamId = getPermissionsTeamId();
setPermissionsTeamId($data->organization_id);
$user->assignRole($data->system_role);     // or syncRoles()
setPermissionsTeamId($prevTeamId);
app(PermissionRegistrar::class)->forgetCachedPermissions();
```

### Why `unsetRelation('roles')`?

Laravel caches eager-loaded relations on the model instance. If `$user->roles` was loaded under the wrong team context (or a previous request's context), `getRoleNames()` returns stale data. Calling `unsetRelation('roles')` forces a fresh query with the current team ID.

### Seeder Requirement

Seeders must also set team context before assigning roles:
```php
setPermissionsTeamId($org->id);
foreach ($users as $user) {
    $user->syncRoles([$role]);
}
setPermissionsTeamId(null);
```

### Data Integrity

`model_has_roles.organization_id` must equal the user's `organization_id`. If this column is NULL for existing records, run the backfill migration:

```sql
UPDATE model_has_roles mhr
INNER JOIN users u ON u.id = mhr.model_id AND mhr.model_type = 'App\Models\User'
SET mhr.organization_id = u.organization_id
WHERE mhr.organization_id IS NULL AND u.organization_id IS NOT NULL;
```

---

## 15. Frontend Architecture

### Index Page (`index.blade.php`)

- **Tabulator** loaded as a backend bundle (lazy via `vite.config.backend.js`)
- Fetches JSON from `backend.api.users` with Tabulator's AJAX pagination
- Filter bar uses Alpine.js for reactive state; filters are serialized as query params on AJAX request
- Organization filter shown only if `$isAdmin` (passed from controller)
- Row actions (Edit/Delete) use URLs from the JSON response (`edit_url`, `delete_url`)

### Create Page (`create.blade.php`)

**Alpine.js component:** `createUserPage`

**Key data properties:**

| Property | Type | Description |
|----------|------|-------------|
| `name` | string | Bound to name field |
| `email` | string | Bound to email field |
| `password` | string | Bound to password field |
| `pwConfirm` | string | Confirmation field |
| `showPw` | bool | Toggle password visibility |
| `selectedOrg` | string | Organization TomSelect value |
| `selectedRole` | string | Role preset selection |
| `selectedRoleLabel` | string | Display label for selected role |
| `touched` | object | Per-field { name, email, password, organization_id, system_role } |
| `attempted` | bool | True after first submit attempt |
| `sendWelcomeEmail` | bool | Checkbox state |

**Key computed getters:**

- `errors` — object with per-field error strings (null if valid). Validates: name (non-empty, min 2), email (non-empty, regex), password (non-empty, ≥8, uppercase, lowercase, digit), organization_id, system_role
- `isValid` — true if all errors are null
- `pwChecks` — array of `{ label, ok }` for the password strength checklist
- `strength` — `{ pct, barCls, textCls, label }` computed from pwChecks pass count
- `avatarUrl` — dicebear initials URL, updated as user types their name

**Client-side validation flow:**
1. Each field has `@blur="touched.fieldName = true"` — errors appear on first blur
2. `showErr(field)` returns true when `touched[field] && errors[field]`
3. `handleSubmit($event)` sets `attempted = true`, marks all fields touched, calls `e.preventDefault()` if `!isValid`, then scrolls to first error
4. Form uses `@submit="handleSubmit($event)"` (not `.prevent`) — native POST proceeds when valid

**TomSelect initialization:**
```js
new TomSelect('#organization_id', { dropdownParent: 'body' });
new TomSelect('#system_role_hidden', { ... });
```
`dropdownParent: 'body'` is required because DaisyUI card containers use `overflow: hidden`.

### Edit Page (`edit.blade.php`)

**Alpine.js component:** `editUserPage`

Inherits all create-page patterns plus:

- **`OLD_ROLE`** blade variable → `selectedRole` initial value (resolved via `resolveUserRole()` in controller — see Section 14)
- **Role preset cards** — clicking highlights the card and sets `selectedRole`; on page load, `_setup()` calls `selectPreset(OLD_ROLE)` to highlight the current role
- **Permission matrix** — `currentMatrix` getter filters `permissionMatrix` array to rows/columns relevant to `selectedRole`, rendered as a reactive table
- **`sidebarModules` getter** — returns module list for selected role from `rolePresets` definition
- Password section: "leave blank to keep current password" hint; strength bar still active if user types

---

## 16. Database Relationships

```
organizations
  ├── id (PK)
  └── owner_id → users.id

users
  ├── id (PK)
  ├── organization_id → organizations.id
  ├── name, email, password, department, is_active
  └── email_verified_at

organization_members
  ├── organization_id → organizations.id
  ├── user_id → users.id
  ├── role: enum(owner, admin, manager, member)
  └── joined_at

model_has_roles          ← Spatie pivot table
  ├── role_id → roles.id
  ├── model_type = 'App\Models\User'
  ├── model_id → users.id
  └── organization_id → organizations.id   ← team key (CRITICAL)

roles                    ← Spatie roles table
  ├── id (PK)
  ├── name (RoleEnum value: ceo, sales, etc.)
  └── team_id → organizations.id
```

**Two parallel role systems:**

| System | Table | Values | Purpose |
|--------|-------|--------|---------|
| Org membership | `organization_members.role` | owner, admin, manager, member | Org-level access hierarchy |
| System roles | `model_has_roles` (Spatie) | RoleEnum values | Feature-level permissions |

`deriveOrgRole()` in Actions maps system role → org role: `ceo` and `system_admin` → `admin`, everything else → `member`.

---

## 17. Known Constraints & Invariants

1. **No self-editing:** `UserPolicy::update()` returns false when `$actor->id === $target->id`. Users must use the profile settings page to change their own data.

2. **No self-deletion:** `UserPolicy::delete()` returns false when `$actor->id === $target->id`.

3. **HR cannot assign privileged roles:** `UserController::guardRoleEscalation()` aborts(403) if an HR user attempts to assign `ceo` or `system_admin`. This is enforced at the controller level, not in the policy, because the policy gate only knows whether the actor _can edit_ the target, not which specific role they're trying to assign.

4. **Cross-org email conflict:** `StoreUserAction::guardAgainstConflict()` throws a `DomainException` if the email exists in a different org. The controller catches this and converts it to a flash error on the `email` field.

5. **Org owner is never downgraded:** `UpdateUserAction` checks `$membership->role !== OrganizationMember::ROLE_OWNER` before changing the org membership role.

6. **Email uniqueness is global:** A user's email must be unique across the entire `users` table, regardless of organization. There is no concept of the same email in two organizations.

7. **`model_has_roles.organization_id` must match `users.organization_id`:** If this diverges (e.g., seeder ran without team context), role reads will silently return empty. Always set team context before `assignRole`/`syncRoles`.

8. **Super-admin is not in `RoleEnum`:** The `super-admin` Spatie role is platform-level and exists outside the organization scope. Super-admin's `model_has_roles.organization_id` is NULL. This is intentional.

9. **`whereNotNull('users.organization_id')`** in `ListUsersHandler` excludes super-admin from the user list by design.

---

## 18. Development Guidelines

### Adding a New Field to Users

1. Write a migration adding the column to `users`.
2. Add the property to `StoreUserData` and `UpdateUserData` with appropriate validation attributes.
3. Update `StoreUserAction` and `UpdateUserAction` to include the field in the `create`/`fill` payload.
4. Add the field to `UserListResource::toArray()` if it should appear in the index.
5. Add the input to `create.blade.php` and `edit.blade.php`.
6. Add client-side validation logic to the Alpine.js `errors` getter if required.

### Adding a New System Role

1. Add a new case to `app/Enums/RoleEnum.php` with a value, `label()`, and `visibleModules()` entry.
2. Add the role to the appropriate rows in `UserController::permissionMatrix()`.
3. Run `php artisan db:seed --class="Modules\Auth\Database\Seeders\AuthDatabaseSeeder"` to create the Spatie role record.
4. Add a demo seed entry in `database/seeders/UserSeeder.php`.
5. Update `UserPolicy` if the new role needs different access rules.

### Adding a New Filter to the User List

1. Add the parameter to `ListUsersQuery` constructor.
2. Add the query constraint in `ListUsersHandler::handle()`.
3. Add the filter input to `index.blade.php`'s filter bar Alpine component.
4. Pass the new parameter in `UserApiController` when constructing `ListUsersQuery`.

### Extending the Permission Matrix

The matrix in `UserController::permissionMatrix()` is purely informational (UI preview). To enforce permissions, update `config/permissions.php` and run the permissions seeder. The matrix and the enforced permissions should be kept in sync manually.

### Writing Tests

- Role reads must use `setPermissionsTeamId($orgId)` before assertions in tests.
- Use `RefreshDatabase` and seed via `AuthDatabaseSeeder` to create Spatie role records.
- Create test users with `User::factory()->create(['organization_id' => $org->id])` then `$user->assignRole(RoleEnum::HR->value)` inside the correct team context.
- Avoid testing with `getRoleNames()` under null team context unless you explicitly intend to test the super-admin path.
