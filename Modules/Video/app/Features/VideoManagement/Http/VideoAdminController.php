<?php

namespace Modules\Video\Features\VideoManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Video\Features\VideoManagement\Actions\CreateVideoAction;
use Modules\Video\Features\VideoManagement\Actions\DeleteVideoAction;
use Modules\Video\Features\VideoManagement\Actions\ToggleVideoActiveAction;
use Modules\Video\Features\VideoManagement\Actions\UpdateVideoAction;
use Modules\Video\Features\VideoManagement\Data\VideoData;
use Modules\Video\Models\Video;

/**
 * spec/Video_Management_Technical_Specification.md §6 — không có bước duyệt (khác Post/Event),
 * tạo xong hiển thị ngay nếu is_active=true. Dữ liệu bảng danh sách lấy qua VideoApiController
 * (Tabulator, remote pagination/sort/filter) — cùng pattern BannerAdminController.
 */
class VideoAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Video::class, 'video');
    }

    public function index(): View
    {
        return view('video::admin.videos.index');
    }

    public function create(): View
    {
        return view('video::admin.videos.create');
    }

    public function store(Request $request, CreateVideoAction $createVideo): RedirectResponse
    {
        $video = $createVideo->handle(VideoData::from($this->validated($request)));

        return redirect()->route('backend.video.items.index')
            ->with('success', "Đã thêm video \"{$video->name}\".");
    }

    public function edit(Video $video): View
    {
        return view('video::admin.videos.edit', compact('video'));
    }

    public function update(Request $request, Video $video, UpdateVideoAction $updateVideo): RedirectResponse
    {
        $updateVideo->handle($video, VideoData::from($this->validated($request)));

        return redirect()->route('backend.video.items.index')
            ->with('success', 'Đã cập nhật video.');
    }

    public function destroy(Request $request, Video $video, DeleteVideoAction $deleteVideo): RedirectResponse|JsonResponse
    {
        $deleteVideo->handle($video);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá video.']);
        }

        return redirect()->route('backend.video.items.index')
            ->with('success', 'Đã xoá video.');
    }

    /** Toggle nhanh is_active từ bảng danh sách — trả JSON cho Tabulator cập nhật UI tại chỗ, không reload trang. */
    public function toggleActive(Video $video, ToggleVideoActiveAction $toggleActive): JsonResponse
    {
        $this->authorize('update', $video);

        $video = $toggleActive->handle($video);

        return response()->json([
            'success'   => true,
            'is_active' => $video->is_active,
            'message'   => $video->is_active ? 'Đã bật hiển thị video.' : 'Đã tắt hiển thị video.',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'video_url'   => ['nullable', 'url', 'max:2048', 'required_without:embed_code'],
            'embed_code'  => ['nullable', 'string', 'max:2000', 'required_without:video_url'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['boolean'],
        ]);
    }
}
