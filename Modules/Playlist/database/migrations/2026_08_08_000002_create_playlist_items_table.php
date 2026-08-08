<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlist_items', function (Blueprint $table): void {
            $table->id();

            // cascadeOnDelete CHỈ kích hoạt khi Playlist::forceDelete() (xoá cứng thật) —
            // soft-delete mặc định (set deleted_at) không chạm tới bảng này, không mâu thuẫn với
            // "giữ hàng khi xoá mềm" (spec §0). Sau forceDelete(), dọn luôn playlist_items là
            // đúng vì không còn playlist cha để trỏ vào.
            $table->foreignId('playlist_id')->constrained('playlists')->cascadeOnDelete();

            // Không có FK thật (itemable_id trỏ 1 trong nhiều bảng khác nhau tuỳ itemable_type)
            // — item "mồ côi" (nguồn đã xoá/ẩn) được lọc ở tầng Query khi đọc, không ở tầng DB
            // (spec §0/§5.3).
            $table->string('itemable_type', 60);
            $table->unsignedBigInteger('itemable_id');

            // Thứ tự RIÊNG trong playlist này — mặc định max+1 khi attach (spec §0/§5.1), chỉ
            // sửa lại hàng loạt qua ReorderPlaylistItemsAction.
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['playlist_id', 'sort_order'], 'idx_playlist_item_sort');
            $table->index(['itemable_type', 'itemable_id'], 'idx_playlist_item_itemable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_items');
    }
};
