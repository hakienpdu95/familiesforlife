<?php

namespace Modules\Recruitment\Enums;

enum NoteType: string
{
    case General        = 'general';
    case InterviewNote  = 'interview_note';
    case Concern        = 'concern';
    case FollowUp       = 'follow_up';
    case ReferenceCheck = 'reference_check';

    public function label(): string
    {
        return match($this) {
            self::General        => 'Ghi chú chung',
            self::InterviewNote  => 'Ghi chú phỏng vấn',
            self::Concern        => 'Lo ngại',
            self::FollowUp       => 'Theo dõi',
            self::ReferenceCheck => 'Kiểm tra tham chiếu',
        };
    }
}
