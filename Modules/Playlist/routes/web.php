<?php

use Illuminate\Support\Facades\Route;
use Modules\Playlist\Features\PlaylistManagement\Http\PlaylistAdminController;
use Modules\Playlist\Features\PlaylistManagement\Http\PlaylistApiController;
use Modules\Playlist\Features\PublicReading\Http\PlaylistPublicController;

// ── Quản trị playlist (spec/Playlist_Technical_Specification.md §6.3) ──────────────────────
Route::middleware(['auth'])->prefix('dashboard/playlists')->name('backend.playlist.')->group(function (): void {
    // ->except(['show']): không có trang xem chi tiết riêng ở khu vực quản trị (cùng pattern
    // Modules/Video VideoAdminController) — "sửa" (edit) đã kiêm luôn việc xem+quản lý item.
    Route::resource('items', PlaylistAdminController::class)->except(['show'])->parameters(['items' => 'playlist']);
    Route::patch('items/{playlist}/toggle-active', [PlaylistAdminController::class, 'toggleActive'])->name('items.toggle-active');
    Route::post('items/{playlist}/attach-item', [PlaylistAdminController::class, 'attachItem'])->name('items.attach-item');
    Route::delete('playlist-items/{playlistItem}', [PlaylistAdminController::class, 'detachItem'])->name('items.detach-item');
    Route::patch('items/{playlist}/reorder-items', [PlaylistAdminController::class, 'reorderItems'])->name('items.reorder-items');
});

// ── Backend JSON API cho Tabulator + ô tìm kiếm hợp nhất (session-based auth) ──────────────
Route::middleware(['auth'])->prefix('backend/api/playlists')->name('backend.api.playlists.')->group(function (): void {
    Route::get('items', [PlaylistApiController::class, 'index'])->name('items');
    Route::get('{playlist}/searchable-items', [PlaylistApiController::class, 'searchableItems'])->name('searchable-items');
});

// ── Trang công khai — danh sách + chi tiết playlist (§7) ───────────────────────────────────
Route::name('playlist.public.')->group(function (): void {
    Route::get('playlists', [PlaylistPublicController::class, 'index'])->name('index');
    Route::get('playlists/{slug}', [PlaylistPublicController::class, 'show'])->name('show');
});
