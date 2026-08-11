<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/Heritage_Technical_Specification.md §3.7 — liên kết TUỲ CHỌN tới HeritageSite (1 sản
 * phẩm OCOP của làng nghề đã được ghi nhận là di sản). nullOnDelete (không cascadeOnDelete) —
 * xoá 1 di tích không được kéo theo xoá sản phẩm đã tồn tại độc lập với nó, chỉ gỡ liên kết.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->foreignId('heritage_site_id')->nullable()->after('category_id')
                ->constrained('heritage_sites')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('heritage_site_id');
        });
    }
};
