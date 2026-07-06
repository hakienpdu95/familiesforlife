<?php

namespace Modules\Recruitment\Enums;

enum AttachmentFileType: string
{
    case Cv          = 'cv';
    case CoverLetter = 'cover_letter';
    case Portfolio   = 'portfolio';
    case TestResult  = 'test_result';
    case Certificate = 'certificate';
    case Other       = 'other';

    public function label(): string
    {
        return match($this) {
            self::Cv          => 'CV / Hồ sơ',
            self::CoverLetter => 'Thư xin việc',
            self::Portfolio   => 'Portfolio',
            self::TestResult  => 'Kết quả bài test',
            self::Certificate => 'Chứng chỉ',
            self::Other       => 'Khác',
        };
    }
}
