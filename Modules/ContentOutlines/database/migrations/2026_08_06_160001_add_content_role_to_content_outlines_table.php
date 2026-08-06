<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// spec/ContentOutlines_Technical_Specification.md §4.9 (v1.6) — vai trò nội dung (pillar/cluster,
// mô hình internal-link Pillar↔Cluster) — định hướng CHIỀU gợi ý internal link ở BOTTOM.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->string('content_role', 10)->nullable()->after('outline_depth');
        });
    }

    public function down(): void
    {
        Schema::table('content_outlines', function (Blueprint $table) {
            $table->dropColumn('content_role');
        });
    }
};
