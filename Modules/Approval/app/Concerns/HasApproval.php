<?php

namespace Modules\Approval\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

/**
 * spec/Workflow_Approval_Technical_Specification.md §7.1.
 */
trait HasApproval
{
    /**
     * Tự tạo ApprovalSubject 'draft' ngay khi entity được tạo — dùng hook boot{Trait}
     * chuẩn của Eloquent (giống cách SoftDeletes/HasFactory tự đăng ký), KHÔNG cần entity
     * tự gọi tay. Chạy trong event `created` (sau `creating`) nên organization_id đã được
     * BelongsToOrganization gán xong (TenantAwareModel áp dụng cho mọi domain model).
     *
     * `updating` (KHÔNG phải `updated`) — TỰ ĐỘNG gọi ReviseContentAction mỗi khi 1 trường
     * "nội dung" (khai báo ở approvalWatchedAttributes()) SẮP thay đổi, KHÔNG chờ module tiêu
     * thụ tự nhớ gọi. Đây là điểm mấu chốt để đảm bảo đúng bản chất nghiệp vụ (§1): nội dung đã
     * duyệt/đã live mà bị sửa PHẢI được đánh dấu "có bản chờ duyệt" — nhưng KHÔNG ảnh hưởng tới
     * cái đang hiển thị công khai (đó là việc của public_snapshot, không phải của cột status).
     *
     * CỐ Ý dùng `updating` (trước khi UPDATE chạy) + `isDirty()`, KHÔNG dùng `updated` (sau khi
     * UPDATE đã chạy) + `wasChanged()` như bản đầu — bug thật phát hiện khi thử sửa nội dung
     * 1 entity đang Archived: ReviseContentAction némInvalidTransitionException đúng như thiết
     * kế, NHƯNG nếu ném SAU khi UPDATE đã chạy (`updated`), câu UPDATE đó đã tự commit rồi —
     * nội dung "read-only" thực ra đã bị ghi vào DB, chỉ có cột status của ApprovalSubject là
     * không đổi. Ném exception ở `updating` (trước khi UPDATE chạy) khiến toàn bộ save() của
     * entity bị huỷ ngay, không có trạng thái nửa vời nào cả.
     */
    public static function bootHasApproval(): void
    {
        static::created(function (Model $model): void {
            $model->ensureApprovalSubject();
        });

        static::updating(function (Model $model): void {
            $watched = $model->approvalWatchedAttributes();

            if ($model->isDirty($watched)) {
                app(\Modules\Approval\Actions\ReviseContentAction::class)->handle($model);
            }
        });
    }

    public function approvalSubject(): MorphOne
    {
        return $this->morphOne(ApprovalSubject::class, 'subject');
    }

    /**
     * KHÔNG viết gọn `$this->approvalSubject ?? $this->approvalSubject()->create(...)` — truy
     * cập `$this->approvalSubject` (vế trái) khiến Eloquent CACHE luôn kết quả null lên
     * `$relations['approvalSubject']` của model khi chưa có subject nào; nếu chỉ trả về giá
     * trị vừa tạo mà không gọi setRelation(), các lần đọc `$model->approvalSubject` sau đó
     * trong CÙNG request/instance (vd `$model->approvalStatus()` ngay sau khi tạo) vẫn thấy
     * null dù DB đã có bản ghi — bug thật phát hiện khi viết smoke test cho Phase 1.
     */
    public function ensureApprovalSubject(): ApprovalSubject
    {
        if ($this->approvalSubject) {
            return $this->approvalSubject;
        }

        $created = $this->approvalSubject()->create([
            'organization_id' => $this->approvalOrganizationId(),
            'status'          => ApprovalStatus::Draft,
        ]);

        $this->setRelation('approvalSubject', $created);

        return $created;
    }

    /**
     * organization_id gán vào ApprovalSubject khi tạo mới. Mặc định đọc `$this->organization_id`
     * (entity tenant-scoped bình thường, vd Product — thuộc VỀ 1 tổ chức). Entity mà CHÍNH NÓ
     * là tenant root (vd `Organization` — không có cột `organization_id` trỏ vào chính mình)
     * PHẢI override method này, trả về `$this->id`. Không default ngầm theo kiểu
     * `$this->organization_id ?? $this->id` — sai lặng lẽ nếu 1 entity tenant-scoped bình
     * thường vô tình có organization_id null (bug ẩn), tốt hơn để entity tự khai báo rõ ràng
     * khi nó thuộc nhóm đặc biệt này.
     */
    public function approvalOrganizationId(): int
    {
        return $this->organization_id;
    }

