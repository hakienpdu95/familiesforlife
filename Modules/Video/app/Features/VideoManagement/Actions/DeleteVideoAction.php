<?php

namespace Modules\Video\Features\VideoManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Video\Models\Video;

/** Không có tài nguyên phụ nào cần dọn (không file ảnh, không bảng con) — chỉ soft-delete. */
class DeleteVideoAction
{
    use AsAction;

    public function handle(Video $video): void
    {
        $video->delete();
    }
}
