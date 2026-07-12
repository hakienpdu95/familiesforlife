<?php

namespace Modules\Product\Policies;

use App\Models\User;
use Modules\Product\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('product.view') || $user->isContentModerator();
    }

    public function create(User $user): bool
    {
        return $user->can('product.create');
    }

    /**
     * Trang edit CŨNG là trang duy nhất để xem chi tiết sản phẩm (không có route "show"
     * riêng) — content_moderator cần load được trang này để xem nội dung trước khi
     * duyệt/từ chối (link "Xem & duyệt" ở dashboard trỏ thẳng vào đây). Cho phép luôn cả
     * quyền sửa (không tách view/edit riêng) — đơn giản hoá phạm vi cho MVP; nếu sau này cần
     * moderator chỉ xem không sửa được, tách thành 1 trang review riêng.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->can('product.edit') || $user->isContentModerator();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('product.delete');
    }

    // ── Approval workflow — Platform Approval Gateway ──────────────────────────────────
    // Hệ thống nội bộ Hà Kiên: MỌI yêu cầu duyệt nội dung sản phẩm (approve/reject/publish/
    // archive) do đội kiểm duyệt tập trung của Hà Kiên xử lý (role content_moderator, tài
    // khoản organization_id=null), KHÔNG phải tài khoản của doanh nghiệp tự duyệt — dù họ có
    // product.publish hay không. Doanh nghiệp chỉ tự submitForApproval (gửi duyệt), không tự
    // approve/publish được nữa. Đặt tên publishApproval/archiveApproval (không phải publish/
    // archive trần) để tránh trùng ý nghĩa với ability khác có thể thêm sau này trên Product.

    public function submitForApproval(User $user, Product $product): bool
    {
        return $user->can('product.edit');
    }

    public function approve(User $user, Product $product): bool
    {
        return $user->isContentModerator();
    }

    public function reject(User $user, Product $product): bool
    {
        return $user->isContentModerator();
    }

    public function publishApproval(User $user, Product $product): bool
    {
        return $user->isContentModerator();
    }

    public function archiveApproval(User $user, Product $product): bool
    {
        return $user->isContentModerator();
    }
}
