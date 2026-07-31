<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->string('video_url', 2048)->nullable();
            // nullable — chỉ bắt buộc "1 trong 2" với video_url, validate ở tầng Action
            // (spec §5.2), không phải ràng buộc DB.
            $table->text('embed_code')->nullable();
            $table->string('youtube_video_id', 20);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // created_by: restrictOnDelete — không cho xoá 1 User nếu họ còn là người TẠO video
            // nào đó (giữ nguyên vẹn provenance để audit). updated_by: nullOnDelete — cho phép
            // xoá User dù họ từng SỬA (không phải tạo) 1 video, vì "người sửa gần nhất" chỉ là
            // vết tích tham khảo phụ. Quyết định CHỦ ĐÍCH — copy nguyên xi cách Banner đã làm
            // (Modules/Banner/database/migrations/2026_07_15_150003_create_banners_table.php).
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order'], 'idx_video_active_sort');
            $table->index('youtube_video_id', 'idx_video_youtube_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
