<?php

namespace Modules\RealEstate\Policies;

use App\Models\User;
use Modules\RealEstate\Models\RealEstateListing;

/**
 * spec/RealEstateForSale_Technical_Specification.md §6 — copy đúng cấu trúc ProductPolicy
 * (Modules/Product/app/Policies/ProductPolicy.php). CRUD gate qua permission Lớp B
 * `real_estate.*` (Organization); duyệt nội dung (submitForApproval/approve/reject/
 * publishApproval/archiveApproval) tách biệt hoàn toàn — `platform_content_moderator` (Lớp A,
 * dùng chung xuyên-domain) duyệt, Organization KHÔNG tự publish.
 */
class RealEstateListingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('real_estate.view');
    }

    public function view(User $user, RealEstateListing $listing): bool
    {
        return $user->can('real_estate.view') || $user->isPlatformContentModerator() || $user->isPlatformViewer();
    }

    public function create(User $user): bool
    {
        return $user->can('real_estate.create');
    }

    /**
     * Trang edit CŨNG là trang duy nhất để xem chi tiết 1 tin (không có route "show" riêng
     * cho admin) — content_moderator cần load được trang này để xem nội dung trước khi
     * duyệt/từ chối (cùng nguyên tắc ProductPolicy::update()).
     */
    public function update(User $user, RealEstateListing $listing): bool
    {
        return $user->can('real_estate.edit') || $user->isPlatformContentModerator() || $user->isPlatformViewer();
    }

    public function delete(User $user, RealEstateListing $listing): bool
    {
        return $user->can('real_estate.delete');
    }

    // ── Approval workflow — dùng chung Modules\Approval, KHÔNG viết lại state machine ──

    public function submitForApproval(User $user, RealEstateListing $listing): bool
    {
        return $user->can('real_estate.edit');
    }

    public function approve(User $user, RealEstateListing $listing): bool
    {
        return $user->isPlatformContentModerator();
    }

    public function reject(User $user, RealEstateListing $listing): bool
    {
        return $user->isPlatformContentModerator();
    }

    public function publishApproval(User $user, RealEstateListing $listing): bool
    {
        return $user->isPlatformContentModerator();
    }

    public function archiveApproval(User $user, RealEstateListing $listing): bool
    {
        return $user->isPlatformContentModerator();
    }
}
