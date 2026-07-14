<?php

namespace Modules\Event\Features\EventModeration\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * spec/Event_Management_Technical_Specification.md §10.4 — validate mime/size ở FormRequest
 * (mimes:jpg,jpeg,png|max:1024), action này chỉ lo phần không validate được bằng rule chuẩn:
 * đọc kích thước ảnh + chặn cứng ảnh chân dung (width < height — poster hiển thị dạng card
 * ngang trên listing). Dùng chung cho cả EventModeration (staff tạo thẳng) lẫn PublicSubmission
 * (Phase 2, form public) — không có gì trong action này phụ thuộc ngữ cảnh staff/public.
 */
class StoreEventPosterAction
{
    use AsAction;

    /** @return array{poster_path: string, poster_width: ?int, poster_height: ?int, poster_size_bytes: int} */
    public function handle(UploadedFile $file): array
    {
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        abort_if(
            $width && $height && $width < $height,
            422,
            'Poster nên ở dạng ngang (landscape), không phải ảnh chân dung.'
        );

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('events/posters', $filename, 'public');

        return [
            'poster_path'       => $path,
            'poster_width'      => $width,
            'poster_height'     => $height,
            'poster_size_bytes' => $file->getSize(),
        ];
    }
}
