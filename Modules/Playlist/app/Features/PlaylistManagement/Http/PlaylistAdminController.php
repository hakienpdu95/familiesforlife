<?php

namespace Modules\Playlist\Features\PlaylistManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Playlist\Features\PlaylistManagement\Actions\AddItemToPlaylistAction;
use Modules\Playlist\Features\PlaylistManagement\Actions\BuildPlaylistIdeaPromptAction;
use Modules\Playlist\Features\PlaylistManagement\Actions\CreatePlaylistAction;
use Modules\Playlist\Features\PlaylistManagement\Actions\DeletePlaylistAction;
use Modules\Playlist\Features\PlaylistManagement\Actions\RemoveItemFromPlaylistAction;
use Modules\Playlist\Features\PlaylistManagement\Actions\ReorderPlaylistItemsAction;
use Modules\Playlist\Features\PlaylistManagement\Actions\TogglePlaylistActiveAction;
use Modules\Playlist\Features\PlaylistManagement\Actions\UpdatePlaylistAction;
use Modules\Playlist\Features\PlaylistManagement\Data\PlaylistData;
use Modules\Playlist\Models\Playlist;
use Modules\Playlist\Models\PlaylistItem;

/**
 * spec/Playlist_Technical_Specification.md §6 — không có bước duyệt (khác Post/Event), tạo xong
 * hiển thị ngay nếu is_active=true. Dữ liệu bảng danh sách lấy qua PlaylistApiController
 * (Tabulator, remote pagination/sort/filter) — cùng pattern VideoAdminController.
 */
class PlaylistAdminController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Playlist::class, 'playlist');
    }

    public function index(): View
    {
        return view('playlist::admin.playlists.index');
    }

    public function create(): View
    {
        return view('playlist::admin.playlists.create');
    }

    public function store(Request $request, CreatePlaylistAction $createPlaylist): RedirectResponse
    {
        $playlist = $createPlaylist->handle(PlaylistData::from($this->validated($request)));

        return redirect()->route('backend.playlist.items.edit', $playlist)
            ->with('success', "Đã tạo playlist \"{$playlist->name}\". Tiếp tục thêm nội dung vào playlist bên dưới.");
    }

    public function edit(Playlist $playlist, BuildPlaylistIdeaPromptAction $buildIdeaPrompt): View
    {
        $playlist->load('items.itemable');

        // Sinh sẵn ở đây (không phải AJAX riêng) vì $playlist->items đã load xong cho phần quản lý
        // item ngay bên dưới — tránh 1 endpoint/round-trip thừa chỉ để lặp lại đúng dữ liệu đã có.
        $ideaPrompt = $buildIdeaPrompt->handle($playlist);

        return view('playlist::admin.playlists.edit', compact('playlist', 'ideaPrompt'));
    }

    public function update(Request $request, Playlist $playlist, UpdatePlaylistAction $updatePlaylist): RedirectResponse
    {
        $updatePlaylist->handle($playlist, PlaylistData::from($this->validated($request, $playlist)));

        return redirect()->route('backend.playlist.items.edit', $playlist)
            ->with('success', 'Đã cập nhật playlist.');
    }

    public function destroy(Request $request, Playlist $playlist, DeletePlaylistAction $deletePlaylist): RedirectResponse|JsonResponse
    {
        $deletePlaylist->handle($playlist);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xoá playlist.']);
        }

        return redirect()->route('backend.playlist.items.index')->with('success', 'Đã xoá playlist.');
    }

    /** Toggle nhanh is_active từ bảng danh sách — trả JSON cho Tabulator cập nhật UI tại chỗ. */
    public function toggleActive(Playlist $playlist, TogglePlaylistActiveAction $toggleActive): JsonResponse
    {
        $this->authorize('update', $playlist);

        $playlist = $toggleActive->handle($playlist);

        return response()->json([
            'success' => true,
            'is_active' => $playlist->is_active,
            'message' => $playlist->is_active ? 'Đã bật hiển thị playlist.' : 'Đã tắt hiển thị playlist.',
        ]);
    }

    /** Thêm 1 item (video/bài viết) vào playlist — gọi từ modal tìm kiếm hợp nhất (§6.4). */
    public function attachItem(Request $request, Playlist $playlist, AddItemToPlaylistAction $addItem): JsonResponse
    {
        $this->authorize('update', $playlist);

        $validated = $request->validate([
            'itemable_type' => ['required', 'string', Rule::in(array_keys(config('playlist.itemables', [])))],
            'itemable_id' => ['required', 'integer', 'min:1'],
        ]);

        $item = $addItem->handle($playlist, $validated['itemable_type'], (int) $validated['itemable_id']);
        $item->load('itemable');

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào playlist.',
            'item' => [
                'id' => $item->id,
                'title' => $item->resolved_itemable?->getPlaylistCardTitle(),
                'type_label' => $item->resolved_itemable?->getPlaylistCardTypeLabel(),
                'sort_order' => $item->sort_order,
            ],
        ]);
    }

    /** Gỡ 1 item khỏi playlist. */
    public function detachItem(PlaylistItem $playlistItem, RemoveItemFromPlaylistAction $removeItem): JsonResponse
    {
        $this->authorize('update', $playlistItem->playlist);

        $removeItem->handle($playlistItem);

        return response()->json(['success' => true, 'message' => 'Đã gỡ khỏi playlist.']);
    }

    /** Sắp xếp lại thứ tự item — nhận mảng ID theo đúng thứ tự mới (§0: nhập tay, không kéo-thả). */
    public function reorderItems(Request $request, Playlist $playlist, ReorderPlaylistItemsAction $reorderItems): JsonResponse
    {
        $this->authorize('update', $playlist);

        $validated = $request->validate([
            'ordered_item_ids' => ['required', 'array', 'min:1'],
            'ordered_item_ids.*' => ['integer'],
        ]);

        $reorderItems->handle($playlist, $validated['ordered_item_ids']);

        return response()->json(['success' => true, 'message' => 'Đã lưu thứ tự mới.']);
    }

    private function validated(Request $request, ?Playlist $playlist = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('playlists', 'slug')->ignore($playlist)],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_image_url' => ['nullable', 'url', 'max:2048'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
