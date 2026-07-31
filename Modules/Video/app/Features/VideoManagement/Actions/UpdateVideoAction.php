<?php

namespace Modules\Video\Features\VideoManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Video\Features\VideoManagement\Data\VideoData;
use Modules\Video\Models\Video;

class UpdateVideoAction
{
    use AsAction;

    public function __construct(
        private readonly ResolveYoutubeVideoIdAction $resolveYoutubeVideoId,
    ) {}

    /**
     * Chỉ re-resolve youtube_video_id khi embed_code/video_url thực sự thay đổi so với giá trị
     * ĐANG lưu trong DB — tránh gọi lại ResolveYoutubeVideoIdAction vô ích mỗi lần chỉ sửa
     * name/description/sort_order. So sánh trực tiếp $data (giá trị MỚI từ form) với $video
     * (giá trị ĐANG lưu) — không dùng $video->isDirty() vì $video ở đây chưa được fill() giá
     * trị mới, isDirty() lúc này luôn false.
     */
    public function handle(Video $video, VideoData $data): Video
    {
        $urlChanged = $data->video_url !== $video->video_url;
        $embedChanged = $data->embed_code !== $video->embed_code;

        $youtubeVideoId = $video->youtube_video_id;

        if ($urlChanged || $embedChanged) {
            if ($data->video_url && ! $this->resolveYoutubeVideoId->isWhitelistedHost($data->video_url)) {
                throw ValidationException::withMessages([
                    'video_url' => 'Link URL Video phải là 1 đường dẫn YouTube hợp lệ (youtube.com, youtu.be, m.youtube.com, music.youtube.com).',
                ]);
            }

            $resolved = $this->resolveYoutubeVideoId->handle($data->embed_code, $data->video_url);

            if (! $resolved) {
                throw ValidationException::withMessages([
                    'embed_code' => 'Không nhận diện được video YouTube hợp lệ từ Mã Embed hoặc Link URL Video đã nhập. Vui lòng dán lại URL hoặc mã nhúng lấy trực tiếp từ YouTube.',
                ]);
            }

            $youtubeVideoId = $resolved;
        }

        $video->update([
            'name'             => $data->name,
            'description'      => $data->description,
            'video_url'        => $data->video_url,
            'embed_code'       => $data->embed_code,
            'youtube_video_id' => $youtubeVideoId,
            'sort_order'       => $data->sort_order,
            'is_active'        => $data->is_active,
            'updated_by'       => auth()->id(),
        ]);

        return $video->fresh();
    }
}
