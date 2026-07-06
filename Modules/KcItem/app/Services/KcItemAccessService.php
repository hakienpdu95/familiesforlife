<?php

namespace Modules\KcItem\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\KcItem\Enums\KcItemVisibility;
use Modules\KcItem\Models\KcAccessControl;
use Modules\KcItem\Models\KcItem;

class KcItemAccessService
{
    public function canView(User $user, KcItem $item): bool
    {
        // 1. Admin → ALLOW
        if ($user->hasRole('System_Admin')) {
            return true;
        }

        // 2. public → ALLOW
        if ($item->visibility === KcItemVisibility::Public) {
            return true;
        }

        // 3. private → owner only
        if ($item->visibility === KcItemVisibility::Private) {
            return $user->id === $item->owner_id;
        }

        // 4. internal → nhân viên hợp lệ
        if ($item->visibility === KcItemVisibility::Internal) {
            return $user->hasAnyRole(['CEO', 'Sales', 'Ops', 'Marketing', 'HR', 'AI_Operator', 'System_Admin', 'Viewer']);
        }

        // 5. restricted → kiểm tra kc_access_controls
        if ($item->visibility === KcItemVisibility::Restricted) {
            if ($user->id === $item->owner_id) {
                return true;
            }

            return $this->hasAccessControl($user, $item->id);
        }

        return false;
    }

    /** Lấy mức permission cao nhất của user với item, hoặc null nếu không có quyền. */
    public function getPermission(User $user, KcItem $item): ?string
    {
        if (! $this->canView($user, $item)) {
            return null;
        }

        if ($user->hasRole('System_Admin') || $user->id === $item->owner_id) {
            return 'manage';
        }

        if ($item->visibility !== KcItemVisibility::Restricted) {
            return 'view';
        }

        $controls = $this->getAccessControls($user, $item->id)->pluck('permission')->toArray();

        if (in_array('manage', $controls)) {
            return 'manage';
        }
        if (in_array('edit', $controls)) {
            return 'edit';
        }
        if (in_array('view', $controls)) {
            return 'view';
        }

        return null;
    }

    private function hasAccessControl(User $user, int $itemId): bool
    {
        return $this->getAccessControls($user, $itemId)->exists();
    }

    private function getAccessControls(User $user, int $itemId)
    {
        $roleIds = $user->roles()->pluck('id')->toArray();
        $deptIds = $this->getUserDepartmentIds($user);

        return KcAccessControl::where('item_id', $itemId)
            ->where(function ($q) use ($user, $roleIds, $deptIds) {
                $q->where(function ($sub) use ($user) {
                    $sub->where('target_type', 'user')->where('target_id', $user->id);
                })->orWhere(function ($sub) use ($roleIds) {
                    if (! empty($roleIds)) {
                        $sub->where('target_type', 'role')->whereIn('target_id', $roleIds);
                    }
                })->orWhere(function ($sub) use ($deptIds) {
                    if (! empty($deptIds)) {
                        $sub->where('target_type', 'dept')->whereIn('target_id', $deptIds);
                    }
                });
            })
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            });
    }

    private function getUserDepartmentIds(User $user): array
    {
        return DB::table('employee_departments')
            ->join('employees', 'employees.id', '=', 'employee_departments.employee_id')
            ->where('employees.user_id', $user->id)
            ->whereNull('employee_departments.left_at')
            ->pluck('employee_departments.department_id')
            ->toArray();
    }

    /**
     * Scope query để chỉ trả về items mà user có quyền xem.
     * Dùng cho ListKcItemsHandler.
     */
    public function applyVisibilityScope($query, User $user): void
    {
        if ($user->hasRole('System_Admin')) {
            return;
        }

        $roleIds = $user->roles()->pluck('id')->toArray();
        $deptIds = $this->getUserDepartmentIds($user);
        $userId  = $user->id;

        $query->where(function ($q) use ($userId, $roleIds, $deptIds) {
            // public: tất cả đều xem được
            $q->where('kc_items.visibility', KcItemVisibility::Public->value)
              // internal: user có role nhân viên
              ->orWhere('kc_items.visibility', KcItemVisibility::Internal->value)
              // private: chỉ owner
              ->orWhere(function ($sub) use ($userId) {
                  $sub->where('kc_items.visibility', KcItemVisibility::Private->value)
                      ->where('kc_items.owner_id', $userId);
              })
              // restricted: user/role/dept được cấp quyền hoặc là owner
              ->orWhere(function ($sub) use ($userId, $roleIds, $deptIds) {
                  $sub->where('kc_items.visibility', KcItemVisibility::Restricted->value)
                      ->where(function ($inner) use ($userId, $roleIds, $deptIds) {
                          $inner->where('kc_items.owner_id', $userId)
                                ->orWhereExists(function ($exists) use ($userId, $roleIds, $deptIds) {
                                    $exists->from('kc_access_controls')
                                        ->whereColumn('kc_access_controls.item_id', 'kc_items.id')
                                        ->where(function ($ac) use ($userId, $roleIds, $deptIds) {
                                            $ac->where(fn ($u) => $u->where('target_type', 'user')->where('target_id', $userId));
                                            if (! empty($roleIds)) {
                                                $ac->orWhere(fn ($r) => $r->where('target_type', 'role')->whereIn('target_id', $roleIds));
                                            }
                                            if (! empty($deptIds)) {
                                                $ac->orWhere(fn ($d) => $d->where('target_type', 'dept')->whereIn('target_id', $deptIds));
                                            }
                                        })
                                        ->where(function ($exp) {
                                            $exp->whereNull('expired_at')->orWhere('expired_at', '>', now());
                                        });
                                });
                      });
              });
        });
    }
}
