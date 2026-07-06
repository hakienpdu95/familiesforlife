<?php
namespace Modules\WorkflowAutomation\Enums;

enum CooldownType: int
{
    case None              = 0;
    case OncePerSubject    = 1;
    case PerSubjectPerDay  = 2;
    case PerSubjectPerHour = 3;
    case GlobalPerDay      = 4;

    public function ttlSeconds(): int {
        return match($this) {
            self::None              => 0,
            self::OncePerSubject    => 365 * 86400,
            self::PerSubjectPerDay  => 86400,
            self::PerSubjectPerHour => 3600,
            self::GlobalPerDay      => 86400,
        };
    }
    public function label(): string {
        return match($this) {
            self::None              => 'Không giới hạn',
            self::OncePerSubject    => 'Mỗi đối tượng 1 lần duy nhất',
            self::PerSubjectPerDay  => 'Mỗi đối tượng 1 lần/ngày',
            self::PerSubjectPerHour => 'Mỗi đối tượng 1 lần/giờ',
            self::GlobalPerDay      => 'Toàn workflow 1 lần/ngày',
        };
    }
}