    /**
     * Danh sách trường được coi là "nội dung" cần duyệt lại khi đổi (vd name/description/ảnh),
     * KHÔNG bao gồm trường vận hành/kinh doanh (giá, tồn kho…) — §2.3, §9. Entity dùng
     * HasApproval PHẢI override method này (contract bắt buộc, không có default an toàn ngầm
     * dùng getFillable() — trộn cả trường kinh doanh vào sẽ vô tình bắt duyệt lại những thay
     * đổi không liên quan tới nội dung công khai, gây phiền và sai bản chất nghiệp vụ ở §1).
     */
    abstract public function approvalWatchedAttributes(): array;

    public function approvalStatus(): ?ApprovalStatus
    {
        return $this->approvalSubject?->status;
    }

    public function isApprovalDraft(): bool     { return $this->approvalStatus() === ApprovalStatus::Draft; }
    public function isApprovalPending(): bool   { return $this->approvalStatus() === ApprovalStatus::Pending; }
    public function isApproved(): bool          { return $this->approvalStatus() === ApprovalStatus::Approved; }
    public function isApprovalPublished(): bool { return $this->approvalStatus() === ApprovalStatus::Published; }
    public function isApprovalArchived(): bool  { return $this->approvalStatus() === ApprovalStatus::Archived; }

    /**
     * Lịch sử duyệt (submit/approve/reject/publish/archive/revise) của entity này, mới nhất
     * trước — tiện dùng trực tiếp trong Blade (vd tab "Lịch sử duyệt" trên trang edit) mà
     * không cần tự viết `$product->approvalSubject?->logs()->latest('id')->get()` mỗi nơi.
     * Sắp xếp theo `id` (không phải `created_at`) — nhiều transition có thể xảy ra trong cùng
     * 1 giây (vd script/test, hoặc double-click nhanh), `created_at` cấp độ giây không đủ để
     * giữ đúng thứ tự; `id` tăng dần tuyệt đối nên luôn cho thứ tự đúng và ổn định.
     */
    public function approvalLogs(): Collection
    {
        return $this->approvalSubject?->logs()->latest('id')->get() ?? collect();
    }

    /**
     * Entity có được coi là "đã từng công khai" hay không — tiêu chí là public_snapshot khác
     * null (đã Publish ít nhất 1 lần) VÀ chưa Archived, KHÔNG dựa vào status hiện tại. Một
     * entity đang ở status=pending (vì vừa bị sửa nội dung) vẫn PHẢI trả về true ở đây — đó
     * chính là điểm cốt lõi của module này (§1): còn bản đã duyệt để hiển thị, dù đang có bản
     * sửa chờ duyệt song song.
     */
    public function isPubliclyVisible(): bool
    {
        return $this->approvalSubject
            && $this->approvalSubject->public_snapshot !== null
            && $this->approvalSubject->status !== ApprovalStatus::Archived;
    }

    /**
     * Scope dùng ở MỌI query của cổng thông tin công khai cho entity có HasApproval — cùng
     * tiêu chí với isPubliclyVisible(), viết dạng query để lọc được ở tầng DB thay vì load hết
     * rồi filter bằng PHP. KHÔNG lọc theo status=published (sai). Không tự trộn logic này vào
     * query nội bộ/CMS (nơi biên tập viên cần thấy cả Draft/Pending của chính họ, đọc trực
     * tiếp cột hiện tại chứ không qua snapshot).
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereHas('approvalSubject', function (Builder $q) {
            $q->whereNotNull('public_snapshot')->where('status', '!=', ApprovalStatus::Archived);
        });
    }

    /**
     * Dữ liệu THỰC SỰ nên hiển thị ra cổng thông tin công khai: các trường "nội dung" (khai
     * báo ở approvalWatchedAttributes()) lấy từ public_snapshot (bản đã duyệt, đóng băng —
     * không phải bản đang chỉnh sửa dở trên chính entity); mọi trường KHÁC (giá, tồn kho…) lấy
     * trực tiếp từ entity vì luôn hiệu lực ngay, không thuộc phạm vi gate của Approval (§2.3).
     * Trả về mảng (không phải Model) — Blade/API layer của module tiêu thụ tự build response
     * cuối cùng từ mảng này, KHÔNG đọc thẳng $entity->name khi hiển thị công khai.
     */
    public function publicContent(): array
    {
        $snapshot = $this->approvalSubject?->public_snapshot ?? [];

        return array_merge($this->attributesToArray(), $snapshot);
    }
}
