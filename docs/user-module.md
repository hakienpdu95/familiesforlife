# User Module — Developer Reference

`Modules/User` is the only place admin accounts are managed. Every managed account is a
**Platform account**: `account_type = platform`, exactly one role from
`App\Enums\RoleEnum`.

Public sign-up (`/register`, `Modules\Auth\Actions\Auth\RegisterUserAction`) and social login create
`free` accounts with no role; they are not listed or managed here.

## Routes (`Modules/User/routes/web.php`)

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `dashboard/users` | `backend.users.index` | list page |
| GET | `backend/api/users` | `backend.api.users` | Tabulator JSON (`search`, `role`, `status`, `sort[0]`, `page`, `size`) |
| GET | `dashboard/users/create` | `backend.users.create` | form |
| POST | `dashboard/users` | `backend.users.store` | `CreateAccountAction` |
| GET | `dashboard/users/{user}/edit` | `backend.users.edit` | form |
| PUT/PATCH | `dashboard/users/{user}` | `backend.users.update` | `UpdateAccountAction` |
| DELETE | `dashboard/users/{user}` | `backend.users.destroy` | `DeactivateAccountAction` (never deletes) |
| PATCH | `dashboard/users/{user}/activate` | `backend.users.activate` | `ActivateAccountAction` |
| POST | `dashboard/users/{user}/password-reset-link` | `backend.users.password-reset-link` | `SendPasswordSetupLinkAction` |
| POST | `dashboard/users/bulk-deactivate` | `backend.users.bulk-deactivate` | per-row policy check, skips ineligible rows |

`dashboard/platform-users` permanently redirects to `dashboard/users`.

## Actions (`Modules/User/app/Actions`)

- `CreateAccountAction::run(CreateAccountData $data, string $via = 'admin_ui')` — creates the user with a
  random unusable password, `email_verified_at = now()`, assigns the role, fires `UserCreated` +
  `UserRoleAssigned`, then sends `AccountInvitationNotification` (set-password link).
- `UpdateAccountAction` — name + role only (email is not editable); fires `UserRoleAssigned` when the role changes.
- `DeactivateAccountAction` — `is_active = false`, clears `remember_token` and database sessions, logs.
- `ActivateAccountAction` — `is_active = true`, logs.
- `SendPasswordSetupLinkAction` — creates a token with the Fortify password broker and sends either the
  invitation mail or Laravel's standard `ResetPassword` mail. Tokens expire after
  `auth.passwords.users.expire` minutes (60); an expired invitation is recovered with "Quên mật khẩu"
  or by sending a new link from the admin.
- `AssignPlatformRoleAction` — `syncRoles()` at Spatie team `null` (Platform roles are not team-scoped).
- `ListAccountsAction` — paginated Platform accounts for the list API.

The CLI `php artisan platform:user-create {email} {name} {role}` validates through
`CreateAccountData` and calls `CreateAccountAction` (the account gets the same invitation email).

## Roles (`app/Enums/RoleEnum.php`)

`super-admin` plus 7 `platform_*` roles, with `label()`, `badgeClass()`, `assignable()`,
`assignableValues()` and `labels()`. `super-admin` is never assignable — `CreateAccountData` /
`UpdateAccountData` validate the role against `RoleEnum::assignableValues()` and the `roles` table.
New super-admins only come from `Modules/Auth/database/seeders/AuthDatabaseSeeder.php`.

Permissions for each role are granted by each module's own `*PermissionSeeder.php`;
`database/seeders/RolePermissionSeeder.php` only creates the permission rows listed in `PermissionEnum`.

## Authorization (`Modules\User\Policies\UserPolicy`)

| Ability | Rule |
|---|---|
| `viewAny`, `create` | actor is a global `super-admin` |
| `update` | super-admin actor, target is a Platform account and not a super-admin |
| `deactivate` | `update` + target active + target is not the actor |
| `activate` | `update` + target inactive |
| `sendPasswordResetLink` | `update` + target active |

`AppServiceProvider`'s `Gate::before` super-admin bypass is skipped when the ability's subject is a
`User` instance or `User::class`, so these rules apply to super-admins too.

## Frontend

- Views: `Modules/User/resources/views/{index,create,edit}.blade.php`
- JS entry `Modules/User/resources/assets/js/user.js` → `pages/user-index.js` (Tabulator list,
  `accountListPage` Alpine component) and `pages/user-form.js` (role TomSelect).
- SCSS entry `Modules/User/resources/assets/sass/user.scss`.
- Sidebar: one "Tài khoản" link in the "Hệ thống" section, gated by `@can('viewAny', User::class)`.

## Tests

`Modules/User/tests/Feature/UserAccountAdminTest.php`. Do not run the suite until `phpunit.xml`
points at a dedicated test database.
