<?php

namespace Modules\Organization\Policies;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Modules\Organization\Models\OrganizationMember;

/**
 * Authorization policy cho Organization resource.
 *
 * Chỉ System_Admin và super-admin mới được phép CRUD qua backend panel.
 * Owner của org có thể edit/view org của mình (dùng cho frontend settings).
 *
 * Type-hint dùng class GỐC `App\Shared\Tenancy\Models\Organization` (không phải subclass
 * `Modules\Organization\Models\Organization`) — Policy này được đăng ký cho CẢ 2 class
 * (OrganizationServiceProvider::boot()) vì RegisterOrganizationAction (Modules/Auth) và
 * config/approval.php dùng class gốc trực tiếp, còn OrganizationController (module này) dùng
 * subclass. Vì PHP không coi "base type-hint nhận được subclass instance" là vấn đề (subclass
 * IS-A base), dùng type-hint gốc là lựa chọn AN TOÀN DUY NHẤT chấp nhận được cả 2; ngược lại
 * (type-hint subclass) sẽ ném TypeError khi Gate gọi method này với 1 instance base class thật
 * (đúng bug đã gặp khi RegisterOrganizationAction tạo Organization mới rồi content_moderator
 * duyệt). Vì vậy KHÔNG gọi trực tiếp $organization->members() (chỉ có ở subclass) — query
 * thẳng OrganizationMember để hoạt động đúng với cả 2 class.
 */
class OrganizationPolicy
{
    /** Super-admin bypass toàn bộ — được xử lý bởi Gate::before() trong AppServiceProvider. */

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super-admin', 'System_Admin']);
    }

    public function view(User $user, Organization $organization): bool
    {
        if ($user->hasRole(['super-admin', 'System_Admin']) || $user->isContentModerator()) {
            return true;
        }

        // Owner của tổ chức được xem thông tin của tổ chức mình
        return $organization->owner_id === $user->id
            || OrganizationMember::where('organization_id', $organization->id)->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super-admin', 'System_Admin']);
    }

    public function update(User $user, Organization $organization): bool
    {
        if ($user->hasRole(['super-admin', 'System_Admin'])) {
            return true;
        }

        // Owner có thể edit tổ chức của mình
        return $organization->owner_id === $user->id;
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->hasRole(['super-admin', 'System_Admin']);
    }

    // ── Approval workflow — Platform Approval Gateway (Hà Kiên nội bộ) ─────────────────
    // Hồ sơ doanh nghiệp mới đăng ký/chỉnh sửa PHẢI qua đội kiểm duyệt tập trung của Hà Kiên
    // (content_moderator), không phải chủ doanh nghiệp tự duyệt hồ sơ của chính mình.

    public function submitForApproval(User $user, Organization $organization): bool
    {
        return $organization->owner_id === $user->id || $user->hasRole(['super-admin', 'System_Admin']);
    }

    public function approve(User $user, Organization $organization): bool
    {
        return $user->isContentModerator();
    }

    public function reject(User $user, Organization $organization): bool
    {
        return $user->isContentModerator();
    }

    public function publishApproval(User $user, Organization $organization): bool
    {
        return $user->isContentModerator();
    }

    public function archiveApproval(User $user, Organization $organization): bool
    {
        return $user->isContentModerator();
    }
}
