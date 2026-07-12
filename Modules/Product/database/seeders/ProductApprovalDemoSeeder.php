<?php

namespace Modules\Product\Database\Seeders;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Modules\Approval\Actions\ApproveAction;
use Modules\Approval\Actions\ArchiveAction;
use Modules\Approval\Actions\PublishAction;
use Modules\Approval\Actions\RejectAction;
use Modules\Approval\Actions\SubmitForApprovalAction;
use Modules\Product\Enums\ProductStatus;
use Modules\Product\Enums\ProductType;
use Modules\Product\Models\Product;

/**
 * Demo dữ liệu minh hoạ toàn bộ vòng đời module Approval (Draft/Pending/Approved/Published/
 * Archived + hành vi "đóng băng snapshot" khi sửa nội dung sau publish + reject có lý do) trên
 * Product — entity đầu tiên tích hợp HasApproval
 * (spec/Workflow_Approval_Technical_Specification.md).
 *
 * Chạy các Action THẬT (không insert thẳng DB) để ApprovalLog/public_snapshot sinh ra đúng như
 * luồng thật, do 2 user demo khác nhau thực hiện (marketing gửi duyệt, ceo duyệt/publish) —
 * đúng vai trò thật trong hệ thống phân quyền.
 *
 * KHÔNG nằm trong SystemDataSeeder (demo-only, giống OrganizationDemoSeeder) — chạy thủ công:
 *   php artisan db:seed --class="Modules\Product\Database\Seeders\ProductApprovalDemoSeeder"
 *
 * Idempotent — mỗi sản phẩm demo dùng slug cố định; nếu đã tồn tại thì bỏ qua toàn bộ (không
 * re-run transition trên sản phẩm đã seed, tránh InvalidTransitionException khi chạy lại).
 */
class ProductApprovalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'demo')->first();

        if (! $org) {
            $this->command->warn('  ⚠ Không tìm thấy Organization slug=demo — chạy OrganizationSeeder trước.');

            return;
        }

        $marketing = User::where('email', 'marketing@demo.test')->first();
        $ceo       = User::where('email', 'ceo@demo.test')->first();

        if (! $marketing || ! $ceo) {
            $this->command->warn('  ⚠ Không tìm thấy user demo (marketing@demo.test / ceo@demo.test) — chạy UserSeeder trước.');

            return;
        }

        TenantContext::set($org);
        $previousUser = Auth::user();

        $this->seedDraft($marketing);
        $this->seedPending($marketing);
        $this->seedApproved($marketing, $ceo);
        $this->seedPublished($marketing, $ceo);
        $this->seedPublishedThenEdited($marketing, $ceo);
        $this->seedArchived($marketing, $ceo);
        $this->seedRejectedOnce($marketing, $ceo);

        $previousUser ? Auth::login($previousUser) : Auth::logout();

        $this->command->info('  ✓ Product Approval demo data seeded (7 sản phẩm — Draft/Pending/Approved/Published/Published+sửa/Archived/Reject).');
    }

    /** @return array{0: Product, 1: bool} [$product, $vừaTạoMới] */
    private function findOrCreate(string $slug, string $name, User $createdBy): array
    {
        $existing = Product::where('slug', $slug)->first();

        if ($existing) {
            return [$existing, false];
        }

        $product = Product::create([
            'name'              => $name,
            'slug'              => $slug,
            'type'              => ProductType::Physical->value,
            'status'            => ProductStatus::Active->value,
            'short_description' => "Dữ liệu demo minh hoạ luồng duyệt nội dung — {$name}.",
            'description'       => "Mô tả đầy đủ cho {$name}. Đây là dữ liệu demo của module Approval, không phải sản phẩm thật.",
            'price'             => 199000,
            'created_by'        => $createdBy->id,
        ]);

        return [$product, true];
    }

    private function seedDraft(User $marketing): void
    {
        // Không transition gì thêm — mặc định đã là Draft ngay sau khi tạo
        // (HasApproval::bootHasApproval tự tạo ApprovalSubject).
        $this->findOrCreate('demo-approval-ao-thun-basic', 'Áo thun basic cotton (Demo — Draft)', $marketing);
    }

    private function seedPending(User $marketing): void
    {
        [$product, $isNew] = $this->findOrCreate('demo-approval-binh-giu-nhiet', 'Bình giữ nhiệt 500ml (Demo — Pending)', $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitForApprovalAction::class)->handle($product);
    }

    private function seedApproved(User $marketing, User $ceo): void
    {
        [$product, $isNew] = $this->findOrCreate('demo-approval-tai-nghe-bluetooth', 'Tai nghe bluetooth ABC (Demo — Approved)', $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitForApprovalAction::class)->handle($product);

        Auth::login($ceo);
        app(ApproveAction::class)->handle($product);
    }

    private function seedPublished(User $marketing, User $ceo): void
    {
        [$product, $isNew] = $this->findOrCreate('demo-approval-balo-laptop', 'Balo laptop chống nước (Demo — Published)', $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitForApprovalAction::class)->handle($product);

        Auth::login($ceo);
        app(ApproveAction::class)->handle($product);
        app(PublishAction::class)->handle($product);
    }

    /**
     * Ca demo QUAN TRỌNG NHẤT — minh hoạ đúng bản chất nghiệp vụ cốt lõi (spec §1): sửa nội
     * dung sau khi đã Published tự động chuyển về Pending, nhưng public_snapshot vẫn giữ bản
     * CŨ (cổng thông tin không gián đoạn). So sánh $product->description (bản mới) với
     * $product->publicContent()['description'] (bản cũ, đã đóng băng) để thấy rõ khác biệt.
     */
    private function seedPublishedThenEdited(User $marketing, User $ceo): void
    {
        [$product, $isNew] = $this->findOrCreate('demo-approval-noi-chien', 'Nồi chiên không dầu XYZ (Demo — Published rồi bị sửa, đang chờ duyệt lại)', $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitForApprovalAction::class)->handle($product);

        Auth::login($ceo);
        app(ApproveAction::class)->handle($product);
        app(PublishAction::class)->handle($product);

        Auth::login($marketing);
        $product->update([
            'description' => $product->description . ' [BẢN CẬP NHẬT — đang chờ CEO duyệt lại; cổng thông tin vẫn hiển thị mô tả cũ cho tới khi duyệt+publish lại].',
        ]);
    }

    private function seedArchived(User $marketing, User $ceo): void
    {
        [$product, $isNew] = $this->findOrCreate('demo-approval-dong-ho-fit', 'Đồng hồ thông minh Fit (Demo — Archived)', $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitForApprovalAction::class)->handle($product);

        Auth::login($ceo);
        app(ApproveAction::class)->handle($product);
        app(PublishAction::class)->handle($product);
        app(ArchiveAction::class)->handle($product);
    }

    private function seedRejectedOnce(User $marketing, User $ceo): void
    {
        [$product, $isNew] = $this->findOrCreate('demo-approval-san-pham-tu-choi', 'Sản phẩm demo bị từ chối duyệt (Demo — Draft sau Reject)', $marketing);
        if (! $isNew) {
            return;
        }

        Auth::login($marketing);
        app(SubmitForApprovalAction::class)->handle($product);

        Auth::login($ceo);
        app(RejectAction::class)->handle($product, 'Mô tả sản phẩm chưa đủ chi tiết, cần bổ sung thông số kỹ thuật trước khi duyệt lại.');
    }
}
