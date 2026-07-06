<?php

namespace Modules\Survey\Enums;

enum ValueKind: int
{
    case String = 1;
    case Text   = 2;
    case Number = 3;
    case Date   = 4;
    case Bool   = 5;
    case Option = 6;

    // Cột vật lý trong bảng survey_answers tương ứng với kind này.
    // Đây là nguồn sự thật duy nhất — AnswerValueResolver gọi method này.
    public function column(): string
    {
        return match ($this) {
            self::String => 'value_string',
            self::Text   => 'value_text',
            self::Number => 'value_number',
            self::Date   => 'value_date',
            self::Bool   => 'value_bool',
            self::Option => 'option_id',
        };
    }
}
