<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('cover_image_url', 2048)->nullable();

            // SEO (spec §0/§7.3) — nullable, view fallback về name/description khi trống.
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            // created_by: restrictOnDelete — không cho xoá 1 User nếu họ còn là người TẠO
            // playlist nào đó (giữ nguyên vẹn provenance để audit). updated_by: nullOnDelete —
            // cho phép xoá User dù họ từng SỬA (không phải tạo) 1 playlist. Quyết định CHỦ ĐÍCH —
            // copy nguyên xi cách Video/Banner đã làm.
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order'], 'idx_playlist_active_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
