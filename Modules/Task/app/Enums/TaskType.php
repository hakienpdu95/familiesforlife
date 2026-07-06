<?php

namespace Modules\Task\Enums;

enum TaskType: string
{
    case Epic        = 'epic';
    case Story       = 'story';
    case Task        = 'task';
    case Subtask     = 'subtask';
    case Bug         = 'bug';
    case Improvement = 'improvement';

    public function label(): string
    {
        return match ($this) {
            self::Epic        => 'Epic',
            self::Story       => 'Story',
            self::Task        => 'Công việc',
            self::Subtask     => 'Công việc con',
            self::Bug         => 'Lỗi',
            self::Improvement => 'Cải tiến',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Epic        => 'badge-secondary',
            self::Story       => 'badge-accent',
            self::Task        => 'badge-primary',
            self::Subtask     => 'badge-info',
            self::Bug         => 'badge-error',
            self::Improvement => 'badge-warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Epic        => '⚡',
            self::Story       => '📖',
            self::Task        => '✓',
            self::Subtask     => '↳',
            self::Bug         => '🐛',
            self::Improvement => '↑',
        };
    }
}
