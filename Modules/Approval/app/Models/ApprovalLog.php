<?php

namespace Modules\Approval\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Audit log append-only — không sửa, không soft-delete, không có updated_at. */
class ApprovalLog extends Model
{
    use BelongsToOrganization;

    const UPDATED_AT = null;

    protected $table = 'approval_logs';

    protected $fillable = [
        'organization_id',
        'approval_subject_id',
        'action',
        'from_status',
        'to_status',
        'reason',
        'performed_by',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(ApprovalSubject::class, 'approval_subject_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }

    /** Nhãn tiếng Việt cho cột `action` — dùng ở trang Lịch sử duyệt (§12 mở rộng). */
    public function actionLabel(): string
    {
        return match ($this->action) {
            'submit'  => 'Gửi duyệt',
            'approve' => 'Duyệt',
            'reject'  => 'Từ chối',
            'publish' => 'Xuất bản',
            'archive' => 'Lưu trữ',
            'revise'  => 'Sửa nội dung (tự động chờ duyệt lại)',
            default   => $this->action,
        };
    }
}
