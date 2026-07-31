<?php

namespace Modules\Video\Features\VideoManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Video\Models\Video;

/** Toggle nhanh is_active từ bảng danh sách (nút switch ở cột trạng thái) — không cần lưu lại field nào khác. */
class ToggleVideoActiveAction
{
    use AsAction;

    public function handle(Video $video): Video
    {
        $video->update([
            'is_active'  => ! $video->is_active,
            'updated_by' => auth()->id(),
        ]);

        return $video->fresh();
    }
}
