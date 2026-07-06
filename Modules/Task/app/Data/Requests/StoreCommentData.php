<?php

namespace Modules\Task\Data\Requests;

use Spatie\LaravelData\Data;

class StoreCommentData extends Data
{
    public function __construct(
        public readonly string $content,
        public readonly ?int   $parent_id = null,
    ) {}

    public static function rules(): array
    {
        return [
            'content'   => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer', 'exists:task_comments,id'],
        ];
    }
}
