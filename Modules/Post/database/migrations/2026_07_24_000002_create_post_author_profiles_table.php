<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §3 — hồ sơ tác giả công khai, mở
 * rộng ngữ cảnh Post cho 1 User mà KHÔNG thêm cột vào bảng users dùng chung toàn nền tảng
 * (đúng tiền lệ post_category_editors). 1-1 với users — user_id unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_author_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            $table->string('slug')->unique();
            $table->string('pen_name', 120)->nullable();
            $table->text('bio')->nullable();
            $table->json('social_links')->nullable();
            $table->boolean('is_public')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_author_profiles');
    }
};
