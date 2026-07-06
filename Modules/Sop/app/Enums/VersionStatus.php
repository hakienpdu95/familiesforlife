<?php

namespace Modules\Sop\Enums;

enum VersionStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Approved  = 'approved';
    case Rejected  = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Nháp',
            self::Submitted => 'Chờ duyệt',
            self::Approved  => 'Đã duyệt',
            self::Rejected  => 'Từ chối',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft     => 'badge-ghost',
            self::Submitted => 'badge-warning',
            self::Approved  => 'badge-success',
            self::Rejected  => 'badge-error',
        };
    }
}
