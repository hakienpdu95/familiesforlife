<?php

namespace Modules\Deployment\Data;

use Spatie\LaravelData\Data;

class AddChecklistNoteData extends Data
{
    public function __construct(
        public readonly string $note,
    ) {}

    public static function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }

    public static function messages(): array
    {
        return [
            'note.required' => 'Vui lòng nhập nội dung ghi chú.',
            'note.max'      => 'Ghi chú không được vượt quá 2000 ký tự.',
        ];
    }
}
