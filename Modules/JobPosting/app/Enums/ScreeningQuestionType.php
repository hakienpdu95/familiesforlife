<?php

namespace Modules\JobPosting\Enums;

enum ScreeningQuestionType: string
{
    case YesNo          = 'yes_no';
    case ShortText      = 'short_text';
    case LongText       = 'long_text';
    case Number         = 'number';
    case SingleChoice   = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case FileUpload     = 'file_upload';

    public function label(): string
    {
        return match($this) {
            self::YesNo          => 'Có/Không',
            self::ShortText      => 'Trả lời ngắn',
            self::LongText       => 'Trả lời dài',
            self::Number         => 'Số',
            self::SingleChoice   => 'Chọn một',
            self::MultipleChoice => 'Chọn nhiều',
            self::FileUpload     => 'Upload file',
        };
    }

    public function hasChoices(): bool
    {
        return in_array($this, [self::SingleChoice, self::MultipleChoice]);
    }
}
