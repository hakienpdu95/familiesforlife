<?php

namespace Modules\Recruitment\Enums;

enum EvaluationVerdict: string
{
    case StrongYes = 'strong_yes';
    case Yes       = 'yes';
    case Neutral   = 'neutral';
    case No        = 'no';
    case StrongNo  = 'strong_no';

    public function label(): string
    {
        return match($this) {
            self::StrongYes => 'Rất đồng ý tuyển',
            self::Yes       => 'Đồng ý tuyển',
            self::Neutral   => 'Trung lập',
            self::No        => 'Không tuyển',
            self::StrongNo  => 'Kiên quyết không tuyển',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::StrongYes => 'badge-success',
            self::Yes       => 'badge-primary',
            self::Neutral   => 'badge-ghost',
            self::No        => 'badge-warning',
            self::StrongNo  => 'badge-error',
        };
    }

    public function score(): int
    {
        return match($this) {
            self::StrongYes => 2,
            self::Yes       => 1,
            self::Neutral   => 0,
            self::No        => -1,
            self::StrongNo  => -2,
        };
    }
}
