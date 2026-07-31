<?php

namespace Modules\Video\Features\VideoManagement\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Video\Features\VideoManagement\Data\VideoData;
use Modules\Video\Models\Video;

class CreateVideoAction
{
    use AsAction;

    public function __construct(
        private readonly ResolveYoutubeVideoIdAction $resolveYoutubeVideoId,
    ) {}

    public function handle(VideoData $data): Video
    {
        if ($data->video_url && ! $this->resolveYoutubeVideoId->isWhitelistedHost($data->video_url)) {
            throw ValidationException::withMessages([
                'video_url' => 'Link URL Video phải là 1 đường dẫn YouTube hợp lệ (youtube.com, youtu.be, m.youtube.com, music.youtube.com).',
            ]);
        }

        $youtubeVideoId = $this->resolveYoutubeVideoId->handle($data->embed_code, $data->video_url);

        if (! $youtubeVideoId) {
            throw ValidationException::withMessages([
                'embed_code' => 'Không nhận diện được video YouTube hợp lệ từ Mã Embed hoặc Link URL Video đã nhập. Vui lòng dán lại URL hoặc mã nhúng lấy trực tiếp từ YouTube.',
            ]);
        }

        return Video::create([
            'name'             => $data->name,
            'description'      => $data->description,
            'video_url'        => $data->video_url,
            'embed_code'       => $data->embed_code,
            'youtube_video_id' => $youtubeVideoId,
            'sort_order'       => $data->sort_order,
            'is_active'        => $data->is_active,
            'created_by'       => auth()->id(),
        ]);
    }
}
