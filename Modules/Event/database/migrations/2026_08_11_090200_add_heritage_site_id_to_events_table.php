<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Heritage_Technical_Specification.md §3.7 — liên kết TUỲ CHỌN tới HeritageSite (1 lễ hội
 * có thể diễn ra tại 1 di tích đã ghi nhận). nullOnDelete (không cascadeOnDelete) — xoá 1 di
 * tích không được kéo theo xoá sự kiện đã tồn tại độc lập với nó, chỉ gỡ liên kết.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('heritage_site_id')->nullable()->after('category_id')
                ->constrained('heritage_sites')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('heritage_site_id');
        });
    }
};
